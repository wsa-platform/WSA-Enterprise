import { useState } from 'react'
import {
  FIELD_CROP_CATEGORIES,
  FIELD_CROP_OPTIONS,
  FIELD_CROP_SELECTOR_LABELS,
  getFieldCropsForCategory,
} from './fieldCropCategories'

/** Two-level dependent selector for the محاصيل الحقل page. */
export function FieldCropSelector() {
  const [categoryId, setCategoryId] = useState<string>('')
  const [cropId, setCropId] = useState<string>('')
  const [optionId, setOptionId] = useState<string>('')

  const crops = getFieldCropsForCategory(categoryId || null)

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

      {cropId ? (
        <fieldset
          style={{ width: '100%', margin: 0, padding: 0, border: 0 }}
          aria-label={FIELD_CROP_SELECTOR_LABELS.options}
        >
          <legend style={{ fontSize: '0.875rem', fontWeight: 700, marginBottom: '0.35rem' }}>
            {FIELD_CROP_SELECTOR_LABELS.options}
          </legend>
          <div style={{ display: 'grid', gap: '0.75rem', width: '100%' }}>
            {FIELD_CROP_OPTIONS.map((option) => (
              <button
                key={option.id}
                type="button"
                className={`gs-btn gs-btn-ghost${optionId === option.id ? ' gs-btn-primary' : ''}`}
                style={{
                  flexDirection: 'column',
                  alignItems: 'stretch',
                  textAlign: 'start',
                  whiteSpace: 'normal',
                  width: '100%',
                }}
                aria-pressed={optionId === option.id}
                onClick={() => setOptionId(option.id)}
              >
                <span style={{ fontWeight: 800 }}>{option.label}</span>
                <span
                  style={{
                    fontSize: '0.8125rem',
                    fontWeight: 500,
                    lineHeight: 1.5,
                    opacity: optionId === option.id ? 0.95 : 0.8,
                  }}
                >
                  {option.description}
                </span>
              </button>
            ))}
          </div>
        </fieldset>
      ) : null}
    </form>
  )
}
