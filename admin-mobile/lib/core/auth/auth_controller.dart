import 'package:flutter/foundation.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthController extends ChangeNotifier {
  AuthController(this.client);

  final ApiClient client;

  AuthStatus status = AuthStatus.unknown;
  bool permissionsLoaded = false;
  String? bootstrapError;
  bool permissionDenied = false;

  bool get isAuthenticated => status == AuthStatus.authenticated;
  bool get hasAdminAccess => client.hasAdminAccess;

  Future<void> bootstrap() async {
    status = AuthStatus.unknown;
    bootstrapError = null;
    permissionDenied = false;
    permissionsLoaded = false;
    notifyListeners();

    try {
      await client.restoreSession();
      if (client.token == null) {
        status = AuthStatus.unauthenticated;
      } else {
        status = AuthStatus.authenticated;
        permissionsLoaded = true;
        permissionDenied = !client.hasAdminAccess;
      }
    } on ApiException catch (error) {
      bootstrapError = error.toString();
      status = AuthStatus.unauthenticated;
    } catch (error) {
      bootstrapError = error is ApiException ? error.toString() : Ar.connectionError;
      status = AuthStatus.unauthenticated;
    }

    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    await client.login(email, password);
    status = AuthStatus.authenticated;
    permissionsLoaded = true;
    permissionDenied = !client.hasAdminAccess;
    notifyListeners();
  }

  Future<void> loginWithPayload(Map<String, dynamic> payload) async {
    await client.applyAuthPayload(payload);
    status = AuthStatus.authenticated;
    permissionsLoaded = true;
    permissionDenied = !client.hasAdminAccess;
    notifyListeners();
  }

  Future<void> logout() async {
    await client.logout();
    status = AuthStatus.unauthenticated;
    permissionsLoaded = false;
    permissionDenied = false;
    notifyListeners();
  }

  Future<void> onOrganizationChanged() async {
    permissionsLoaded = false;
    notifyListeners();
    try {
      await client.refreshPermissions();
      permissionDenied = !client.hasAdminAccess;
    } finally {
      permissionsLoaded = true;
      notifyListeners();
    }
  }

  void handleUnauthorized() {
    status = AuthStatus.unauthenticated;
    permissionsLoaded = false;
    permissionDenied = false;
    notifyListeners();
  }
}
