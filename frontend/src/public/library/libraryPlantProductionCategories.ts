export type LibraryPlantProductionCategory = {
  id: string
  name: string
}

/** Phase 1 library taxonomy — الإنتاج النباتي only. */
export const LIBRARY_PLANT_PRODUCTION_CATEGORIES: readonly LibraryPlantProductionCategory[] = [
  { id: 'grains', name: 'محاصيل الحبوب' },
  { id: 'sugar', name: 'المحاصيل السكرية' },
  { id: 'forage', name: 'محاصيل الأعلاف' },
  { id: 'oil', name: 'المحاصيل الزيتية' },
  { id: 'vegetables', name: 'محاصيل الخضر' },
  { id: 'legumes', name: 'المحاصيل البقولية' },
  { id: 'fruit-trees', name: 'أشجار الفاكهة' },
  { id: 'palm', name: 'النخيل' },
  { id: 'medicinal-aromatic', name: 'النباتات الطبية والعطرية' },
  { id: 'ornamental', name: 'نباتات الزينة' },
  { id: 'fiber', name: 'محاصيل الألياف' },
  { id: 'other', name: 'محاصيل أخرى' },
] as const

export const LIBRARY_PLANT_PRODUCTION_SECTION_TITLE = 'الإنتاج النباتي'
