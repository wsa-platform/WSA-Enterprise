import { useTranslation } from 'react-i18next'
import { Link, useLocation } from 'react-router-dom'
import { getMyJobSeekerProfile } from '../../api/jobs'
import { ApiError } from '../../api/client'
import { PageHeader } from '../../components/PageHeader'
import { Panel } from '../../components/AppShell'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { useAsyncData } from '../../hooks/useAsyncData'

export function MyApplicationsPage() {
  const { t } = useTranslation()
  const location = useLocation()
  const { token, organizationId } = useAuth()
  const notice = typeof (location.state as { notice?: unknown } | null)?.notice === 'string'
    ? String((location.state as { notice: string }).notice)
    : ''

  const { data: profile, loading, error, reload } = useAsyncData(async () => {
    if (!token) return null
    try {
      return await getMyJobSeekerProfile(token, organizationId ?? undefined)
    } catch (requestError) {
      if (requestError instanceof ApiError && requestError.isNotFound) return null
      throw requestError
    }
  }, [token, organizationId])

  if (!token) {
    return <ErrorBanner message={t('jobs.noPermissionTalent')} />
  }

  return (
    <div className="job-seeker-profile">
      <PageHeader eyebrow={t('nav.ecosystem')} title={t('jobs.applicationsListTitle')} />
      {notice ? <p className="notice success" role="status">{notice}</p> : null}
      {error ? <ErrorBanner message={error} onRetry={reload} /> : null}
      {loading ? <p className="loading">{t('jobs.loadingProfile')}</p> : (
        <Panel eyebrow={t('jobs.applicationsListTitle')} title={t('jobs.applicationsListTitle')}>
          {profile ? (
            <div className="js-item-card">
              <p className="js-item-title">{profile.full_name}</p>
              <p className="muted">{profile.target_job_title || t('jobs.emptyData')}</p>
              <Link to="/jobs/application" className="js-btn js-btn-secondary">{t('jobs.viewApplication')}</Link>
            </div>
          ) : (
            <div>
              <p className="js-empty">{t('jobs.noApplications')}</p>
              <Link to="/jobs/application" className="js-btn js-btn-primary">{t('jobs.createApplication')}</Link>
            </div>
          )}
        </Panel>
      )}
    </div>
  )
}
