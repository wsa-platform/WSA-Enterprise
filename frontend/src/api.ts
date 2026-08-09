const apiUrl = import.meta.env.VITE_API_URL ?? '/api/v1'

export type User = { id: number; name: string; email: string }

export type Dashboard = {
  organization: { id: number; name: string; slug: string }
  metrics: {
    active_projects: number
    open_tasks: number
    completed_tasks: number
    overdue_tasks: number
  }
  projects: Array<{
    id: number
    code: string
    name: string
    status: string
    tasks_count: number
    completed_tasks_count: number
  }>
  recent_tasks: Array<{
    id: number
    title: string
    status: string
    priority: string
    due_at: string | null
    project: { code: string; name: string }
    assignee: { name: string } | null
  }>
}

async function request<T>(path: string, options: RequestInit = {}, token?: string): Promise<T> {
  const response = await fetch(`${apiUrl}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  })

  if (!response.ok) {
    const body = await response.json().catch(() => null)
    throw new Error(body?.message ?? 'Unable to complete the request.')
  }

  return response.status === 204 ? (undefined as T) : response.json() as Promise<T>
}

export async function login(email: string, password: string) {
  return request<{ token: string; user: User }>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password, device_name: 'wsa-web-dashboard' }),
  })
}

export const getDashboard = (token: string) => request<Dashboard>('/dashboard', {}, token)

export const updateTaskStatus = (token: string, taskId: number, status: string) =>
  request(`/tasks/${taskId}/status`, {
    method: 'PATCH',
    body: JSON.stringify({ status }),
  }, token)

export const logout = (token: string) => request<void>('/auth/logout', { method: 'POST' }, token)

export const getModule = (token: string, path: string) => request<unknown[] | PaginatedResponse<unknown>>(path, {}, token)
export const getReport = (token: string) => request<Record<string, number>>('/reports/summary', {}, token)

export type PaginatedResponse<T> = { data: T[]; current_page: number; last_page: number; total: number }

export type AiProviderInfo = { provider: string; decision_support_notice: string }

export const getAiProvider = (token: string) => request<AiProviderInfo>('/ai/provider', {}, token)

export const searchLibrary = (token: string, query: string) =>
  request<PaginatedResponse<Record<string, unknown>>>(`/library/search?q=${encodeURIComponent(query)}`, {}, token)

export function unwrapModuleRows(payload: unknown[] | PaginatedResponse<unknown>): unknown[] {
  return Array.isArray(payload) ? payload : payload.data ?? []
}
