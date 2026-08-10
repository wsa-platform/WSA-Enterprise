/// Abstraction for persisting auth session data.
/// Production builds can swap in secure storage (Keychain / Keystore).
abstract class TokenStorage {
  Future<String?> readToken();
  Future<void> writeToken(String token);
  Future<void> clearToken();

  Future<int?> readOrganizationId();
  Future<void> writeOrganizationId(int organizationId);
  Future<void> clearOrganizationId();

  Future<String?> readUserJson();
  Future<void> writeUserJson(String userJson);
  Future<void> clearUserJson();

  Future<void> clearAll();
}
