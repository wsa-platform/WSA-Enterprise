import 'package:flutter_test/flutter_test.dart';
import 'package:wsa_enterprise/data/models/models.dart';

void main() {
  group('model parsers', () {
    test('userFromJson maps user fields', () {
      final user = userFromJson({'id': 1, 'name': 'Admin', 'email': 'admin@wsa.test'});
      expect(user.id, 1);
      expect(user.name, 'Admin');
      expect(user.email, 'admin@wsa.test');
    });

    test('organizationFromJson maps organization fields', () {
      final org = organizationFromJson({
        'id': 3,
        'name': 'Demo Org',
        'slug': 'demo-org',
        'role': 'admin',
      });
      expect(org.id, 3);
      expect(org.slug, 'demo-org');
      expect(org.role, 'admin');
    });

    test('aiRequestFromJson maps pending request', () {
      final request = aiRequestFromJson({
        'id': 9,
        'status': 'pending',
        'request_type': 'library_qa',
      });
      expect(request.id, 9);
      expect(request.isPending, isTrue);
      expect(request.isTerminal, isFalse);
    });

    test('notificationFromJson maps notification fields', () {
      final notification = notificationFromJson({
        'id': 2,
        'title': 'AI completed',
        'body': 'Your request finished.',
        'read_at': null,
        'created_at': '2026-08-10T12:00:00Z',
      });
      expect(notification.id, 2);
      expect(notification.isRead, isFalse);
      expect(notification.title, 'AI completed');
    });
  });
}
