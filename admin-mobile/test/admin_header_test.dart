import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';
import 'package:wsa_admin/presentation/widgets/admin_header.dart';

void main() {
  group('AdminHeader RTL', () {
    late ApiClient client;
    late AuthController auth;
    late GoRouter router;

    setUp(() {
      client = ApiClient.inMemory();
      client.setPermissionsForTest(['platform.view', 'access.manage']);
      client.setUserForTest({'name': 'سارة', 'email': 'sara@example.com'});
      auth = AuthController(client);
    });

    Widget buildApp({required bool compact, required String route}) {
      router = GoRouter(
        initialLocation: route,
        routes: [
          GoRoute(
            path: AppRoutes.dashboard,
            builder: (context, state) => Scaffold(
              appBar: AdminHeader(auth: auth, client: client, compact: compact),
              body: const SizedBox.shrink(),
            ),
          ),
          GoRoute(
            path: AppRoutes.settings,
            builder: (context, state) => Scaffold(
              appBar: AdminHeader(auth: auth, client: client, compact: compact),
              body: const SizedBox.shrink(),
            ),
          ),
        ],
      );

      return Directionality(
        textDirection: TextDirection.rtl,
        child: MaterialApp.router(routerConfig: router),
      );
    }

    testWidgets('shows section title and notification/settings actions on desktop', (tester) async {
      await tester.pumpWidget(buildApp(compact: false, route: AppRoutes.dashboard));
      await tester.pumpAndSettle();

      expect(find.text(Ar.navDashboard), findsOneWidget);
      expect(find.byIcon(Icons.notifications_outlined), findsOneWidget);
      expect(find.byIcon(Icons.settings_outlined), findsOneWidget);
      expect(find.text('سارة'), findsOneWidget);
    });

    testWidgets('shows compact header with profile menu on mobile', (tester) async {
      await tester.pumpWidget(buildApp(compact: true, route: AppRoutes.dashboard));
      await tester.pumpAndSettle();

      expect(find.text(Ar.navDashboard), findsOneWidget);
      expect(find.byType(PopupMenuButton<String>), findsOneWidget);
    });
  });
}
