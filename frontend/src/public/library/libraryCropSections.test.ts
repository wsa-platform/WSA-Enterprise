import { describe, expect, it } from 'vitest'
import {
  getLibraryCropSectionLabel,
  getLibraryCropSectionsForCrop,
  LIBRARY_CROP_SECTION_IDS,
} from './libraryCropSections'

describe('library crop sections', () => {
  it('defines exactly four generic sections', () => {
    expect(LIBRARY_CROP_SECTION_IDS).toEqual([
      'farming-needs',
      'scientific-research',
      'industries',
      'other',
    ])
  })

  it.each([
    ['القمح', 'الأبحاث العلمية الخاصة بـ القمح', 'الصناعات القائمة على القمح', 'ملفات أخرى تخص القمح'],
    ['الشوفان', 'الأبحاث العلمية الخاصة بـ الشوفان', 'الصناعات القائمة على الشوفان', 'ملفات أخرى تخص الشوفان'],
    ['الذرة', 'الأبحاث العلمية الخاصة بـ الذرة', 'الصناعات القائمة على الذرة', 'ملفات أخرى تخص الذرة'],
    ['الذرة الرفيعة', 'الأبحاث العلمية الخاصة بـ الذرة الرفيعة', 'الصناعات القائمة على الذرة الرفيعة', 'ملفات أخرى تخص الذرة الرفيعة'],
  ])('uses dynamic crop name for %s', (cropName, research, industries, other) => {
    expect(getLibraryCropSectionLabel('farming-needs', cropName)).toBe('الزراعة والاحتياجات الزراعية')
    expect(getLibraryCropSectionLabel('scientific-research', cropName)).toBe(research)
    expect(getLibraryCropSectionLabel('industries', cropName)).toBe(industries)
    expect(getLibraryCropSectionLabel('other', cropName)).toBe(other)
  })

  it('returns four sections for any crop', () => {
    const sections = getLibraryCropSectionsForCrop('القمح')
    expect(sections).toHaveLength(4)
    expect(sections.map((section) => section.id)).toEqual(LIBRARY_CROP_SECTION_IDS)
  })
})
