import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { getMonitoringHealth, getMonitoringIncidents, resolveMonitoringIncident, unwrapModuleRows, type MonitoringIncident } from '../../api'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import i18n from '../../i18n/config'

function componentTone(status: string): string {
  if (status === 'healthy') return 'ok'
  if (status === 'failed') return 'failed'
  return 'degraded'
}

export function MonitoringPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [message, setMessage] = useState('')
  const [resolveTarget, setResolveTarget] = useState<MonitoringIncident | null>(null)
  const [resolveNote, setResolveNote] = useState('')

  const { data: health, loading: healthLoading, error: healthError, reload: reloadHealth } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getMonitoringHealth(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: incidentsPayload, loading: incidentsLoading, error: incidentsError, reload: reloadIncidents } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getMonitoringIncidents(token, organizationId ?? undefined, { per_page: 25 })
  }, [token, organizationId])

  if (!can('monitoring.view') && !can('access.manage')) {
    return <ErrorBanner message={t('monitoring.noPermission')} />
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
      setMessage(t('monitoring.incidentResolved'))
      await reloadIncidents()
      await reloadHealth()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('monitoring.resolveFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('common.operations')}
      title={t('monitoring.title')}
      description={t('monitoring.adminDescription')}
    />

    {error && <ErrorBanner message={error} onRetry={() => { void reloadHealth(); void reloadIncidents() }} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading">
        <div><p className="eyebrow">{t('common.health')}</p><h2>{t('monitoring.serviceStatus')}</h2></div>
        {health && (
          <StatusBadge status={health.status === 'healthy' ? 'ok' : 'degraded'} />
        )}
      </div>
      {healthLoading && !health ? <p className="loading">{t('monitoring.checkingServices')}</p> : health ? (
        <>
          <p className="muted">{t('monitoring.lastChecked', { time: new Date(health.checked_at).toLocaleString() })}</p>
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
        <EmptyState title={t('monitoring.healthUnavailableTitle')} description={t('monitoring.healthUnavailableDescription')} />
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.incidents')}</p><h2>{t('monitoring.monitoringEvents')}</h2></div></div>
      {incidentsLoading ? <p className="loading">{t('monitoring.loadingIncidents')}</p> : incidents.length === 0 ? (
        <EmptyState title={t('monitoring.emptyTitle')} description={t('monitoring.emptyIncidentsDescription')} />
      ) : (
        <DataTable
          rows={incidents}
          rowKey={(incident) => incident.id}
          columns={[
            { key: 'component', header: t('monitoring.component'), render: (incident) => incident.component },
            { key: 'status', header: t('common.status'), render: (incident) => (
              <StatusBadge status={incident.status === 'resolved' ? 'ok' : 'open'} />
            ) },
            { key: 'severity', header: t('monitoring.severity'), render: (incident) => incident.severity },
            { key: 'detected', header: t('monitoring.detected'), render: (incident) => new Date(incident.detected_at).toLocaleString() },
            {
              key: 'actions',
              header: t('common.actions'),
              render: (incident) => incident.status === 'resolved' || !canResolve ? '—' : (
                <button type="button" className="link-button inline" onClick={() => setResolveTarget(incident)}>{t('monitoring.resolve')}</button>
              ),
            },
          ]}
        />
      )}
    </section>

    {resolveTarget && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.resolve')}</p><h2>{resolveTarget.component}</h2></div></div>
        <label>
          {t('monitoring.noteOptional')}
          <input value={resolveNote} onChange={(event) => setResolveNote(event.target.value)} maxLength={500} />
        </label>
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setResolveTarget(null)}>{t('common.cancel')}</button>
          <button type="button" onClick={() => void handleResolve()}>{t('monitoring.markResolved')}</button>
        </div>
      </section>
    )}
  </>
}
