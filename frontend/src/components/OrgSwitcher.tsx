import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { getOrganizations, type Organization } from '../api'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'

export function OrgSwitcher() {
  const { t } = useTranslation()
  const { token, organizationId, setOrganizationId } = useAuth()
  const [organizations, setOrganizations] = useState<Organization[]>([])
  const [error, setError] = useState('')

  useEffect(() => {
    if (!token) return
    void getOrganizations(token)
      .then((rows) => {
        setOrganizations(rows)
        if (!organizationId && rows[0]) setOrganizationId(rows[0].id)
      })
      .catch((requestError) => setError(translateApiError(requestError) || t('orgSwitcher.loadFailed')))
  }, [token, organizationId, setOrganizationId, t])

  if (organizations.length === 0) return null

  return (
    <label className="org-switcher">
      <span>{t('orgSwitcher.label')}</span>
      <select
        value={organizationId ?? organizations[0]?.id ?? ''}
        onChange={(event) => setOrganizationId(Number(event.target.value))}
        aria-label={t('orgSwitcher.ariaLabel')}
      >
        {organizations.map((organization) => (
          <option key={organization.id} value={organization.id}>{organization.name}</option>
        ))}
      </select>
      {error && <span className="error">{error}</span>}
    </label>
  )
}
