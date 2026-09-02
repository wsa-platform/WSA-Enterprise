/** Extensible crop entry — additional metadata can be added later. */
export type FieldCrop = {
  id: string
  name: string
  scientificName?: string
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
  options: 'خدمة المحصول',
} as const

/** Stable ids for the three crop-specific service options. */
export const FIELD_CROP_OPTION_IDS = [
  'farming-needs',
  'scientific-research',
  'industries',
] as const

export type FieldCropOptionId = (typeof FIELD_CROP_OPTION_IDS)[number]

export type FieldCropOption = {
  id: FieldCropOptionId
  label: string
}

/** Builds the exact visible label for a crop-specific service option. */
export function getFieldCropOptionLabel(optionId: FieldCropOptionId, cropName: string): string {
  switch (optionId) {
    case 'farming-needs':
      return 'زراعة واحتياجات المحصول'
    case 'scientific-research':
      return `الأبحاث العلمية لمحصول ${cropName}`
    case 'industries':
      return `الصناعات القائمة على ${cropName}`
  }
}

/** Returns the three crop-specific options with labels for the selected crop. */
export function getFieldCropOptionsForCrop(cropName: string): FieldCropOption[] {
  return FIELD_CROP_OPTION_IDS.map((id) => ({
    id,
    label: getFieldCropOptionLabel(id, cropName),
  }))
}

export function getFieldCropOptionById(
  optionId: string,
  cropName: string,
): FieldCropOption | undefined {
  if (!FIELD_CROP_OPTION_IDS.includes(optionId as FieldCropOptionId)) {
    return undefined
  }
  return {
    id: optionId as FieldCropOptionId,
    label: getFieldCropOptionLabel(optionId as FieldCropOptionId, cropName),
  }
}

/** Initial field crop taxonomy for the محاصيل الحقل page. */
export const FIELD_CROP_CATEGORIES: FieldCropCategory[] = [
  {
    id: 'grains',
    name: 'محاصيل الحبوب',
    crops: [
      { id: 'wheat', name: 'القمح', scientificName: 'Triticum aestivum' },
      { id: 'corn', name: 'الذرة', scientificName: 'Zea mays' },
      { id: 'rice', name: 'الأرز', scientificName: 'Oryza sativa' },
      { id: 'barley', name: 'الشعير', scientificName: 'Hordeum vulgare' },
      { id: 'oats', name: 'الشوفان', scientificName: 'Avena sativa' },
      { id: 'sorghum', name: 'الذرة الرفيعة', scientificName: 'Sorghum bicolor' },
      { id: 'millet', name: 'الدخن', scientificName: 'Pennisetum glaucum' },
      { id: 'rye', name: 'الجاودار', scientificName: 'Secale cereale' },
      { id: 'triticale', name: 'التريتيكال', scientificName: '× Triticosecale' },
    ],
  },
  {
    id: 'sugar',
    name: 'المحاصيل السكرية',
    crops: [
      { id: 'sugarcane', name: 'قصب السكر', scientificName: 'Saccharum officinarum' },
      { id: 'sugar-beet', name: 'بنجر السكر', scientificName: 'Beta vulgaris' },
    ],
  },
  {
    id: 'forage',
    name: 'محاصيل الأعلاف',
    crops: [
      { id: 'alfalfa', name: 'البرسيم', scientificName: 'Medicago sativa' },
      { id: 'clover', name: 'الفصة', scientificName: 'Trifolium alexandrinum' },
      { id: 'fodder-corn', name: 'الذرة العلفية', scientificName: 'Zea mays' },
      { id: 'fodder-sorghum', name: 'السورجم العلفي', scientificName: 'Sorghum bicolor' },
      { id: 'sudan-grass', name: 'حشيشة السودان', scientificName: 'Sorghum sudanense' },
    ],
  },
  {
    id: 'oil',
    name: 'المحاصيل الزيتية',
    crops: [
      { id: 'sunflower', name: 'دوار الشمس', scientificName: 'Helianthus annuus' },
      { id: 'soybean', name: 'فول الصويا', scientificName: 'Glycine max' },
      { id: 'sesame', name: 'السمسم', scientificName: 'Sesamum indicum' },
      { id: 'peanut', name: 'الفول السوداني', scientificName: 'Arachis hypogaea' },
      { id: 'canola', name: 'الكانولا', scientificName: 'Brassica napus' },
      { id: 'castor', name: 'الخروع', scientificName: 'Ricinus communis' },
    ],
  },
  {
    id: 'legumes',
    name: 'المحاصيل البقولية',
    crops: [
      { id: 'fava-bean', name: 'الفول', scientificName: 'Vicia faba' },
      { id: 'lentil', name: 'العدس', scientificName: 'Lens culinaris' },
      { id: 'chickpea', name: 'الحمص', scientificName: 'Cicer arietinum' },
      { id: 'pea', name: 'البازلاء', scientificName: 'Pisum sativum' },
      { id: 'cowpea', name: 'اللوبيا', scientificName: 'Vigna unguiculata' },
    ],
  },
  {
    id: 'fiber',
    name: 'محاصيل الألياف',
    crops: [
      { id: 'cotton', name: 'القطن', scientificName: 'Gossypium hirsutum' },
      { id: 'flax', name: 'الكتان', scientificName: 'Linum usitatissimum' },
      { id: 'hemp', name: 'القنب', scientificName: 'Cannabis sativa' },
      { id: 'jute', name: 'الجوت', scientificName: 'Corchorus olitorius' },
    ],
  },
  {
    id: 'other',
    name: 'محاصيل أخرى',
    crops: [{ id: 'tobacco', name: 'التبغ', scientificName: 'Nicotiana tabacum' }],
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
