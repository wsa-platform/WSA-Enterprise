import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Navigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { parseAudience, resolvePostAuthPath } from '../navigation/roleDestinations'

export function RoleHomeRedirect({ next }: { next?: string | null }) {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const [params] = useSearchParams()
  const [to, setTo] = useState<string | null>(null)
  const audience = parseAudience(params.get('audience'))
  const requestedNext = next ?? params.get('next')

  useEffect(() => {
    if (!token) {
      setTo('/')
      return
    }

    let cancelled = false
    void resolvePostAuthPath({
      token,
      organizationId,
      audience,
      next: requestedNext,
    }).then((path) => {
      if (!cancelled) setTo(path)
    })

    return () => {
      cancelled = true
    }
  }, [token, organizationId, audience, requestedNext])

  if (!to) {
    return <p className="loading">{t('common.loading')}</p>
  }

  return <Navigate to={to} replace />
}
