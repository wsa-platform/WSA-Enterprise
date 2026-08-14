import 'package:wsa_admin/data/api/http_client.dart';

class AuthApi {
  AuthApi(this.http);

  final HttpClient http;

  Future<Map<String, dynamic>> login(String email, String password) => http.postJson('/auth/login', {
        'email': email,
        'password': password,
        'device_name': 'wsa-admin',
      });

  Future<void> logout() async {
    await http.postJson('/auth/logout', {});
  }

  Future<Map<String, dynamic>> currentUser() => http.getJson('/user');
}
