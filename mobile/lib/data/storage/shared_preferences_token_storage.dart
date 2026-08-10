import 'package:shared_preferences/shared_preferences.dart';
import 'package:wsa_enterprise/data/storage/token_storage.dart';

class SharedPreferencesTokenStorage implements TokenStorage {
  static const tokenKey = 'wsa_token';
  static const organizationKey = 'wsa_organization_id';
  static const userKey = 'wsa_user';

  @override
  Future<void> clearAll() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(tokenKey);
    await prefs.remove(organizationKey);
    await prefs.remove(userKey);
  }

  @override
  Future<void> clearOrganizationId() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(organizationKey);
  }

  @override
  Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(tokenKey);
  }

  @override
  Future<void> clearUserJson() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(userKey);
  }

  @override
  Future<int?> readOrganizationId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt(organizationKey);
  }

  @override
  Future<String?> readToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(tokenKey);
  }

  @override
  Future<String?> readUserJson() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(userKey);
  }

  @override
  Future<void> writeOrganizationId(int organizationId) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(organizationKey, organizationId);
  }

  @override
  Future<void> writeToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(tokenKey, token);
  }

  @override
  Future<void> writeUserJson(String userJson) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(userKey, userJson);
  }
}
