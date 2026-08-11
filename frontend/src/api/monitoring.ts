import { request } from './client'
import type { MonitoringHealth, MonitoringIncident, PaginatedResponse } from './types'

export const getMonitoringHealth = (token: string, organizationId?: number) =>
  request<MonitoringHealth>('/monitoring/health', {}, token, organizationId)

export const getMonitoringIncidents = (
  token: string,
  organizationId?: number,
  query: Record<string, string | number> = {},
) => {
  const params = new URLSearchParams()
  Object.entries(query).forEach(([key, value]) => params.set(key, String(value)))

  return request<MonitoringIncident[] | PaginatedResponse<MonitoringIncident>>(
    `/monitoring/incidents?${params.toString()}`,
    {},
    token,
    organizationId,
  )
}

export const resolveMonitoringIncident = (
  token: string,
  incidentId: number,
  note?: string,
  organizationId?: number,
) =>
  request<{ id: number; status: string }>(`/monitoring/incidents/${incidentId}/resolve`, {
    method: 'POST',
    body: JSON.stringify({ note: note ?? null }),
  }, token, organizationId)
