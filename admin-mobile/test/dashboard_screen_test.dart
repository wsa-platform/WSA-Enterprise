import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';
import 'package:wsa_admin/presentation/screens/dashboard/dashboard_screen.dart';

void main() {
  group('DashboardScreen', () {
    late ApiClient client;

    setUp(() {
      client = ApiClient.inMemory();
      client.setPermissionsForTest(['access.manage', 'platform.view']);
      client.setUserForTest({'name': 'أحمد', 'email': 'admin@example.com'});
      client.setOrganizationsForTest(
        [
          {'id': 1, 'name': 'مؤسسة الاختبار', 'role': 'مدير'},
        ],
        organizationId: 1,
      );
      DashboardScreen.debugLoader = null;
    });

    tearDown(() {
      DashboardScreen.debugLoader = null;
    });

    Widget buildScreen() {
      return Directionality(
        textDirection: TextDirection.rtl,
        child: MaterialApp(
          home: DashboardScreen(client: client),
        ),
      );
    }

    testWidgets('shows loading state initially', (tester) async {
      await tester.pumpWidget(buildScreen());

      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      expect(find.text(Ar.loading), findsOneWidget);
    });

    testWidgets('shows identity and organization context after load', (tester) async {
      DashboardScreen.debugLoader = (_) async => DashboardMetrics(
            organizations: '1',
            activeUsers: Ar.notAvailable,
            farms: Ar.notAvailable,
            crops: Ar.notAvailable,
            products: Ar.notAvailable,
            communications: Ar.notAvailable,
            reports: Ar.notAvailable,
            systemHealth: Ar.systemUnknown,
            alerts: Ar.notAvailable,
            jobSeekers: Ar.notAvailable,
            marketplace: Ar.notAvailable,
          );

      await tester.pumpWidget(buildScreen());
      await tester.pumpAndSettle();

      expect(find.text(Ar.navDashboard), findsOneWidget);
      expect(find.text('أحمد'), findsOneWidget);
      expect(find.text('admin@example.com'), findsOneWidget);
      expect(find.text('مؤسسة الاختبار'), findsOneWidget);
      expect(find.text('${Ar.dashboardRole}: مدير'), findsOneWidget);
    });

    testWidgets('shows empty state when all metrics are unavailable', (tester) async {
      DashboardScreen.debugLoader = (_) async => DashboardMetrics.empty();

      await tester.pumpWidget(buildScreen());
      await tester.pumpAndSettle();

      expect(find.text(Ar.emptyData), findsOneWidget);
    });

    testWidgets('shows metric cards when metrics are available', (tester) async {
      DashboardScreen.debugLoader = (_) async => DashboardMetrics(
            organizations: '3',
            activeUsers: Ar.notAvailable,
            farms: Ar.notAvailable,
            crops: Ar.notAvailable,
            products: Ar.notAvailable,
            communications: Ar.notAvailable,
            reports: Ar.notAvailable,
            systemHealth: Ar.systemUnknown,
            alerts: Ar.notAvailable,
            jobSeekers: Ar.notAvailable,
            marketplace: Ar.notAvailable,
          );

      await tester.pumpWidget(buildScreen());
      await tester.pumpAndSettle();

      expect(find.text(Ar.metricOrganizations), findsOneWidget);
      expect(find.text('3'), findsOneWidget);
      expect(find.text(Ar.emptyData), findsNothing);
    });
  });

  group('DashboardMetrics', () {
    test('isEmpty is true when all primary metrics are unavailable', () {
      final metrics = DashboardMetrics.empty();
      expect(metrics.isEmpty, isTrue);
    });

    test('isEmpty is false when any metric has data', () {
      final metrics = DashboardMetrics(
        organizations: '3',
        activeUsers: Ar.notAvailable,
        farms: Ar.notAvailable,
        crops: Ar.notAvailable,
        products: Ar.notAvailable,
        communications: Ar.notAvailable,
        reports: Ar.notAvailable,
        systemHealth: Ar.systemUnknown,
        alerts: Ar.notAvailable,
        jobSeekers: Ar.notAvailable,
        marketplace: Ar.notAvailable,
      );

      expect(metrics.isEmpty, isFalse);
    });
  });
}
