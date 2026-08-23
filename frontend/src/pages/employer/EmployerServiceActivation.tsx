import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { activateEmployerService } from '../../api/auth'
import { useAuth } from '../../context/AuthContext'
import { translateApiError } from '../../i18n/apiErrors'
import { EmployerBlockedPage } from './EmployerBlockedPage'

export function EmployerServiceActivation({ onActivated }: { onActivated: () => void }) {
  const { t } = useTranslation()
  const { token, setOrganizationId } = useAuth()
  const [error, setError] = useState('')

  useEffect(() => {
    if (!token) return
    let cancelled = false
    void activateEmployerService(token)
      .then((result) => {
        if (cancelled) return
        if (!result.recruitment?.is_employer) {
          setError(t('auth.employer.activationFailed'))
          return
        }
        if (result.organization?.id) setOrganizationId(result.organization.id)
        onActivated()
      })
      .catch((requestError) => {
        if (!cancelled) setError(translateApiError(requestError) || t('auth.employer.activationFailed'))
      })
    return () => {
      cancelled = true
    }
  }, [token, onActivated, setOrganizationId, t])

  if (error) {
    return <EmployerBlockedPage message={error} />
  }

  return <p className="loading">{t('auth.employer.activating')}</p>
}
