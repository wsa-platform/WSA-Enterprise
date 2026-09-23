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

    test('shows platform admin routes from server platform permissions', () {
      client.setPermissionsForTest([
        'platform.access',
        'platform.users.view',
        'platform.roles.manage',
        'platform.audit.view',
      ], isPlatformAdministrator: true);

      final paths = SidebarNav.visibleDestinations(client).map((item) => item.path).toList();

      expect(paths, contains(AppRoutes.dashboard));
      expect(paths, contains(AppRoutes.users));
      expect(paths, contains(AppRoutes.roles));
      expect(paths, contains(AppRoutes.audit));
      expect(paths, isNot(contains(AppRoutes.agriculture)));
    });

    test('hides agriculture without a dedicated platform overlay permission', () {
      client.setPermissionsForTest(['platform.access', 'platform.reports.view'], isPlatformAdministrator: true);

      final paths = SidebarNav.visibleDestinations(client).map((item) => item.path).toList();

      expect(paths, contains(AppRoutes.dashboard));
      expect(paths, isNot(contains(AppRoutes.agriculture)));
      expect(paths, isNot(contains(AppRoutes.users)));
    });

    test('maps selected index against filtered destinations', () {
      client.setPermissionsForTest(['platform.users.view'], isPlatformAdministrator: true);

      final usersIndex = SidebarNav.indexForLocation(AppRoutes.users, client);
      expect(SidebarNav.pathForIndex(usersIndex, client), AppRoutes.users);
    });

    test('bottom nav keeps only primary destinations', () {
      client.setPermissionsForTest([
        'platform.access',
        'platform.users.view',
        'platform.organizations.view',
        'platform.settings.view',
      ], isPlatformAdministrator: true);

      final paths = SidebarNav.bottomDestinations(client).map((item) => item.path).toList();

      expect(paths, contains(AppRoutes.dashboard));
      expect(paths, contains(AppRoutes.users));
      expect(paths, contains(AppRoutes.organizations));
      expect(paths, contains(AppRoutes.settings));
      expect(paths, isNot(contains(AppRoutes.communications)));
      expect(paths.length, lessThanOrEqualTo(SidebarNav.bottomNavPaths.length));
    });

    test('shows job seekers for platform.jobs.view', () {
      client.setPermissionsForTest(['platform.jobs.view'], isPlatformAdministrator: true);

      final paths = SidebarNav.visibleDestinations(client).map((item) => item.path).toList();

      expect(paths, contains(AppRoutes.jobSeekers));
      expect(paths, isNot(contains(AppRoutes.users)));
    });

    test('organization permissions alone show no platform admin destinations', () {
      client.setPermissionsForTest(['*', 'access.manage', 'jobs.view']);

      final paths = SidebarNav.visibleDestinations(client).map((item) => item.path).toList();

      expect(paths, isEmpty);
    });
  });
}
