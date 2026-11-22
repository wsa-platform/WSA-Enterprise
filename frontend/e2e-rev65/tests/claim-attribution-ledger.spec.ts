import { expect, test } from '@playwright/test'

/**
 * Browser E2E: ClaimAttributionLedger + citations + status rendering on Home.
 * Uses route mocking so the UI contract is verified without paid APIs.
 * Does NOT claim live multi-source scientific success.
 */
test.describe('Home claim attribution ledger presentation', () => {
  test('renders ledger edges, citations, and non-empty failure/status metadata', async ({
    page,
  }) => {
    await page.route('**/api/v1/public/research-agent/query', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'scientific_generated',
          failure_class: null,
          failure_code: null,
          answer: 'Temperature modulates wheat growth rate under controlled conditions.',
          language: 'en',
          citations: [
            {
              citation_id: 'cite-ev-1',
              evidence_id: 'ev-1',
              source_id: 'W1',
              source_key: 'openalex',
              title: 'Temperature and wheat growth',
              url: 'https://example.test/paper',
              doi: '10.1000/example',
            },
          ],
          claim_attribution_ledger: [
            {
              claim_id: 'claim-J1',
              claim_text: 'Temperature modulates wheat growth rate under controlled conditions.',
              evidence_ids: ['ev-1'],
              source_keys: ['openalex'],
              l7_final_claim_attribution_proven: true,
              attributions: [
                {
                  evidence_id: 'ev-1',
                  source_key: 'openalex',
                  provider_record_id: '10.1000/example',
                  citation_id: 'cite-ev-1',
                  citation_resolves_to_evidence: true,
                },
              ],
            },
          ],
          user_presentation: {
            human_status: 'answered',
            primary_answer: 'Temperature modulates wheat growth rate under controlled conditions.',
            sources: [
              {
                result_id: 'cite-ev-1',
                title: 'Temperature and wheat growth',
                original_url: 'https://example.test/paper',
                organization: 'OpenAlex',
              },
            ],
            results: [
              {
                result_id: 'cite-ev-1',
                title: 'Temperature and wheat growth',
                original_url: 'https://example.test/paper',
              },
            ],
          },
        }),
      })
    })

    await page.goto('/?lang=en#home-research')
    await page.locator('#home-research-query').fill(
      'What is the effect of temperature on cereal crop growth?',
    )
    await page.locator('.hp-research-submit').click()

    await expect(page.getByTestId('home-research-api-status')).toHaveAttribute(
      'data-status',
      'scientific_generated',
    )
    await expect(page.getByTestId('home-research-citations')).toBeVisible()
    await expect(page.getByTestId('home-research-claim-attribution-ledger')).toBeVisible()
    const row = page.getByTestId('home-research-claim-attribution-row').first()
    await expect(row).toHaveAttribute('data-l7', 'true')
    await expect(row).toHaveAttribute('data-source-keys', 'openalex')
    await expect(page.getByTestId('home-research-claim-evidence-edge').first()).toHaveAttribute(
      'data-citation-id',
      'cite-ev-1',
    )
  })

  test('surfaces insufficient failure class instead of silent empty success', async ({ page }) => {
    await page.route('**/api/v1/public/research-agent/query', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'no_validated_evidence',
          failure_class: 'insufficient_validated_evidence',
          failure_code: 'no_validated_evidence',
          answer: null,
          citations: [],
          claim_attribution_ledger: [],
          user_presentation: {
            human_status: 'insufficient',
            primary_answer: null,
            sources: [],
            results: [],
          },
        }),
      })
    })

    await page.goto('/?lang=en#home-research')
    await page.locator('#home-research-query').fill('Unrecognized scientific query for failure path')
    await page.locator('.hp-research-submit').click()

    await expect(page.getByTestId('home-research-api-status')).toHaveAttribute(
      'data-failure-class',
      'insufficient_validated_evidence',
    )
    await expect(page.getByTestId('home-research-sources-empty')).toBeVisible()
  })
})
