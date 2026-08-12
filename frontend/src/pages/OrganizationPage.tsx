import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
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
import { translateApiError } from '../i18n/apiErrors'
import i18n from '../i18n/config'
import { LANGUAGE_LABELS, SUPPORTED_LANGUAGES, type SupportedLanguage } from '../i18n/languages'

export function OrganizationPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can, context } = usePermissions()
  const [message, setMessage] = useState('')
  const [name, setName] = useState('')
  const [timezone, setTimezone] = useState('UTC')
  const [locale, setLocale] = useState<SupportedLanguage>('en')
  const [supportEmail, setSupportEmail] = useState('')
  const [requireMfa, setRequireMfa] = useState(false)
  const [emailEnabled, setEmailEnabled] = useState(true)

  const { data: organization, loading: orgLoading, error: orgError, reload: reloadOrg } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getOrganization(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: summary, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
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
    const storedLocale = read('operations.locale', 'en')
    setLocale(SUPPORTED_LANGUAGES.includes(storedLocale as SupportedLanguage) ? storedLocale as SupportedLanguage : 'en')
    setSupportEmail(read('operations.support_email'))
    setRequireMfa(read('security.require_mfa') === 'true' || read('security.require_mfa') === '1')
    setEmailEnabled(read('notifications.email_enabled') !== 'false' && read('notifications.email_enabled') !== '0')
  }, [settings])

  if (!can('platform.view')) {
    return <ErrorBanner message={t('organization.noPermission')} />
  }

  if ((loading || orgLoading) && !summary) return <SkeletonGrid count={2} />
  if (error || orgError) {
    return <ErrorBanner message={error ?? orgError ?? t('organization.loadFailed')} onRetry={() => { void reload(); void reloadOrg() }} />
  }
  if (!summary || !organization) return null

  const handleUpdateProfile = async () => {
    if (!token || !canManageAccess) return
    setMessage('')
    try {
      await updateOrganization(token, { name }, organizationId ?? undefined)
      setMessage(t('organization.profileUpdated'))
      await reloadOrg()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('organization.updateFailed'))
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
      setMessage(t('organization.settingsSaved'))
      await reloadSettings()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('organization.settingsFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('common.enterprise')}
      title={t('organization.title')}
      description={t('organization.description')}
    />

    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.profile')}</p><h2>{organization.name}</h2></div></div>
      <div className="detail-grid">
        <div><span>{t('common.slug')}</span><strong>{organization.slug}</strong></div>
        <div><span>{t('organization.membershipRole')}</span><strong>{context?.membership_role ?? organization.membership_role ?? t('common.member')}</strong></div>
        <div><span>{t('organization.assignedRole')}</span><strong>{context?.roles[0]?.name ?? '—'}</strong></div>
        <div><span>{t('organization.users')}</span><strong>{summary.users_count}</strong></div>
        <div><span>{t('organization.membershipStatus')}</span><strong>{organization.is_active === false ? t('common.inactive') : t('common.active')}</strong></div>
      </div>
    </section>

    {canManageAccess && (
      <>
        <section className="panel">
          <div className="panel-heading"><div><p className="eyebrow">{t('common.administration')}</p><h2>{t('organization.orgProfile')}</h2></div></div>
          <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleUpdateProfile() }}>
            <label>{t('common.name')}<input value={name} onChange={(event) => setName(event.target.value)} required /></label>
            <button type="submit">{t('organization.saveProfile')}</button>
          </form>
        </section>

        <section className="panel">
          <div className="panel-heading"><div><p className="eyebrow">{t('common.settings')}</p><h2>{t('organization.orgSettings')}</h2></div></div>
          <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleUpdateSettings() }}>
            <label>{t('common.timezone')}<input value={timezone} onChange={(event) => setTimezone(event.target.value)} /></label>
            <label>
              {t('common.locale')}
              <select value={locale} onChange={(event) => setLocale(event.target.value as SupportedLanguage)}>
                {SUPPORTED_LANGUAGES.map((lang) => (
                  <option key={lang} value={lang}>{LANGUAGE_LABELS[lang]}</option>
                ))}
              </select>
            </label>
            <label>{t('organization.supportEmail')}<input type="email" value={supportEmail} onChange={(event) => setSupportEmail(event.target.value)} /></label>
            <label className="checkbox-label">
              <input type="checkbox" checked={requireMfa} onChange={(event) => setRequireMfa(event.target.checked)} />
              {t('organization.requireMfa')}
            </label>
            <label className="checkbox-label">
              <input type="checkbox" checked={emailEnabled} onChange={(event) => setEmailEnabled(event.target.checked)} />
              {t('organization.emailNotifications')}
            </label>
            <button type="submit">{t('organization.saveSettings')}</button>
          </form>
        </section>
      </>
    )}
  </>
}
