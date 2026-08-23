import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { getNotifications, markNotificationRead, type AppNotification, type PaginatedResponse } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import '../jobs/jobSeekerProfile.css'
import './employerWorkspace.css'

export function EmployerNotificationsPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const [page, setPage] = useState(1)

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(t('errors.notAuthenticated'))
    return getNotifications(token, organizationId ?? undefined, page)
  }, [token, organizationId, page, t])

  const payload = data as PaginatedResponse<AppNotification> | AppNotification[] | null
  const rows = Array.isArray(payload) ? payload : payload?.data ?? []
  const lastPage = payload && !Array.isArray(payload) ? payload.last_page : 1

  return (
    <div className="job-seeker-profile employer-workspace">
      <header className="page-header">
        <h1>{t('auth.employer.notifications')}</h1>
      </header>
      {error ? <p className="js-field-error" role="alert">{error}</p> : null}
      <section className="panel">
        {loading ? <p>{t('common.loading')}</p> : null}
        {!loading && rows.length === 0 ? <p>{t('notifications.emptyTitle')}</p> : null}
        <div className="employer-candidate-grid">
          {rows.map((notification) => (
            <article key={notification.id} className="employer-candidate-card">
              <h3>{notification.title}</h3>
              <p>{notification.body ?? '—'}</p>
              {!notification.read_at ? (
                <button
                  type="button"
                  className="js-btn"
                  onClick={() => {
                    if (!token) return
                    void markNotificationRead(token, notification.id, organizationId ?? undefined)
                      .then(() => reload())
                      .catch(() => undefined)
                  }}
                >
                  {t('notifications.markRead')}
                </button>
              ) : null}
            </article>
          ))}
        </div>
        {lastPage > 1 ? (
          <div className="form-actions">
            <button type="button" className="js-btn" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>{t('common.previous')}</button>
            <button type="button" className="js-btn" disabled={page >= lastPage} onClick={() => setPage((value) => value + 1)}>{t('common.next')}</button>
          </div>
        ) : null}
      </section>
    </div>
  )
}
