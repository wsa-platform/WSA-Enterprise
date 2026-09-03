import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:wsa_enterprise/config/app_config.dart';
import 'package:wsa_enterprise/data/api/api_client.dart';
import 'package:wsa_enterprise/data/api/http_client.dart';
import 'package:wsa_enterprise/data/media/diagnosis_image.dart';
import 'package:wsa_enterprise/data/storage/token_storage.dart';

class MemoryTokenStorage implements TokenStorage {
  String? token;
  int? organizationId;
  String? userJson;

  @override
  Future<void> clearAll() async {
    token = null;
    organizationId = null;
    userJson = null;
  }

  @override
  Future<void> clearOrganizationId() async {
    organizationId = null;
  }

  @override
  Future<void> clearToken() async {
    token = null;
  }

  @override
  Future<void> clearUserJson() async {
    userJson = null;
  }

  @override
  Future<int?> readOrganizationId() async => organizationId;

  @override
  Future<String?> readToken() async => token;

  @override
  Future<String?> readUserJson() async => userJson;

  @override
  Future<void> writeOrganizationId(int value) async {
    organizationId = value;
  }

  @override
  Future<void> writeToken(String value) async {
    token = value;
  }

  @override
  Future<void> writeUserJson(String value) async {
    userJson = value;
  }
}

class FakeDiagnosisImagePicker implements DiagnosisImagePicker {
  FakeDiagnosisImagePicker(this.image);
  final DiagnosisImageSelection image;

  @override
  Future<DiagnosisImageSelection?> pickFromCamera() async => image;

  @override
  Future<DiagnosisImageSelection?> pickFromGallery() async => image;
}

http.Client jsonClient(Map<String, http.Response> routes,
    {http.Response? fallback}) {
  return MockClient((request) async {
    final key = '${request.method} ${request.url.path}';
    for (final entry in routes.entries) {
      if (key.endsWith(entry.key) ||
          request.url.path.endsWith(entry.key) ||
          request.url.toString().contains(entry.key)) {
        return entry.value;
      }
    }
    return fallback ?? http.Response('{"message":"not found"}', 404);
  });
}

http.Response jsonOk(Object body, {int status = 200}) =>
    http.Response(jsonEncode(body), status,
        headers: {'content-type': 'application/json'});

ApiClient testApiClient({
  required http.Client httpClient,
  DiagnosisImagePicker? picker,
}) {
  return ApiClient(
    baseUrl: 'http://example.test/api/v1',
    tokenStorage: MemoryTokenStorage(),
    httpClient: httpClient,
    config: const AppConfig(
      apiBaseUrl: 'http://example.test/api/v1',
      publicOrganizationSlug: 'wsa-demo',
      requestTimeout: Duration(seconds: 5),
      researchTimeout: Duration(seconds: 5),
      diagnosisTimeout: Duration(seconds: 5),
    ),
    diagnosisImagePicker: picker,
  );
}

HttpClient testHttpClient(http.Client httpClient) => HttpClient(
      baseUrl: 'http://example.test/api/v1',
      httpClient: httpClient,
      timeout: const Duration(seconds: 5),
    );
