import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/auth/guards.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_client.dart';

void main() {
  group('authGuard', () {
    late ApiClient client;
    late AuthController auth;

    setUp(() {
      client = ApiClient.inMemory();
      auth = AuthController(client);
    });

    test('redirects unauthenticated users to login', () {
      auth.status = AuthStatus.unauthenticated;
      final state = _FakeGoRouterState(AppRoutes.dashboard);

      expect(authGuard(auth, state), AppRoutes.login);
    });

    test('allows login route when unauthenticated', () {
      auth.status = AuthStatus.unauthenticated;
      final state = _FakeGoRouterState(AppRoutes.login);

      expect(authGuard(auth, state), isNull);
    });

    test('redirects authenticated users away from login', () {
      auth.status = AuthStatus.authenticated;
      final state = _FakeGoRouterState(AppRoutes.login);

      expect(authGuard(auth, state), AppRoutes.dashboard);
    });
  });

  group('permissionGuard', () {
    late ApiClient client;
    late AuthController auth;

    setUp(() {
      client = ApiClient.inMemory();
      auth = AuthController(client);
      auth.status = AuthStatus.authenticated;
      auth.permissionsLoaded = true;
    });

    test('redirects to access denied without admin permissions', () {
      client.setPermissionsForTest(['platform.view']);
      final state = _FakeGoRouterState(AppRoutes.dashboard);

      expect(permissionGuard(auth, state), AppRoutes.accessDenied);
    });

    test('allows dashboard when access.manage is present', () {
      client.setPermissionsForTest(['access.manage']);
      final state = _FakeGoRouterState(AppRoutes.dashboard);

      expect(permissionGuard(auth, state), isNull);
    });

    test('allows dashboard when services.supervise is present', () {
      client.setPermissionsForTest(['services.supervise']);
      final state = _FakeGoRouterState(AppRoutes.dashboard);

      expect(permissionGuard(auth, state), isNull);
    });

    test('allows access denied route without redirect loop', () {
      client.setPermissionsForTest(['platform.view']);
      final state = _FakeGoRouterState(AppRoutes.accessDenied);

      expect(permissionGuard(auth, state), isNull);
    });
  });
}

class _FakeGoRouterState extends Fake implements GoRouterState {
  _FakeGoRouterState(this.location);

  final String location;

  @override
  String get matchedLocation => location;
}
