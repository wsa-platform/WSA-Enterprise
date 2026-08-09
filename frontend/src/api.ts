const apiUrl = import.meta.env.VITE_API_URL ?? '/api/v1'

export type User = { id: number; name: string; email: string }

export type Organization = { id: number; name: string; slug: string; role?: string | null }

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

export type WorkflowSummary = {
  organization_id: number
  farms: number
  diagnosis_requests: number
  training_courses: number
  library_items: number
  active_enrollments: number
}

export type PaginatedResponse<T> = { data: T[]; current_page: number; last_page: number; total: number; query?: string }

export type AiProviderInfo = {
  provider: string
  decision_support_notice: string
  supported_request_types?: string[]
}

function buildHeaders(token?: string, organizationId?: number, body?: unknown) {
  return {
    Accept: 'application/json',
    ...(body ? { 'Content-Type': 'application/json' } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(organizationId ? { 'X-Organization-Id': String(organizationId) } : {}),
  }
}

async function request<T>(path: string, options: RequestInit = {}, token?: string, organizationId?: number): Promise<T> {
  const body = options.body
  const response = await fetch(`${apiUrl}${path}`, {
    ...options,
    headers: {
      ...buildHeaders(token, organizationId, body),
      ...options.headers,
    },
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null)
    throw new Error(payload?.message ?? 'Unable to complete the request.')
  }

  return response.status === 204 ? (undefined as T) : response.json() as Promise<T>
}

export async function login(email: string, password: string) {
  return request<{ token: string; user: User }>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password, device_name: 'wsa-web-dashboard' }),
  })
}

export const logout = (token: string) => request<void>('/auth/logout', { method: 'POST' }, token)

export const getOrganizations = (token: string) => request<Organization[]>('/platform/organizations', {}, token)

export const getDashboard = (token: string, organizationId?: number) => request<Dashboard>('/dashboard', {}, token, organizationId)

export const getWorkflowSummary = (token: string, organizationId?: number) =>
  request<WorkflowSummary>('/platform/workflow-summary', {}, token, organizationId)

export const getModule = (token: string, path: string, organizationId?: number) =>
  request<unknown[] | PaginatedResponse<unknown>>(path, {}, token, organizationId)

export const getAiProvider = (token: string, organizationId?: number) =>
  request<AiProviderInfo>('/ai/provider', {}, token, organizationId)

export const searchLibrary = (token: string, query: string, organizationId?: number) =>
  request<PaginatedResponse<Record<string, unknown>>>(`/library/search?q=${encodeURIComponent(query)}`, {}, token, organizationId)

export const createDiagnosisRequest = (
  token: string,
  payload: { reference: string; notes?: string; crop_type_id?: number },
  organizationId?: number,
) => request<Record<string, unknown>>('/diagnosis/requests', {
  method: 'POST',
  body: JSON.stringify(payload),
}, token, organizationId)

export const createAiRequest = (
  token: string,
  payload: { request_type: string; input: Record<string, unknown> },
  organizationId?: number,
) => request<Record<string, unknown>>('/ai/requests', {
  method: 'POST',
  body: JSON.stringify(payload),
}, token, organizationId)

export function unwrapModuleRows(payload: unknown[] | PaginatedResponse<unknown>): unknown[] {
  return Array.isArray(payload) ? payload : payload.data ?? []
}
