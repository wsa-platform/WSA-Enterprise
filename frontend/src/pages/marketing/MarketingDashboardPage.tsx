import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { getMarketingDashboard } from '../../api/marketing'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'

export function MarketingDashboardPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()

  const { data: stats, loading, error, reload } = useAsyncData(async () => {
    if (!token || !can('marketing.admin')) return null
    return getMarketingDashboard(token, organizationId ?? undefined)
  }, [token, organizationId, can])

  if (!can('marketing.admin')) {
    return <ErrorBanner message={t('marketing.noPermissionAdmin')} />
  }

  return <>
    <PageHeader
      eyebrow={t('nav.marketing')}
      title={t('marketing.dashboardTitle')}
      description={t('marketing.dashboardDescription')}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}

    {loading ? (
      <p className="loading">{t('marketing.loadingDashboard')}</p>
    ) : stats && (
      <>
        <section className="panel">
          <div className="panel-heading"><div><p className="eyebrow">{t('marketing.overview')}</p><h2>{t('marketing.stats')}</h2></div></div>
          <div className="module-results">
            <article className="record-card">
              <strong>{stats.campaigns}</strong>
              <span>{t('marketing.totalCampaigns')}</span>
            </article>
            <article className="record-card">
              <strong>{stats.scheduled}</strong>
              <span>{t('marketing.scheduledCampaigns')}</span>
            </article>
            <article className="record-card">
              <strong>{stats.deliveries}</strong>
              <span>{t('marketing.totalDeliveries')}</span>
            </article>
            <article className="record-card">
              <strong>{stats.failed_deliveries}</strong>
              <span>{t('marketing.failedDeliveries')}</span>
            </article>
            <article className="record-card">
              <strong>{stats.segments}</strong>
              <span>{t('marketing.segments')}</span>
            </article>
            <article className="record-card">
              <strong>{stats.templates}</strong>
              <span>{t('marketing.templates')}</span>
            </article>
          </div>
        </section>

        {Object.keys(stats.channel_stats).length > 0 && (
          <section className="panel">
            <div className="panel-heading"><div><p className="eyebrow">{t('marketing.channels')}</p><h2>{t('marketing.channelBreakdown')}</h2></div></div>
            <div className="module-results">
              {Object.entries(stats.channel_stats).map(([channel, total]) => (
                <article className="record-card" key={channel}>
                  <strong>{total}</strong>
                  <span>{t(`marketing.channel.${channel}`, { defaultValue: channel })}</span>
                </article>
              ))}
            </div>
          </section>
        )}

        <section className="panel">
          <div className="panel-heading"><div><p className="eyebrow">{t('marketing.quickLinks')}</p><h2>{t('marketing.manage')}</h2></div></div>
          <div className="module-results">
            <Link className="record-card" to="/marketing/campaigns">{t('marketing.campaigns')}</Link>
            <Link className="record-card" to="/marketing/templates">{t('marketing.templates')}</Link>
            <Link className="record-card" to="/marketing/segments">{t('marketing.segments')}</Link>
            <Link className="record-card" to="/marketing/consent">{t('marketing.consent')}</Link>
          </div>
        </section>
      </>
    )}
  </>
}
