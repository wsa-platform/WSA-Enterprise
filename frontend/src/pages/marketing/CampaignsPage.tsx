import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { listMarketingCampaigns, type MarketingCampaign } from '../../api/marketing'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'

export function CampaignsPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [page, setPage] = useState(1)

  const { data: payload, loading, error, reload } = useAsyncData(async () => {
    if (!token || !can('marketing.view')) return null
    return listMarketingCampaigns(token, organizationId ?? undefined, page)
  }, [token, organizationId, page, can])

  if (!can('marketing.view')) {
    return <ErrorBanner message={t('marketing.noPermissionView')} />
  }

  return <>
    <PageHeader
      eyebrow={t('nav.marketing')}
      title={t('marketing.campaignsTitle')}
      description={t('marketing.campaignsDescription')}
      actions={can('marketing.manage') ? (
        <Link className="refresh" to="/marketing/campaigns/new">{t('marketing.newCampaign')}</Link>
      ) : undefined}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}

    <section className="panel">
      {loading ? (
        <p className="loading">{t('marketing.loadingCampaigns')}</p>
      ) : (payload?.data.length ?? 0) === 0 ? (
        <EmptyState title={t('marketing.noCampaigns')} description={t('marketing.noCampaignsDescription')} />
      ) : (
        <>
          <DataTable<MarketingCampaign>
            rows={payload?.data ?? []}
            rowKey={(row) => row.id}
            columns={[
              {
                key: 'name',
                header: t('common.name'),
                render: (row) => <Link to={`/marketing/campaigns/${row.id}`} dir="auto">{row.name}</Link>,
              },
              { key: 'channel', header: t('marketing.channelLabel'), render: (row) => t(`marketing.channel.${row.channel}`) },
              { key: 'status', header: t('common.status'), render: (row) => <StatusBadge status={row.status} /> },
              { key: 'segment', header: t('marketing.segment'), render: (row) => row.segment?.name ?? '—' },
            ]}
          />
          {payload && (
            <PaginationBar page={payload.current_page} lastPage={payload.last_page} total={payload.total} onPageChange={setPage} />
          )}
        </>
      )}
    </section>
  </>
}
