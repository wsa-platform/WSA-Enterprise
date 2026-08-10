import { request } from './client'
import type { BillingInvoice, BillingPlan, BillingSubscriptionResponse, BillingUsageSummary } from './types'

export const getBillingPlans = (token: string, organizationId?: number) =>
  request<BillingPlan[]>('/billing/plans', {}, token, organizationId)

export const getBillingSubscription = (token: string, organizationId?: number) =>
  request<BillingSubscriptionResponse>('/billing/subscription', {}, token, organizationId)

export const getBillingUsage = (token: string, organizationId?: number) =>
  request<BillingUsageSummary>('/billing/usage', {}, token, organizationId)

export const getBillingInvoices = (token: string, organizationId?: number, page = 1) =>
  request<{ data: BillingInvoice[]; current_page: number; last_page: number; total: number } | BillingInvoice[]>(
    `/billing/invoices?page=${page}`,
    {},
    token,
    organizationId,
  )

export const assignBillingPlan = (token: string, planSlug: string, organizationId?: number) =>
  request('/billing/subscription/plan', {
    method: 'POST',
    body: JSON.stringify({ plan_slug: planSlug }),
  }, token, organizationId)

export const cancelBillingSubscription = (token: string, organizationId?: number, atPeriodEnd = true) =>
  request('/billing/subscription/cancel', {
    method: 'POST',
    body: JSON.stringify({ at_period_end: atPeriodEnd }),
  }, token, organizationId)

export const reactivateBillingSubscription = (token: string, organizationId?: number) =>
  request('/billing/subscription/reactivate', { method: 'POST' }, token, organizationId)

export const getOperationalSettings = (token: string, organizationId?: number) =>
  request<Record<string, unknown>>('/billing/settings', {}, token, organizationId)

export const updateOperationalSettings = (
  token: string,
  settings: Record<string, unknown>,
  organizationId?: number,
) =>
  request<Record<string, unknown>>('/billing/settings', {
    method: 'PUT',
    body: JSON.stringify({ settings }),
  }, token, organizationId)
