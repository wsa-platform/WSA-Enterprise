import { request } from './client'
import type { AppNotification, PaginatedResponse } from './types'

export async function getNotifications(token: string, organizationId?: number, page = 1) {
  return request<PaginatedResponse<AppNotification> | AppNotification[]>(
    `/notifications?page=${page}`,
    {},
    token,
    organizationId,
  )
}

export const markNotificationRead = (
  token: string,
  notificationId: number,
  organizationId?: number,
) =>
  request<AppNotification>(`/notifications/${notificationId}/read`, {
    method: 'POST',
  }, token, organizationId)
