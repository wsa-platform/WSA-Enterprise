import { useState } from 'react'
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

export function BillingPage() {
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [message, setMessage] = useState('')
  const [selectedPlan, setSelectedPlan] = useState('pro')
  const [timezone, setTimezone] = useState('UTC')
  const [supportEmail, setSupportEmail] = useState('')

  const { data: subscriptionPayload, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getBillingSubscription(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: usage } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getBillingUsage(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: plans } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getBillingPlans(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: invoicesPayload, reload: reloadInvoices } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getBillingInvoices(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: settings, reload: reloadSettings } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getOperationalSettings(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('billing.view')) {
    return <ErrorBanner message="You do not have permission to view billing." />
  }

  const subscription = subscriptionPayload?.subscription
  const invoices = Array.isArray(invoicesPayload) ? invoicesPayload : invoicesPayload?.data ?? []

  const handleAssignPlan = async () => {
    if (!token || !can('billing.manage')) return
    setMessage('')
    try {
      await assignBillingPlan(token, selectedPlan, organizationId ?? undefined)
      setMessage(`Plan changed to ${selectedPlan}.`)
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to change plan.')
    }
  }

  const handleCancel = async () => {
    if (!token || !can('billing.manage')) return
    setMessage('')
    try {
      await cancelBillingSubscription(token, organizationId ?? undefined, true)
      setMessage('Subscription scheduled for cancellation at period end.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to cancel subscription.')
    }
  }

  const handleReactivate = async () => {
    if (!token || !can('billing.manage')) return
    setMessage('')
    try {
      await reactivateBillingSubscription(token, organizationId ?? undefined)
      setMessage('Subscription reactivated.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to reactivate subscription.')
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
      setMessage('Operational settings updated.')
      await reloadSettings()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to update settings.')
    }
  }

  if (loading && !subscription) return <p className="loading">Loading billing…</p>
  if (error) return <ErrorBanner message={error} onRetry={reload} />

  const aiUsage = usage?.metrics['ai.requests']

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="Billing & operations"
      description="Subscription, usage, invoices, and operational settings. Live payment providers are not enabled in M5."
    />

    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">SUBSCRIPTION</p><h2>Current plan</h2></div></div>
      {subscription ? (
        <div className="detail-grid">
          <div><span>Plan</span><strong>{subscription.plan?.name ?? '—'}</strong></div>
          <div><span>Status</span><strong><StatusBadge status={subscription.status} /></strong></div>
          <div><span>Period</span><strong>{subscription.current_period_start ?? '—'} → {subscription.current_period_end ?? '—'}</strong></div>
          <div><span>Active</span><strong>{subscriptionPayload?.entitlements.subscription_active ? 'Yes' : 'No'}</strong></div>
        </div>
      ) : (
        <EmptyState title="No subscription" description="Assign a plan to begin billing tracking." />
      )}

      {can('billing.manage') && (
        <div className="record-form">
          <label>
            Change plan
            <select value={selectedPlan} onChange={(event) => setSelectedPlan(event.target.value)}>
              {(plans ?? []).map((plan: BillingPlan) => (
                <option key={plan.slug} value={plan.slug}>{plan.name}</option>
              ))}
            </select>
          </label>
          <div className="confirm-actions">
            <button type="button" onClick={() => void handleAssignPlan()}>Assign plan</button>
            <button type="button" className="refresh" onClick={() => void handleCancel()}>Cancel subscription</button>
            <button type="button" className="refresh" onClick={() => void handleReactivate()}>Reactivate</button>
          </div>
        </div>
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">USAGE</p><h2>Quota summary</h2></div></div>
      {aiUsage ? (
        <div className="detail-grid">
          <div><span>AI requests used</span><strong>{aiUsage.used}</strong></div>
          <div><span>Limit</span><strong>{aiUsage.limit ?? 'Unlimited'}</strong></div>
          <div><span>Remaining</span><strong>{aiUsage.remaining ?? '—'}</strong></div>
          <div><span>Usage %</span><strong>{aiUsage.usage_percent ?? 0}%</strong></div>
        </div>
      ) : (
        <p className="muted">Usage metrics unavailable.</p>
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">INVOICES</p><h2>Billing records</h2></div></div>
      {invoices.length === 0 ? (
        <EmptyState title="No invoices yet" description="Invoices appear when billing periods are recorded." />
      ) : (
        <DataTable
          rows={invoices as BillingInvoice[]}
          rowKey={(invoice) => invoice.id}
          columns={[
            { key: 'number', header: 'Number', render: (invoice) => invoice.number },
            { key: 'status', header: 'Status', render: (invoice) => <StatusBadge status={invoice.status} /> },
            { key: 'amount', header: 'Amount', render: (invoice) => `${(invoice.amount_cents / 100).toFixed(2)} ${invoice.currency}` },
            { key: 'due', header: 'Due', render: (invoice) => invoice.due_at ? new Date(invoice.due_at).toLocaleDateString() : '—' },
          ]}
        />
      )}
      {can('billing.manage') && (
        <button type="button" className="refresh" onClick={() => void reloadInvoices()}>Refresh invoices</button>
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">SETTINGS</p><h2>Operational settings</h2></div></div>
      <div className="record-form">
        <label>Timezone<input value={timezone} onChange={(event) => setTimezone(event.target.value)} /></label>
        <label>Support email<input value={supportEmail} onChange={(event) => setSupportEmail(event.target.value)} /></label>
        {can('billing.manage') && <button type="button" onClick={() => void handleSaveSettings()}>Save settings</button>}
      </div>
      {settings && Object.keys(settings).length > 0 && (
        <pre className="audit-detail">{JSON.stringify(settings, null, 2)}</pre>
      )}
    </section>
  </>
}
