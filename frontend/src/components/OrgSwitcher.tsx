import { useEffect, useState } from 'react'
import { getOrganizations, type Organization } from '../api'
import { useAuth } from '../context/AuthContext'

export function OrgSwitcher() {
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
      .catch((requestError) => setError(requestError instanceof Error ? requestError.message : 'Unable to load organizations.'))
  }, [token, organizationId, setOrganizationId])

  if (organizations.length === 0) return null

  return (
    <label className="org-switcher">
      <span>Organization</span>
      <select
        value={organizationId ?? organizations[0]?.id ?? ''}
        onChange={(event) => setOrganizationId(Number(event.target.value))}
        aria-label="Active organization"
      >
        {organizations.map((organization) => (
          <option key={organization.id} value={organization.id}>{organization.name}</option>
        ))}
      </select>
      {error && <span className="error">{error}</span>}
    </label>
  )
}
