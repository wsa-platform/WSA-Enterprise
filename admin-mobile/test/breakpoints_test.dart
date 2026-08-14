import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_admin/core/layout/breakpoints.dart';

void main() {
  group('AdminBreakpoints', () {
    test('classifies mobile widths', () {
      expect(AdminBreakpoints.isMobile(599), isTrue);
      expect(AdminBreakpoints.isTablet(599), isFalse);
      expect(AdminBreakpoints.isDesktop(599), isFalse);
    });

    test('classifies tablet widths', () {
      expect(AdminBreakpoints.isMobile(600), isFalse);
      expect(AdminBreakpoints.isTablet(750), isTrue);
      expect(AdminBreakpoints.isDesktop(750), isFalse);
    });

    test('classifies desktop widths', () {
      expect(AdminBreakpoints.isDesktop(900), isTrue);
      expect(AdminBreakpoints.isTablet(900), isFalse);
      expect(AdminBreakpoints.isMobile(900), isFalse);
    });
  });
}
