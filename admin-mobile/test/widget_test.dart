import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_admin/data/api/api_exception.dart';

void main() {
  group('ApiException', () {
    test('formats validation errors in Arabic context', () {
      final error = ApiException('فشل التحقق', errors: {
        'email': ['حقل البريد الإلكتروني مطلوب.'],
      });
      expect(error.toString(), 'email: حقل البريد الإلكتروني مطلوب.');
    });

    test('uses default forbidden message for 403 without body message', () {
      final error = ApiException('', statusCode: 403);
      expect(error.toString(), 'ليس لديك صلاحية لتنفيذ هذا الإجراء.');
    });
  });
}
