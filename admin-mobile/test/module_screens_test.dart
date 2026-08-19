import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/screens/organizations/organizations_screen.dart';
import 'package:wsa_admin/presentation/screens/users/users_screen.dart';

void main() {
  group('UsersScreen', () {
    late ApiClient client;

    setUp(() {
      client = ApiClient.inMemory();
      client.setPermissionsForTest(['access.manage']);
      UsersScreen.debugLoader = null;
    });

    tearDown(() {
      UsersScreen.debugLoader = null;
    });

    Widget buildScreen(Widget child) {
      return Directionality(
        textDirection: TextDirection.rtl,
        child: MaterialApp(home: Scaffold(body: child)),
      );
    }

    testWidgets('shows loading then user metrics in RTL', (tester) async {
      UsersScreen.debugLoader = (_) async => UsersData(
            usersCount: '10',
            teamsCount: '2',
            rolesCount: '3',
            recentAudit: const [],
          );

      await tester.pumpWidget(buildScreen(UsersScreen(client: client)));
      expect(find.byType(CircularProgressIndicator), findsOneWidget);

      await tester.pumpAndSettle();

      expect(find.text(Ar.navUsers), findsOneWidget);
      expect(find.text('10'), findsOneWidget);
      expect(find.text(Ar.usersList), findsOneWidget);
      expect(find.text('10'), findsOneWidget);
    });

    testWidgets('shows user list section when audit data in loader', (tester) async {
      UsersScreen.debugLoader = (_) async => UsersData(
            usersCount: '5',
            teamsCount: Ar.notAvailable,
            rolesCount: Ar.notAvailable,
            recentAudit: [
              AuditRow(action: 'login', userName: 'أحمد', createdAt: '2026-01-01'),
            ],
          );

      await tester.pumpWidget(buildScreen(UsersScreen(client: client)));
      await tester.pumpAndSettle();

      expect(find.text(Ar.usersList), findsOneWidget);
      expect(find.text('5'), findsOneWidget);
    });
  });

  group('OrganizationsScreen', () {
    late ApiClient client;

    setUp(() {
      client = ApiClient.inMemory();
      client.setPermissionsForTest(['platform.view']);
      OrganizationsScreen.debugLoader = null;
    });

    tearDown(() {
      OrganizationsScreen.debugLoader = null;
    });

    testWidgets('shows organization list from loader', (tester) async {
      OrganizationsScreen.debugLoader = (_) async => [
            OrgRow(name: 'مؤسسة أ', slug: 'org-a', role: 'مدير'),
            OrgRow(name: 'مؤسسة ب', slug: 'org-b', role: 'عضو'),
          ];

      await tester.pumpWidget(
        Directionality(
          textDirection: TextDirection.rtl,
          child: MaterialApp(home: Scaffold(body: OrganizationsScreen(client: client))),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text(Ar.navOrganizations), findsOneWidget);
      expect(find.text('مؤسسة أ'), findsOneWidget);
      expect(find.text('org-a'), findsOneWidget);
      expect(find.text('2'), findsOneWidget);
    });

    testWidgets('filters organizations by search', (tester) async {
      OrganizationsScreen.debugLoader = (_) async => [
            OrgRow(name: 'مؤسسة أ', slug: 'org-a', role: 'مدير'),
            OrgRow(name: 'مؤسسة ب', slug: 'org-b', role: 'عضو'),
          ];

      await tester.pumpWidget(
        Directionality(
          textDirection: TextDirection.rtl,
          child: MaterialApp(home: Scaffold(body: OrganizationsScreen(client: client))),
        ),
      );
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField), 'org-b');
      await tester.pumpAndSettle();

      expect(find.text('مؤسسة ب'), findsOneWidget);
      expect(find.text('مؤسسة أ'), findsNothing);
    });
  });
}
