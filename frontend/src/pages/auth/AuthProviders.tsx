import { useTranslation } from 'react-i18next'
import {
  AUTH_NEXT_STORAGE_KEY,
  AUTH_PROVIDER_STORAGE_KEY,
  persistAudience,
  type AuthAudience,
} from '../../navigation/roleDestinations'
import { getFacebookRedirect, getGoogleRedirect } from '../../api'
import { translateApiError } from '../../i18n/apiErrors'

export function AuthProviders({
  loading,
  nextPath,
  audience,
  onError,
  onLoading,
}: {
  loading: boolean
  nextPath: string
  audience: AuthAudience | null
  onError: (message: string) => void
  onLoading: (loading: boolean) => void
}) {
  const { t } = useTranslation()

  const startOAuth = async (provider: 'google' | 'facebook') => {
    onLoading(true)
    onError('')
    try {
      const result = provider === 'google' ? await getGoogleRedirect() : await getFacebookRedirect()
      if ('error' in result || !('url' in result)) {
        onError(t('website.auth.unavailable'))
        return
      }
      sessionStorage.setItem(AUTH_NEXT_STORAGE_KEY, nextPath)
      sessionStorage.setItem(AUTH_PROVIDER_STORAGE_KEY, provider)
      persistAudience(audience)
      window.location.assign(result.url)
    } catch (requestError) {
      onError(translateApiError(requestError) || t('website.auth.unavailable'))
    } finally {
      onLoading(false)
    }
  }

  return (
    <div className="public-auth-providers">
      <button type="button" className="public-auth-provider" disabled={loading} onClick={() => void startOAuth('google')}>
        {t('website.auth.google')}
      </button>
      <button type="button" className="public-auth-provider" disabled={loading} onClick={() => void startOAuth('facebook')}>
        {t('website.auth.facebook')}
      </button>
    </div>
  )
}
