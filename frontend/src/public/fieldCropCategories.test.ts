import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { FieldCropSelector } from './FieldCropSelector'
import {
  EXCLUDED_MEDICINAL_AROMATIC_CROP_NAMES,
  FIELD_CROP_CATEGORIES,
  FIELD_CROP_OPTION_IDS,
  FIELD_CROP_SELECTOR_LABELS,
  getFieldCropById,
  getFieldCropOptionById,
  getFieldCropOptionLabel,
  getFieldCropOptionsForCrop,
  getFieldCropsForCategory,
  listAllFieldCropNames,
} from './fieldCropCategories'

describe('field crop categories data', () => {
  it('defines exactly seven field crop categories', () => {
    expect(FIELD_CROP_CATEGORIES).toHaveLength(7)
    expect(FIELD_CROP_CATEGORIES.map((category) => category.name)).toEqual([
      'محاصيل الحبوب',
      'المحاصيل السكرية',
      'محاصيل الأعلاف',
      'المحاصيل الزيتية',
      'المحاصيل البقولية',
      'محاصيل الألياف',
      'محاصيل أخرى',
    ])
  })

  it('lists grain crops only for محاصيل الحبوب', () => {
    expect(getFieldCropsForCategory('grains').map((crop) => crop.name)).toEqual([
      'القمح',
      'الذرة',
      'الأرز',
      'الشعير',
      'الشوفان',
      'الذرة الرفيعة',
      'الدخن',
      'الجاودار',
      'التريتيكال',
    ])
  })

  it('lists forage crops only for محاصيل الأعلاف', () => {
    expect(getFieldCropsForCategory('forage').map((crop) => crop.name)).toEqual([
      'البرسيم',
      'الفصة',
      'الذرة العلفية',
      'السورجم العلفي',
      'حشيشة السودان',
    ])
  })

  it('lists oil crops only for المحاصيل الزيتية', () => {
    expect(getFieldCropsForCategory('oil').map((crop) => crop.name)).toEqual([
      'دوار الشمس',
      'فول الصويا',
      'السمسم',
      'الفول السوداني',
      'الكانولا',
      'الخروع',
    ])
  })

  it('lists legume crops only for المحاصيل البقولية', () => {
    expect(getFieldCropsForCategory('legumes').map((crop) => crop.name)).toEqual([
      'الفول',
      'العدس',
      'الحمص',
      'البازلاء',
      'اللوبيا',
    ])
  })

  it('lists sugar crops only for المحاصيل السكرية', () => {
    expect(getFieldCropsForCategory('sugar').map((crop) => crop.name)).toEqual([
      'قصب السكر',
      'بنجر السكر',
    ])
  })

  it('lists fiber crops only for محاصيل الألياف', () => {
    expect(getFieldCropsForCategory('fiber').map((crop) => crop.name)).toEqual([
      'القطن',
      'الكتان',
      'القنب',
      'الجوت',
    ])
  })

  it('resolves a selected crop within its category', () => {
    expect(getFieldCropById('grains', 'wheat')).toEqual({ id: 'wheat', name: 'القمح' })
    expect(getFieldCropById('forage', 'wheat')).toBeUndefined()
  })

  it('does not include medicinal or aromatic crops', () => {
    const allNames = listAllFieldCropNames()
    for (const excluded of EXCLUDED_MEDICINAL_AROMATIC_CROP_NAMES) {
      expect(allNames).not.toContain(excluded)
    }
    expect(allNames).not.toContain('محاصيل التوابل والعطرية')
    expect(allNames).not.toContain('النباتات الطبية')
    expect(allNames).not.toContain('النباتات العطرية')
  })

  it('returns no crops when category is unset', () => {
    expect(getFieldCropsForCategory(null)).toEqual([])
    expect(getFieldCropsForCategory('')).toEqual([])
  })
})

describe('field crop options data', () => {
  it('defines exactly three crop-specific service option ids', () => {
    expect(FIELD_CROP_OPTION_IDS).toHaveLength(3)
    expect(FIELD_CROP_OPTION_IDS).toEqual([
      'farming-needs',
      'scientific-research',
      'industries',
    ])
  })

  it('builds exact labels for القمح', () => {
    expect(getFieldCropOptionsForCrop('القمح').map((option) => option.label)).toEqual([
      'زراعة واحتياجات المحصول',
      'الأبحاث العلمية لمحصول القمح',
      'الصناعات القائمة على القمح',
    ])
  })

  it('builds exact labels for الذرة', () => {
    expect(getFieldCropOptionsForCrop('الذرة').map((option) => option.label)).toEqual([
      'زراعة واحتياجات المحصول',
      'الأبحاث العلمية لمحصول الذرة',
      'الصناعات القائمة على الذرة',
    ])
  })

  it('builds exact labels for الأرز', () => {
    expect(getFieldCropOptionsForCrop('الأرز').map((option) => option.label)).toEqual([
      'زراعة واحتياجات المحصول',
      'الأبحاث العلمية لمحصول الأرز',
      'الصناعات القائمة على الأرز',
    ])
  })

  it('resolves crop option helpers with dynamic crop name', () => {
    expect(getFieldCropOptionLabel('farming-needs', 'الشعير')).toBe('زراعة واحتياجات المحصول')
    expect(getFieldCropOptionLabel('scientific-research', 'الشعير')).toBe(
      'الأبحاث العلمية لمحصول الشعير',
    )
    expect(getFieldCropOptionLabel('industries', 'الشعير')).toBe('الصناعات القائمة على الشعير')
    expect(getFieldCropOptionById('industries', 'الشعير')?.label).toBe('الصناعات القائمة على الشعير')
    expect(getFieldCropOptionById('unknown', 'الشعير')).toBeUndefined()
  })
})

describe('FieldCropSelector', () => {
  it('renders both selectors with Arabic labels and placeholders', () => {
    const html = renderToStaticMarkup(createElement(FieldCropSelector))
    expect(html).toContain(FIELD_CROP_SELECTOR_LABELS.category)
    expect(html).toContain(FIELD_CROP_SELECTOR_LABELS.categoryPlaceholder)
    expect(html).toContain(FIELD_CROP_SELECTOR_LABELS.crop)
    expect(html).toContain(FIELD_CROP_SELECTOR_LABELS.cropPlaceholderDisabled)
    expect(html).toContain('disabled')
    expect(html).toContain('محاصيل الحبوب')
    expect(html).not.toContain('الكمون')
    expect(html).not.toContain('معلومات الإنتاج')
    expect(html).not.toContain('معلومات الأمراض والآفات')
    expect(html).not.toContain('توصيات زراعية')
  })
})
