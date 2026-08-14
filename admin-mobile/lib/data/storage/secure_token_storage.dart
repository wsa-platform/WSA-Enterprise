import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:wsa_admin/data/storage/token_storage.dart';

/// Token in secure storage; org/user metadata in shared preferences.
class SecureTokenStorage implements TokenStorage {
  SecureTokenStorage({
    FlutterSecureStorage? secureStorage,
  }) : _secure = secureStorage ?? const FlutterSecureStorage();

  static const _tokenKey = 'wsa_admin_token';
  static const _organizationKey = 'wsa_admin_organization_id';
  static const _userKey = 'wsa_admin_user';

  final FlutterSecureStorage _secure;

  @override
  Future<void> clearAll() async {
    await _secure.delete(key: _tokenKey);
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_organizationKey);
    await prefs.remove(_userKey);
  }

  @override
  Future<void> clearOrganizationId() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_organizationKey);
  }

  @override
  Future<void> clearToken() async {
    await _secure.delete(key: _tokenKey);
  }

  @override
  Future<void> clearUserJson() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_userKey);
  }

  @override
  Future<int?> readOrganizationId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt(_organizationKey);
  }

  @override
  Future<String?> readToken() => _secure.read(key: _tokenKey);

  @override
  Future<String?> readUserJson() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_userKey);
  }

  @override
  Future<void> writeOrganizationId(int organizationId) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_organizationKey, organizationId);
  }

  @override
  Future<void> writeToken(String token) async {
    await _secure.write(key: _tokenKey, value: token);
  }

  @override
  Future<void> writeUserJson(String userJson) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, userJson);
  }
}
