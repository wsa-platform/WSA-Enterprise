import { request } from './client'
import type { Organization } from './types'

export const getOrganizations = (token: string) =>
  request<Organization[]>('/platform/organizations', {}, token)
