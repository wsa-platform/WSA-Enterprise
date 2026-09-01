import { describe, expect, it } from 'vitest'
import { getLibraryCropById, getLibraryCropsForCategory } from './libraryCrops'

describe('library crops resolver', () => {
  it('lists grains crops for محاصيل الحبوب category', () => {
    const crops = getLibraryCropsForCategory('grains')
    expect(crops.map((crop) => crop.id)).toEqual(
      expect.arrayContaining(['wheat', 'corn', 'oats', 'sorghum']),
    )
  })

  it('resolves wheat corn oats and sorghum under grains', () => {
    expect(getLibraryCropById('grains', 'wheat')?.name).toBe('القمح')
    expect(getLibraryCropById('grains', 'corn')?.name).toBe('الذرة')
    expect(getLibraryCropById('grains', 'oats')?.name).toBe('الشوفان')
    expect(getLibraryCropById('grains', 'sorghum')?.name).toBe('الذرة الرفيعة')
  })

  it('returns empty list for categories without an existing crop catalog', () => {
    expect(getLibraryCropsForCategory('palm')).toEqual([])
    expect(getLibraryCropsForCategory('vegetables')).toEqual([])
  })
})
