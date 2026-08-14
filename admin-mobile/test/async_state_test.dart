import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_admin/l10n/strings.dart';
import 'package:wsa_admin/presentation/widgets/async_state.dart';

void main() {
  group('AsyncState', () {
    testWidgets('shows loading indicator', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: AsyncState(
            loading: true,
            child: Text('content'),
          ),
        ),
      );

      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      expect(find.text(Ar.loading), findsOneWidget);
      expect(find.text('content'), findsNothing);
    });

    testWidgets('shows error with retry button', (tester) async {
      var retried = false;

      await tester.pumpWidget(
        MaterialApp(
          home: AsyncState(
            loading: false,
            error: 'خطأ في التحميل',
            onRetry: () => retried = true,
            child: const Text('content'),
          ),
        ),
      );

      expect(find.text('خطأ في التحميل'), findsOneWidget);
      expect(find.text(Ar.retry), findsOneWidget);

      await tester.tap(find.text(Ar.retry));
      expect(retried, isTrue);
    });

    testWidgets('shows empty message', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: AsyncState(
            loading: false,
            empty: true,
            child: Text('content'),
          ),
        ),
      );

      expect(find.text(Ar.emptyData), findsOneWidget);
      expect(find.text('content'), findsNothing);
    });

    testWidgets('renders child when ready', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: AsyncState(
            loading: false,
            child: Text('content'),
          ),
        ),
      );

      expect(find.text('content'), findsOneWidget);
    });
  });
}
