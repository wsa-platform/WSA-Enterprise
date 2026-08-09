import { Navigate, Route, Routes, useNavigate } from 'react-router-dom'
import { useEffect, type ReactNode } from 'react'
import { AuthProvider, useAuth } from './context/AuthContext'
import { setUnauthorizedHandler } from './api'
import { AppShell } from './components/AppShell'
import { LoginPage } from './pages/LoginPage'
import { DashboardPage, useDashboardTitle } from './pages/DashboardPage'
import { ModulePage, cropCreateFields, cropTabs, farmCreateFields, farmTabs, soilCreateFields, soilTabs } from './pages/ModulePage'
import { BusinessPage } from './pages/BusinessPage'
import { DiagnosisPage } from './pages/DiagnosisPage'
import { TrainingPage } from './pages/TrainingPage'
import { LibraryPage } from './pages/LibraryPage'
import { AiPage } from './pages/AiPage'
import './App.css'

function ProtectedShell() {
  const { token, organizationId } = useAuth()
  const workspaceName = useDashboardTitle(token, organizationId)

  if (!token) return <Navigate to="/login" replace />

  return <AppShell workspaceName={workspaceName} />
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
        <Route path="/farms" element={<ModulePage eyebrow="AGRICULTURE" title="Farms" tabs={farmTabs} defaultPath="/farm/farms" createFields={farmCreateFields} />} />
        <Route path="/crops" element={<ModulePage eyebrow="AGRICULTURE" title="Crops" tabs={cropTabs} defaultPath="/crop/types" createFields={cropCreateFields} />} />
        <Route path="/soil" element={<ModulePage eyebrow="AGRICULTURE" title="Soil" tabs={soilTabs} defaultPath="/soil/analyses" createFields={soilCreateFields} />} />
        <Route path="/business" element={<BusinessPage />} />
        <Route path="/diagnosis" element={<DiagnosisPage />} />
        <Route path="/training" element={<TrainingPage />} />
        <Route path="/library" element={<LibraryPage />} />
        <Route path="/ai" element={<AiPage />} />
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
