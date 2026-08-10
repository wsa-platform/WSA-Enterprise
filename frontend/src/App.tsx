import { Navigate, Route, Routes, useNavigate } from 'react-router-dom'
import { useEffect, type ReactNode } from 'react'
import { AuthProvider, useAuth } from './context/AuthContext'
import { PermissionProvider } from './context/PermissionContext'
import { setUnauthorizedHandler } from './api'
import { AppShell } from './components/AppShell'
import { LoginPage } from './pages/LoginPage'
import { DashboardPage, useDashboardTitle } from './pages/DashboardPage'
import { ModulePage, cropCreateFields, cropTabs, farmCreateFields, farmTabs, soilCreateFields, soilTabs } from './pages/ModulePage'
import { BusinessPage } from './pages/BusinessPage'
import { DiagnosisPage } from './pages/DiagnosisPage'
import { TrainingPage } from './pages/TrainingPage'
import { LibraryPage } from './pages/LibraryPage'
import { AiWorkspacePage } from './pages/AiWorkspacePage'
import { AiRequestDetailPage } from './pages/AiRequestDetailPage'
import { BillingPage } from './pages/BillingPage'
import { OrganizationPage } from './pages/OrganizationPage'
import { SettingsPage } from './pages/SettingsPage'
import { NotificationsPage } from './pages/NotificationsPage'
import { UsersPage } from './pages/admin/UsersPage'
import { TeamsPage } from './pages/admin/TeamsPage'
import { TeamDetailPage } from './pages/admin/TeamDetailPage'
import { RolesPage } from './pages/admin/RolesPage'
import { AuditPage } from './pages/admin/AuditPage'
import './App.css'

function ProtectedShell() {
  const { token, organizationId } = useAuth()
  const workspaceName = useDashboardTitle(token, organizationId)

  if (!token) return <Navigate to="/login" replace />

  return (
    <PermissionProvider>
      <AppShell workspaceName={workspaceName} />
    </PermissionProvider>
  )
}

function SessionGuard({ children }: { children: ReactNode }) {
  const { clearSession } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    setUnauthorizedHandler(() => {
      clearSession()
      navigate('/login', { replace: true, state: { expired: true } })
    })
    return () => setUnauthorizedHandler(null)
  }, [clearSession, navigate])

  return children
}

function AppRoutes() {
  const { token } = useAuth()

  return (
    <Routes>
      <Route path="/login" element={token ? <Navigate to="/" replace /> : <LoginPage />} />
      <Route element={<ProtectedShell />}>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/organization" element={<OrganizationPage />} />
        <Route path="/billing" element={<BillingPage />} />
        <Route path="/settings" element={<SettingsPage />} />
        <Route path="/notifications" element={<NotificationsPage />} />
        <Route path="/admin/users" element={<UsersPage />} />
        <Route path="/admin/teams" element={<TeamsPage />} />
        <Route path="/admin/teams/:teamId" element={<TeamDetailPage />} />
        <Route path="/admin/roles" element={<RolesPage />} />
        <Route path="/admin/audit" element={<AuditPage />} />
        <Route path="/ai/workspace" element={<AiWorkspacePage />} />
        <Route path="/ai/requests/:requestId" element={<AiRequestDetailPage />} />
        <Route path="/ai" element={<Navigate to="/ai/workspace" replace />} />
        <Route path="/farms" element={<ModulePage eyebrow="AGRICULTURE" title="Farms" tabs={farmTabs} defaultPath="/farm/farms" createFields={farmCreateFields} />} />
        <Route path="/crops" element={<ModulePage eyebrow="AGRICULTURE" title="Crops" tabs={cropTabs} defaultPath="/crop/types" createFields={cropCreateFields} />} />
        <Route path="/soil" element={<ModulePage eyebrow="AGRICULTURE" title="Soil" tabs={soilTabs} defaultPath="/soil/analyses" createFields={soilCreateFields} />} />
        <Route path="/business" element={<BusinessPage />} />
        <Route path="/diagnosis" element={<DiagnosisPage />} />
        <Route path="/training" element={<TrainingPage />} />
        <Route path="/library" element={<LibraryPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}

export default function App() {
  return (
    <AuthProvider>
      <SessionGuard>
        <AppRoutes />
      </SessionGuard>
    </AuthProvider>
  )
}
