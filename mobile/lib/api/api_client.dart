import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiClient {
  ApiClient({this.baseUrl = const String.fromEnvironment('API_URL', defaultValue: 'http://localhost:8080/api/v1')});

  final String baseUrl;
  String? token;

  Map<String, String> get headers => {
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      };

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: headers,
      body: jsonEncode({'email': email, 'password': password, 'device_name': 'wsa-mobile'}),
    );
    final body = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode >= 400) {
      throw Exception(body['message'] ?? 'Login failed');
    }
    token = body['token'] as String?;
    return body;
  }

  Future<List<dynamic>> fetchList(String path) async {
    final response = await http.get(Uri.parse('$baseUrl$path'), headers: headers);
    final body = jsonDecode(response.body);
    if (response.statusCode >= 400) {
      throw Exception('Request failed');
    }
    if (body is Map<String, dynamic> && body['data'] is List) {
      return body['data'] as List<dynamic>;
    }
    return body as List<dynamic>;
  }
}
