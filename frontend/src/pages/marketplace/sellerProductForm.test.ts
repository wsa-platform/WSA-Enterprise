import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { I18nextProvider } from 'react-i18next'
import { beforeAll, describe, expect, it } from 'vitest'
import i18n from '../../i18n/config'
import { SellerProductForm } from './SellerProductForm'

function renderForm() {
  return renderToStaticMarkup(
    createElement(
      I18nextProvider,
      { i18n },
      createElement(SellerProductForm, {
        token: 'tok',
        saveLabel: 'حفظ',
        cancelLabel: 'إلغاء',
        onCancel: () => undefined,
        onSaved: () => undefined,
      }),
    ),
  )
}

describe('seller product form specifications field', () => {
  beforeAll(async () => {
    await i18n.changeLanguage('ar')
  })

  it('shows one المواصفات field in the upper description slot and removes the lower duplicate', () => {
    const html = renderForm()
    expect(html).toContain('data-field="product-specifications"')
    expect(html.split('data-field="product-specifications"').length - 1).toBe(1)
    expect((html.match(/المواصفات/g) ?? []).length).toBe(1)
    expect(html).not.toContain('الوصف')
    expect(html).not.toContain('تفاصيل اختيارية، سطر لكل مواصفة')
    expect(html).toContain('حفظ')
  })
})
