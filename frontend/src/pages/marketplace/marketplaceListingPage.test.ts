import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { I18nextProvider } from 'react-i18next'
import { MemoryRouter } from 'react-router-dom'
import { beforeAll, describe, expect, it } from 'vitest'
import i18n from '../../i18n/config'
import { ContactUnlockPanel } from './MarketplaceListingPage'

function renderPanel(overrides: Partial<Parameters<typeof ContactUnlockPanel>[0]> = {}) {
  return renderToStaticMarkup(
    createElement(
      MemoryRouter,
      null,
      createElement(
        I18nextProvider,
        { i18n },
        createElement(ContactUnlockPanel, {
          authenticated: overrides.authenticated ?? false,
          loginHref: overrides.loginHref ?? '/login?next=%2Fmarket%2F7',
          paying: overrides.paying ?? false,
          paymentOpen: overrides.paymentOpen ?? false,
          error: overrides.error ?? '',
          contact: overrides.contact ?? null,
          price: overrides.price ?? 25,
          currency: overrides.currency ?? 'SAR',
          onShowContact: overrides.onShowContact ?? (() => undefined),
          onConfirmPayment: overrides.onConfirmPayment ?? (() => undefined),
        }),
      ),
    ),
  )
}

describe('public product contact gate', () => {
  beforeAll(async () => {
    await i18n.changeLanguage('ar')
  })

  it('does not show seller contact until payment unlocks it', () => {
    const html = renderPanel()
    expect(html).toContain('إظهار بيانات الاتصال')
    expect(html).not.toContain('seller@wsa.test')
    expect(html).not.toContain('+966500000000')
    expect(html).toContain('href="/login?next=%2Fmarket%2F7"')
  })

  it('opens the payment flow instead of revealing contact immediately', () => {
    const html = renderPanel({ authenticated: true, paymentOpen: true })
    expect(html).toContain('بيانات التواصل محمية')
    expect(html).not.toContain('seller@wsa.test')
  })

  it('shows contact only after a paid unlock result', () => {
    const html = renderPanel({
      authenticated: true,
      contact: { seller_email: 'seller@wsa.test', seller_phone: '+966500000000' },
    })
    expect(html).toContain('seller@wsa.test')
    expect(html).toContain('+966500000000')
    expect(html).not.toContain('إظهار بيانات الاتصال')
  })
})
