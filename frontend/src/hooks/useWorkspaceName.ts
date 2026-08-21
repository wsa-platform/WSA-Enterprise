import { getDashboard } from '../api'
import i18n from '../i18n/config'
import { useAsyncData } from './useAsyncData'

export function useWorkspaceName(token: string, organizationId: number | null) {
  const { data } = useAsyncData(async () => {
    if (!token) return null
    return getDashboard(token, organizationId ?? undefined)
  }, [token, organizationId])
  return data?.organization.name ?? i18n.t('nav.myAccount')
}
