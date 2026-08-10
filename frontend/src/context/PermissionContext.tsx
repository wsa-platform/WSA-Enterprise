import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { getMeContext, type MeContext } from '../api'
import { useAuth } from './AuthContext'

type PermissionContextValue = {
  loading: boolean
  error: string
  context: MeContext | null
  reload: () => Promise<void>
  can: (permission: string) => boolean
}

const PermissionContext = createContext<PermissionContextValue | null>(null)

export function PermissionProvider({ children }: { children: ReactNode }) {
  const { token, organizationId } = useAuth()
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [context, setContext] = useState<MeContext | null>(null)

  const reload = useCallback(async () => {
    if (!token || !organizationId) {
      setContext(null)
      setLoading(false)
      return
    }

    setLoading(true)
    setError('')
    try {
      const next = await getMeContext(token, organizationId)
      setContext(next)
    } catch (requestError) {
      setContext(null)
      setError(requestError instanceof Error ? requestError.message : 'Unable to load permissions.')
    } finally {
      setLoading(false)
    }
  }, [token, organizationId])

  useEffect(() => {
    void reload()
  }, [reload])

  const can = useCallback((permission: string) => {
    if (!context) return false
    return context.permissions.includes('*') || context.permissions.includes(permission)
  }, [context])

  const value = useMemo(() => ({
    loading,
    error,
    context,
    reload,
    can,
  }), [loading, error, context, reload, can])

  return <PermissionContext.Provider value={value}>{children}</PermissionContext.Provider>
}

export function usePermissions() {
  const context = useContext(PermissionContext)
  if (!context) throw new Error('usePermissions must be used within PermissionProvider')
  return context
}

export function usePermission(permission: string) {
  const { can, loading } = usePermissions()
  return { allowed: can(permission), loading }
}
