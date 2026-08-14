import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/presentation/widgets/sidebar_nav.dart';

void main() {
  group('SidebarNav RBAC', () {
    late ApiClient client;

    setUp(() {
      client = ApiClient.inMemory();
    });

    test('shows admin routes for access.manage', () {
      client.setPermissionsForTest(['access.manage']);

      final paths = SidebarNav.visibleDestinations(client).map((item) => item.path).toList();

      expect(paths, contains(AppRoutes.dashboard));
      expect(paths, contains(AppRoutes.users));
      expect(paths, contains(AppRoutes.roles));
      expect(paths, contains(AppRoutes.audit));
      expect(paths, isNot(contains(AppRoutes.agriculture)));
    });

    test('shows agriculture routes for farm.view only', () {
      client.setPermissionsForTest(['farm.view', 'platform.view']);

      final paths = SidebarNav.visibleDestinations(client).map((item) => item.path).toList();

      expect(paths, contains(AppRoutes.agriculture));
      expect(paths, isNot(contains(AppRoutes.users)));
    });

    test('maps selected index against filtered destinations', () {
      client.setPermissionsForTest(['access.manage']);

      final usersIndex = SidebarNav.indexForLocation(AppRoutes.users, client);
      expect(SidebarNav.pathForIndex(usersIndex, client), AppRoutes.users);
    });

    test('bottom nav keeps only primary destinations', () {
      client.setPermissionsForTest(['access.manage', 'platform.view']);

      final paths = SidebarNav.bottomDestinations(client).map((item) => item.path).toList();

      expect(paths, contains(AppRoutes.dashboard));
      expect(paths, contains(AppRoutes.settings));
      expect(paths.length, lessThanOrEqualTo(SidebarNav.bottomNavPaths.length));
    });
  });
}
