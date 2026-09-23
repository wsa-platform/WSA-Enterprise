import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { beforeAll, describe, expect, it } from 'vitest'
import i18n from '../../i18n/config'
import { AuthProviders } from './AuthProviders'

describe('AuthProviders', () => {
  beforeAll(async () => {
    await i18n.changeLanguage('en')
  })

  it('exposes official Google and Facebook continue buttons', () => {
    const html = renderToStaticMarkup(
      createElement(AuthProviders, {
        loading: false,
        nextPath: '/jobs/application',
        audience: 'job_seeker',
        onError: () => undefined,
        onLoading: () => undefined,
      }),
    )

    expect(html).toContain('data-auth-provider="google"')
    expect(html).toContain('data-auth-provider="facebook"')
    expect(html).toContain('Continue with Google')
    expect(html).toContain('Continue with Facebook')
    expect(html).not.toContain('disabled')
  })

  it('disables both providers while a flow is in progress', () => {
    const html = renderToStaticMarkup(
      createElement(AuthProviders, {
        loading: true,
        nextPath: '/',
        audience: null,
        onError: () => undefined,
        onLoading: () => undefined,
      }),
    )

    expect(html.match(/disabled/g)?.length).toBeGreaterThanOrEqual(2)
  })
})
