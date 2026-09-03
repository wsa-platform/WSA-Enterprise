import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:wsa_enterprise/data/api/api_exception.dart';

class HttpClient {
  HttpClient({
    required this.baseUrl,
    http.Client? httpClient,
    this.timeout = const Duration(seconds: 30),
    this.getToken,
    this.getOrganizationId,
    this.onUnauthorized,
  }) : _http = httpClient ?? http.Client();

  final String baseUrl;
  final http.Client _http;
  final Duration timeout;
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

  Future<Map<String, dynamic>> getJson(String path,
      {Map<String, String>? query, Duration? timeout}) async {
    final uri = _uri(path, query);
    final response = await _send(
        () => _http.get(uri, headers: _headers()), timeout ?? this.timeout);
    return _decodeResponse(response);
  }

  Future<List<dynamic>> getList(String path,
      {Map<String, String>? query}) async {
    final decoded = await getJson(path, query: query);
    return unwrapRows(decoded);
  }

  Future<Map<String, dynamic>> postJson(
    String path,
    Map<String, dynamic> body, {
    Duration? timeout,
  }) async {
    final response = await _send(
      () => _http.post(
        _uri(path),
        headers: _headers(includeJson: true),
        body: jsonEncode(body),
      ),
      timeout ?? this.timeout,
    );
    return _decodeResponse(response);
  }

  Future<Map<String, dynamic>> putJson(
      String path, Map<String, dynamic> body) async {
    final response = await _send(
      () => _http.put(
        _uri(path),
        headers: _headers(includeJson: true),
        body: jsonEncode(body),
      ),
      timeout,
    );
    return _decodeResponse(response);
  }

  Future<void> delete(String path) async {
    final response = await _send(
        () => _http.delete(_uri(path), headers: _headers()), timeout);
    if (response.statusCode >= 400) {
      throw _parseError(response);
    }
  }

  Future<List<int>> getBytes(String path, {Map<String, String>? query}) async {
    final response = await _send(
        () => _http.get(_uri(path, query), headers: _headers()), timeout);
    if (response.statusCode >= 400) {
      throw _parseError(response);
    }
    return response.bodyBytes;
  }

  Uri _uri(String path, [Map<String, String>? query]) {
    final parsed = Uri.parse('$baseUrl$path');
    if (query == null || query.isEmpty) return parsed;
    return parsed.replace(queryParameters: {
      ...parsed.queryParameters,
      ...query,
    });
  }

  Future<http.Response> _send(
      Future<http.Response> Function() request, Duration timeout) async {
    try {
      return await request().timeout(timeout);
    } on TimeoutException {
      throw ApiException.network();
    } on SocketException {
      throw ApiException.network();
    } on http.ClientException {
      throw ApiException.network();
    } on HandshakeException {
      throw ApiException.network();
    }
  }

  Map<String, dynamic> _decodeResponse(http.Response response) {
    if (response.statusCode == 204 || response.body.isEmpty) {
      return {};
    }
    if (response.statusCode >= 400) {
      throw _parseError(response);
    }
    Object? decoded;
    try {
      decoded = jsonDecode(response.body);
    } on FormatException {
      throw ApiException(
        'Unable to complete the request.',
        statusCode: response.statusCode,
      );
    }
    if (decoded is Map<String, dynamic>) return decoded;
    if (decoded is Map) return Map<String, dynamic>.from(decoded);
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

    final message =
        payload?['message']?.toString() ?? 'Unable to complete the request.';
    final exception = ApiException(
      message,
      statusCode: response.statusCode,
      errors: errors,
      payload: payload,
    );

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
      if (includeJson) 'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
      if (organizationId != null) 'X-Organization-Id': '$organizationId',
    };
  }
}
