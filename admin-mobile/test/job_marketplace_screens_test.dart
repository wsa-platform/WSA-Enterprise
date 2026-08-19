import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/data/models/paginated_response.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/screens/job_seekers/job_seekers_screen.dart';
import 'package:wsa_admin/presentation/screens/marketplace/marketplace_admin_screen.dart';

void main() {
  group('JobSeekersScreen', () {
    late ApiClient client;

    setUp(() {
      client = ApiClient.inMemory();
      client.setPermissionsForTest(['jobs.view', 'jobs.status']);
      JobSeekersScreen.debugLoader = null;
      JobSeekersScreen.debugListFetch = null;
      JobSeekersScreen.debugProfileLoader = null;
      JobSeekersScreen.debugNotesLoader = null;
      JobSeekersScreen.debugHistoryLoader = null;
    });

    tearDown(() {
      JobSeekersScreen.debugLoader = null;
      JobSeekersScreen.debugListFetch = null;
      JobSeekersScreen.debugProfileLoader = null;
      JobSeekersScreen.debugNotesLoader = null;
      JobSeekersScreen.debugHistoryLoader = null;
    });

    testWidgets('shows recruitment metrics in RTL', (tester) async {
      JobSeekersScreen.debugLoader = (_) async => JobSeekersSummary(
            total: '5',
            active: '4',
            underReview: '2',
            hired: '1',
          );
      JobSeekersScreen.debugListFetch = (page, perPage) async => PaginatedResponse(
            data: [
              {
                'id': 1,
                'full_name': 'فاطمة',
                'specialization': 'زراعة',
                'city': 'الرياض',
                'recruitment_status': 'new',
                'completeness_percent': 40,
              },
            ],
            currentPage: 1,
            lastPage: 1,
            total: 1,
            perPage: perPage,
          );

      await tester.pumpWidget(
        Directionality(
          textDirection: TextDirection.rtl,
          child: MaterialApp(home: Scaffold(body: JobSeekersScreen(client: client))),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text(Ar.navJobSeekers), findsOneWidget);
      expect(find.text('5'), findsOneWidget);
      expect(find.text('فاطمة'), findsOneWidget);
      expect(find.text(Ar.jobSeekerViewProfile), findsOneWidget);
      expect(find.text(Ar.jobSeekerUpdateStatus), findsOneWidget);
    });

    testWidgets('hides status action without jobs.status and omits private fields', (tester) async {
      client.setPermissionsForTest(['jobs.view']);
      JobSeekersScreen.debugLoader = (_) async => JobSeekersSummary(
            total: '1',
            active: '1',
            underReview: '0',
            hired: '0',
          );
      JobSeekersScreen.debugListFetch = (page, perPage) async => PaginatedResponse(
            data: [
              {
                'id': 9,
                'full_name': 'محمد',
                'specialization': 'ري',
                'city': 'جدة',
                'recruitment_status': 'qualified',
                'completeness_percent': 55,
              },
            ],
            currentPage: 1,
            lastPage: 1,
            total: 1,
            perPage: perPage,
          );
      JobSeekersScreen.debugProfileLoader = (_) async => {
            'id': 9,
            'full_name': 'محمد',
            'specialization': 'ري',
            'city': 'جدة',
            'recruitment_status': 'qualified',
            'completeness_percent': 55,
          };
      JobSeekersScreen.debugHistoryLoader = (_) async => [];

      await tester.pumpWidget(
        Directionality(
          textDirection: TextDirection.rtl,
          child: MaterialApp(home: Scaffold(body: JobSeekersScreen(client: client))),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text(Ar.jobSeekerUpdateStatus), findsNothing);
      expect(find.text('محمد'), findsOneWidget);
      expect(find.textContaining('hidden@wsa.test'), findsNothing);
    });

    testWidgets('detail sheet omits private fields without jobs.private_data', (tester) async {
      client.setPermissionsForTest(['jobs.view']);
      await tester.pumpWidget(
        Directionality(
          textDirection: TextDirection.rtl,
          child: MaterialApp(
            home: Scaffold(
              body: JobSeekerDetailSheet(
                client: client,
                profile: {
                  'id': 9,
                  'full_name': 'محمد',
                  'specialization': 'ري',
                  'city': 'جدة',
                  'recruitment_status': 'qualified',
                  'completeness_percent': 55,
                },
                notes: const [
                  {'body': 'secret note'},
                ],
                history: const [
                  {'status': 'new'},
                ],
              ),
            ),
          ),
        ),
      );

      expect(find.text('محمد'), findsOneWidget);
      expect(find.textContaining('55'), findsOneWidget);
      expect(find.textContaining('hidden@wsa.test'), findsNothing);
      expect(find.textContaining('resumes/'), findsNothing);
      expect(find.text(Ar.jobSeekerNotes), findsNothing);
      expect(find.text('secret note'), findsNothing);
    });

    testWidgets('detail sheet shows private contact when jobs.private_data is granted', (tester) async {
      client.setPermissionsForTest(['jobs.view', 'jobs.private_data', 'jobs.notes']);
      await tester.pumpWidget(
        Directionality(
          textDirection: TextDirection.rtl,
          child: MaterialApp(
            home: Scaffold(
              body: JobSeekerDetailSheet(
                client: client,
                profile: {
                  'id': 3,
                  'full_name': 'سارة',
                  'email': 'sara@wsa.test',
                  'phone': '+966500000003',
                  'cv_path': 'resumes/sara.pdf',
                  'desired_salary': '15000.00',
                  'salary_currency': 'SAR',
                  'completeness_percent': 70,
                  'recruitment_status': 'new',
                },
                notes: const [
                  {'body': 'ملاحظة داخلية', 'author': {'name': 'Admin'}},
                ],
                history: const [
                  {'status': 'new'},
                ],
              ),
            ),
          ),
        ),
      );

      expect(find.textContaining('sara@wsa.test'), findsOneWidget);
      expect(find.textContaining('+966500000003'), findsOneWidget);
      expect(find.textContaining('resumes/sara.pdf'), findsOneWidget);
      expect(find.text(Ar.jobSeekerNotes), findsOneWidget);
      expect(find.text('ملاحظة داخلية'), findsOneWidget);
    });
  });

  group('MarketplaceAdminScreen', () {
    late ApiClient client;

    setUp(() {
      client = ApiClient.inMemory();
      client.setPermissionsForTest(['market.review', 'market.approve']);
      MarketplaceAdminScreen.debugLoader = null;
    });

    tearDown(() => MarketplaceAdminScreen.debugLoader = null);

    testWidgets('shows marketplace metrics in RTL', (tester) async {
      MarketplaceAdminScreen.debugLoader = (_) async => MarketplaceSummary(
            total: '8',
            published: '3',
            pending: '2',
            contactOrders: '1',
          );

      await tester.pumpWidget(
        Directionality(
          textDirection: TextDirection.rtl,
          child: MaterialApp(home: Scaffold(body: MarketplaceAdminScreen(client: client))),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text(Ar.navMarketplace), findsOneWidget);
      expect(find.text('8'), findsOneWidget);
    });
  });
}
