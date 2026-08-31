import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/l10n/strings.dart';

/// Arabic section titles keyed by route path.
abstract final class RouteTitles {
  static const _titles = {
    AppRoutes.dashboard: Ar.navDashboard,
    AppRoutes.organizations: Ar.navOrganizations,
    AppRoutes.users: Ar.navUsers,
    AppRoutes.roles: Ar.navRoles,
    AppRoutes.agriculture: Ar.navAgriculture,
    AppRoutes.content: Ar.navContent,
    AppRoutes.store: Ar.navProducts,
    AppRoutes.marketing: Ar.navMarketing,
    AppRoutes.communications: Ar.navCommunications,
    AppRoutes.ai: Ar.navAi,
    AppRoutes.reports: Ar.navReports,
    AppRoutes.notifications: Ar.navNotifications,
    AppRoutes.audit: Ar.navAudit,
    AppRoutes.monitoring: Ar.navMonitoring,
    AppRoutes.settings: Ar.navSettings,
    AppRoutes.jobSeekers: Ar.navJobSeekers,
    AppRoutes.marketplace: Ar.navMarketplace,
  };

  static String forLocation(String location) => _titles[location] ?? Ar.appTitle;
}
