import 'package:wsa_enterprise/data/api/http_client.dart';
import 'package:wsa_enterprise/data/models/models.dart';

class AuthApi {
  AuthApi(this.http);

  final HttpClient http;

  Future<Map<String, dynamic>> login(String email, String password) =>
      http.postJson('/auth/login', {
        'email': email,
        'password': password,
        'device_name': 'wsa-mobile',
      });

  Future<void> logout() async {
    await http.postJson('/auth/logout', {});
  }

  Future<ApiUser> currentUser() async {
    final payload = await http.getJson('/user');
    return userFromJson(payload);
  }
}
