import { describe, expect, it } from 'vitest'
import {
  PRODUCT_CATEGORIES,
  PRODUCT_CATEGORY_SLUGS,
  isProductCategorySlug,
  productCategoryLabel,
  resolveCategorySlug,
} from './productCategories'

describe('product categories', () => {
  it('exposes the agricultural and food marketplace categories', () => {
    expect(PRODUCT_CATEGORY_SLUGS).toEqual(expect.arrayContaining([
      'grains',
      'vegetables',
      'fruits',
      'legumes',
      'feed',
      'fertilizers',
      'seeds-seedlings',
      'animal-products',
      'dairy-products',
      'meat-poultry',
      'fish-seafood',
      'honey-bee-products',
      'processed-food',
      'food-beverages',
      'agricultural-supplies',
      'livestock-supplies',
      'beekeeping-supplies',
      'aquaculture-supplies',
      'agricultural-crops',
      'seeds',
      'seedlings',
      'plants',
      'pesticides',
      'food-products',
      'foodstuffs',
      'meat',
      'poultry',
      'oils',
      'dates',
      'herbs-spices',
      'agricultural-equipment',
      'other',
    ]))
    expect(PRODUCT_CATEGORIES).toHaveLength(PRODUCT_CATEGORY_SLUGS.length)
    expect(isProductCategorySlug('grains')).toBe(true)
    expect(isProductCategorySlug('random-text')).toBe(false)
  })

  it('uses Arabic-first labels and keeps other locales', () => {
    expect(productCategoryLabel('grains', 'ar')).toBe('الحبوب')
    expect(productCategoryLabel('vegetables', 'ar')).toBe('الخضروات')
    expect(productCategoryLabel('fruits', 'en')).toBe('Fruits')
    expect(productCategoryLabel('other', 'tr')).toBe('Diğer tarım ve gıda ürünleri')
    expect(productCategoryLabel('agricultural-crops', 'ar')).toBe('المحاصيل الزراعية')
    expect(productCategoryLabel('herbs-spices', 'ar')).toBe('الأعشاب والتوابل')
    expect(productCategoryLabel('seeds', 'ar')).toBe('البذور')
  })

  it('resolves current and legacy category values without crashing', () => {
    expect(resolveCategorySlug({ category: { slug: 'fruits' } })).toBe('fruits')
    expect(resolveCategorySlug({ product_type: 'seeds' })).toBe('seeds')
    expect(resolveCategorySlug({ category: { slug: 'agricultural-crops' } })).toBe('agricultural-crops')
    expect(resolveCategorySlug({ category: { slug: 'herbs-spices' } })).toBe('herbs-spices')
    expect(resolveCategorySlug({ category: { slug: 'legacy-unknown' } })).toBe('legacy-unknown')
    expect(resolveCategorySlug({})).toBe('')
  })
})
