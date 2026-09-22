import { describe, expect, it, vi } from 'vitest'
import {
  hasCanonicalStage5Answer,
  resolveCanonicalCropAnswerText,
  resolveCropAnswerRenderMode,
} from './fieldCropCultivation'

describe('field crop cultivation api', () => {
  it('builds public farming needs profile query', async () => {
    vi.stubEnv('VITE_PUBLIC_ORG_SLUG', 'wsa-demo')

    const { fetchFieldCropFarmingNeedsProfile } = await import('./fieldCropCultivation')

    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ title: 'زراعة واحتياجات محصول القمح', sections: [] }),
      headers: { get: () => null },
    })
    vi.stubGlobal('fetch', fetchMock)

    await fetchFieldCropFarmingNeedsProfile({
      selectedCropId: 'wheat',
      selectedCropName: 'القمح',
      selectedCategoryId: 'grains',
      selectedCategoryName: 'محاصيل الحبوب',
    })

    const url = String(fetchMock.mock.calls[0]?.[0] ?? '')
    expect(url).toContain('/public/field-crops/farming-needs-profile')
    expect(url).toContain('selected_crop_id=wheat')
    expect(url).toContain('organization=wsa-demo')
  })

  it('O accepts Stage 5 dual-emit fields on the profile contract helpers', () => {
    expect(
      resolveCanonicalCropAnswerText({
        answer: 'Stage 5 answer',
        concise_summary: 'summary',
      }),
    ).toBe('Stage 5 answer')
    expect(hasCanonicalStage5Answer({ answer: 'x', concise_summary: null })).toBe(true)
    expect(
      resolveCropAnswerRenderMode({
        answer: 'x',
        concise_summary: null,
        status: 'completed',
      }),
    ).toBe('canonical')
  })
})
