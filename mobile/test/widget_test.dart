import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_enterprise/main.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';

void main() {
  testWidgets('WSA Enterprise app renders login screen', (WidgetTester tester) async {
    await tester.pumpWidget(const WsaEnterpriseApp());
    await tester.pumpAndSettle();
    expect(find.text('WSA Enterprise'), findsOneWidget);
    expect(find.text('Sign in'), findsOneWidget);
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
}
