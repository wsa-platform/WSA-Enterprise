import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { usePermission } from '../context/PermissionContext'

export function StatusBadge({ status }: { status: string }) {
  return <span className={`status-badge status-${status.replace('_', '-')}`}>{status.replace('_', ' ')}</span>
}

export function EmptyState({ title, description, action }: { title: string; description?: string; action?: ReactNode }) {
  return (
    <div className="empty-state">
      <strong>{title}</strong>
      {description && <p>{description}</p>}
      {action}
    </div>
  )
}

export function ErrorBanner({ message, onRetry }: { message: string; onRetry?: () => void }) {
  const { t } = useTranslation()

  return (
    <div className="error banner" role="alert">
      {message}
      {onRetry && <button type="button" className="link-button inline" onClick={onRetry}>{t('common.retry')}</button>}
    </div>
  )
}

export function SkeletonGrid({ count = 4 }: { count?: number }) {
  return (
    <div className="skeleton-grid">
      {Array.from({ length: count }).map((_, index) => (
        <div className="skeleton-card" key={index} aria-hidden="true" />
      ))}
    </div>
  )
}

export function PermissionGate({
  permission,
  children,
  fallback = null,
}: {
  permission: string
  children: ReactNode
  fallback?: ReactNode
}) {
  const { allowed, loading } = usePermission(permission)
  const { t } = useTranslation()
  if (loading) return <p className="loading">{t('errors.checkingAccess')}</p>
  if (!allowed) return fallback
  return children
}
