import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_enterprise/main.dart';

void main() {
  testWidgets('WSA Enterprise app renders', (WidgetTester tester) async {
    await tester.pumpWidget(const WsaEnterpriseApp());
    expect(find.text('WSA Enterprise'), findsOneWidget);
  });
}
