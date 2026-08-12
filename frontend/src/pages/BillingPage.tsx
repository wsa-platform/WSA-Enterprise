import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  assignBillingPlan,
  cancelBillingSubscription,
  getBillingInvoices,
  getBillingPlans,
  getBillingSubscription,
  getBillingUsage,
  getOperationalSettings,
  reactivateBillingSubscription,
  updateOperationalSettings,
  type BillingInvoice,
  type BillingPlan,
} from '../api'
import { DataTable } from '../components/DataTable'
import { PageHeader } from '../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'
import { translateApiError } from '../i18n/apiErrors'
import i18n from '../i18n/config'

export function BillingPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [message, setMessage] = useState('')
  const [selectedPlan, setSelectedPlan] = useState('pro')
  const [timezone, setTimezone] = useState('UTC')
  const [supportEmail, setSupportEmail] = useState('')

  const { data: subscriptionPayload, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getBillingSubscription(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: usage } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getBillingUsage(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: plans } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getBillingPlans(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: invoicesPayload, reload: reloadInvoices } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getBillingInvoices(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: settings, reload: reloadSettings } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getOperationalSettings(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('billing.view')) {
    return <ErrorBanner message={t('billing.noPermission')} />
  }

  const subscription = subscriptionPayload?.subscription
  const invoices = Array.isArray(invoicesPayload) ? invoicesPayload : invoicesPayload?.data ?? []

  const handleAssignPlan = async () => {
    if (!token || !can('billing.manage')) return
    setMessage('')
    try {
      await assignBillingPlan(token, selectedPlan, organizationId ?? undefined)
      setMessage(t('billing.planChanged', { plan: selectedPlan }))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('billing.planChangeFailed'))
    }
  }

  const handleCancel = async () => {
    if (!token || !can('billing.manage')) return
    setMessage('')
    try {
      await cancelBillingSubscription(token, organizationId ?? undefined, true)
      setMessage(t('billing.cancelScheduled'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('billing.cancelFailed'))
    }
  }

  const handleReactivate = async () => {
    if (!token || !can('billing.manage')) return
    setMessage('')
    try {
      await reactivateBillingSubscription(token, organizationId ?? undefined)
      setMessage(t('billing.reactivated'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('billing.reactivateFailed'))
    }
  }

  const handleSaveSettings = async () => {
    if (!token || !can('billing.manage')) return
    setMessage('')
    try {
      await updateOperationalSettings(token, {
        'operations.timezone': timezone,
        'operations.support_email': supportEmail,
      }, organizationId ?? undefined)
      setMessage(t('billing.settingsUpdated'))
      await reloadSettings()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('billing.settingsUpdateFailed'))
    }
  }

  if (loading && !subscription) return <p className="loading">{t('billing.loading')}</p>
  if (error) return <ErrorBanner message={error} onRetry={reload} />

  const aiUsage = usage?.metrics['ai.requests']

  return <>
    <PageHeader
      eyebrow={t('common.enterprise')}
      title={t('billing.titleFull')}
      description={t('billing.descriptionFull')}
    />

    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.subscription')}</p><h2>{t('billing.currentPlan')}</h2></div></div>
      {subscription ? (
        <div className="detail-grid">
          <div><span>{t('billing.plan')}</span><strong>{subscription.plan?.name ?? '—'}</strong></div>
          <div><span>{t('billing.status')}</span><strong><StatusBadge status={subscription.status} /></strong></div>
          <div><span>{t('common.period')}</span><strong>{t('billing.periodRange', { start: subscription.current_period_start ?? '—', end: subscription.current_period_end ?? '—' })}</strong></div>
          <div><span>{t('billing.active')}</span><strong>{subscriptionPayload?.entitlements.subscription_active ? t('common.yes') : t('common.no')}</strong></div>
        </div>
      ) : (
        <EmptyState title={t('billing.noSubscriptionTitle')} description={t('billing.noSubscriptionDescription')} />
      )}

      {can('billing.manage') && (
        <div className="record-form">
          <label>
            {t('billing.changePlan')}
            <select value={selectedPlan} onChange={(event) => setSelectedPlan(event.target.value)}>
              {(plans ?? []).map((plan: BillingPlan) => (
                <option key={plan.slug} value={plan.slug}>{plan.name}</option>
              ))}
            </select>
          </label>
          <div className="confirm-actions">
            <button type="button" onClick={() => void handleAssignPlan()}>{t('billing.assignPlan')}</button>
            <button type="button" className="refresh" onClick={() => void handleCancel()}>{t('billing.cancelSubscription')}</button>
            <button type="button" className="refresh" onClick={() => void handleReactivate()}>{t('billing.reactivate')}</button>
          </div>
        </div>
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.usage')}</p><h2>{t('billing.quotaSummary')}</h2></div></div>
      {aiUsage ? (
        <div className="detail-grid">
          <div><span>{t('billing.aiRequestsUsed')}</span><strong>{aiUsage.used}</strong></div>
          <div><span>{t('billing.limit')}</span><strong>{aiUsage.limit ?? t('common.unlimited')}</strong></div>
          <div><span>{t('billing.remaining')}</span><strong>{aiUsage.remaining ?? '—'}</strong></div>
          <div><span>{t('billing.usagePercent')}</span><strong>{aiUsage.usage_percent ?? 0}%</strong></div>
        </div>
      ) : (
        <p className="muted">{t('billing.usageUnavailable')}</p>
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.invoices')}</p><h2>{t('billing.billingRecords')}</h2></div></div>
      {invoices.length === 0 ? (
        <EmptyState title={t('billing.noInvoicesTitle')} description={t('billing.noInvoicesDescription')} />
      ) : (
        <DataTable
          rows={invoices as BillingInvoice[]}
          rowKey={(invoice) => invoice.id}
          columns={[
            { key: 'number', header: t('common.number'), render: (invoice) => invoice.number },
            { key: 'status', header: t('billing.status'), render: (invoice) => <StatusBadge status={invoice.status} /> },
            { key: 'amount', header: t('common.amount'), render: (invoice) => `${(invoice.amount_cents / 100).toFixed(2)} ${invoice.currency}` },
            { key: 'due', header: t('common.due'), render: (invoice) => invoice.due_at ? new Date(invoice.due_at).toLocaleDateString() : '—' },
          ]}
        />
      )}
      {can('billing.manage') && (
        <button type="button" className="refresh" onClick={() => void reloadInvoices()}>{t('billing.refreshInvoices')}</button>
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.settings')}</p><h2>{t('billing.operationalSettings')}</h2></div></div>
      <div className="record-form">
        <label>{t('common.timezone')}<input value={timezone} onChange={(event) => setTimezone(event.target.value)} /></label>
        <label>{t('organization.supportEmail')}<input value={supportEmail} onChange={(event) => setSupportEmail(event.target.value)} /></label>
        {can('billing.manage') && <button type="button" onClick={() => void handleSaveSettings()}>{t('organization.saveSettings')}</button>}
      </div>
      {settings && Object.keys(settings).length > 0 && (
        <pre className="audit-detail">{JSON.stringify(settings, null, 2)}</pre>
      )}
    </section>
  </>
}
