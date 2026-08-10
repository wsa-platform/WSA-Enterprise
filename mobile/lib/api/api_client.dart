import 'dart:convert';
import 'dart:async';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors});

  final String message;
  final int? statusCode;
  final Map<String, List<String>>? errors;

  @override
  String toString() {
    if (statusCode == 403) {
      return message.isNotEmpty ? message : 'You do not have permission to perform this action.';
    }
    if (errors != null && errors!.isNotEmpty) {
      final details = errors!.entries
          .expand((entry) => entry.value.map((value) => '${entry.key}: $value'))
          .join(' · ');
      return details.isEmpty ? message : details;
    }
    return message;
  }
}

class ApiClient {
  ApiClient({String? baseUrl})
      : baseUrl = baseUrl ??
            const String.fromEnvironment('API_URL', defaultValue: 'http://localhost:8081/api/v1');

  final String baseUrl;
  String? _token;
  int? _organizationId;
  Map<String, dynamic>? _user;
  List<Map<String, dynamic>> _organizations = [];

  String? get token => _token;
  int? get organizationId => _organizationId;
  Map<String, dynamic>? get user => _user;
  List<Map<String, dynamic>> get organizations => List.unmodifiable(_organizations);

  /// Called when any API response returns HTTP 401 after clearing the local session.
  void Function()? onUnauthorized;

  Future<void> restoreSession() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('wsa_token');
    _organizationId = prefs.getInt('wsa_organization_id');
    final userJson = prefs.getString('wsa_user');
    if (userJson != null) {
      _user = jsonDecode(userJson) as Map<String, dynamic>;
    }

    if (_token == null) return;

    try {
      await _getJson('/user');
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
    final payload = await _postJson('/auth/login', {
      'email': email,
      'password': password,
      'device_name': 'wsa-mobile',
    });

    _token = payload['token'] as String;
    _user = (payload['user'] as Map<String, dynamic>?) ?? {};

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('wsa_token', _token!);
    await prefs.setString('wsa_user', jsonEncode(_user));

    await refreshOrganizations();
    if (_organizationId == null && _organizations.isNotEmpty) {
      await setOrganizationId(_organizations.first['id'] as int);
    }
  }

  Future<void> refreshOrganizations() async {
    final rows = await fetchList('/platform/organizations');
    _organizations = rows.map((row) => Map<String, dynamic>.from(row as Map)).toList();
    if (_organizationId != null &&
        !_organizations.any((organization) => organization['id'] == _organizationId)) {
      _organizationId = _organizations.isNotEmpty ? _organizations.first['id'] as int? : null;
      final prefs = await SharedPreferences.getInstance();
      if (_organizationId != null) {
        await prefs.setInt('wsa_organization_id', _organizationId!);
      } else {
        await prefs.remove('wsa_organization_id');
      }
    }
  }

  Future<void> setOrganizationId(int organizationId) async {
    _organizationId = organizationId;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('wsa_organization_id', organizationId);
  }

  Future<void> logout() async {
    if (_token != null) {
      try {
        await http.post(Uri.parse('$baseUrl/auth/logout'), headers: _headers());
      } catch (_) {}
    }
    await _clearSession();
  }

  Future<Map<String, dynamic>> fetchDashboard() => _getJson('/dashboard');

  Future<Map<String, dynamic>> fetchWorkflowSummary() => _getJson('/platform/workflow-summary');

  Future<Map<String, dynamic>> fetchAiProvider() => _getJson('/ai/provider');

  Future<List<dynamic>> fetchList(String path) async {
    final decoded = await _getJson(path);
    return unwrapRows(decoded);
  }

  static List<dynamic> unwrapRows(dynamic decoded) {
    if (decoded is List) return decoded;
    if (decoded is Map<String, dynamic> && decoded['data'] is List) {
      return decoded['data'] as List<dynamic>;
    }
    return [];
  }

