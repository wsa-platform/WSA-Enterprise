import { useState } from 'react'
import { getNotifications, markNotificationRead, type AppNotification, type PaginatedResponse } from '../api'
import { DataTable, PaginationBar } from '../components/DataTable'
import { PageHeader } from '../components/PageHeader'
import { EmptyState, ErrorBanner } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'

export function NotificationsPage() {
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [page, setPage] = useState(1)
  const [message, setMessage] = useState('')

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getNotifications(token, organizationId ?? undefined, page)
  }, [token, organizationId, page])

  if (!can('platform.view')) {
    return <ErrorBanner message="You do not have permission to view notifications." />
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
      setMessage('Notification marked as read.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to mark notification as read.')
    }
  }

  return <>
    <PageHeader eyebrow="PLATFORM" title="Notifications" description="Organization and personal notifications." />
    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      {loading ? <p className="loading">Loading notifications…</p> : rows.length === 0 ? (
        <EmptyState title="No notifications" description="You are all caught up." />
      ) : (
        <>
          <DataTable
            rows={rows}
            rowKey={(notification) => notification.id}
            columns={[
              { key: 'title', header: 'Title', render: (notification) => notification.title },
              { key: 'body', header: 'Message', render: (notification) => notification.body ?? '—' },
              { key: 'read', header: 'Status', render: (notification) => notification.read_at ? 'Read' : 'Unread' },
              { key: 'created', header: 'Created', render: (notification) => new Date(notification.created_at).toLocaleString() },
              {
                key: 'actions',
                header: '',
                render: (notification) => !notification.read_at
                  ? <button type="button" className="link-button inline" onClick={() => void handleRead(notification)}>Mark read</button>
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
