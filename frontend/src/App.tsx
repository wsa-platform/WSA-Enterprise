import { Navigate, Route, Routes, useLocation, useNavigate, useParams } from 'react-router-dom'
import { useEffect, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { AuthProvider, useAuth } from './context/AuthContext'
import { PermissionProvider, usePermissions } from './context/PermissionContext'
import { setUnauthorizedHandler } from './api'
import { AppShell } from './components/AppShell'
import { MarketplaceSellerShell } from './components/MarketplaceSellerShell'
import { HomePage } from './pages/public/HomePage'
import { CropCategoryPage } from './pages/public/CropCategoryPage'
import { PlantProductionPage } from './pages/public/PlantProductionPage'
import { SectionPage } from './pages/public/SectionPage'
import { InfoPage } from './pages/public/InfoPage'
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
import { MyJobApplicationPage } from './pages/jobs/MyJobApplicationPage'
import { MyApplicationsPage } from './pages/jobs/MyApplicationsPage'
import { BeekeepingDashboardPage } from './pages/beekeeping/BeekeepingDashboardPage'
import { AiAssistantPage } from './pages/ai/AiAssistantPage'
import { AiVisionPage } from './pages/ai/AiVisionPage'
import { MarketingDashboardPage } from './pages/marketing/MarketingDashboardPage'
import { CampaignsPage } from './pages/marketing/CampaignsPage'
import { CampaignEditorPage } from './pages/marketing/CampaignEditorPage'
import { TemplatesPage } from './pages/marketing/TemplatesPage'
import { SegmentsPage } from './pages/marketing/SegmentsPage'
import { ConsentPage } from './pages/marketing/ConsentPage'
import { loginPathForProtectedRoute, unknownRouteFallback } from './navigation/routeGuards'
import { ADMIN_HOME, EMPLOYER_ENTER, JOB_SEEKER_AUTH_ENTER, JOB_SEEKER_HOME, employerWorkspaceGate, roleFlagsFromPermissions } from './navigation/roleDestinations'
import { EmployerShell } from './components/EmployerShell'
import { JobSeekerShell } from './components/JobSeekerShell'
import { RoleHomeRedirect } from './components/RoleHomeRedirect'
import { JobsEnterPage } from './pages/jobs/JobsEnterPage'
import { EmployerEnterPage } from './pages/employer/EmployerEnterPage'
import { EmployerAudienceAuthPage } from './pages/employer/EmployerAudienceAuthPage'
import { EmployerBlockedPage } from './pages/employer/EmployerBlockedPage'
import { EmployerServiceActivation } from './pages/employer/EmployerServiceActivation'
import { EmployerWorkspacePage } from './pages/employer/EmployerWorkspacePage'
import { EmployerSearchPage } from './pages/employer/EmployerSearchPage'
import { EmployerCandidatePage } from './pages/employer/EmployerCandidatePage'
import { EmployerNotificationsPage } from './pages/employer/EmployerNotificationsPage'
import { EmployerAccountPage } from './pages/employer/EmployerAccountPage'
import { JobSeekerEnterPage } from './pages/jobs/JobSeekerEnterPage'
import { useRecruitmentRole } from './hooks/useRecruitmentRole'
import { useWorkspaceName } from './hooks/useWorkspaceName'

function AuthenticatedRedirect() {
  return <RoleHomeRedirect />
}

function UnknownRouteFallback() {
  const { token } = useAuth()
  if (token) return <RoleHomeRedirect />
  return <Navigate to={unknownRouteFallback(false)} replace />
}

function JobSeekerProtected() {
  const { t } = useTranslation()
  const { token } = useAuth()
  const { role, loading } = useRecruitmentRole()

  if (!token) {
    return <Navigate to={JOB_SEEKER_AUTH_ENTER} replace />
  }

  if (loading) {
    return <p className="loading">{t('common.loading')}</p>
  }

  if (role?.is_employer) {
    return <EmployerBlockedPage asJobSeeker />
  }

  return (
    <PermissionProvider>
      <JobSeekerShell />
    </PermissionProvider>
  )
}

function EmployerProtected() {
  const { t } = useTranslation()
  const { token } = useAuth()
  const { role, loading, reload } = useRecruitmentRole()

  if (!token) {
    return <Navigate to={EMPLOYER_ENTER} replace />
  }

  if (loading) {
    return <p className="loading">{t('common.loading')}</p>
  }

  const gate = employerWorkspaceGate(role)
  if (gate === 'blocked') {
    return <EmployerBlockedPage />
  }
  if (gate === 'activate') {
    return <EmployerServiceActivation onActivated={reload} />
  }

  return (
    <PermissionProvider>
      <EmployerShell />
    </PermissionProvider>
  )
}

function ProtectedShell() {
  const { token, organizationId } = useAuth()
  const location = useLocation()
  const workspaceName = useWorkspaceName(token, organizationId)

  if (!token) {
    const next = `${location.pathname}${location.search}`
    return <Navigate to={loginPathForProtectedRoute(next)} replace />
  }

  return (
    <PermissionProvider>
      <AppShell workspaceName={workspaceName} />
    </PermissionProvider>
  )
}

function LegacyDashboardRedirect() {
  const { token } = useAuth()
  if (!token) {
    return <Navigate to={loginPathForProtectedRoute(ADMIN_HOME, 'admin')} replace />
  }
  return <RoleHomeRedirect />
}

function SessionGuard({ children }: { children: ReactNode }) {
  const { clearSession } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  useEffect(() => {
    setUnauthorizedHandler(() => {
      clearSession()
      const next = `${location.pathname}${location.search}`
      navigate(loginPathForProtectedRoute(next), { replace: true, state: { expired: true } })
    })
    return () => setUnauthorizedHandler(null)
  }, [clearSession, navigate, location.pathname, location.search])

  return children
}

function RecruiterJobsGuard() {
  const { t } = useTranslation()
  const { loading, context } = usePermissions()
  if (loading) return <p className="loading">{t('common.loading')}</p>
  const roles = roleFlagsFromPermissions(context?.permissions)
  if (roles.isJobSeeker && !roles.isAdmin && !roles.isEmployer) {
    return <Navigate to={JOB_SEEKER_HOME} replace />
  }
  return <JobsMarketplacePage />
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

function AccountProductRedirect() {
  const { listingId } = useParams()
  return <Navigate to={`/seller/listings/${listingId ?? ''}`} replace />
}

function AppRoutes() {
  const { token } = useAuth()

  return (
    <Routes>
      <Route path="/" element={<HomePage />} />
      <Route path="/sections/:sectionId" element={<SectionPage />} />
      <Route path="/crops/:cropCategoryId" element={<CropCategoryPage />} />
      <Route path="/plant-production/:categoryId" element={<PlantProductionPage />} />
      <Route path="/about" element={<InfoPage page="about" />} />
      <Route path="/privacy" element={<InfoPage page="privacy" />} />
      <Route path="/terms" element={<InfoPage page="terms" />} />
      <Route path="/contact" element={<InfoPage page="contact" />} />
      <Route path="/market" element={<MarketplacePage />} />
      <Route path="/market/:id" element={<MarketplaceListingPage />} />
      <Route path="/jobs/enter" element={<JobsEnterPage />} />
      <Route path="/jobs/enter/seeker" element={<JobSeekerEnterPage />} />
      <Route path="/employer/enter" element={<EmployerEnterPage />} />
      <Route path="/login" element={<EmployerAudienceAuthPage mode="login" />} />
      <Route path="/register" element={<EmployerAudienceAuthPage mode="register" />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />
      <Route path="/auth/callback" element={<OAuthCallbackPage />} />
      <Route path="/browse" element={<Navigate to="/" replace />} />
      <Route path="/accept-invitation" element={token ? <AuthenticatedRedirect /> : <AcceptInvitationPage />} />
      <Route element={<JobSeekerProtected />}>
        <Route path="/jobs/application" element={<MyJobApplicationPage />} />
        <Route path="/jobs/applications" element={<MyApplicationsPage />} />
        <Route path="/jobs/talent" element={<TalentProfilePage />} />
      </Route>
      <Route element={<EmployerProtected />}>
        <Route path="/employer" element={<EmployerWorkspacePage />} />
        <Route path="/employer/search" element={<EmployerSearchPage />} />
        <Route path="/employer/candidates/:candidateId" element={<EmployerCandidatePage />} />
        <Route path="/employer/notifications" element={<EmployerNotificationsPage />} />
        <Route path="/employer/account" element={<EmployerAccountPage />} />
      </Route>
      <Route path="/dashboard" element={<LegacyDashboardRedirect />} />
      <Route element={<MarketplaceSellerShell />}>
        <Route path="/seller/listings" element={<MyListingsPage />} />
        <Route path="/seller/listings/new" element={<ListingEditorPage />} />
        <Route path="/seller/listings/:listingId" element={<ListingEditorPage />} />
      </Route>
      <Route path="/account/products" element={<Navigate to="/seller/listings" replace />} />
      <Route path="/account/products/new" element={<Navigate to="/seller/listings/new" replace />} />
      <Route path="/account/products/:listingId" element={<AccountProductRedirect />} />
      <Route element={<ProtectedShell />}>
        <Route path="/organization" element={<OrganizationPage />} />
        <Route path="/billing" element={<BillingPage />} />
        <Route path="/settings" element={<SettingsPage />} />
        <Route path="/account" element={<AccountHomePage />} />
        <Route path="/account/profile" element={<AccountProfilePage />} />
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
        <Route path="/jobs" element={<RecruiterJobsGuard />} />
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
      <Route path="*" element={<UnknownRouteFallback />} />
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
