import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';
import 'package:wsa_admin/presentation/screens/shell/admin_shell.dart';

void main() {
  group('AdminShell responsive layout', () {
    late ApiClient client;
    late AuthController auth;
    late GoRouter router;

    setUp(() {
      client = ApiClient.inMemory();
      client.setPermissionsForTest(['access.manage', 'platform.view']);
      auth = AuthController(client);
      auth.status = AuthStatus.authenticated;
      auth.permissionsLoaded = true;
      router = GoRouter(
        initialLocation: AppRoutes.dashboard,
        routes: [
          ShellRoute(
            builder: (context, state, child) => AdminShell(
              auth: auth,
              client: client,
              child: child,
            ),
            routes: [
              GoRoute(
                path: AppRoutes.dashboard,
                builder: (context, state) => const Center(child: Text('content')),
              ),
            ],
          ),
        ],
      );
    });

    Widget buildApp(double width) {
      return Directionality(
        textDirection: TextDirection.rtl,
        child: MaterialApp.router(
          routerConfig: router,
          builder: (context, child) => MediaQuery(
            data: MediaQuery.of(context).copyWith(size: Size(width, 800)),
            child: child ?? const SizedBox.shrink(),
          ),
        ),
      );
    }

    testWidgets('shows desktop sidebar on wide screens', (tester) async {
      await tester.pumpWidget(buildApp(1200));
      await tester.pumpAndSettle();

      expect(find.text(Ar.navUsers), findsOneWidget);
      expect(find.byType(NavigationBar), findsNothing);
    });

    testWidgets('shows drawer menu and bottom nav on mobile', (tester) async {
      await tester.pumpWidget(buildApp(480));
      await tester.pumpAndSettle();

      expect(find.byIcon(Icons.menu), findsOneWidget);
      expect(find.byType(NavigationBar), findsOneWidget);
    });

    testWidgets('shows compact rail on tablet', (tester) async {
      await tester.pumpWidget(buildApp(720));
      await tester.pumpAndSettle();

      expect(find.byType(NavigationRail), findsOneWidget);
      expect(find.byType(NavigationBar), findsNothing);
    });
  });
}
