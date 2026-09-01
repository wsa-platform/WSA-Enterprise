import { cropsMenuReducer, type CropsMenuAction } from './cropsMenu'

export const PLANT_PRODUCTION_ROUTES = {
  'field-crops': '/plant-production/field-crops',
  'vegetable-crops': '/plant-production/vegetable-crops',
  'fruit-trees': '/plant-production/fruit-trees',
  'ornamental-plants': '/plant-production/ornamental-plants',
  'medicinal-aromatic-plants': '/plant-production/medicinal-aromatic-plants',
} as const

export type PlantProductionCategoryId = keyof typeof PLANT_PRODUCTION_ROUTES

export type PlantProductionCategoryItem = {
  id: PlantProductionCategoryId
  to: (typeof PLANT_PRODUCTION_ROUTES)[PlantProductionCategoryId]
  icon: string
  labelKey: string
}

/** Header dropdown destinations for الإنتاج النباتي. */
export const PLANT_PRODUCTION_CATEGORY_ITEMS: readonly PlantProductionCategoryItem[] = [
  {
    id: 'field-crops',
    to: PLANT_PRODUCTION_ROUTES['field-crops'],
    icon: '🌾',
    labelKey: 'website.plantProduction.fieldCrops',
  },
  {
    id: 'vegetable-crops',
    to: PLANT_PRODUCTION_ROUTES['vegetable-crops'],
    icon: '🥬',
    labelKey: 'website.plantProduction.vegetableCrops',
  },
  {
    id: 'fruit-trees',
    to: PLANT_PRODUCTION_ROUTES['fruit-trees'],
    icon: '🍎',
    labelKey: 'website.plantProduction.fruitTrees',
  },
  {
    id: 'ornamental-plants',
    to: PLANT_PRODUCTION_ROUTES['ornamental-plants'],
    icon: '🌸',
    labelKey: 'website.plantProduction.ornamentalPlants',
  },
  {
    id: 'medicinal-aromatic-plants',
    to: PLANT_PRODUCTION_ROUTES['medicinal-aromatic-plants'],
    icon: '🌿',
    labelKey: 'website.plantProduction.medicinalAromaticPlants',
  },
] as const

export { cropsMenuReducer, type CropsMenuAction as PlantProductionMenuAction }

export function isPlantProductionCategoryId(
  value: string | undefined,
): value is PlantProductionCategoryId {
  return Boolean(value && value in PLANT_PRODUCTION_ROUTES)
}

export function plantProductionCategoryItem(id: PlantProductionCategoryId): PlantProductionCategoryItem {
  const item = PLANT_PRODUCTION_CATEGORY_ITEMS.find((entry) => entry.id === id)
  if (!item) throw new Error(`Unknown plant production category: ${id}`)
  return item
}

export function isPlantProductionPath(pathname: string): boolean {
  return pathname.startsWith('/plant-production/')
}
