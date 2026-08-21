import { createContext, useContext, useMemo, useState, type ReactNode } from 'react'
import type { Organization, User } from '../api'
import { clearStoredAuthNavigation } from '../navigation/roleDestinations'

type AuthContextValue = {
  token: string
  user: User | null
  organizationId: number | null
  setSession: (token: string, user: User) => void
  setOrganizationId: (organizationId: number | null) => void
  clearSession: () => void
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [token, setToken] = useState(() => localStorage.getItem('wsa_token') ?? '')
  const [user, setUser] = useState<User | null>(() => {
    const stored = localStorage.getItem('wsa_user')
    return stored ? JSON.parse(stored) as User : null
  })
  const [organizationId, setOrganizationIdState] = useState<number | null>(() => {
    const stored = localStorage.getItem('wsa_organization_id')
    return stored ? Number(stored) : null
  })

  const value = useMemo<AuthContextValue>(() => ({
    token,
    user,
    organizationId,
    setSession(nextToken, nextUser) {
      localStorage.setItem('wsa_token', nextToken)
      localStorage.setItem('wsa_user', JSON.stringify(nextUser))
      setToken(nextToken)
      setUser(nextUser)
    },
    setOrganizationId(nextOrganizationId) {
      if (nextOrganizationId) {
        localStorage.setItem('wsa_organization_id', String(nextOrganizationId))
      } else {
        localStorage.removeItem('wsa_organization_id')
      }
      setOrganizationIdState(nextOrganizationId)
    },
    clearSession() {
      localStorage.removeItem('wsa_token')
      localStorage.removeItem('wsa_user')
      localStorage.removeItem('wsa_organization_id')
      clearStoredAuthNavigation()
      setToken('')
      setUser(null)
      setOrganizationIdState(null)
    },
  }), [token, user, organizationId])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('useAuth must be used within AuthProvider')
  return context
}

export type { Organization }
