import { describe, expect, it } from 'vitest'
import {
  LIBRARY_PLANT_PRODUCTION_CATEGORIES,
  LIBRARY_PLANT_PRODUCTION_SECTION_TITLE,
} from './libraryPlantProductionCategories'

describe('library plant production categories', () => {
  it('defines exactly the approved phase-1 taxonomy', () => {
    expect(LIBRARY_PLANT_PRODUCTION_SECTION_TITLE).toBe('الإنتاج النباتي')
    expect(LIBRARY_PLANT_PRODUCTION_CATEGORIES.map((category) => category.name)).toEqual([
      'محاصيل الحبوب',
      'المحاصيل السكرية',
      'محاصيل الأعلاف',
      'المحاصيل الزيتية',
      'محاصيل الخضر',
      'المحاصيل البقولية',
      'أشجار الفاكهة',
      'النخيل',
      'النباتات الطبية والعطرية',
      'نباتات الزينة',
      'محاصيل الألياف',
      'محاصيل أخرى',
    ])
  })
})
