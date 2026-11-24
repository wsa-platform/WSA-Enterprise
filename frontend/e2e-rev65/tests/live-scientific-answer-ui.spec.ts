import { expect, test } from '@playwright/test'
import fs from 'node:fs'
import path from 'node:path'

/**
 * Live UI closure: real homepage → scientific search → backend → rendered answer.
 * Does not mock the research-agent API. Requires WSA_E2E_BASE_URL (default :8079).
 */
test.describe('Live scientific answer UI', () => {
  test('submits Arabic deficit irrigation question and renders answer substance', async ({
    page,
  }) => {
    const query = 'ما تأثير الري الناقص على إنتاجية القمح؟'
    const apiResponses: Array<{ url: string; status: number; bodyPreview: string }> = []

    page.on('response', async (response) => {
      const url = response.url()
      if (!url.includes('/public/research-agent/query')) {
        return
      }
      let bodyPreview = ''
      try {
        const json = await response.json()
        bodyPreview = JSON.stringify({
          status: json?.status ?? null,
          language: json?.language ?? null,
          answer_preview: String(json?.answer ?? json?.user_presentation?.primary_answer ?? '').slice(0, 240),
          citations: Array.isArray(json?.citations) ? json.citations.length : 0,
          has_ledger: Array.isArray(json?.claim_attribution_ledger)
            ? json.claim_attribution_ledger.length
            : 0,
        })
      } catch {
        bodyPreview = '(non-json)'
      }
      apiResponses.push({ url, status: response.status(), bodyPreview })
    })

    await page.goto('/?lang=ar#home-research')
    await expect(page.locator('#home-research-query')).toBeVisible()
    await page.locator('#home-research-query').fill(query)
    await page.locator('.hp-research-submit').click()

    await expect(page.getByTestId('home-research-api-status')).toBeVisible({ timeout: 120_000 })
    const status = await page.getByTestId('home-research-api-status').textContent()

    const primary = page.getByTestId('home-research-primary-answer')
    const noAnswer = page.getByTestId('home-research-no-answer')
    const failure = page.getByTestId('home-research-failure')

    const primaryVisible = await primary.isVisible().catch(() => false)
    const noAnswerVisible = await noAnswer.isVisible().catch(() => false)
    const failureVisible = await failure.isVisible().catch(() => false)

    let answerText = ''
    if (primaryVisible) {
      answerText = (await primary.textContent()) ?? ''
    } else if (noAnswerVisible) {
      answerText = (await noAnswer.textContent()) ?? ''
    } else if (failureVisible) {
      answerText = (await failure.textContent()) ?? ''
    }

    const evidence = {
      schema_id: 'wsa.react_live_ui_evidence.v1',
      generated_at: new Date().toISOString(),
      query,
      page_url: page.url(),
      api_status_text: status,
      primary_visible: primaryVisible,
      no_answer_visible: noAnswerVisible,
      failure_visible: failureVisible,
      answer_text_preview: answerText.slice(0, 600),
      answer_length: answerText.length,
      api_responses: apiResponses,
      citations_visible: await page.getByTestId('home-research-citations').isVisible().catch(() => false),
      ledger_visible: await page
        .getByTestId('home-research-claim-attribution-ledger')
        .isVisible()
        .catch(() => false),
    }

    const outDir = path.resolve(
      process.cwd(),
      '../../docs/architecture/science-source-expansion/2026-11-24-multilingual-answer-substance-parity-live-ui-closure',
    )
    fs.mkdirSync(outDir, { recursive: true })
    fs.writeFileSync(
      path.join(outDir, 'react_live_ui_evidence.json'),
      `${JSON.stringify(evidence, null, 2)}\n`,
      'utf8',
    )

    expect(apiResponses.length).toBeGreaterThan(0)
    expect(apiResponses[0]?.status).toBe(200)
    expect(answerText.trim().length).toBeGreaterThan(20)
    // Must not present empty chrome as a definitive scientific answer without content.
    expect(primaryVisible || noAnswerVisible || failureVisible).toBeTruthy()
    // Substance: primary card must reflect deficit irrigation evidence, not wastewater-only chrome.
    expect(answerText.toLowerCase()).toMatch(/deficit|ناقص|water deficit|regulated water deficit/)
    expect(answerText.toLowerCase()).not.toMatch(/^answer\s*raw wastewater/)
  })
})
