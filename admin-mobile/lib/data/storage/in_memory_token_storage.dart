import 'package:wsa_admin/data/storage/token_storage.dart';

/// In-memory storage for unit tests.
class InMemoryTokenStorage implements TokenStorage {
  String? _token;
  int? _organizationId;
  String? _userJson;

  @override
  Future<void> clearAll() async {
    _token = null;
    _organizationId = null;
    _userJson = null;
  }

  @override
  Future<void> clearOrganizationId() async {
    _organizationId = null;
  }

  @override
  Future<void> clearToken() async {
    _token = null;
  }

  @override
  Future<void> clearUserJson() async {
    _userJson = null;
  }

  @override
  Future<int?> readOrganizationId() async => _organizationId;

  @override
  Future<String?> readToken() async => _token;

  @override
  Future<String?> readUserJson() async => _userJson;

  @override
  Future<void> writeOrganizationId(int organizationId) async {
    _organizationId = organizationId;
  }

  @override
  Future<void> writeToken(String token) async {
    _token = token;
  }

  @override
  Future<void> writeUserJson(String userJson) async {
    _userJson = userJson;
  }
}
