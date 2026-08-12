import { useTranslation } from 'react-i18next'
import { PageHeader } from '../components/PageHeader'
import { ErrorBanner } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'
import { getAuthSessions, revokeAuthSession, type AuthSession } from '../api'
import i18n from '../i18n/config'

export function SettingsPage() {
  const { t } = useTranslation()
  const { token } = useAuth()
  const { can, context } = usePermissions()
  const { user } = useAuth()

  const { data: sessions, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getAuthSessions(token)
  }, [token])

  const handleRevoke = async (session: AuthSession) => {
    if (!token || session.is_current) return
    await revokeAuthSession(token, session.id)
    await reload()
  }

  return <>
    <PageHeader eyebrow={t('common.account')} title={t('settings.title')} description={t('settings.description')} />

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.profile')}</p><h2>{t('settings.yourAccount')}</h2></div></div>
      <div className="detail-grid">
        <div><span>{t('common.name')}</span><strong>{user?.name}</strong></div>
        <div><span>{t('common.email')}</span><strong>{user?.email}</strong></div>
      </div>
    </section>

    {can('platform.view') && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.enterprise')}</p><h2>{t('organization.workspaceAccess')}</h2></div></div>
        <div className="detail-grid">
          <div><span>{t('organization.organizationId')}</span><strong>{context?.organization_id ?? '—'}</strong></div>
          <div><span>{t('settings.membershipRole')}</span><strong>{context?.membership_role ?? '—'}</strong></div>
        </div>
      </section>
    )}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.security')}</p><h2>{t('settings.activeSessions')}</h2></div></div>
      {error && <ErrorBanner message={error} onRetry={reload} />}
      {loading ? <p className="loading">{t('settings.loadingSessions')}</p> : (
        <div className="detail-grid">
          {(sessions ?? []).map((session) => (
            <div key={session.id}>
              <span>{session.name}{session.is_current ? ` (${t('common.current')})` : ''}</span>
              <strong>
                {session.last_used_at ? new Date(session.last_used_at).toLocaleString() : t('settings.neverUsed')}
                {!session.is_current && (
                  <button type="button" className="link-button inline" onClick={() => void handleRevoke(session)} style={{ marginLeft: '0.75rem' }}>
                    {t('sessions.revoke')}
                  </button>
                )}
              </strong>
            </div>
          ))}
        </div>
      )}
      <p className="muted">{t('settings.signOutHint')}</p>
    </section>
  </>
}
