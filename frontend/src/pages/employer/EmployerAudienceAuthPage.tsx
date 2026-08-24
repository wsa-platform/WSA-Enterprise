import { useTranslation } from 'react-i18next'
import { Navigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { useRecruitmentRole } from '../../hooks/useRecruitmentRole'
import {
  EMPLOYER_HOME,
  parseAudience,
  shouldOpenEmployerRegisterForm,
  shouldStayOnPlatformAuthPage,
} from '../../navigation/roleDestinations'
import { RoleHomeRedirect } from '../../components/RoleHomeRedirect'
import { LoginPage } from '../LoginPage'
import { RegisterPage } from '../RegisterPage'

export function EmployerAudienceAuthPage({ mode }: { mode: 'login' | 'register' }) {
  const { t } = useTranslation()
  const [params] = useSearchParams()
  const audience = parseAudience(params.get('audience'))
  const { token } = useAuth()
  const { role, loading } = useRecruitmentRole()

  if (audience !== 'employer') {
    if (token && !shouldStayOnPlatformAuthPage(audience, params.get('next'))) {
      return <RoleHomeRedirect />
    }
    return mode === 'login' ? <LoginPage /> : <RegisterPage />
  }

  if (token && loading) {
    return <p className="loading">{t('common.loading')}</p>
  }

  if (token && role?.is_employer) {
    return <Navigate to={EMPLOYER_HOME} replace />
  }

  if (mode === 'register' && shouldOpenEmployerRegisterForm(Boolean(role?.is_employer))) {
    return <RegisterPage />
  }

  return <LoginPage />
}
