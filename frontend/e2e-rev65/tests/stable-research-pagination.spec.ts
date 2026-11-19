import { execFileSync } from 'node:child_process'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { expect, test, type Page, type Response } from '@playwright/test'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const PKG_ROOT = path.resolve(__dirname, '..')
const REPO_ROOT = path.resolve(PKG_ROOT, '..', '..')
const ARTIFACTS = path.join(PKG_ROOT, 'artifacts')
const EVIDENCE_DIR = path.join(
  REPO_ROOT,
  'docs',
  'architecture',
  'science-source-expansion',
  'rev65-browser-evidence',
)
const BACKEND_MANIFEST_CONTAINER = '/var/www/html/storage/app/e2e-tmp/rev65_controlled_result_set.json'
const BACKEND_COUNTERS_CONTAINER = '/var/www/html/storage/app/e2e-tmp/rev65_backend_counters.json'
const HOST_MANIFEST = path.join(REPO_ROOT, 'backend', 'storage', 'app', 'e2e-tmp', 'rev65_controlled_result_set.json')
const HOST_COUNTERS = path.join(REPO_ROOT, 'backend', 'storage', 'app', 'e2e-tmp', 'rev65_backend_counters.json')

const SCIENTIFIC_QUESTION = 'What is the effect of temperature on wheat growth?'

type NetworkLog = {
  method: string
  url: string
  status: number
  phase: string
  bodySnippet?: string
}

type ControlledManifest = {
  result_set_id: string
  organization_id: number
  total_answers: number
  total_pages: number
  page_size: number
  page1: {
    result_set_id: string
    page: number
    page_size: number
    total_answers: number
    total_pages: number
    answers: Array<{ answer: string; result_id: string; position: number }>
    rerun_performed: boolean
    expires_at?: string
    status?: string
    original_question?: string
    observability?: Record<string, unknown>
  }
  expected_first_answers: { page1: string; page2: string; page3: string }
}

function ensureDirs(): void {
  fs.mkdirSync(ARTIFACTS, { recursive: true })
  fs.mkdirSync(EVIDENCE_DIR, { recursive: true })
}

function seedControlledResultSet(): ControlledManifest {
  execFileSync(
    'docker',
    [
      'exec',
      'wsa-enterprise-backend-1',
      'php',
      'scripts/rev65_seed_controlled_result_set.php',
      BACKEND_MANIFEST_CONTAINER,
    ],
    { encoding: 'utf8', cwd: REPO_ROOT },
  )
  const raw = fs.readFileSync(HOST_MANIFEST, 'utf8')
  return JSON.parse(raw) as ControlledManifest
}

function runBackendCounters(resultSetId: string, orgId: number): Record<string, unknown> {
  execFileSync(
    'docker',
    [
      'exec',
      'wsa-enterprise-backend-1',
      'php',
      'scripts/rev65_backend_counters_probe.php',
      resultSetId,
      String(orgId),
      BACKEND_COUNTERS_CONTAINER,
    ],
    { encoding: 'utf8', cwd: REPO_ROOT },
  )
  return JSON.parse(fs.readFileSync(HOST_COUNTERS, 'utf8')) as Record<string, unknown>
}

function isQueryUrl(url: string): boolean {
  return /\/api\/v1\/public\/research-agent\/query\/?(\?|$)/.test(url)
}

function isResultsUrl(url: string): boolean {
  return /\/api\/v1\/public\/research-agent\/results\/?(\?|$)/.test(url)
}

async function collectAnswerTexts(page: Page): Promise<string[]> {
  const texts: string[] = []
  const primary = page.getByTestId('home-research-primary-answer')
  if (await primary.count()) {
    const paragraphs = primary.locator('p')
    const pCount = await paragraphs.count()
    const parts: string[] = []
    for (let i = 0; i < pCount; i++) {
      parts.push((await paragraphs.nth(i).innerText()).trim())
    }
    const joined = parts.join(' ').replace(/\s+/g, ' ').trim()
    if (joined) {
      texts.push(joined)
    }
  }
  const alts = page.getByTestId('home-research-alternatives').locator('li')
  const altCount = await alts.count()
  for (let i = 0; i < altCount; i++) {
    texts.push((await alts.nth(i).innerText()).trim())
  }
  return texts
}

