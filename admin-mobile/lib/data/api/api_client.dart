import 'dart:async';
import 'dart:convert';

import 'package:wsa_admin/config/app_config.dart';
import 'package:wsa_admin/data/api/api_exception.dart';
import 'package:wsa_admin/data/api/auth_api.dart';
import 'package:wsa_admin/data/api/http_client.dart';
import 'package:wsa_admin/data/api/admin_modules_api.dart';
import 'package:wsa_admin/data/api/modules_api.dart';
import 'package:wsa_admin/data/api/platform_api.dart';
import 'package:wsa_admin/data/storage/in_memory_token_storage.dart';
import 'package:wsa_admin/data/storage/secure_token_storage.dart';
import 'package:wsa_admin/data/storage/token_storage.dart';

export 'package:wsa_admin/data/api/api_exception.dart';

class ApiClient {
  ApiClient({
    String? baseUrl,
    TokenStorage? tokenStorage,
    HttpClient? httpClient,
  })  : baseUrl = baseUrl ?? AppConfig.apiUrl,
        _tokenStorage = tokenStorage ?? SecureTokenStorage() {
    _http = httpClient ??
        HttpClient(
          baseUrl: this.baseUrl,
          getToken: () => _token,
          getOrganizationId: () => _organizationId,
          onUnauthorized: _handleUnauthorized,
        );
    auth = AuthApi(_http);
    platform = PlatformApi(_http);
    modules = ModulesApi(_http);
    adminModules = AdminModulesApi(_http);
  }

  final String baseUrl;
  final TokenStorage _tokenStorage;

  late final HttpClient _http;
  late final AuthApi auth;
  late final PlatformApi platform;
  late final ModulesApi modules;
  late final AdminModulesApi adminModules;

  String? _token;
  int? _organizationId;
  Map<String, dynamic>? _user;
  List<Map<String, dynamic>> _organizations = [];
  List<String> _permissions = [];

  String? get token => _token;
  int? get organizationId => _organizationId;
  Map<String, dynamic>? get user => _user;
  List<Map<String, dynamic>> get organizations => List.unmodifiable(_organizations);
  List<String> get permissions => List.unmodifiable(_permissions);

  bool get hasAdminAccess =>
      _permissions.any((permission) => AppConfig.adminPermissions.contains(permission));

  bool hasPermission(String permission) => _permissions.contains(permission);

  bool hasAnyPermission(Iterable<String> permissions) =>
      permissions.any(hasPermission);

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
      _user = Map<String, dynamic>.from(current);
      await refreshOrganizations();
      await refreshPermissions();
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
    await refreshPermissions();
  }

  Future<void> refreshOrganizations() async {
    final rows = await platform.organizations();
    _organizations = rows
        .map((row) {
          final org = Map<String, dynamic>.from(row as Map);
          return {
            'id': org['id'],
            'name': org['name'],
            'slug': org['slug'],
            'role': org['role'],
          };
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

  Future<void> refreshPermissions() async {
    if (_token == null || _organizationId == null) {
      _permissions = [];
      return;
    }

    final payload = await platform.me();
    final raw = payload['permissions'];
    if (raw is List) {
      _permissions = raw.map((item) => '$item').toList();
    } else {
      _permissions = [];
    }
  }

  Future<void> setOrganizationId(int organizationId) async {
    _organizationId = organizationId;
    await _tokenStorage.writeOrganizationId(organizationId);
    await refreshPermissions();
  }

  Future<void> logout() async {
    if (_token != null) {
      try {
        await auth.logout();
      } catch (_) {}
    }
    await _clearSession();
  }

  Future<Map<String, dynamic>> fetchHealth() => _http.getJson('/health');

  static List<dynamic> unwrapRows(dynamic decoded) => HttpClient.unwrapRows(decoded);

  Future<void> _clearSession() async {
    _token = null;
    _organizationId = null;
    _user = null;
    _organizations = [];
    _permissions = [];
    await _tokenStorage.clearAll();
  }

  void _handleUnauthorized() {
    unawaited(_clearSession().then((_) => onUnauthorized?.call()));
  }

  /// Test helper for setting permissions without network calls.
  void setPermissionsForTest(List<String> permissions) {
    _permissions = List<String>.from(permissions);
  }

  /// Test helper for setting user profile without network calls.
  void setUserForTest(Map<String, dynamic> user) {
    _user = Map<String, dynamic>.from(user);
  }

  /// Test helper for setting organizations without network calls.
  void setOrganizationsForTest(List<Map<String, dynamic>> organizations, {int? organizationId}) {
    _organizations = organizations
        .map((organization) => Map<String, dynamic>.from(organization))
        .toList();
    _organizationId = organizationId ?? (_organizations.isNotEmpty ? _organizations.first['id'] as int? : null);
  }

  /// Test helper — not used in production bootstrap.
  factory ApiClient.inMemory({String? baseUrl}) {
    return ApiClient(
      baseUrl: baseUrl ?? AppConfig.apiUrl,
      tokenStorage: InMemoryTokenStorage(),
    );
  }
}
