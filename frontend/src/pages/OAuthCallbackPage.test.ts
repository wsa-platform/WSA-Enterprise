import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { MemoryRouter } from 'react-router-dom'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import i18n from '../i18n/config'

vi.mock('../context/AuthContext', () => ({
  useAuth: () => ({ setSession: () => undefined, setOrganizationId: () => undefined }),
}))

import { OAuthCallbackPage } from './OAuthCallbackPage'

describe('OAuthCallbackPage', () => {
  beforeAll(async () => {
    await i18n.changeLanguage('en')
  })

  it('treats provider cancellation as a cancelled sign-in, not a failure', () => {
    const html = renderToStaticMarkup(
      createElement(
        MemoryRouter,
        { initialEntries: ['/auth/callback?error=access_denied'] },
        createElement(OAuthCallbackPage),
      ),
    )

    expect(html).toContain('Sign-in was cancelled.')
    expect(html).not.toContain('Google sign-in could not be completed.')
  })
})