test.describe.configure({ mode: 'serial' })

test('Rev 6.5 browser E2E: real search + controlled stable pagination', async ({ page }) => {
  ensureDirs()
  const networkLog: NetworkLog[] = []
  let phase = 'boot'

  page.on('response', async (response: Response) => {
    const url = response.url()
    if (!isQueryUrl(url) && !isResultsUrl(url)) {
      return
    }
    let bodySnippet: string | undefined
    try {
      const text = await response.text()
      bodySnippet = text.slice(0, 400)
    } catch {
      bodySnippet = undefined
    }
    networkLog.push({
      method: response.request().method(),
      url,
      status: response.status(),
      phase,
      bodySnippet,
    })
  })

  // ── Stage A: real scientific search through the UI ─────────────────────
  phase = 'stage_a_real_search'
  await page.goto('/', { waitUntil: 'domcontentloaded' })
  await page.locator('#home-research').scrollIntoViewIfNeeded()
  await expect(page.locator('#home-research-query')).toBeVisible({ timeout: 30_000 })

  const queryResponsePromise = page.waitForResponse(
    (r) => isQueryUrl(r.url()) && r.request().method() === 'POST',
    { timeout: 120_000 },
  )

  await page.locator('#home-research-query').fill(SCIENTIFIC_QUESTION)
  await page.locator('#home-research form').locator('button[type="submit"]').click()

  const queryResponse = await queryResponsePromise
  expect(queryResponse.status(), 'Query HTTP status').toBe(200)
  const queryJson = (await queryResponse.json()) as {
    status?: string
    stable_result_set?: { result_set_id?: string; total_answers?: number; page?: { answers?: unknown[] } }
    multi_answers?: unknown[]
    user_presentation?: { primary_answer?: string | null }
  }
  const realResultSetId = queryJson.stable_result_set?.result_set_id ?? null
  expect(realResultSetId, 'real search result_set_id').toBeTruthy()
  expect(String(realResultSetId)).toMatch(/^srs_/)

  await expect(page.getByTestId('home-research-presentation')).toBeVisible({ timeout: 30_000 })
  const realDisplayed =
    (await page.getByTestId('home-research-primary-answer').count()) > 0 ||
    (await page.getByTestId('home-research-no-answer').count()) > 0
  expect(realDisplayed, 'UI shows answer or no-answer state').toBe(true)

  await page.screenshot({
    path: path.join(EVIDENCE_DIR, '01-real-scientific-search.png'),
    fullPage: false,
  })

  const stageAQueryCount = networkLog.filter((e) => e.phase === 'stage_a_real_search' && isQueryUrl(e.url)).length
  expect(stageAQueryCount, 'exactly one Query in Stage A').toBe(1)

  const realAnswerCount =
    queryJson.stable_result_set?.total_answers ??
    queryJson.multi_answers?.length ??
    (queryJson.user_presentation?.primary_answer ? 1 : 0)

  // ── Stage B: controlled 21-answer pagination via real Results endpoint ─
  phase = 'stage_b_seed'
  const manifest = seedControlledResultSet()
  expect(manifest.total_answers).toBe(21)
  expect(manifest.total_pages).toBe(3)
  const controlledId = manifest.result_set_id

  const backendCounters = runBackendCounters(controlledId, manifest.organization_id)

  phase = 'stage_b_controlled_pagination'
  // Isolate Stage B: fulfill Query once with controlled stable page 1 from real store.
  // Results requests are NOT mocked — they hit the live Results endpoint.
  await page.route('**/api/v1/public/research-agent/query', async (route) => {
    if (route.request().method() !== 'POST') {
      await route.continue()
      return
    }
    const page1 = manifest.page1
    const primary = page1.answers[0]?.answer ?? 'TEST-DATA independent answer 0'
    const body = {
      status: 'scientific_generated',
      failure_class: null,
      failure_code: null,
      user_presentation: {
        primary_answer: primary,
        human_status: 'answered',
        candidates: page1.answers.slice(1).map((a) => ({
          result_id: a.result_id,
          answer: a.answer,
        })),
        sources: [],
        results: [],
      },
      multi_answers: page1.answers,
      stable_result_set: {
        created: true,
        status: 'created',
        result_set_id: controlledId,
        page_size: manifest.page_size,
        total_answers: manifest.total_answers,
        total_pages: manifest.total_pages,
        expires_at: page1.expires_at ?? null,
        page: page1,
      },
      _rev65_note: 'CONTROLLED_TEST_DATA_NOT_SCIENTIFIC — Query fulfilled for browser pagination Stage B only',
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(body),
    })
  })

  await page.goto('/', { waitUntil: 'domcontentloaded' })
  await page.locator('#home-research').scrollIntoViewIfNeeded()
  await page.locator('#home-research-query').fill('TEST FIXTURE Rev 6.5 controlled pagination')
  await page.locator('#home-research form').locator('button[type="submit"]').click()

  await expect(page.getByTestId('home-research-presentation')).toBeVisible({ timeout: 30_000 })
  await expect(page.getByTestId('home-research-pagination')).toBeVisible()
  await expect(page.getByTestId('home-research-primary-answer')).toContainText(
    manifest.expected_first_answers.page1,
  )

  const page1Answers = await collectAnswerTexts(page)
  expect(page1Answers.length, 'page 1 answer count').toBe(10)
  expect(page1Answers[0]).toContain('TEST-DATA independent answer 0')
  await page.screenshot({ path: path.join(EVIDENCE_DIR, '02-controlled-page-1.png'), fullPage: false })

  const resultsBeforeNav = networkLog.filter(
    (e) => e.phase === 'stage_b_controlled_pagination' && isResultsUrl(e.url),
  ).length

  // Page 2 via real Results
  const resultsPage2 = page.waitForResponse(
    (r) => isResultsUrl(r.url()) && r.url().includes(`result_set_id=${controlledId}`) && r.url().includes('page=2'),
    { timeout: 30_000 },
  )
  await page.getByTestId('home-research-page-next').click()
  const p2Resp = await resultsPage2
  expect(p2Resp.status()).toBe(200)
  const p2Json = (await p2Resp.json()) as { result_set_id: string; page: number; answers: unknown[]; rerun_performed?: boolean }
  expect(p2Json.result_set_id).toBe(controlledId)
  expect(p2Json.page).toBe(2)
  expect(p2Json.answers).toHaveLength(10)
  expect(p2Json.rerun_performed).toBe(false)

  await expect(page.getByTestId('home-research-primary-answer')).toContainText(
    manifest.expected_first_answers.page2,
    { timeout: 15_000 },
  )
  const page2Answers = await collectAnswerTexts(page)
  expect(page2Answers.length, 'page 2 answer count').toBe(10)
  expect(page2Answers[0]).toContain('TEST-DATA independent answer 10')
  await page.screenshot({ path: path.join(EVIDENCE_DIR, '03-controlled-page-2.png'), fullPage: false })

  // Page 3 via real Results
  const resultsPage3 = page.waitForResponse(
    (r) => isResultsUrl(r.url()) && r.url().includes(`result_set_id=${controlledId}`) && r.url().includes('page=3'),
    { timeout: 30_000 },
  )
  await page.getByTestId('home-research-page-next').click()
  const p3Resp = await resultsPage3
  expect(p3Resp.status()).toBe(200)
  const p3Json = (await p3Resp.json()) as { result_set_id: string; page: number; answers: unknown[]; rerun_performed?: boolean }
  expect(p3Json.result_set_id).toBe(controlledId)
  expect(p3Json.page).toBe(3)
  expect(p3Json.answers).toHaveLength(1)
  expect(p3Json.rerun_performed).toBe(false)

  await expect(page.getByTestId('home-research-primary-answer')).toContainText(
    manifest.expected_first_answers.page3,
    { timeout: 15_000 },
  )
  const page3Answers = await collectAnswerTexts(page)
  expect(page3Answers.length, 'page 3 answer count').toBe(1)
  expect(page3Answers[0]).toContain('TEST-DATA independent answer 20')
  await page.screenshot({ path: path.join(EVIDENCE_DIR, '04-controlled-page-3.png'), fullPage: false })

  // Uniqueness across pages
  const all = [...page1Answers, ...page2Answers, ...page3Answers]
  expect(new Set(all).size, 'no duplicate answers across pages').toBe(21)
  expect(all.length, 'no missing answers').toBe(21)

  const stageBQuery = networkLog.filter(
    (e) => e.phase === 'stage_b_controlled_pagination' && isQueryUrl(e.url),
  )
  const stageBResults = networkLog.filter(
    (e) => e.phase === 'stage_b_controlled_pagination' && isResultsUrl(e.url),
  )
  // One fulfilled Query to establish controlled set in UI; no additional Query during nav.
  expect(stageBQuery.length, 'Stage B Query count (setup only)').toBe(1)
  expect(stageBResults.length, 'Stage B Results requests for page 2+3').toBeGreaterThanOrEqual(2)
  expect(resultsBeforeNav, 'no Results before page navigation').toBe(0)

  // No unexpected alternative search endpoints during Stage B nav window
  const unexpected = networkLog.filter(
    (e) =>
      e.phase === 'stage_b_controlled_pagination' &&
      /research-agent\/(search|synthesize|conduct)/.test(e.url),
  )
  expect(unexpected, 'no unexpected search endpoints').toEqual([])

  const evidence = {
    revision: '6.5',
    date: new Date().toISOString().slice(0, 10),
    base_url: page.url().split('?')[0],
    browser: await page.evaluate(() => navigator.userAgent),
    stage_a: {
      question: SCIENTIFIC_QUESTION,
      query_http_status: queryResponse.status(),
      result_set_id: realResultSetId,
      response_status: queryJson.status ?? null,
      answer_count: realAnswerCount,
      query_request_count: stageAQueryCount,
      multi_page_from_live_search: Number(realAnswerCount) > 10,
    },
    stage_b: {
      label: 'CONTROLLED_TEST_DATA_NOT_SCIENTIFIC',
      result_set_id: controlledId,
      total_answers: 21,
      page_counts: {
        page1: page1Answers.length,
        page2: page2Answers.length,
        page3: page3Answers.length,
      },
      ordering: {
        page1_first: page1Answers[0],
        page2_first: page2Answers[0],
        page3_first: page3Answers[0],
      },
      unique_answers: new Set(all).size,
      query_during_pagination_after_setup: stageBQuery.length === 1,
      results_requests: stageBResults.map((r) => ({ url: r.url, status: r.status })),
    },
    network_log: networkLog.map(({ method, url, status, phase: p }) => ({ method, url, status, phase: p })),
    backend_counters: backendCounters,
    screenshots: [
      '01-real-scientific-search.png',
      '02-controlled-page-1.png',
      '03-controlled-page-2.png',
      '04-controlled-page-3.png',
    ],
  }

  fs.writeFileSync(
    path.join(EVIDENCE_DIR, 'rev65-browser-e2e-evidence.json'),
    JSON.stringify(evidence, null, 2) + '\n',
    'utf8',
  )
  fs.writeFileSync(
    path.join(ARTIFACTS, 'network-log.json'),
    JSON.stringify(networkLog, null, 2) + '\n',
    'utf8',
  )
})
