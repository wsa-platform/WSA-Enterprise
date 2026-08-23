import { useCallback, useEffect, useState } from 'react'
import { getRecruitmentRole, type RecruitmentRole } from '../api/auth'
import { useAuth } from '../context/AuthContext'

export function useRecruitmentRole() {
  const { token } = useAuth()
  const [role, setRole] = useState<RecruitmentRole | null>(null)
  const [loading, setLoading] = useState(Boolean(token))
  const [revision, setRevision] = useState(0)

  const reload = useCallback(() => {
    setRevision((current) => current + 1)
  }, [])

  useEffect(() => {
    if (!token) {
      setRole(null)
      setLoading(false)
      return
    }
    let cancelled = false
    setLoading(true)
    void getRecruitmentRole(token)
      .then((next) => {
        if (!cancelled) setRole(next)
      })
      .catch(() => {
        if (!cancelled) setRole(null)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [token, revision])

  return { role, loading, reload }
}
