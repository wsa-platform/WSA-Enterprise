import { useEffect, useState } from 'react'
import {
  getAccessSummary,
  getOrganization,
  getOrganizationSettings,
  updateOrganization,
  updateOrganizationSettings,
} from '../api'
import { PageHeader } from '../components/PageHeader'
import { ErrorBanner, SkeletonGrid } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'

export function OrganizationPage() {
  const { token, organizationId } = useAuth()
  const { can, context } = usePermissions()
  const [message, setMessage] = useState('')
  const [name, setName] = useState('')
  const [timezone, setTimezone] = useState('UTC')
  const [locale, setLocale] = useState('en')
  const [supportEmail, setSupportEmail] = useState('')
  const [requireMfa, setRequireMfa] = useState(false)
  const [emailEnabled, setEmailEnabled] = useState(true)

  const { data: organization, loading: orgLoading, error: orgError, reload: reloadOrg } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getOrganization(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: summary, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getAccessSummary(token, organizationId ?? undefined)
  }, [token, organizationId])

  const canManageAccess = (context?.permissions ?? []).includes('access.manage')

  const { data: settings, reload: reloadSettings } = useAsyncData(async () => {
    if (!token || !canManageAccess) return null
    return getOrganizationSettings(token, organizationId ?? undefined)
  }, [token, organizationId, canManageAccess])

  useEffect(() => {
    if (organization) setName(organization.name)
  }, [organization])

  useEffect(() => {
    if (!settings) return
    const read = (key: string, fallback = '') => {
      const value = settings[key]
      if (value && typeof value === 'object' && 'value' in (value as object)) {
        return String((value as { value: unknown }).value)
      }
      return fallback
    }
    setTimezone(read('operations.timezone', 'UTC'))
    setLocale(read('operations.locale', 'en'))
    setSupportEmail(read('operations.support_email'))
    setRequireMfa(read('security.require_mfa') === 'true' || read('security.require_mfa') === '1')
    setEmailEnabled(read('notifications.email_enabled') !== 'false' && read('notifications.email_enabled') !== '0')
  }, [settings])

  if (!can('platform.view')) {
    return <ErrorBanner message="You do not have permission to view organization settings." />
  }

  if ((loading || orgLoading) && !summary) return <SkeletonGrid count={2} />
  if (error || orgError) {
    return <ErrorBanner message={error ?? orgError ?? 'Unable to load organization.'} onRetry={() => { void reload(); void reloadOrg() }} />
  }
  if (!summary || !organization) return null

  const handleUpdateProfile = async () => {
    if (!token || !canManageAccess) return
    setMessage('')
    try {
      await updateOrganization(token, { name }, organizationId ?? undefined)
      setMessage('Organization profile updated.')
      await reloadOrg()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to update organization.')
    }
  }

  const handleUpdateSettings = async () => {
    if (!token || !canManageAccess) return
    setMessage('')
    try {
      await updateOrganizationSettings(token, {
        'operations.timezone': timezone,
        'operations.locale': locale,
        'operations.support_email': supportEmail,
        'security.require_mfa': requireMfa,
        'notifications.email_enabled': emailEnabled,
      }, organizationId ?? undefined)
      setMessage('Organization settings saved.')
      await reloadSettings()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to save settings.')
    }
  }

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="Organization"
      description="Organization profile, membership, and administrative settings."
    />

    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">PROFILE</p><h2>{organization.name}</h2></div></div>
      <div className="detail-grid">
        <div><span>Slug</span><strong>{organization.slug}</strong></div>
        <div><span>Your membership role</span><strong>{context?.membership_role ?? organization.membership_role ?? 'member'}</strong></div>
        <div><span>Assigned RBAC role</span><strong>{context?.roles[0]?.name ?? '—'}</strong></div>
        <div><span>Users</span><strong>{summary.users_count}</strong></div>
        <div><span>Membership status</span><strong>{organization.is_active === false ? 'Inactive' : 'Active'}</strong></div>
      </div>
    </section>

    {canManageAccess && (
      <>
        <section className="panel">
          <div className="panel-heading"><div><p className="eyebrow">ADMINISTRATION</p><h2>Organization profile</h2></div></div>
          <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleUpdateProfile() }}>
            <label>Name<input value={name} onChange={(event) => setName(event.target.value)} required /></label>
            <button type="submit">Save profile</button>
          </form>
        </section>

        <section className="panel">
          <div className="panel-heading"><div><p className="eyebrow">SETTINGS</p><h2>Organization settings</h2></div></div>
          <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleUpdateSettings() }}>
            <label>Timezone<input value={timezone} onChange={(event) => setTimezone(event.target.value)} /></label>
            <label>Locale<input value={locale} onChange={(event) => setLocale(event.target.value)} /></label>
            <label>Support email<input type="email" value={supportEmail} onChange={(event) => setSupportEmail(event.target.value)} /></label>
            <label className="checkbox-label">
              <input type="checkbox" checked={requireMfa} onChange={(event) => setRequireMfa(event.target.checked)} />
              Require MFA (stored policy; enforcement deferred)
            </label>
            <label className="checkbox-label">
              <input type="checkbox" checked={emailEnabled} onChange={(event) => setEmailEnabled(event.target.checked)} />
              Email notifications enabled
            </label>
            <button type="submit">Save settings</button>
          </form>
        </section>
      </>
    )}
  </>
}
