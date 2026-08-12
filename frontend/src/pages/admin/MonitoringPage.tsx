import { useState } from 'react'
import { getMonitoringHealth, getMonitoringIncidents, resolveMonitoringIncident, unwrapModuleRows, type MonitoringIncident } from '../../api'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'

function componentTone(status: string): string {
  if (status === 'healthy') return 'ok'
  if (status === 'failed') return 'failed'
  return 'degraded'
}

export function MonitoringPage() {
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [message, setMessage] = useState('')
  const [resolveTarget, setResolveTarget] = useState<MonitoringIncident | null>(null)
  const [resolveNote, setResolveNote] = useState('')

  const { data: health, loading: healthLoading, error: healthError, reload: reloadHealth } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getMonitoringHealth(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: incidentsPayload, loading: incidentsLoading, error: incidentsError, reload: reloadIncidents } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getMonitoringIncidents(token, organizationId ?? undefined, { per_page: 25 })
  }, [token, organizationId])

  if (!can('monitoring.view') && !can('access.manage')) {
    return <ErrorBanner message="You do not have permission to view monitoring." />
  }

  const canResolve = can('access.manage')

  const incidents = unwrapModuleRows(incidentsPayload ?? []) as MonitoringIncident[]
  const error = healthError || incidentsError

  const handleResolve = async () => {
    if (!token || !resolveTarget) return
    setMessage('')
    try {
      await resolveMonitoringIncident(token, resolveTarget.id, resolveNote || undefined, organizationId ?? undefined)
      setResolveTarget(null)
      setResolveNote('')
      setMessage('Incident resolved.')
      await reloadIncidents()
      await reloadHealth()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to resolve incident.')
    }
  }

  return <>
    <PageHeader
      eyebrow="OPERATIONS"
      title="Monitoring & health"
      description="Administrator view of platform health checks and operational incidents."
    />

    {error && <ErrorBanner message={error} onRetry={() => { void reloadHealth(); void reloadIncidents() }} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading">
        <div><p className="eyebrow">HEALTH</p><h2>Service status</h2></div>
        {health && (
          <StatusBadge status={health.status === 'healthy' ? 'ok' : 'degraded'} />
        )}
      </div>
      {healthLoading && !health ? <p className="loading">Checking services…</p> : health ? (
        <>
          <p className="muted">Last checked: {new Date(health.checked_at).toLocaleString()}</p>
          <div className="detail-grid">
            {Object.entries(health.components).map(([component, check]) => (
              <div key={component}>
                <span>{component}</span>
                <strong>
                  <StatusBadge status={componentTone(check.status)} />
                </strong>
                {check.message && <p className="muted">{check.message}</p>}
              </div>
            ))}
          </div>
        </>
      ) : (
        <EmptyState title="Health data unavailable" description="Unable to load health checks." />
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">INCIDENTS</p><h2>Monitoring events</h2></div></div>
      {incidentsLoading ? <p className="loading">Loading incidents…</p> : incidents.length === 0 ? (
        <EmptyState title="No incidents" description="Open incidents appear when health checks fail." />
      ) : (
        <DataTable
          rows={incidents}
          rowKey={(incident) => incident.id}
          columns={[
            { key: 'component', header: 'Component', render: (incident) => incident.component },
            { key: 'status', header: 'Status', render: (incident) => (
              <StatusBadge status={incident.status === 'resolved' ? 'ok' : 'open'} />
            ) },
            { key: 'severity', header: 'Severity', render: (incident) => incident.severity },
            { key: 'detected', header: 'Detected', render: (incident) => new Date(incident.detected_at).toLocaleString() },
            {
              key: 'actions',
              header: 'Actions',
              render: (incident) => incident.status === 'resolved' || !canResolve ? '—' : (
                <button type="button" className="link-button inline" onClick={() => setResolveTarget(incident)}>Resolve</button>
              ),
            },
          ]}
        />
      )}
    </section>

    {resolveTarget && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">RESOLVE</p><h2>{resolveTarget.component}</h2></div></div>
        <label>
          Note (optional)
          <input value={resolveNote} onChange={(event) => setResolveNote(event.target.value)} maxLength={500} />
        </label>
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setResolveTarget(null)}>Cancel</button>
          <button type="button" onClick={() => void handleResolve()}>Mark resolved</button>
        </div>
      </section>
    )}
  </>
}
