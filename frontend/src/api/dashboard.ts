import { request } from './client'
import type { Dashboard, WorkflowSummary } from './types'

export const getDashboard = (token: string, organizationId?: number) =>
  request<Dashboard>('/dashboard', {}, token, organizationId)

export const getWorkflowSummary = (token: string, organizationId?: number) =>
  request<WorkflowSummary>('/platform/workflow-summary', {}, token, organizationId)

export const getHealth = () => request<{ status: string }>('/health')
