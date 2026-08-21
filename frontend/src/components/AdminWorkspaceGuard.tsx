import { type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate } from 'react-router-dom'
import { usePermissions } from '../context/PermissionContext'
import { destinationForRoles, readStoredAudience, roleFlagsFromPermissions } from '../navigation/roleDestinations'

export function AdminWorkspaceGuard({ children }: { children: ReactNode }) {
  const { t } = useTranslation()
  const { can, loading, context } = usePermissions()

  if (loading) {
    return <p className="loading">{t('common.loading')}</p>
  }

  const roles = roleFlagsFromPermissions(context?.permissions ?? (can('*') ? ['*'] : []))
  if (roles.isAdmin) {
    return children
  }

  return <Navigate to={destinationForRoles(roles, readStoredAudience(), null)} replace />
}
