import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/routing/routes.dart';

/// Redirects unauthenticated users to login.
String? authGuard(AuthController auth, GoRouterState state) {
  if (auth.status == AuthStatus.unknown) {
    return null;
  }

  final location = state.matchedLocation;
  final isAuthRoute = location == AppRoutes.login || location == AppRoutes.accessDenied;

  if (!auth.isAuthenticated) {
    return isAuthRoute ? null : AppRoutes.login;
  }

  if (location == AppRoutes.login) {
    return AppRoutes.dashboard;
  }

  return null;
}

/// Redirects users without admin permissions to access denied.
String? permissionGuard(AuthController auth, GoRouterState state) {
  if (!auth.isAuthenticated || auth.status == AuthStatus.unknown) {
    return null;
  }

  if (!auth.permissionsLoaded) {
    return null;
  }

  final location = state.matchedLocation;
  if (location == AppRoutes.accessDenied || location == AppRoutes.login) {
    return null;
  }

  if (!auth.hasAdminAccess) {
    return AppRoutes.accessDenied;
  }

  return null;
}
