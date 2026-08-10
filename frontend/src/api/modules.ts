import { request } from './client'
import type { PaginatedResponse } from './types'

export const getModule = (token: string, path: string, organizationId?: number) =>
  request<unknown[] | PaginatedResponse<unknown>>(path, {}, token, organizationId)

export const searchLibrary = (token: string, query: string, organizationId?: number) =>
  request<PaginatedResponse<Record<string, unknown>>>(
    `/library/search?q=${encodeURIComponent(query)}`,
    {},
    token,
    organizationId,
  )

export const createDiagnosisRequest = (
  token: string,
  payload: { reference: string; notes?: string; crop_type_id?: number },
  organizationId?: number,
) =>
  request<Record<string, unknown>>('/diagnosis/requests', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const createModuleRecord = (
  token: string,
  path: string,
  payload: Record<string, unknown>,
  organizationId?: number,
) =>
  request<Record<string, unknown>>(path.split('?')[0], {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const updateModuleRecord = (
  token: string,
  path: string,
  id: number,
  payload: Record<string, unknown>,
  organizationId?: number,
) =>
  request<Record<string, unknown>>(`${path.split('?')[0]}/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const deleteModuleRecord = (
  token: string,
  path: string,
  id: number,
  organizationId?: number,
) =>
  request<void>(`${path.split('?')[0]}/${id}`, { method: 'DELETE' }, token, organizationId)
