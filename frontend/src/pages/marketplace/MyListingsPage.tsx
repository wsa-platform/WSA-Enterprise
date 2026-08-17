import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { deleteListing, fetchMyListings, submitListing, type OwnerListing } from '../../api/marketplace'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'

export function MyListingsPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [page, setPage] = useState(1)
  const [notice, setNotice] = useState('')
  const canCreate = can('market.create')
  const canManage = can('market.manage_own') || can('market.manage_all') || canCreate

  const { data: payload, loading, error, reload } = useAsyncData(async () => {
    if (!token || !can('market.view')) return null
    return fetchMyListings(token, organizationId ?? undefined, page)
  }, [token, organizationId, page, can])

  if (!can('market.view')) {
    return <ErrorBanner message={t('market.noPermissionView')} />
  }

  const runAction = async (listing: OwnerListing, action: 'submit' | 'delete') => {
    if (!token) return
    setNotice('')
    try {
      if (action === 'delete') {
        if (!window.confirm(t('market.confirmDelete'))) return
        await deleteListing(token, listing.id, organizationId ?? undefined)
        setNotice(t('market.deleted'))
      } else {
        await submitListing(token, listing.id, organizationId ?? undefined)
        setNotice(t('market.submitted'))
      }
      await reload()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('market.actionFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('nav.market')}
      title={t('market.myListingsTitle')}
      description={t('market.myListingsDescription')}
      actions={canCreate ? (
        <Link className="refresh" to="/seller/listings/new">{t('market.newListing')}</Link>
      ) : undefined}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {notice && <p className="notice">{notice}</p>}

    <section className="panel">
      {loading ? (
        <p className="loading">{t('market.loadingListings')}</p>
      ) : (payload?.data.length ?? 0) === 0 ? (
        <EmptyState title={t('market.noListings')} description={t('market.noListingsDescription')} />
      ) : (
        <>
          <DataTable<OwnerListing>
            rows={payload?.data ?? []}
            rowKey={(row) => row.id}
            columns={[
              {
                key: 'title',
                header: t('common.title'),
                render: (row) => canManage
                  ? <Link to={`/seller/listings/${row.id}`} dir="auto">{row.title}</Link>
                  : <span dir="auto">{row.title}</span>,
              },
              { key: 'status', header: t('common.status'), render: (row) => <StatusBadge status={row.status ?? 'draft'} /> },
              { key: 'city', header: t('market.city'), render: (row) => row.city ?? '—' },
              {
                key: 'actions',
                header: t('common.actions'),
                render: (row) => canManage ? (
                  <span className="table-actions">
                    {(row.status === 'draft' || row.status === 'rejected') && (
                      <button type="button" className="link-button" onClick={() => void runAction(row, 'submit')}>
                        {t('market.submit')}
                      </button>
                    )}
                    <button type="button" className="link-button" onClick={() => void runAction(row, 'delete')}>
                      {t('common.delete')}
                    </button>
                  </span>
                ) : '—',
              },
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