  Future<Map<String, dynamic>> createRecord(String path, Map<String, dynamic> payload) =>
      _postJson(path.split('?').first, payload);

  Future<Map<String, dynamic>> updateRecord(String path, int id, Map<String, dynamic> payload) =>
      _putJson('${path.split('?').first}/$id', payload);

  Future<void> deleteRecord(String path, int id) => _delete('${path.split('?').first}/$id');

  Future<Map<String, dynamic>> createDiagnosisRequest({
    required String reference,
    String? notes,
    int? cropTypeId,
  }) =>
      createRecord('/diagnosis/requests', {
        'reference': reference,
        if (notes != null) 'notes': notes,
        if (cropTypeId != null) 'crop_type_id': cropTypeId,
      });

  Future<List<dynamic>> searchLibrary(String query) async {
    final decoded = await _getJson('/library/search?q=${Uri.encodeQueryComponent(query)}');
    return unwrapRows(decoded);
  }

  Future<Map<String, dynamic>> createAiRequest({
    required String requestType,
    required Map<String, dynamic> input,
  }) =>
      createRecord('/ai/requests', {
        'request_type': requestType,
        'input': input,
      });

  Future<Map<String, dynamic>> fetchAiRequest(int id) => _getJson('/ai/requests/$id');

  Future<Map<String, dynamic>> fetchHealth() => _getJson('/health');

  Future<void> _clearSession() async {
    _token = null;
    _organizationId = null;
    _user = null;
    _organizations = [];
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('wsa_token');
    await prefs.remove('wsa_organization_id');
    await prefs.remove('wsa_user');
  }

  Future<Map<String, dynamic>> _getJson(String path) async {
    final response = await http.get(Uri.parse('$baseUrl$path'), headers: _headers());
    return _decodeResponse(response);
  }

  Future<Map<String, dynamic>> _postJson(String path, Map<String, dynamic> body) async {
    final response = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: _headers(includeJson: true),
      body: jsonEncode(body),
    );
    return _decodeResponse(response);
  }

  Future<Map<String, dynamic>> _putJson(String path, Map<String, dynamic> body) async {
    final response = await http.put(
      Uri.parse('$baseUrl$path'),
      headers: _headers(includeJson: true),
      body: jsonEncode(body),
    );
    return _decodeResponse(response);
  }

  Future<void> _delete(String path) async {
    final response = await http.delete(Uri.parse('$baseUrl$path'), headers: _headers());
    if (response.statusCode >= 400) {
      throw _parseError(response);
    }
  }

  Map<String, dynamic> _decodeResponse(http.Response response) {
    if (response.statusCode == 204 || response.body.isEmpty) {
      return {};
    }
    if (response.statusCode >= 400) {
      throw _parseError(response);
    }
    final decoded = jsonDecode(response.body);
    if (decoded is Map<String, dynamic>) return decoded;
    return {'data': decoded};
  }

  ApiException _parseError(http.Response response) {
    Map<String, dynamic>? payload;
    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) payload = decoded;
    } catch (_) {}

    Map<String, List<String>>? errors;
    final rawErrors = payload?['errors'];
    if (rawErrors is Map) {
      errors = rawErrors.map((key, value) {
        if (value is List) {
          return MapEntry('$key', value.map((item) => '$item').toList());
        }
        return MapEntry('$key', ['$value']);
      });
    }

    final message = payload?['message']?.toString() ?? 'Unable to complete the request.';
    final exception = ApiException(message, statusCode: response.statusCode, errors: errors);

    if (response.statusCode == 401 && _token != null) {
      unawaited(_handleUnauthorized());
    }

    return exception;
  }

  Future<void> _handleUnauthorized() async {
    await _clearSession();
    onUnauthorized?.call();
  }

  Map<String, String> _headers({bool includeJson = false}) {
    return {
      'Accept': 'application/json',
      if (includeJson) 'Content-Type': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
      if (_organizationId != null) 'X-Organization-Id': '$_organizationId',
    };
  }
}
