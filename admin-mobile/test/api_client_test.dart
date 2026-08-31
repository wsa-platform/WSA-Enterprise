import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/data/api/http_client.dart';
import 'package:wsa_admin/data/storage/in_memory_token_storage.dart';

void main() {
  group('HttpClient headers', () {
    test('sends bearer token, org header, and Arabic accept language', () async {
      Map<String, String>? capturedHeaders;

      final inner = MockClient((request) async {
        capturedHeaders = request.headers;
        return http.Response('{"ok":true}', 200);
      });

      final httpClient = HttpClient(
        baseUrl: 'http://localhost:8081/api/v1',
        getToken: () => 'test-token',
        getOrganizationId: () => 42,
        inner: inner,
      );

      await httpClient.getJson('/platform/me');

      expect(capturedHeaders?['Authorization'], 'Bearer test-token');
      expect(capturedHeaders?['X-Organization-Id'], '42');
      expect(capturedHeaders?['Accept-Language'], 'ar');
    });

    test('invokes onUnauthorized for 401 responses when token exists', () async {
      var unauthorized = false;

      final inner = MockClient((request) async {
        return http.Response(jsonEncode({'message': 'Unauthenticated'}), 401);
      });

      final httpClient = HttpClient(
        baseUrl: 'http://localhost:8081/api/v1',
        getToken: () => 'expired-token',
        onUnauthorized: () => unauthorized = true,
        inner: inner,
      );

      await expectLater(
        httpClient.getJson('/user'),
        throwsA(isA<ApiException>()),
      );
      expect(unauthorized, isTrue);
    });
  });

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
  });

  group('ApiClient admin access', () {
    test('detects admin permissions', () {
      final client = ApiClient.inMemory();
      client.setPermissionsForTest(['access.manage']);
      expect(client.hasAdminAccess, isTrue);
    });

    test('detects supervise permission', () {
      final client = ApiClient.inMemory();
      client.setPermissionsForTest(['services.supervise']);
      expect(client.hasAdminAccess, isTrue);
    });

    test('rejects non-admin permissions', () {
      final client = ApiClient.inMemory();
      client.setPermissionsForTest(['platform.view']);
      expect(client.hasAdminAccess, isFalse);
    });

    test('checks single and multiple permissions', () {
      final client = ApiClient.inMemory();
      client.setPermissionsForTest(['platform.view', 'farm.view']);

      expect(client.hasPermission('platform.view'), isTrue);
      expect(client.hasPermission('access.manage'), isFalse);
      expect(client.hasAnyPermission(['access.manage', 'farm.view']), isTrue);
      expect(client.hasAnyPermission(['access.manage', 'ai.use']), isFalse);
    });

    test('wildcard permission grants full admin access', () {
      final client = ApiClient.inMemory();
      client.setPermissionsForTest(['*']);

      expect(client.hasAdminAccess, isTrue);
      expect(client.hasPermission('access.manage'), isTrue);
      expect(client.hasPermission('platform.view'), isTrue);
      expect(client.hasAnyPermission(['access.manage', 'farm.view']), isTrue);
      expect(client.hasAnyPermission(['ai.use']), isTrue);
    });
  });

  group('InMemoryTokenStorage', () {
    test('persists token and organization id', () async {
      final storage = InMemoryTokenStorage();
      await storage.writeToken('abc');
      await storage.writeOrganizationId(7);

      expect(await storage.readToken(), 'abc');
      expect(await storage.readOrganizationId(), 7);

      await storage.clearAll();
      expect(await storage.readToken(), isNull);
      expect(await storage.readOrganizationId(), isNull);
    });
  });
}
