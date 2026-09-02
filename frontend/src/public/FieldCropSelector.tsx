import { useState } from 'react'
import { FieldCropFarmingNeedsPanel } from './FieldCropFarmingNeedsPanel'
import {
  FIELD_CROP_CATEGORIES,
  FIELD_CROP_SELECTOR_LABELS,
  getFieldCropById,
  getFieldCropOptionsForCrop,
  getFieldCropsForCategory,
  type FieldCropOptionId,
} from './fieldCropCategories'

/** Two-level dependent selector for the محاصيل الحقل page. */
export function FieldCropSelector() {
  const [categoryId, setCategoryId] = useState<string>('')
  const [cropId, setCropId] = useState<string>('')
  const [optionId, setOptionId] = useState<FieldCropOptionId | ''>('')

  const crops = getFieldCropsForCategory(categoryId || null)
  const selectedCrop = categoryId && cropId ? getFieldCropById(categoryId, cropId) : undefined
  const selectedCategory = FIELD_CROP_CATEGORIES.find((category) => category.id === categoryId)
  const cropOptions = selectedCrop ? getFieldCropOptionsForCrop(selectedCrop.name) : []

  function handleCategoryChange(nextCategoryId: string) {
    setCategoryId(nextCategoryId)
    setCropId('')
    setOptionId('')
  }

  function handleCropChange(nextCropId: string) {
    setCropId(nextCropId)
    setOptionId('')
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
          onChange={(event) => handleCropChange(event.target.value)}
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

      {selectedCrop ? (
        <fieldset
          style={{ width: '100%', margin: 0, padding: 0, border: 0 }}
          aria-label={FIELD_CROP_SELECTOR_LABELS.options}
        >
          <legend style={{ fontSize: '0.875rem', fontWeight: 700, marginBottom: '0.35rem' }}>
            {FIELD_CROP_SELECTOR_LABELS.options}
          </legend>
          <div style={{ display: 'grid', gap: '0.75rem', width: '100%' }}>
            {cropOptions.map((option) => (
              <button
                key={option.id}
                type="button"
                className={`gs-btn gs-btn-ghost${optionId === option.id ? ' gs-btn-primary' : ''}`}
                style={{
                  alignItems: 'stretch',
                  textAlign: 'start',
                  whiteSpace: 'normal',
                  width: '100%',
                }}
                aria-pressed={optionId === option.id}
                data-field-crop-id={selectedCrop.id}
                data-field-crop-category-id={categoryId}
                onClick={() => setOptionId(option.id)}
              >
                <span style={{ fontWeight: 800 }}>{option.label}</span>
              </button>
            ))}
          </div>
        </fieldset>
      ) : null}

      {optionId && selectedCrop && selectedCategory ? (
        <FieldCropFarmingNeedsPanel
          knowledgeOption={optionId}
          categoryId={categoryId}
          categoryName={selectedCategory.name}
          cropId={selectedCrop.id}
          cropName={selectedCrop.name}
        />
      ) : null}
    </form>
  )
}
