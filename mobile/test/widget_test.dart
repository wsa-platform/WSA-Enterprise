import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:wsa_enterprise/main.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';
import 'package:wsa_enterprise/widgets/record_form.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  testWidgets('WSA Enterprise app renders login screen', (WidgetTester tester) async {
    await tester.pumpWidget(const WsaEnterpriseApp());
    await tester.pumpAndSettle();
    expect(find.text('WSA Enterprise'), findsOneWidget);
    expect(find.text('Sign in'), findsOneWidget);
    expect(find.text('admin@wsa.test'), findsNothing);
    expect(find.text('password'), findsNothing);
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

  testWidgets('RecordForm validates required fields', (WidgetTester tester) async {
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
