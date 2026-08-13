import { request } from './client'
import type { User } from './types'

export async function login(email: string, password: string) {
  return request<{ token: string; user: User }>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password, device_name: 'wsa-web-dashboard' }),
  })
}

export async function register(payload: {
  name: string
  email: string
  password: string
  password_confirmation: string
}) {
  return request<{
    token: string
    user: User
    organization: { id: number; name: string; slug: string }
  }>('/auth/register', {
    method: 'POST',
    body: JSON.stringify({ ...payload, device_name: 'wsa-web-dashboard' }),
  })
}

export const logout = (token: string) => request<void>('/auth/logout', { method: 'POST' }, token)

export const getCurrentUser = (token: string) => request<User>('/user', {}, token)

export type AuthSession = {
  id: number
  name: string
  last_used_at: string | null
  created_at: string
  is_current: boolean
}

export const getAuthSessions = (token: string) => request<AuthSession[]>('/auth/sessions', {}, token)

export const revokeAuthSession = (token: string, sessionId: number) =>
  request<void>(`/auth/sessions/${sessionId}`, { method: 'DELETE' }, token)

export const acceptInvitation = (payload: {
  token: string
  name?: string
  password: string
  device_name?: string
}) =>
  request<{ token: string; user: User; organization: { id: number; name: string; slug: string } }>(
    '/auth/accept-invitation',
    { method: 'POST', body: JSON.stringify({ ...payload, device_name: payload.device_name ?? 'wsa-web-dashboard' }) },
  )
