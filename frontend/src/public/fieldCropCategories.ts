/** Extensible crop entry — additional metadata can be added later. */
export type FieldCrop = {
  id: string
  name: string
}

/** Field crop category with dependent crops. */
export type FieldCropCategory = {
  id: string
  name: string
  crops: FieldCrop[]
}

export const FIELD_CROP_SELECTOR_LABELS = {
  category: 'تصنيف المحصول',
  categoryPlaceholder: 'اختر تصنيف المحاصيل',
  crop: 'المحصول',
  cropPlaceholderDisabled: 'اختر التصنيف أولاً',
  cropPlaceholderEnabled: 'اختر المحصول',
} as const

/** Initial field crop taxonomy for the محاصيل الحقل page. */
export const FIELD_CROP_CATEGORIES: FieldCropCategory[] = [
  {
    id: 'grains',
    name: 'محاصيل الحبوب',
    crops: [
      { id: 'wheat', name: 'القمح' },
      { id: 'corn', name: 'الذرة' },
      { id: 'rice', name: 'الأرز' },
      { id: 'barley', name: 'الشعير' },
      { id: 'oats', name: 'الشوفان' },
      { id: 'sorghum', name: 'الذرة الرفيعة' },
      { id: 'millet', name: 'الدخن' },
      { id: 'rye', name: 'الجاودار' },
      { id: 'triticale', name: 'التريتيكال' },
    ],
  },
  {
    id: 'sugar',
    name: 'المحاصيل السكرية',
    crops: [
      { id: 'sugarcane', name: 'قصب السكر' },
      { id: 'sugar-beet', name: 'بنجر السكر' },
    ],
  },
  {
    id: 'forage',
    name: 'محاصيل الأعلاف',
    crops: [
      { id: 'alfalfa', name: 'البرسيم' },
      { id: 'clover', name: 'الفصة' },
      { id: 'fodder-corn', name: 'الذرة العلفية' },
      { id: 'fodder-sorghum', name: 'السورجم العلفي' },
      { id: 'sudan-grass', name: 'حشيشة السودان' },
    ],
  },
  {
    id: 'oil',
    name: 'المحاصيل الزيتية',
    crops: [
      { id: 'sunflower', name: 'دوار الشمس' },
      { id: 'soybean', name: 'فول الصويا' },
      { id: 'sesame', name: 'السمسم' },
      { id: 'peanut', name: 'الفول السوداني' },
      { id: 'canola', name: 'الكانولا' },
      { id: 'castor', name: 'الخروع' },
    ],
  },
  {
    id: 'legumes',
    name: 'المحاصيل البقولية',
    crops: [
      { id: 'fava-bean', name: 'الفول' },
      { id: 'lentil', name: 'العدس' },
      { id: 'chickpea', name: 'الحمص' },
      { id: 'pea', name: 'البازلاء' },
      { id: 'cowpea', name: 'اللوبيا' },
    ],
  },
  {
    id: 'fiber',
    name: 'محاصيل الألياف',
    crops: [
      { id: 'cotton', name: 'القطن' },
      { id: 'flax', name: 'الكتان' },
      { id: 'hemp', name: 'القنب' },
      { id: 'jute', name: 'الجوت' },
    ],
  },
  {
    id: 'other',
    name: 'محاصيل أخرى',
    crops: [{ id: 'tobacco', name: 'التبغ' }],
  },
]

export function getFieldCropCategoryById(categoryId: string): FieldCropCategory | undefined {
  return FIELD_CROP_CATEGORIES.find((category) => category.id === categoryId)
}

export function getFieldCropsForCategory(categoryId: string | null): FieldCrop[] {
  if (!categoryId) return []
  return getFieldCropCategoryById(categoryId)?.crops ?? []
}

export function getFieldCropById(categoryId: string, cropId: string): FieldCrop | undefined {
  return getFieldCropsForCategory(categoryId).find((crop) => crop.id === cropId)
}

/** Crops that must never appear in the field crops selector. */
export const EXCLUDED_MEDICINAL_AROMATIC_CROP_NAMES = [
  'الكمون',
  'الكزبرة',
  'الحلبة',
  'اليانسون',
  'الشمر',
  'النعناع',
  'البابونج',
  'الزعتر',
] as const

export function listAllFieldCropNames(): string[] {
  return FIELD_CROP_CATEGORIES.flatMap((category) => category.crops.map((crop) => crop.name))
}
