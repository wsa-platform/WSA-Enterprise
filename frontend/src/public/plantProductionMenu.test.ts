import { describe, expect, it } from 'vitest'
import {
  PLANT_PRODUCTION_CATEGORY_ITEMS,
  PLANT_PRODUCTION_ROUTES,
  cropsMenuReducer,
  isPlantProductionCategoryId,
  plantProductionCategoryItem,
} from './plantProductionMenu'

describe('plant production menu data', () => {
  it('exposes exactly five dropdown destinations with semantic routes', () => {
    expect(PLANT_PRODUCTION_CATEGORY_ITEMS).toHaveLength(5)
    expect(PLANT_PRODUCTION_CATEGORY_ITEMS.map((item) => item.id)).toEqual([
      'field-crops',
      'vegetable-crops',
      'fruit-trees',
      'ornamental-plants',
      'medicinal-aromatic-plants',
    ])
    expect(PLANT_PRODUCTION_CATEGORY_ITEMS.map((item) => item.to)).toEqual([
      '/plant-production/field-crops',
      '/plant-production/vegetable-crops',
      '/plant-production/fruit-trees',
      '/plant-production/ornamental-plants',
      '/plant-production/medicinal-aromatic-plants',
    ])
    expect(PLANT_PRODUCTION_ROUTES).toEqual({
      'field-crops': '/plant-production/field-crops',
      'vegetable-crops': '/plant-production/vegetable-crops',
      'fruit-trees': '/plant-production/fruit-trees',
      'ornamental-plants': '/plant-production/ornamental-plants',
      'medicinal-aromatic-plants': '/plant-production/medicinal-aromatic-plants',
    })
  })

  it('resolves category helpers', () => {
    expect(isPlantProductionCategoryId('field-crops')).toBe(true)
    expect(isPlantProductionCategoryId('unknown')).toBe(false)
    expect(plantProductionCategoryItem('fruit-trees').to).toBe('/plant-production/fruit-trees')
  })
})

describe('plant production menu reducer', () => {
  it('opens on hover and closes on leave commit', () => {
    expect(cropsMenuReducer(false, { type: 'pointer_enter' })).toBe(true)
    expect(cropsMenuReducer(true, { type: 'pointer_leave_request' })).toBe(true)
    expect(cropsMenuReducer(true, { type: 'pointer_leave_commit' })).toBe(false)
  })

  it('toggles for touch interaction', () => {
    expect(cropsMenuReducer(false, { type: 'toggle' })).toBe(true)
    expect(cropsMenuReducer(true, { type: 'toggle' })).toBe(false)
  })
})
