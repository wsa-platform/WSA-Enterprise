import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:wsa_enterprise/core/routing/app_routes.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/main.dart';
import 'package:wsa_enterprise/presentation/screens/public/research_agent_screen.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';
import 'package:wsa_enterprise/widgets/record_form.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  testWidgets('bootstrap renders Arabic public home in RTL',
      (WidgetTester tester) async {
    await tester.pumpWidget(const WsaEnterpriseApp());
    await tester.pumpAndSettle();

    expect(find.text(ArStrings.home), findsWidgets);
    expect(find.text('Sign in'), findsNothing);
    expect(find.text('admin@wsa.test'), findsNothing);

    final directionality =
        tester.widget<Directionality>(find.byType(Directionality).first);
    expect(directionality.textDirection, TextDirection.rtl);
  });

  testWidgets('public navigation exposes required Arabic destinations',
      (WidgetTester tester) async {
    await tester.pumpWidget(const WsaEnterpriseApp());
    await tester.pumpAndSettle();

    await tester.pumpWidget(const WsaEnterpriseApp());
    await tester.pumpAndSettle();

    tester.state<ScaffoldState>(find.byType(Scaffold).first).openDrawer();
    await tester.pumpAndSettle();
    expect(find.byType(Drawer), findsOneWidget);

    for (final label in [
      ArStrings.home,
      ArStrings.plantProduction,
      ArStrings.honeyBees,
      ArStrings.servicesPortal,
      ArStrings.training,
      ArStrings.library,
      ArStrings.blog,
      ArStrings.store,
      ArStrings.about,
    ]) {
      expect(
        find.descendant(of: find.byType(Drawer), matching: find.text(label)),
        findsWidgets,
        reason: 'missing $label',
      );
    }

    expect(AppRoutes.pathFor(AppSection.library), '/library');
    expect(AppRoutes.pathFor(AppSection.plantDiagnosis),
        '/services/plant-ai-diagnosis');
  });

  testWidgets('home cards open research agent', (WidgetTester tester) async {
    await tester.pumpWidget(const WsaEnterpriseApp());
    await tester.pumpAndSettle();
    await tester.tap(find.text(ArStrings.researchAgent).first);
    await tester.pumpAndSettle();
    expect(find.byType(ResearchAgentScreen), findsOneWidget);
    expect(find.text(ArStrings.askQuestion), findsOneWidget);
  });

  testWidgets('AsyncState shows empty message', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: AsyncState(
            loading: false,
            empty: true,
            emptyMessage: 'Nothing here yet.',
            child: Text('content'),
          ),
        ),
      ),
    );

    expect(find.text('Nothing here yet.'), findsOneWidget);
  });

  testWidgets('AsyncState shows retry on error', (WidgetTester tester) async {
    var retried = false;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: AsyncState(
            loading: false,
            error: 'Network failed',
            onRetry: () => retried = true,
            child: const Text('content'),
          ),
        ),
      ),
    );

    expect(find.text('Network failed'), findsOneWidget);
    await tester.tap(find.text('Retry'));
    expect(retried, isTrue);
  });

  testWidgets('RecordForm validates required fields',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: RecordForm(
            title: 'Create farm',
            fields: const [
              FormFieldConfig(name: 'code', label: 'Code', required: true),
              FormFieldConfig(name: 'name', label: 'Name', required: true),
            ],
            onSubmit: (_) async {},
          ),
        ),
      ),
    );

    await tester.tap(find.text('Save'));
    await tester.pumpAndSettle();
    expect(find.text('Code is required'), findsOneWidget);
  });
}
