import { request } from './client'
import type { PaginatedResponse, TeamDetail, TeamSummary } from './types'

export const getTeams = (token: string, organizationId?: number, page = 1) =>
  request<PaginatedResponse<TeamSummary> | TeamSummary[]>(
    `/teams?page=${page}`,
    {},
    token,
    organizationId,
  )

export const getTeam = (token: string, teamId: number, organizationId?: number) =>
  request<TeamDetail>(`/teams/${teamId}`, {}, token, organizationId)

export const createTeam = (
  token: string,
  payload: { name: string; description?: string },
  organizationId?: number,
) =>
  request<TeamDetail>('/teams', {
    method: 'POST',
    body: JSON.stringify(payload),
  }, token, organizationId)

export const addTeamMember = (
  token: string,
  teamId: number,
  userId: number,
  organizationId?: number,
) =>
  request<TeamDetail>(`/teams/${teamId}/members`, {
    method: 'POST',
    body: JSON.stringify({ user_id: userId }),
  }, token, organizationId)

export const removeTeamMember = (
  token: string,
  teamId: number,
  userId: number,
  organizationId?: number,
) =>
  request<void>(`/teams/${teamId}/members/${userId}`, {
    method: 'DELETE',
  }, token, organizationId)
