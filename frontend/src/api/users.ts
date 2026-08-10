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

export const getAuditLogs = (
  token: string,
  organizationId?: number,
  query: Record<string, string | number> = {},
) => {
  const params = new URLSearchParams()
  Object.entries(query).forEach(([key, value]) => params.set(key, String(value)))

  return request<EnvelopeResponse<AuditLogEntry[]> | PaginatedResponse<AuditLogEntry>>(
    `/audit-logs?${params.toString()}`,
    {},
    token,
    organizationId,
  ).then(unwrapEnvelope)
}
