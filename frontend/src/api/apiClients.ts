import { request } from './client'
import type { ApiClientRecord, PaginatedResponse } from './types'

export const getApiClients = (token: string, organizationId?: number) =>
  request<ApiClientRecord[] | PaginatedResponse<ApiClientRecord>>('/api-clients', {}, token, organizationId)

export const createApiClient = (
  token: string,
  payload: { name: string; scopes?: string[] },
  organizationId?: number,
) =>
  request<{ client: ApiClientRecord; client_secret: string; message: string }>(
    '/api-clients',
    { method: 'POST', body: JSON.stringify(payload) },
    token,
    organizationId,
  )

export const revokeApiClient = (token: string, clientId: number, organizationId?: number) =>
  request<ApiClientRecord>(`/api-clients/${clientId}/revoke`, { method: 'POST' }, token, organizationId)
