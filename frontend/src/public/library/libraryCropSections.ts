/** Stable ids for the four crop library file sections (phase 2). */
export const LIBRARY_CROP_SECTION_IDS = [
  'farming-needs',
  'scientific-research',
  'industries',
  'other',
] as const

export type LibraryCropSectionId = (typeof LIBRARY_CROP_SECTION_IDS)[number]

export function isLibraryCropSectionId(value: string): value is LibraryCropSectionId {
  return LIBRARY_CROP_SECTION_IDS.includes(value as LibraryCropSectionId)
}

/** Builds the exact visible Arabic label for a crop library section. */
export function getLibraryCropSectionLabel(sectionId: LibraryCropSectionId, cropName: string): string {
  switch (sectionId) {
    case 'farming-needs':
      return 'الزراعة والاحتياجات الزراعية'
    case 'scientific-research':
      return `الأبحاث العلمية الخاصة بـ ${cropName}`
    case 'industries':
      return `الصناعات القائمة على ${cropName}`
    case 'other':
      return `ملفات أخرى تخص ${cropName}`
  }
}

export function getLibraryCropSectionsForCrop(cropName: string): Array<{
  id: LibraryCropSectionId
  label: string
}> {
  return LIBRARY_CROP_SECTION_IDS.map((id) => ({
    id,
    label: getLibraryCropSectionLabel(id, cropName),
  }))
}
