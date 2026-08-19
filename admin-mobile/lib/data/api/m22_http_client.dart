import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:wsa_admin/data/api/api_exception.dart';
import 'package:wsa_admin/data/api/http_client.dart';
import 'package:wsa_admin/data/models/paginated_response.dart';

/// M22 HTTP surface used by ModulesApi / AdminModulesApi / PlatformApi.
///
/// Kept separate from [HttpClient] so pagination, PATCH/PUT, multipart, and
/// text download can compile against HEAD without taking the mixed
/// network-wrap / dart:io work in `http_client.dart`.
class M22HttpClient {
  M22HttpClient({
    required this.baseUrl,
    this.getToken,
    this.getOrganizationId,
    this.onUnauthorized,
    http.Client? inner,
  }) : _inner = inner ?? http.Client();

  factory M22HttpClient.from(HttpClient source, {http.Client? inner}) {
    return M22HttpClient(
      baseUrl: source.baseUrl,
      getToken: source.getToken,
      getOrganizationId: source.getOrganizationId,
      onUnauthorized: () => source.onUnauthorized?.call(),
      inner: inner,
    );
  }

  final String baseUrl;
  final String? Function()? getToken;
  final int? Function()? getOrganizationId;
  void Function()? onUnauthorized;
  final http.Client _inner;

  static List<dynamic> unwrapRows(dynamic decoded) {
    if (decoded is List) return decoded;
    if (decoded is Map<String, dynamic> && decoded['data'] is List) {
      return decoded['data'] as List<dynamic>;
    }
    return [];
  }

  Future<Map<String, dynamic>> getJson(String path) async {
    final response = await _inner.get(Uri.parse('$baseUrl$path'), headers: _headers());
    return _decodeResponse(response);
  }

  Future<List<dynamic>> getList(String path) async {
    final decoded = await getJson(path);
    return unwrapRows(decoded);
  }

  Future<PaginatedResponse<Map<String, dynamic>>> getPaginated(String path) async {
    final decoded = await getJson(path);
    return PaginatedResponse.fromJson(decoded, (row) => row);
  }

  Future<Map<String, dynamic>> postJson(String path, Map<String, dynamic> body) async {
    final response = await _inner.post(
      Uri.parse('$baseUrl$path'),
      headers: _headers(includeJson: true),
      body: jsonEncode(body),
    );
    return _decodeResponse(response);
  }

  Future<Map<String, dynamic>> putJson(String path, Map<String, dynamic> body) async {
    final response = await _inner.put(
      Uri.parse('$baseUrl$path'),
      headers: _headers(includeJson: true),
      body: jsonEncode(body),
    );
    return _decodeResponse(response);
  }

  Future<Map<String, dynamic>> patchJson(String path, Map<String, dynamic> body) async {
    final response = await _inner.patch(
      Uri.parse('$baseUrl$path'),
      headers: _headers(includeJson: true),
      body: jsonEncode(body),
    );
    return _decodeResponse(response);
  }

  Future<Map<String, dynamic>> postMultipart(
    String path, {
    required String fieldName,
    required List<int> bytes,
    required String filename,
    Map<String, String>? fields,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl$path'));
    request.headers.addAll(_headers());
    request.files.add(http.MultipartFile.fromBytes(fieldName, bytes, filename: filename));
    if (fields != null) request.fields.addAll(fields);

    final streamed = await _inner.send(request);
    final response = await http.Response.fromStream(streamed);
    return _decodeResponse(response);
  }

  Future<String> getText(String path) async {
    final response = await _inner.get(Uri.parse('$baseUrl$path'), headers: _headers());
    if (response.statusCode >= 400) {
      throw _parseError(response);
    }
    return response.body;
  }

  Future<void> delete(String path) async {
    final response = await _inner.delete(Uri.parse('$baseUrl$path'), headers: _headers());
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

    final message = payload?['message']?.toString() ?? '';
    final exception = ApiException(message, statusCode: response.statusCode, errors: errors);

    if (response.statusCode == 401 && getToken?.call() != null) {
      onUnauthorized?.call();
    }

    return exception;
  }

  Map<String, String> _headers({bool includeJson = false}) {
    final token = getToken?.call();
    final organizationId = getOrganizationId?.call();

    return {
      'Accept': 'application/json',
      'Accept-Language': 'ar',
      if (includeJson) 'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
      if (organizationId != null) 'X-Organization-Id': '$organizationId',
    };
  }
}