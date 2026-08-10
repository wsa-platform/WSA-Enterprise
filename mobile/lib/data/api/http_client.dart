import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:wsa_enterprise/data/api/api_exception.dart';

class HttpClient {
  HttpClient({
    required this.baseUrl,
    this.getToken,
    this.getOrganizationId,
    this.onUnauthorized,
  });

  final String baseUrl;
  final String? Function()? getToken;
  final int? Function()? getOrganizationId;
  void Function()? onUnauthorized;

  static List<dynamic> unwrapRows(dynamic decoded) {
    if (decoded is List) return decoded;
    if (decoded is Map<String, dynamic> && decoded['data'] is List) {
      return decoded['data'] as List<dynamic>;
    }
    return [];
  }

  Future<Map<String, dynamic>> getJson(String path) async {
    final response = await http.get(Uri.parse('$baseUrl$path'), headers: _headers());
    return _decodeResponse(response);
  }

  Future<List<dynamic>> getList(String path) async {
    final decoded = await getJson(path);
    return unwrapRows(decoded);
  }

  Future<Map<String, dynamic>> postJson(String path, Map<String, dynamic> body) async {
    final response = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: _headers(includeJson: true),
      body: jsonEncode(body),
    );
    return _decodeResponse(response);
  }

  Future<Map<String, dynamic>> putJson(String path, Map<String, dynamic> body) async {
    final response = await http.put(
      Uri.parse('$baseUrl$path'),
      headers: _headers(includeJson: true),
      body: jsonEncode(body),
    );
    return _decodeResponse(response);
  }

  Future<void> delete(String path) async {
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

    if (response.statusCode == 401 && getToken?.call() != null) {
      unawaited(onUnauthorized?.call());
    }

    return exception;
  }

  Map<String, String> _headers({bool includeJson = false}) {
    final token = getToken?.call();
    final organizationId = getOrganizationId?.call();

    return {
      'Accept': 'application/json',
      if (includeJson) 'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
      if (organizationId != null) 'X-Organization-Id': '$organizationId',
    };
  }
}
