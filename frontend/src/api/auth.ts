import { request } from './client'
import type { User } from './types'

export async function login(email: string, password: string, audience?: 'job_seeker' | 'employer' | 'admin' | null) {
  return request<{ token: string; user: User; recruitment?: RecruitmentRole }>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password, device_name: 'wsa-web-dashboard', audience }),
  })
}

export async function register(payload: {
  name: string
  email: string
  password: string
  password_confirmation: string
  audience?: 'job_seeker' | 'employer' | 'admin' | null
}) {
  return request<{
    token: string
    user: User
    organization?: { id: number; name: string; slug: string }
  }>('/auth/register', {
    method: 'POST',
    body: JSON.stringify({ ...payload, device_name: 'wsa-web-dashboard' }),
  })
}

export type RecruitmentRole = {
  role: 'job_seeker' | 'employer' | 'conflict' | null
  is_job_seeker: boolean
  is_employer: boolean
}

export const getRecruitmentRole = (token: string) =>
  request<RecruitmentRole>('/auth/recruitment-role', {}, token)

export const logout = (token: string) => request<void>('/auth/logout', { method: 'POST' }, token)

export const getCurrentUser = (token: string) => request<User>('/user', {}, token)

export const updateAccountProfile = (token: string, payload: { name: string }) =>
  request<User>('/account/profile', { method: 'PATCH', body: JSON.stringify(payload) }, token)

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

export const getGoogleRedirect = () =>
  request<{ url: string; state: string } | { error: string }>('/auth/google/redirect')

export const completeGoogleCallback = (code: string, state: string) =>
  request<{ token: string; user: User; created?: boolean }>('/auth/google/callback', {
    method: 'POST',
    body: JSON.stringify({ code, state, device_name: 'wsa-web-dashboard' }),
  })

export const getFacebookRedirect = () =>
  request<{ url: string; state: string } | { error: string }>('/auth/facebook/redirect')

export const completeFacebookCallback = (code: string, state: string) =>
  request<{ token: string; user: User; created?: boolean }>('/auth/facebook/callback', {
    method: 'POST',
    body: JSON.stringify({ code, state, device_name: 'wsa-web-dashboard' }),
  })

export const sendPhoneOtp = (phone: string) =>
  request<{ sent?: boolean; message?: string }>('/auth/phone/send-otp', {
    method: 'POST',
    body: JSON.stringify({ phone }),
  })

export const verifyPhoneOtp = (payload: { phone: string; code: string; name?: string }) =>
  request<{ token: string; user: User }>('/auth/phone/verify-otp', {
    method: 'POST',
    body: JSON.stringify({ ...payload, device_name: 'wsa-web-dashboard' }),
  })

export const forgotPassword = (email: string) =>
  request<{ message: string }>('/auth/forgot-password', {
    method: 'POST',
    body: JSON.stringify({ email }),
  })

export const resetPassword = (payload: {
  token: string
  email: string
  password: string
  password_confirmation: string
}) =>
  request<{ message: string }>('/auth/reset-password', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
