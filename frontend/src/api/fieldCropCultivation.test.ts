import { describe, expect, it, vi } from 'vitest'

describe('field crop cultivation api', () => {
  it('builds public farming needs profile query', async () => {
    vi.stubEnv('VITE_PUBLIC_ORG_SLUG', 'wsa-demo')

    const { fetchFieldCropFarmingNeedsProfile } = await import('../api/fieldCropCultivation')

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
})
