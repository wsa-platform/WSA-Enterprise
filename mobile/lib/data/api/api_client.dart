import 'dart:async';
import 'dart:convert';

import 'package:wsa_enterprise/data/api/ai_api.dart';
import 'package:wsa_enterprise/data/api/api_exception.dart';
import 'package:wsa_enterprise/data/api/auth_api.dart';
import 'package:wsa_enterprise/data/api/http_client.dart';
import 'package:wsa_enterprise/data/api/module_api.dart';
import 'package:wsa_enterprise/data/api/notifications_api.dart';
import 'package:wsa_enterprise/data/api/platform_api.dart';
import 'package:wsa_enterprise/data/models/models.dart';
import 'package:wsa_enterprise/data/storage/shared_preferences_token_storage.dart';
import 'package:wsa_enterprise/data/storage/token_storage.dart';

export 'package:wsa_enterprise/data/api/api_exception.dart';

/// Facade over domain-scoped API clients. Preserves the Phase 7 public surface
/// while routing through the M6 layered architecture.
class ApiClient {
  ApiClient({
    String? baseUrl,
    TokenStorage? tokenStorage,
  })  : baseUrl = baseUrl ??
            const String.fromEnvironment('API_URL', defaultValue: 'http://localhost:8081/api/v1'),
        _tokenStorage = tokenStorage ?? SharedPreferencesTokenStorage() {
    _http = HttpClient(
      baseUrl: this.baseUrl,
      getToken: () => _token,
      getOrganizationId: () => _organizationId,
      onUnauthorized: _handleUnauthorized,
    );
    auth = AuthApi(_http);
    platform = PlatformApi(_http);
    ai = AiApi(_http);
    modules = ModuleApi(_http);
    notifications = NotificationsApi(_http);
  }

  final String baseUrl;
  final TokenStorage _tokenStorage;

  late final HttpClient _http;
  late final AuthApi auth;
  late final PlatformApi platform;
  late final AiApi ai;
  late final ModuleApi modules;
  late final NotificationsApi notifications;

  String? _token;
  int? _organizationId;
  Map<String, dynamic>? _user;
  List<Map<String, dynamic>> _organizations = [];

  String? get token => _token;
  int? get organizationId => _organizationId;
  Map<String, dynamic>? get user => _user;
  List<Map<String, dynamic>> get organizations => List.unmodifiable(_organizations);

  ApiUser? get typedUser => _user == null ? null : userFromJson(_user!);

  void Function()? onUnauthorized;

  Future<void> restoreSession() async {
    _token = await _tokenStorage.readToken();
    _organizationId = await _tokenStorage.readOrganizationId();
    final userJson = await _tokenStorage.readUserJson();
    if (userJson != null) {
      _user = jsonDecode(userJson) as Map<String, dynamic>;
    }

    if (_token == null) return;

    try {
      final current = await auth.currentUser();
      _user = {'id': current.id, 'name': current.name, 'email': current.email};
      await refreshOrganizations();
    } on ApiException catch (error) {
      if (error.statusCode == 401) {
        await _clearSession();
      } else {
        rethrow;
      }
    }
  }

  Future<void> login(String email, String password) async {
    final payload = await auth.login(email, password);
    _token = payload['token'] as String;
    _user = (payload['user'] as Map<String, dynamic>?) ?? {};

    await _tokenStorage.writeToken(_token!);
    await _tokenStorage.writeUserJson(jsonEncode(_user));

    await refreshOrganizations();
    if (_organizationId == null && _organizations.isNotEmpty) {
      await setOrganizationId(_organizations.first['id'] as int);
    }
  }

  Future<void> refreshOrganizations() async {
    final rows = await platform.organizations();
    _organizations = rows
        .map((org) => {
              'id': org.id,
              'name': org.name,
              'slug': org.slug,
              'role': org.role,
            })
        .toList();

    if (_organizationId != null &&
        !_organizations.any((organization) => organization['id'] == _organizationId)) {
      _organizationId = _organizations.isNotEmpty ? _organizations.first['id'] as int? : null;
      if (_organizationId != null) {
        await _tokenStorage.writeOrganizationId(_organizationId!);
      } else {
        await _tokenStorage.clearOrganizationId();
      }
    }
  }

  Future<void> setOrganizationId(int organizationId) async {
    _organizationId = organizationId;
    await _tokenStorage.writeOrganizationId(organizationId);
  }

  Future<void> logout() async {
    if (_token != null) {
      try {
        await auth.logout();
      } catch (_) {}
    }
    await _clearSession();
  }

  Future<Map<String, dynamic>> fetchDashboard() async {
    final dashboard = await platform.dashboard();
    return {
      'organization': dashboard.organization,
      'metrics': dashboard.metrics,
    };
  }

  Future<Map<String, dynamic>> fetchWorkflowSummary() => platform.workflowSummary();

  Future<Map<String, dynamic>> fetchAiProvider() => ai.provider();

  Future<List<dynamic>> fetchList(String path) => modules.fetchList(path);

  static List<dynamic> unwrapRows(dynamic decoded) => HttpClient.unwrapRows(decoded);

  Future<Map<String, dynamic>> createRecord(String path, Map<String, dynamic> payload) =>
      modules.createRecord(path, payload);

  Future<Map<String, dynamic>> updateRecord(String path, int id, Map<String, dynamic> payload) =>
      modules.updateRecord(path, id, payload);

  Future<void> deleteRecord(String path, int id) => modules.deleteRecord(path, id);

  Future<Map<String, dynamic>> createDiagnosisRequest({
    required String reference,
    String? notes,
    int? cropTypeId,
  }) =>
      modules.createDiagnosisRequest(reference: reference, notes: notes, cropTypeId: cropTypeId);

  Future<List<dynamic>> searchLibrary(String query) => modules.searchLibrary(query);

  Future<Map<String, dynamic>> createAiRequest({
    required String requestType,
    required Map<String, dynamic> input,
  }) async {
    final request = await ai.createRequest(requestType: requestType, input: input);
    return {
      'id': request.id,
      'status': request.status,
      'request_type': request.requestType,
      'output': request.output,
      'error_message': request.errorMessage,
    };
  }

  Future<Map<String, dynamic>> fetchAiRequest(int id) async {
    final request = await ai.fetchRequest(id);
    return {
      'id': request.id,
      'status': request.status,
      'request_type': request.requestType,
      'output': request.output,
      'error_message': request.errorMessage,
    };
  }

  Future<Map<String, dynamic>> fetchHealth() => _http.getJson('/health');

  Future<List<ApiNotification>> fetchNotifications({int page = 1}) => notifications.list(page: page);

  Future<ApiNotification> markNotificationRead(int notificationId) => notifications.markRead(notificationId);

  Future<void> _clearSession() async {
    _token = null;
    _organizationId = null;
    _user = null;
    _organizations = [];
    await _tokenStorage.clearAll();
  }

  void _handleUnauthorized() {
    unawaited(_clearSession().then((_) => onUnauthorized?.call()));
  }
}
