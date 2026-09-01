import {
  getFieldCropById,
  getFieldCropsForCategory,
  type FieldCrop,
} from '../fieldCropCategories'

/** Crops for a plant-production library category — sourced from the existing field crop catalog. */
export function getLibraryCropsForCategory(categoryId: string): FieldCrop[] {
  return getFieldCropsForCategory(categoryId)
}

export function getLibraryCropById(categoryId: string, cropId: string): FieldCrop | undefined {
  return getFieldCropById(categoryId, cropId)
}
