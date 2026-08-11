import { request, unwrapEnvelope } from './client'
import type { AuditLogEntry, EnvelopeResponse, PaginatedResponse, Permission, Role, UserWithRoles } from './types'

export const getUsers = (token: string, organizationId?: number) =>
  request<UserWithRoles[] | PaginatedResponse<UserWithRoles>>('/users', {}, token, organizationId)

export const getUser = (token: string, userId: number, organizationId?: number) =>
  request<UserWithRoles>(`/users/${userId}`, {}, token, organizationId)

export const createUser = (
  token: string,
  payload: { name: string; email: string; password: string },
  organizationId?: number,
) =>
  request<UserWithRoles>('/users', { method: 'POST', body: JSON.stringify(payload) }, token, organizationId)

export const updateUser = (
  token: string,
  userId: number,
  payload: { name?: string; email?: string; membership_role?: 'admin' | 'member'; is_active?: boolean },
  organizationId?: number,
) =>
  request<UserWithRoles>(`/users/${userId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const removeUser = (token: string, userId: number, organizationId?: number) =>
  request<{ message: string }>(`/users/${userId}`, { method: 'DELETE' }, token, organizationId)

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

export const unassignRole = (
  token: string,
  userId: number,
  roleId: number,
  organizationId?: number,
) =>
  request<UserWithRoles>(`/users/${userId}/roles/${roleId}`, { method: 'DELETE' }, token, organizationId)

export const getRoles = (token: string, organizationId?: number) =>
  request<Role[] | PaginatedResponse<Role>>('/roles', {}, token, organizationId)

export const getRole = (token: string, roleId: number, organizationId?: number) =>
  request<Role>(`/roles/${roleId}`, {}, token, organizationId)

export const createRole = (
  token: string,
  payload: { name: string; slug?: string; description?: string; permission_ids?: number[] },
  organizationId?: number,
) =>
  request<Role>('/roles', { method: 'POST', body: JSON.stringify(payload) }, token, organizationId)

export const updateRole = (
  token: string,
  roleId: number,
  payload: { name?: string; description?: string; permission_ids?: number[] },
  organizationId?: number,
) =>
  request<Role>(`/roles/${roleId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const deleteRole = (token: string, roleId: number, organizationId?: number) =>
  request<{ message: string }>(`/roles/${roleId}`, { method: 'DELETE' }, token, organizationId)

export const getPermissions = (token: string, organizationId?: number) =>
  request<Permission[] | PaginatedResponse<Permission>>('/permissions', {}, token, organizationId)

export const createPermission = (
  token: string,
  payload: { name: string; description?: string },
  organizationId?: number,
) =>
  request<Permission>('/permissions', { method: 'POST', body: JSON.stringify(payload) }, token, organizationId)

export const updatePermission = (
  token: string,
  permissionId: number,
  payload: { name?: string; description?: string },
  organizationId?: number,
) =>
  request<Permission>(`/permissions/${permissionId}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const deletePermission = (token: string, permissionId: number, organizationId?: number) =>
  request<{ message: string }>(`/permissions/${permissionId}`, { method: 'DELETE' }, token, organizationId)

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

export const SYSTEM_ROLE_SLUGS = ['owner', 'admin', 'manager', 'member', 'viewer'] as const

export function isSystemRole(role: Role): boolean {
  return role.slug != null && SYSTEM_ROLE_SLUGS.includes(role.slug as typeof SYSTEM_ROLE_SLUGS[number])
}
