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
  quota?: AiQuotaSummary
}

export type AiQuotaSummary = {
  enabled: boolean
  limit: number | null
  used: number
  remaining: number | null
  period_start: string
}

export type AiRequestRecord = {
  id: number
  organization_id: number
  user_id: number | null
  request_type: string
  provider: string
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'
  latency_ms: number | null
  tokens_used: number | null
  error_message: string | null
  cancelled_at?: string | null
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

export type Role = { id: number; name: string; slug?: string | null; description?: string | null; permissions?: Permission[] }
export type Permission = { id: number; name: string; description?: string | null }

export type UserWithRoles = User & {
  roles?: Role[]
  membership_role?: string
  is_active?: boolean
}

export type OrganizationProfile = {
  id: number
  name: string
  slug: string
  membership_role?: string
  is_active?: boolean
  members_count?: number
  created_at?: string
  updated_at?: string
}

export type MonitoringHealth = {
  status: 'healthy' | 'degraded'
  checked_at: string
  components: Record<string, { status: 'healthy' | 'failed'; message?: string | null }>
}

export type MonitoringIncident = {
  id: number
  component: string
  status: string
  severity: string
  lifecycle_stage: string
  detected_at: string
  resolved_at?: string | null
  details?: Record<string, unknown> | null
  analysis_summary?: string | null
}

export type MeContext = {
  user: User
  organization_id: number
  membership_role: string | null
  roles: Role[]
  permissions: string[]
}

export type AccessSummary = {
  organization_id: number
  users_count: number
  teams_count: number | null
  roles_count: number | null
  audit_events_24h: number | null
  ai_requests: {
    today: number
    pending: number
    processing: number
    completed: number
    failed: number
    cancelled: number
  } | null
  quota: AiQuotaSummary | null
  system: { api: string; queue: string }
  recent_audit?: AuditLogEntry[]
  recent_ai?: AiRequestRecord[]
}

export type TeamSummary = {
  id: number
  name: string
  slug: string
  description?: string | null
  members_count?: number
}

export type TeamDetail = TeamSummary & {
  members: Array<User & { pivot?: { role?: string } }>
}

export type AppNotification = {
  id: number
  title: string
  body?: string | null
  read_at?: string | null
  created_at: string
}

export type BillingPlan = {
  id: number
  slug: string
  name: string
  description?: string | null
  is_active: boolean
  features?: Array<{ feature_key: string; limit_value: number | null; limit_period: string }>
}

export type BillingSubscription = {
  id: number
  organization_id: number
  plan_id: number
  status: string
  current_period_start?: string | null
  current_period_end?: string | null
  cancelled_at?: string | null
  cancel_at_period_end?: boolean
  plan?: BillingPlan
}

export type BillingSubscriptionResponse = {
  subscription: BillingSubscription
  entitlements: {
    enabled: boolean
    subscription_active: boolean
    plan?: { slug: string; name: string } | null
    features?: Record<string, { limit: number | null; period: string }>
  }
}

export type BillingUsageSummary = {
  period_start: string
  metrics: Record<string, {
    used: number
    limit: number | null
    remaining: number | null
    usage_percent: number | null
  }>
  history: Array<{ period_start: string; quantity: number }>
}

export type BillingInvoice = {
  id: number
  organization_id: number
  number: string
  status: string
  amount_cents: number
  currency: string
  period_start?: string | null
  period_end?: string | null
  due_at?: string | null
  paid_at?: string | null
}
