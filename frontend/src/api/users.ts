import { request, unwrapEnvelope } from './client'
import type { AuditLogEntry, EnvelopeResponse, PaginatedResponse, Permission, Role, UserWithRoles } from './types'

export const getUsers = (token: string, organizationId?: number) =>
  request<UserWithRoles[] | PaginatedResponse<UserWithRoles>>('/users', {}, token, organizationId)

export const createUser = (
  token: string,
  payload: { name: string; email: string; password: string },
  organizationId?: number,
) =>
  request<UserWithRoles>('/users', { method: 'POST', body: JSON.stringify(payload) }, token, organizationId)

export const assignRole = (
  token: string,
  userId: number,
  roleId: number,
  organizationId?: number,
) =>
  request<UserWithRoles>(`/users/${userId}/roles`, {
    method: 'POST',
    body: JSON.stringify({ role_id: roleId }),
  }, token, organizationId)

export const getRoles = (token: string, organizationId?: number) =>
  request<Role[] | PaginatedResponse<Role>>('/roles', {}, token, organizationId)

export const getPermissions = (token: string, organizationId?: number) =>
  request<Permission[] | PaginatedResponse<Permission>>('/permissions', {}, token, organizationId)

export const getAuditLogs = async (
  token: string,
  organizationId?: number,
  query: Record<string, string | number> = {},
) => {
  const params = new URLSearchParams()
  Object.entries(query).forEach(([key, value]) => params.set(key, String(value)))

  const payload = await request<
    EnvelopeResponse<AuditLogEntry[]>
    | PaginatedResponse<AuditLogEntry>
    | { data: AuditLogEntry[]; meta: { current_page: number; last_page: number; total: number } }
  >(
    `/audit-logs?${params.toString()}`,
    {},
    token,
    organizationId,
  )

  if (payload && typeof payload === 'object' && 'meta' in payload && 'data' in payload) {
    const meta = payload.meta as { current_page: number; last_page: number; total: number }
    return {
      data: payload.data as AuditLogEntry[],
      current_page: meta.current_page,
      last_page: meta.last_page,
      total: meta.total,
    } satisfies PaginatedResponse<AuditLogEntry>
  }

  return unwrapEnvelope(payload)
}
