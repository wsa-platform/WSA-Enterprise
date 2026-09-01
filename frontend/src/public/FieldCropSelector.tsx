import { useState } from 'react'
import {
  FIELD_CROP_CATEGORIES,
  FIELD_CROP_SELECTOR_LABELS,
  getFieldCropsForCategory,
} from './fieldCropCategories'

/** Two-level dependent selector for the محاصيل الحقل page. */
export function FieldCropSelector() {
  const [categoryId, setCategoryId] = useState<string>('')
  const [cropId, setCropId] = useState<string>('')

  const crops = getFieldCropsForCategory(categoryId || null)

  function handleCategoryChange(nextCategoryId: string) {
    setCategoryId(nextCategoryId)
    setCropId('')
  }

  return (
    <form
      className="gs-field-crop-selector"
      aria-label={FIELD_CROP_SELECTOR_LABELS.category}
      onSubmit={(event) => event.preventDefault()}
    >
      <label htmlFor="field-crop-category">
        <span>{FIELD_CROP_SELECTOR_LABELS.category}</span>
        <select
          id="field-crop-category"
          name="fieldCropCategory"
          value={categoryId}
          onChange={(event) => handleCategoryChange(event.target.value)}
        >
          <option value="">{FIELD_CROP_SELECTOR_LABELS.categoryPlaceholder}</option>
          {FIELD_CROP_CATEGORIES.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
      </label>

      <label htmlFor="field-crop-name">
        <span>{FIELD_CROP_SELECTOR_LABELS.crop}</span>
        <select
          id="field-crop-name"
          name="fieldCrop"
          value={cropId}
          disabled={!categoryId}
          onChange={(event) => setCropId(event.target.value)}
        >
          <option value="">
            {categoryId
              ? FIELD_CROP_SELECTOR_LABELS.cropPlaceholderEnabled
              : FIELD_CROP_SELECTOR_LABELS.cropPlaceholderDisabled}
          </option>
          {crops.map((crop) => (
            <option key={crop.id} value={crop.id}>
              {crop.name}
            </option>
          ))}
        </select>
      </label>
    </form>
  )
}
