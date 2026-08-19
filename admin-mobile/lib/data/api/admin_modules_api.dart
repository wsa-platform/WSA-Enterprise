import 'package:wsa_admin/data/api/http_client.dart';
import 'package:wsa_admin/data/api/m22_http_client.dart';
import 'package:wsa_admin/data/models/paginated_response.dart';

/// Non-recruitment admin module API — relocated from ModulesApi.
class AdminModulesApi {
  AdminModulesApi(HttpClient source) : http = M22HttpClient.from(source);

  final M22HttpClient http;

  String paginatedPath(String base, {int page = 1, int perPage = 25, Map<String, String>? params}) {
    final query = <String, String>{
      'page': '$page',
      'per_page': '$perPage',
      if (params != null) ...params,
    };
    final qs = query.entries.map((e) => '${e.key}=${Uri.encodeComponent(e.value)}').join('&');
    return '$base?$qs';
  }

  // ── Users & Access ──────────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> usersPage({
    String? search,
    int page = 1,
    int perPage = 25,
  }) async {
    final params = <String, String>{};
    if (search != null && search.isNotEmpty) params['search'] = search;
    return http.getPaginated(paginatedPath('/users', page: page, perPage: perPage, params: params));
  }

  Future<List<dynamic>> users({String? search, int page = 1}) async {
    final pageData = await usersPage(search: search, page: page);
    return pageData.data;
  }

  Future<Map<String, dynamic>> createUser(Map<String, dynamic> body) =>
      http.postJson('/users', body);

  Future<Map<String, dynamic>> updateUser(int id, Map<String, dynamic> body) =>
      http.patchJson('/users/$id', body);

  Future<void> deleteUser(int id) => http.delete('/users/$id');

  Future<PaginatedResponse<Map<String, dynamic>>> rolesPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/roles', page: page, perPage: perPage));

  Future<List<dynamic>> roles() async => (await rolesPage()).data;

  Future<Map<String, dynamic>> createRole(Map<String, dynamic> body) =>
      http.postJson('/roles', body);

  Future<Map<String, dynamic>> updateRole(int id, Map<String, dynamic> body) =>
      http.patchJson('/roles/$id', body);

  Future<void> deleteRole(int id) => http.delete('/roles/$id');

  Future<PaginatedResponse<Map<String, dynamic>>> permissionsPage({int page = 1, int perPage = 100}) =>
      http.getPaginated(paginatedPath('/permissions', page: page, perPage: perPage));

  Future<List<dynamic>> permissions() async => (await permissionsPage()).data;

  Future<Map<String, dynamic>> permissionsCatalog() => http.getJson('/permissions/catalog');

  Future<Map<String, dynamic>> createPermission(Map<String, dynamic> body) =>
      http.postJson('/permissions', body);

  Future<Map<String, dynamic>> updatePermission(int id, Map<String, dynamic> body) =>
      http.patchJson('/permissions/$id', body);

  Future<void> deletePermission(int id) => http.delete('/permissions/$id');

  Future<void> assignRole(int userId, int roleId) =>
      http.postJson('/users/$userId/roles', {'role_id': roleId});

  Future<void> unassignRole(int userId, int roleId) =>
      http.delete('/users/$userId/roles/$roleId');

  // ── Organization ────────────────────────────────────────────────

  Future<Map<String, dynamic>> organization() => http.getJson('/organization');

  Future<Map<String, dynamic>> updateOrganization(Map<String, dynamic> body) =>
      http.patchJson('/organization', body);

  Future<Map<String, dynamic>> organizationSettings() =>
      http.getJson('/organization/settings');

  Future<Map<String, dynamic>> updateOrganizationSettings(Map<String, dynamic> settings) =>
      http.putJson('/organization/settings', {'settings': settings});

  // ── User profile & preferences ──────────────────────────────────

  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> body) =>
      http.patchJson('/user', body);

  Future<Map<String, dynamic>> userSettings() => http.getJson('/user/settings');

  Future<Map<String, dynamic>> updateUserSettings(Map<String, dynamic> settings) =>
      http.putJson('/user/settings', {'settings': settings});

  // ── Catalog / Products ──────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> productsPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/catalog/products', page: page, perPage: perPage));

  Future<List<dynamic>> products({int page = 1}) async => (await productsPage(page: page)).data;

  Future<List<dynamic>> categories() => http.getList('/catalog/categories');

  Future<Map<String, dynamic>> createProduct(Map<String, dynamic> body) =>
      http.postJson('/catalog/products', body);

  Future<Map<String, dynamic>> updateProduct(int id, Map<String, dynamic> body) =>
      http.putJson('/catalog/products/$id', body);

  Future<void> deleteProduct(int id) => http.delete('/catalog/products/$id');

  Future<Map<String, dynamic>> createCategory(Map<String, dynamic> body) =>
      http.postJson('/catalog/categories', body);

  Future<List<dynamic>> productImages(int productId) =>
      http.getList('/catalog/products/$productId/images');

  Future<Map<String, dynamic>> uploadProductImage(
    int productId, {
    required List<int> bytes,
    required String filename,
  }) =>
      http.postMultipart(
        '/catalog/products/$productId/images',
        fieldName: 'file',
        bytes: bytes,
        filename: filename,
      );

  Future<void> deleteProductImage(int productId, int imageId) =>
      http.delete('/catalog/products/$productId/images/$imageId');

  // ── Crops & Agriculture ─────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> cropTypesPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/crop/types', page: page, perPage: perPage));

  Future<List<dynamic>> cropTypes({int page = 1}) async => (await cropTypesPage(page: page)).data;

  Future<List<dynamic>> cropVarieties() => http.getList('/crop/varieties');

  Future<List<dynamic>> farms() => http.getList('/farm/farms');

  Future<Map<String, dynamic>> createCropType(Map<String, dynamic> body) =>
      http.postJson('/crop/types', body);

  Future<Map<String, dynamic>> updateCropType(int id, Map<String, dynamic> body) =>
      http.putJson('/crop/types/$id', body);

  Future<void> deleteCropType(int id) => http.delete('/crop/types/$id');

  // ── Content / CMS ───────────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> libraryItemsPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/library/items', page: page, perPage: perPage));

  Future<List<dynamic>> libraryItems({int page = 1}) async => (await libraryItemsPage(page: page)).data;

  Future<List<dynamic>> libraryCategories() => http.getList('/library/categories');

  Future<List<dynamic>> libraryTags() => http.getList('/library/tags');

  Future<Map<String, dynamic>> createLibraryItem(Map<String, dynamic> body) =>
      http.postJson('/library/items', body);

  Future<Map<String, dynamic>> updateLibraryItem(int id, Map<String, dynamic> body) =>
      http.putJson('/library/items/$id', body);

  Future<void> deleteLibraryItem(int id) => http.delete('/library/items/$id');

  Future<List<dynamic>> trainingCourses() => http.getList('/training/courses');

  Future<Map<String, dynamic>> uploadMedia({
    required List<int> bytes,
    required String filename,
    String context = 'general',
  }) =>
      http.postMultipart(
        '/media/uploads',
        fieldName: 'file',
        bytes: bytes,
        filename: filename,
        fields: {'context': context},
      );

  Future<void> deleteMedia(int id) => http.delete('/media/uploads/$id');

  // ── Marketing ───────────────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> campaignsPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/marketing/campaigns', page: page, perPage: perPage));

  Future<List<dynamic>> campaigns({int page = 1}) async => (await campaignsPage(page: page)).data;

  Future<Map<String, dynamic>> createCampaign(Map<String, dynamic> body) =>
      http.postJson('/marketing/campaigns', body);

  Future<Map<String, dynamic>> updateCampaign(int id, Map<String, dynamic> body) =>
      http.patchJson('/marketing/campaigns/$id', body);

  Future<void> deleteCampaign(int id) => http.delete('/marketing/campaigns/$id');

  Future<List<dynamic>> marketingSegments() => http.getList('/marketing/segments');

  Future<List<dynamic>> marketingTemplates() => http.getList('/marketing/templates');

  // ── AI Services ─────────────────────────────────────────────────

  Future<Map<String, dynamic>> aiProvider() => http.getJson('/ai/provider');

  Future<Map<String, dynamic>> aiUsage() => http.getJson('/ai/usage');

  Future<PaginatedResponse<Map<String, dynamic>>> aiRequestsPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/ai/requests', page: page, perPage: perPage));

  Future<List<dynamic>> aiRequests({int page = 1}) async => (await aiRequestsPage(page: page)).data;

  Future<List<dynamic>> aiConversations() => http.getList('/ai/assistant/conversations');

  // ── Notifications ───────────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> notificationsPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/notifications', page: page, perPage: perPage));

  Future<List<dynamic>> notifications({int page = 1}) async => (await notificationsPage(page: page)).data;

  Future<Map<String, dynamic>> createNotification(Map<String, dynamic> body) =>
      http.postJson('/notifications', body);

  Future<Map<String, dynamic>> readNotification(int id) =>
      http.postJson('/notifications/$id/read', {});

  Future<Map<String, dynamic>> markAllNotificationsRead() =>
      http.postJson('/notifications/read-all', {});

  // ── Communications ──────────────────────────────────────────────

  Future<Map<String, dynamic>> communicationsInbox({int page = 1, int perPage = 25, String? source}) {
    final params = <String, String>{'page': '$page', 'per_page': '$perPage'};
    if (source != null && source.isNotEmpty) params['source'] = source;
    return http.getJson(paginatedPath('/communications/inbox', page: page, perPage: perPage, params: params));
  }

  Future<PaginatedResponse<Map<String, dynamic>>> communicationMessagesPage({
    int page = 1,
    int perPage = 25,
    String? status,
  }) {
    final params = <String, String>{'page': '$page', 'per_page': '$perPage'};
    if (status != null && status.isNotEmpty) params['status'] = status;
    return http.getPaginated(paginatedPath('/communications/messages', page: page, perPage: perPage, params: params));
  }

  Future<PaginatedResponse<Map<String, dynamic>>> communicationDraftsPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/communications/drafts', page: page, perPage: perPage));

  Future<Map<String, dynamic>> communicationMessage(int id) => http.getJson('/communications/messages/$id');

  Future<Map<String, dynamic>> composeMessage(Map<String, dynamic> body) =>
      http.postJson('/communications/messages', body);

  Future<Map<String, dynamic>> updateMessage(int id, Map<String, dynamic> body) =>
      http.patchJson('/communications/messages/$id', body);

  Future<void> deleteMessage(int id) => http.delete('/communications/messages/$id');

  Future<Map<String, dynamic>> previewMessage(Map<String, dynamic> body) =>
      http.postJson('/communications/messages', {...body, 'preview_only': true});

  Future<Map<String, dynamic>> sendMessage(int id, {Map<String, dynamic>? contact}) =>
      http.postJson('/communications/messages/$id/send', contact ?? {});

  Future<List<Map<String, dynamic>>> searchContacts(String query, {int limit = 10}) async {
    final response = await http.getJson('/communications/contacts/search?q=${Uri.encodeQueryComponent(query)}&limit=$limit');
    return (response['data'] as List<dynamic>? ?? []).map((r) => Map<String, dynamic>.from(r as Map)).toList();
  }

  Future<PaginatedResponse<Map<String, dynamic>>> contactsPage({
    String? search,
    int page = 1,
    int perPage = 25,
  }) async {
    final params = <String, String>{};
    if (search != null && search.isNotEmpty) params['search'] = search;
    return http.getPaginated(paginatedPath('/communications/contacts', page: page, perPage: perPage, params: params));
  }

  Future<Map<String, dynamic>> createContact(Map<String, dynamic> body) =>
      http.postJson('/communications/contacts', body);

  Future<Map<String, dynamic>> updateContact(int id, Map<String, dynamic> body) =>
      http.patchJson('/communications/contacts/$id', body);

  Future<void> deleteContact(int id) => http.delete('/communications/contacts/$id');

  Future<Map<String, dynamic>> communicationsProviders() => http.getJson('/communications/providers');

  Future<PaginatedResponse<Map<String, dynamic>>> mailingListsPage({int page = 1, int perPage = 20}) =>
      http.getPaginated(paginatedPath('/communications/mailing-lists', page: page, perPage: perPage));

  Future<List<dynamic>> mailingLists() async => (await mailingListsPage()).data;

  Future<Map<String, dynamic>> createMailingList(Map<String, dynamic> body) =>
      http.postJson('/communications/mailing-lists', body);

  Future<Map<String, dynamic>> updateMailingList(int id, Map<String, dynamic> body) =>
      http.patchJson('/communications/mailing-lists/$id', body);

  Future<void> deleteMailingList(int id) => http.delete('/communications/mailing-lists/$id');

  Future<Map<String, dynamic>> addMailingListMembers(int listId, List<Map<String, dynamic>> members) =>
      http.postJson('/communications/mailing-lists/$listId/members', {'members': members});

  Future<PaginatedResponse<Map<String, dynamic>>> mailingListMembersPage(int listId, {int page = 1, int perPage = 50}) =>
      http.getPaginated(paginatedPath('/communications/mailing-lists/$listId/members', page: page, perPage: perPage));

  Future<void> removeMailingListMember(int listId, int memberId) =>
      http.delete('/communications/mailing-lists/$listId/members/$memberId');

  // ── Analytics traffic ───────────────────────────────────────────

  Future<Map<String, dynamic>> analyticsTraffic({int days = 30}) =>
      http.getJson('/analytics/traffic?days=$days');

  Future<Map<String, dynamic>> recordAnalyticsEvent(Map<String, dynamic> body) =>
      http.postJson('/analytics/events', body);

  Future<Map<String, dynamic>> monitoringHealth() => http.getJson('/monitoring/health');

  Future<PaginatedResponse<Map<String, dynamic>>> monitoringLogsPage({
    String? level,
    String? search,
    int page = 1,
    int perPage = 25,
  }) async {
    final params = <String, String>{};
    if (level != null) params['level'] = level;
    if (search != null) params['search'] = search;
    return http.getPaginated(paginatedPath('/monitoring/logs', page: page, perPage: perPage, params: params));
  }

  Future<Map<String, dynamic>> aiAssistantMessage(int conversationId, String message) =>
      http.postJson('/ai/assistant/conversations/$conversationId/messages', {'message': message});

  Future<Map<String, dynamic>> createAiConversation({String? title, String domain = 'platform'}) =>
      http.postJson('/ai/assistant/conversations', {
        'domain': domain,
        'message': title ?? 'مرحباً',
        if (title != null) 'title': title,
      });

  // ── Audit ───────────────────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> auditLogsPage({
    String? action,
    int page = 1,
    int perPage = 25,
  }) async {
    final params = <String, String>{};
    if (action != null && action.isNotEmpty) params['action'] = action;
    return http.getPaginated(paginatedPath('/audit-logs', page: page, perPage: perPage, params: params));
  }

  Future<List<dynamic>> auditLogs({String? action, int page = 1}) async =>
      (await auditLogsPage(action: action, page: page)).data;

  // ── Reports ─────────────────────────────────────────────────────

  Future<Map<String, dynamic>> reportsOverview({int days = 30}) =>
      http.getJson('/reports/overview?days=$days');

  Future<Map<String, dynamic>> reportsSummary() => http.getJson('/reports/summary');

  Future<String> reportsExport({int days = 30}) =>
      http.getText('/reports/export?days=$days');

  // ── Monitoring ──────────────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> monitoringIncidentsPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/monitoring/incidents', page: page, perPage: perPage));

  Future<List<dynamic>> monitoringIncidents({int page = 1}) async =>
      (await monitoringIncidentsPage(page: page)).data;

  Future<Map<String, dynamic>> resolveIncident(int id) =>
      http.postJson('/monitoring/incidents/$id/resolve', {});

  // ── Inventory ───────────────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> inventoryPage({int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/inventory', page: page, perPage: perPage));

  Future<List<dynamic>> inventory() async => (await inventoryPage()).data;

  Future<PaginatedResponse<Map<String, dynamic>>> inventoryMovementsPage({
    int? productId,
    int page = 1,
    int perPage = 25,
  }) async {
    final params = <String, String>{};
    if (productId != null) params['product_id'] = '$productId';
    return http.getPaginated(paginatedPath('/inventory/movements', page: page, perPage: perPage, params: params));
  }

  Future<Map<String, dynamic>> adjustInventory(Map<String, dynamic> body) =>
      http.postJson('/inventory/adjustments', body);

  // ── Marketplace Admin ───────────────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> adminMarketListingsPage({
    String? status,
    int page = 1,
    int perPage = 25,
  }) async {
    final params = <String, String>{};
    if (status != null && status.isNotEmpty) params['status'] = status;
    return http.getPaginated(paginatedPath('/admin/market/listings', page: page, perPage: perPage, params: params));
  }

  Future<Map<String, dynamic>> approveMarketListing(int id, {String? reason}) =>
      http.postJson('/admin/market/listings/$id/approve', {if (reason != null) 'reason': reason});

  Future<Map<String, dynamic>> rejectMarketListing(int id, {String? reason}) =>
      http.postJson('/admin/market/listings/$id/reject', {if (reason != null) 'reason': reason});

  Future<Map<String, dynamic>> suspendMarketListing(int id, {String? reason}) =>
      http.postJson('/admin/market/listings/$id/suspend', {if (reason != null) 'reason': reason});

  Future<Map<String, dynamic>> reportsMarketplace({int days = 30}) =>
      http.getJson('/reports/marketplace?days=$days');
}
