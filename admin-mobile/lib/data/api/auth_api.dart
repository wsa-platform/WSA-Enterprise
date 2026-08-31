import 'package:wsa_admin/data/api/http_client.dart';

class AuthApi {
  AuthApi(this.http);

  final HttpClient http;

  Future<Map<String, dynamic>> login(String email, String password) => http.postJson('/auth/login', {
        'email': email,
        'password': password,
        'device_name': 'wsa-admin',
      });

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) =>
      http.postJson('/auth/register', {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'device_name': 'wsa-admin',
      });

  Future<void> logout() async {
    await http.postJson('/auth/logout', {});
  }

  Future<Map<String, dynamic>> currentUser() => http.getJson('/user');

  Future<Map<String, dynamic>> forgotPassword(String email) =>
      http.postJson('/auth/forgot-password', {'email': email});

  Future<Map<String, dynamic>> resetPassword({
    required String email,
    required String token,
    required String password,
    required String passwordConfirmation,
  }) =>
      http.postJson('/auth/reset-password', {
        'email': email,
        'token': token,
        'password': password,
        'password_confirmation': passwordConfirmation,
      });

  Future<Map<String, dynamic>> googleRedirect() => http.getJson('/auth/google/redirect');

  Future<Map<String, dynamic>> googleCallback(String code, String state) =>
      http.postJson('/auth/google/callback', {
        'code': code,
        'state': state,
        'device_name': 'wsa-admin',
      });

  Future<Map<String, dynamic>> facebookRedirect() => http.getJson('/auth/facebook/redirect');

  Future<Map<String, dynamic>> facebookCallback(String code, String state) =>
      http.postJson('/auth/facebook/callback', {
        'code': code,
        'state': state,
        'device_name': 'wsa-admin',
      });

  Future<Map<String, dynamic>> sendPhoneOtp(String phone) =>
      http.postJson('/auth/phone/send-otp', {'phone': phone});

  Future<Map<String, dynamic>> verifyPhoneOtp({
    required String phone,
    required String code,
    String? name,
  }) =>
      http.postJson('/auth/phone/verify-otp', {
        'phone': phone,
        'code': code,
        if (name != null) 'name': name,
        'device_name': 'wsa-admin',
      });

  Future<List<dynamic>> identities() => http.getList('/auth/identities');

  Future<Map<String, dynamic>> providersStatus() => http.getJson('/providers/status');

  Future<Map<String, dynamic>> testProvider(String provider) =>
      http.postJson('/providers/$provider/test', {});
}
