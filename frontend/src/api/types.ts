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

export type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
  query?: string
}

export type EnvelopeResponse<T> = {
  data: T
  meta?: Record<string, unknown>
}

export type AiProviderInfo = {
  provider: string
  decision_support_notice: string
  supported_request_types?: string[]
  async_dispatch?: boolean
}

export type AiRequestRecord = {
  id: number
  organization_id: number
  user_id: number | null
  request_type: string
  provider: string
  status: 'pending' | 'processing' | 'completed' | 'failed'
  latency_ms: number | null
  tokens_used: number | null
  error_message: string | null
  created_at: string
  updated_at: string
  output?: Record<string, unknown>
}

export type AuditLogEntry = {
  id: number
  action: string
  organization_id: number | null
  user_id: number | null
  auditable_type: string | null
  auditable_id: number | null
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
  user?: User
}

export type Role = { id: number; name: string; description?: string | null }
export type Permission = { id: number; name: string; description?: string | null }

export type UserWithRoles = User & { roles?: Role[] }
