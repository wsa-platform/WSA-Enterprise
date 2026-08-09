import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiClient {
  ApiClient({String? baseUrl}) : baseUrl = baseUrl ?? const String.fromEnvironment('API_URL', defaultValue: 'http://localhost:8080/api/v1');

  final String baseUrl;
  String? _token;
  int? _organizationId;

  String? get token => _token;
  int? get organizationId => _organizationId;

  Future<void> restoreSession() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('wsa_token');
    final orgId = prefs.getInt('wsa_organization_id');
    _organizationId = orgId;
  }

  Future<void> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password, 'device_name': 'wsa-mobile'}),
    );

    if (response.statusCode >= 400) {
      throw Exception('Sign in failed (${response.statusCode})');
    }

    final payload = jsonDecode(response.body) as Map<String, dynamic>;
    _token = payload['token'] as String;

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('wsa_token', _token!);

    final organizations = await fetchList('/platform/organizations');
    if (organizations.isNotEmpty) {
      final first = organizations.first as Map<String, dynamic>;
      _organizationId = first['id'] as int?;
      if (_organizationId != null) {
        await prefs.setInt('wsa_organization_id', _organizationId!);
      }
    }
  }

  Future<void> logout() async {
    if (_token != null) {
      await http.post(
        Uri.parse('$baseUrl/auth/logout'),
        headers: _headers(),
      );
    }

    _token = null;
    _organizationId = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('wsa_token');
    await prefs.remove('wsa_organization_id');
  }

  Future<Map<String, dynamic>> fetchDashboard() async {
    final response = await http.get(Uri.parse('$baseUrl/dashboard'), headers: _headers());
    if (response.statusCode >= 400) throw Exception('Unable to load dashboard');
    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> fetchWorkflowSummary() async {
    final response = await http.get(Uri.parse('$baseUrl/platform/workflow-summary'), headers: _headers());
    if (response.statusCode >= 400) throw Exception('Unable to load workflow summary');
    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<List<dynamic>> fetchList(String path) async {
    final response = await http.get(Uri.parse('$baseUrl$path'), headers: _headers());
    if (response.statusCode >= 400) throw Exception('Unable to load $path');
    final decoded = jsonDecode(response.body);
    if (decoded is List) return decoded;
    if (decoded is Map<String, dynamic> && decoded['data'] is List) {
      return decoded['data'] as List<dynamic>;
    }
    return [];
  }

  Map<String, String> _headers() {
    return {
      'Accept': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
      if (_organizationId != null) 'X-Organization-Id': '$_organizationId',
    };
  }
}
