import { request } from './client'
import type { User } from './types'

export async function login(email: string, password: string) {
  return request<{ token: string; user: User }>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password, device_name: 'wsa-web-dashboard' }),
  })
}

export const logout = (token: string) => request<void>('/auth/logout', { method: 'POST' }, token)

export const getCurrentUser = (token: string) => request<User>('/user', {}, token)
