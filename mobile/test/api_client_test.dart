import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_enterprise/api/api_client.dart';

void main() {
  group('ApiClient.unwrapRows', () {
    test('returns list payloads directly', () {
      expect(ApiClient.unwrapRows([{'id': 1}]), [{'id': 1}]);
    });

    test('unwraps paginated payloads', () {
      expect(
        ApiClient.unwrapRows({'data': [{'id': 2}], 'current_page': 1}),
        [{'id': 2}],
      );
    });

    test('returns empty list for unknown payloads', () {
      expect(ApiClient.unwrapRows({'message': 'ok'}), isEmpty);
    });
  });

  group('ApiException', () {
    test('formats validation errors', () {
      final error = ApiException('Validation failed', errors: {
        'email': ['The email field is required.'],
      });
      expect(error.toString(), 'email: The email field is required.');
    });

    test('returns message when no field errors exist', () {
      final error = ApiException('Forbidden', statusCode: 403);
      expect(error.toString(), 'Forbidden');
    });
  });
}
