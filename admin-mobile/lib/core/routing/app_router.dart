import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/auth/guards.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/presentation/screens/access_denied_screen.dart';
import 'package:wsa_admin/presentation/screens/dashboard/dashboard_screen.dart';
import 'package:wsa_admin/presentation/screens/login_screen.dart';
import 'package:wsa_admin/presentation/screens/placeholders/placeholder_screen.dart';
import 'package:wsa_admin/presentation/screens/shell/admin_shell.dart';
import 'package:wsa_admin/presentation/screens/splash_screen.dart';

GoRouter createAppRouter({
  required AuthController auth,
  required ApiClient client,
}) {
  return GoRouter(
    initialLocation: AppRoutes.dashboard,
    refreshListenable: auth,
    redirect: (context, state) {
      return authGuard(auth, state) ?? permissionGuard(auth, state);
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
            builder: (context, state) => const PlaceholderScreen(titleKey: 'organizations'),
          ),
          GoRoute(
            path: AppRoutes.users,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'users'),
          ),
          GoRoute(
            path: AppRoutes.roles,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'roles'),
          ),
          GoRoute(
            path: AppRoutes.agriculture,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'agriculture'),
          ),
          GoRoute(
            path: AppRoutes.content,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'content'),
          ),
          GoRoute(
            path: AppRoutes.store,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'store'),
          ),
          GoRoute(
            path: AppRoutes.marketing,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'marketing'),
          ),
          GoRoute(
            path: AppRoutes.communications,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'communications'),
          ),
          GoRoute(
            path: AppRoutes.ai,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'ai'),
          ),
          GoRoute(
            path: AppRoutes.reports,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'reports'),
          ),
          GoRoute(
            path: AppRoutes.notifications,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'notifications'),
          ),
          GoRoute(
            path: AppRoutes.audit,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'audit'),
          ),
          GoRoute(
            path: AppRoutes.monitoring,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'monitoring'),
          ),
          GoRoute(
            path: AppRoutes.settings,
            builder: (context, state) => const PlaceholderScreen(titleKey: 'settings'),
          ),
        ],
      ),
    ],
    errorBuilder: (context, state) => SplashScreen(message: state.error?.toString()),
  );
}
