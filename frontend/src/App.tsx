import { Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider, useAuth } from './context/AuthContext'
import { AppShell } from './components/AppShell'
import { LoginPage } from './pages/LoginPage'
import { DashboardPage, useDashboardTitle } from './pages/DashboardPage'
import { ModulePage, cropTabs, farmTabs, soilTabs } from './pages/ModulePage'
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

function AppRoutes() {
  const { token } = useAuth()

  return (
    <Routes>
      <Route path="/login" element={token ? <Navigate to="/" replace /> : <LoginPage />} />
      <Route element={<ProtectedShell />}>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/farms" element={<ModulePage eyebrow="AGRICULTURE" title="Farms" tabs={farmTabs} defaultPath="/farm/farms" />} />
        <Route path="/crops" element={<ModulePage eyebrow="AGRICULTURE" title="Crops" tabs={cropTabs} defaultPath="/crop/types" />} />
        <Route path="/soil" element={<ModulePage eyebrow="AGRICULTURE" title="Soil" tabs={soilTabs} defaultPath="/soil/analyses" />} />
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
      <AppRoutes />
    </AuthProvider>
  )
}
