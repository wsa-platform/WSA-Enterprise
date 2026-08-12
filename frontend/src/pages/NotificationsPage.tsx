import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { getNotifications, markNotificationRead, type AppNotification, type PaginatedResponse } from '../api'
import { DataTable, PaginationBar } from '../components/DataTable'
import { PageHeader } from '../components/PageHeader'
import { EmptyState, ErrorBanner } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'
import { translateApiError } from '../i18n/apiErrors'
import i18n from '../i18n/config'

export function NotificationsPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [page, setPage] = useState(1)
  const [message, setMessage] = useState('')

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getNotifications(token, organizationId ?? undefined, page)
  }, [token, organizationId, page])

  if (!can('platform.view')) {
    return <ErrorBanner message={t('notifications.noPermission')} />
  }

  const payload = data as PaginatedResponse<AppNotification> | AppNotification[] | null
  const rows = Array.isArray(payload) ? payload : payload?.data ?? []
  const pagination = payload && !Array.isArray(payload)
    ? { page: payload.current_page, lastPage: payload.last_page, total: payload.total }
    : null

  const handleRead = async (notification: AppNotification) => {
    if (!token || notification.read_at) return
    setMessage('')
    try {
      await markNotificationRead(token, notification.id, organizationId ?? undefined)
      setMessage(t('notifications.markedRead'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('notifications.markReadFailed'))
    }
  }

  return <>
    <PageHeader eyebrow={t('common.platform')} title={t('notifications.title')} description={t('notifications.personalDescription')} />
    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      {loading ? <p className="loading">{t('notifications.loading')}</p> : rows.length === 0 ? (
        <EmptyState title={t('notifications.emptyTitle')} description={t('notifications.emptyDescription')} />
      ) : (
        <>
          <DataTable
            rows={rows}
            rowKey={(notification) => notification.id}
            columns={[
              { key: 'title', header: t('common.title'), render: (notification) => notification.title },
              { key: 'body', header: t('common.message'), render: (notification) => notification.body ?? '—' },
              { key: 'read', header: t('common.status'), render: (notification) => notification.read_at ? t('common.read') : t('common.unread') },
              { key: 'created', header: t('common.created'), render: (notification) => new Date(notification.created_at).toLocaleString() },
              {
                key: 'actions',
                header: '',
                render: (notification) => !notification.read_at
                  ? <button type="button" className="link-button inline" onClick={() => void handleRead(notification)}>{t('notifications.markRead')}</button>
                  : '—',
              },
            ]}
          />
          {pagination && (
            <PaginationBar
              page={pagination.page}
              lastPage={pagination.lastPage}
              total={pagination.total}
              onPageChange={setPage}
            />
          )}
        </>
      )}
    </section>
  </>
}
