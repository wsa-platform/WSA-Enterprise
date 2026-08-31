import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/auth/guards.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/presentation/screens/access_denied_screen.dart';
import 'package:wsa_admin/presentation/screens/agriculture/agriculture_screen.dart';
import 'package:wsa_admin/presentation/screens/ai/ai_screen.dart';
import 'package:wsa_admin/presentation/screens/audit/audit_screen.dart';
import 'package:wsa_admin/presentation/screens/communications/communications_screen.dart';
import 'package:wsa_admin/presentation/screens/content/content_screen.dart';
import 'package:wsa_admin/presentation/screens/dashboard/dashboard_screen.dart';
import 'package:wsa_admin/presentation/screens/login_screen.dart';
import 'package:wsa_admin/presentation/screens/job_seekers/job_seekers_screen.dart';
import 'package:wsa_admin/presentation/screens/marketplace/marketplace_admin_screen.dart';
import 'package:wsa_admin/presentation/screens/marketing/marketing_screen.dart';
import 'package:wsa_admin/presentation/screens/monitoring/monitoring_screen.dart';
import 'package:wsa_admin/presentation/screens/notifications/notifications_screen.dart';
import 'package:wsa_admin/presentation/screens/organizations/organizations_screen.dart';
import 'package:wsa_admin/presentation/screens/products/products_screen.dart';
import 'package:wsa_admin/presentation/screens/reports/reports_screen.dart';
import 'package:wsa_admin/presentation/screens/roles/roles_screen.dart';
import 'package:wsa_admin/presentation/screens/settings/settings_screen.dart';
import 'package:wsa_admin/presentation/screens/shell/admin_shell.dart';
import 'package:wsa_admin/presentation/screens/splash_screen.dart';
import 'package:wsa_admin/presentation/screens/users/users_screen.dart';

GoRouter createAppRouter({
  required AuthController auth,
  required ApiClient client,
}) {
  return GoRouter(
    initialLocation: AppRoutes.dashboard,
    refreshListenable: auth,
    redirect: (context, state) {
      return authGuard(auth, state) ??
          permissionGuard(auth, state) ??
          routePermissionGuard(client, state);
    },
    routes: [
      GoRoute(
        path: AppRoutes.login,
        builder: (context, state) => LoginScreen(auth: auth),
      ),
      GoRoute(
        path: AppRoutes.accessDenied,
        builder: (context, state) => AccessDeniedScreen(auth: auth),
      ),
      ShellRoute(
        builder: (context, state, child) => AdminShell(
          auth: auth,
          client: client,
          child: child,
        ),
        routes: [
          GoRoute(
            path: AppRoutes.dashboard,
            builder: (context, state) => DashboardScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.organizations,
            builder: (context, state) => OrganizationsScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.users,
            builder: (context, state) => UsersScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.roles,
            builder: (context, state) => RolesScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.agriculture,
            builder: (context, state) => AgricultureScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.content,
            builder: (context, state) => ContentScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.store,
            builder: (context, state) => ProductsScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.marketing,
            builder: (context, state) => MarketingScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.communications,
            builder: (context, state) => CommunicationsScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.ai,
            builder: (context, state) => AiScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.reports,
            builder: (context, state) => ReportsScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.notifications,
            builder: (context, state) => NotificationsScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.audit,
            builder: (context, state) => AuditScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.monitoring,
            builder: (context, state) => MonitoringScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.jobSeekers,
            builder: (context, state) => JobSeekersScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.marketplace,
            builder: (context, state) => MarketplaceAdminScreen(client: client),
          ),
          GoRoute(
            path: AppRoutes.settings,
            builder: (context, state) => SettingsScreen(client: client, auth: auth),
          ),
        ],
      ),
    ],
    errorBuilder: (context, state) => SplashScreen(message: state.error?.toString()),
  );
}
