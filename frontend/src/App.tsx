import { Navigate, Route, Routes, useLocation, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { useEffect, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { AuthProvider, useAuth } from './context/AuthContext'
import { PermissionProvider } from './context/PermissionContext'
import { setUnauthorizedHandler } from './api'
import { AppShell } from './components/AppShell'
import { LoginPage } from './pages/LoginPage'
import { RegisterPage } from './pages/RegisterPage'
import { HomePage } from './pages/public/HomePage'
import { SectionPage } from './pages/public/SectionPage'
import { InfoPage } from './pages/public/InfoPage'
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
import { MonitoringPage } from './pages/admin/MonitoringPage'
import { AnalyticsPage } from './pages/admin/AnalyticsPage'
import { ApiClientsPage } from './pages/admin/ApiClientsPage'
import { MarketplaceListingPage } from './pages/marketplace/MarketplaceListingPage'
import { MarketplacePage } from './pages/marketplace/MarketplacePage'
import { MyListingsPage } from './pages/marketplace/MyListingsPage'
import { ListingEditorPage } from './pages/marketplace/ListingEditorPage'
import { AccountHomePage } from './pages/account/AccountHomePage'
import { AccountProfilePage } from './pages/account/AccountProfilePage'
import { CommunicationsPage } from './pages/communications/CommunicationsPage'
import { ForgotPasswordPage } from './pages/ForgotPasswordPage'
import { ResetPasswordPage } from './pages/ResetPasswordPage'
import { OAuthCallbackPage } from './pages/OAuthCallbackPage'
import { AcceptInvitationPage } from './pages/AcceptInvitationPage'
import { JobsMarketplacePage } from './pages/jobs/JobsMarketplacePage'
import { TalentProfilePage } from './pages/jobs/TalentProfilePage'
import { BeekeepingDashboardPage } from './pages/beekeeping/BeekeepingDashboardPage'
import { AiAssistantPage } from './pages/ai/AiAssistantPage'
import { AiVisionPage } from './pages/ai/AiVisionPage'
import { MarketingDashboardPage } from './pages/marketing/MarketingDashboardPage'
import { CampaignsPage } from './pages/marketing/CampaignsPage'
import { CampaignEditorPage } from './pages/marketing/CampaignEditorPage'
import { TemplatesPage } from './pages/marketing/TemplatesPage'
import { SegmentsPage } from './pages/marketing/SegmentsPage'
import { ConsentPage } from './pages/marketing/ConsentPage'

function safeReturnPath(value: string | null | undefined): string {
  return value && value.startsWith('/') && !value.startsWith('//') ? value : '/dashboard'
}

function AuthenticatedRedirect() {
  const [params] = useSearchParams()
  return <Navigate to={safeReturnPath(params.get('next'))} replace />
}

function ProtectedShell() {
  const { token, organizationId } = useAuth()
  const location = useLocation()
  const workspaceName = useDashboardTitle(token, organizationId)

  if (!token) {
    const next = `${location.pathname}${location.search}`
    return <Navigate to={`/login?next=${encodeURIComponent(safeReturnPath(next))}`} replace />
  }

  return (
    <PermissionProvider>
      <AppShell workspaceName={workspaceName} />
    </PermissionProvider>
  )
}

function SessionGuard({ children }: { children: ReactNode }) {
  const { clearSession } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  useEffect(() => {
    setUnauthorizedHandler(() => {
      clearSession()
      const next = `${location.pathname}${location.search}`
      navigate(`/login?next=${encodeURIComponent(safeReturnPath(next))}`, { replace: true, state: { expired: true } })
    })
    return () => setUnauthorizedHandler(null)
  }, [clearSession, navigate, location.pathname, location.search])

  return children
}

function FarmsPage() {
  const { t } = useTranslation()
  return <ModulePage eyebrow={t('modules.agriculture')} title={t('nav.farms')} tabs={farmTabs} defaultPath="/farm/farms" createFields={farmCreateFields} />
}

function CropsPage() {
  const { t } = useTranslation()
  return <ModulePage eyebrow={t('modules.agriculture')} title={t('nav.crops')} tabs={cropTabs} defaultPath="/crop/types" createFields={cropCreateFields} />
}

function SoilPage() {
  const { t } = useTranslation()
  return <ModulePage eyebrow={t('modules.agriculture')} title={t('nav.soil')} tabs={soilTabs} defaultPath="/soil/analyses" createFields={soilCreateFields} />
}

function SellerListingRedirect() {
  const { listingId } = useParams()
  return <Navigate to={`/account/products/${listingId ?? ''}`} replace />
}

function AppRoutes() {
  const { token } = useAuth()

  return (
    <Routes>
      <Route path="/" element={<HomePage />} />
      <Route path="/sections/:sectionId" element={<SectionPage />} />
      <Route path="/about" element={<InfoPage page="about" />} />
      <Route path="/privacy" element={<InfoPage page="privacy" />} />
      <Route path="/terms" element={<InfoPage page="terms" />} />
      <Route path="/contact" element={<InfoPage page="contact" />} />
      <Route path="/market" element={<MarketplacePage />} />
      <Route path="/market/:id" element={<MarketplaceListingPage />} />
      <Route path="/login" element={token ? <AuthenticatedRedirect /> : <LoginPage />} />
      <Route path="/register" element={token ? <AuthenticatedRedirect /> : <RegisterPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />
      <Route path="/auth/callback" element={<OAuthCallbackPage />} />
      <Route path="/browse" element={<Navigate to="/" replace />} />
      <Route path="/accept-invitation" element={token ? <Navigate to="/dashboard" replace /> : <AcceptInvitationPage />} />
      <Route element={<ProtectedShell />}>
        <Route path="/dashboard" element={<DashboardPage />} />
        <Route path="/organization" element={<OrganizationPage />} />
        <Route path="/billing" element={<BillingPage />} />
        <Route path="/settings" element={<SettingsPage />} />
        <Route path="/account" element={<AccountHomePage />} />
        <Route path="/account/profile" element={<AccountProfilePage />} />
        <Route path="/account/products" element={<MyListingsPage />} />
        <Route path="/account/products/new" element={<ListingEditorPage />} />
        <Route path="/account/products/:listingId" element={<ListingEditorPage />} />
        <Route path="/notifications" element={<NotificationsPage />} />
        <Route path="/admin/users" element={<UsersPage />} />
        <Route path="/admin/teams" element={<TeamsPage />} />
        <Route path="/admin/teams/:teamId" element={<TeamDetailPage />} />
        <Route path="/admin/roles" element={<RolesPage />} />
        <Route path="/admin/audit" element={<AuditPage />} />
        <Route path="/admin/monitoring" element={<MonitoringPage />} />
        <Route path="/admin/analytics" element={<AnalyticsPage />} />
        <Route path="/admin/api-clients" element={<ApiClientsPage />} />
        <Route path="/ai/workspace" element={<AiWorkspacePage />} />
        <Route path="/ai/assistant" element={<AiAssistantPage />} />
        <Route path="/ai/vision" element={<AiVisionPage />} />
        <Route path="/ai/requests/:requestId" element={<AiRequestDetailPage />} />
        <Route path="/ai" element={<Navigate to="/ai/workspace" replace />} />
        <Route path="/marketing" element={<MarketingDashboardPage />} />
        <Route path="/marketing/campaigns" element={<CampaignsPage />} />
        <Route path="/marketing/campaigns/:campaignId" element={<CampaignEditorPage />} />
        <Route path="/marketing/templates" element={<TemplatesPage />} />
        <Route path="/marketing/segments" element={<SegmentsPage />} />
        <Route path="/marketing/consent" element={<ConsentPage />} />
        <Route path="/jobs" element={<JobsMarketplacePage />} />
        <Route path="/jobs/talent" element={<TalentProfilePage />} />
        <Route path="/seller/listings" element={<Navigate to="/account/products" replace />} />
        <Route path="/seller/listings/new" element={<Navigate to="/account/products/new" replace />} />
        <Route path="/seller/listings/:listingId" element={<SellerListingRedirect />} />
        <Route path="/communications" element={<CommunicationsPage />} />
        <Route path="/beekeeping" element={<BeekeepingDashboardPage />} />
        <Route path="/farms" element={<FarmsPage />} />
        <Route path="/crops" element={<CropsPage />} />
        <Route path="/soil" element={<SoilPage />} />
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
