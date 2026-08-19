import 'package:wsa_admin/data/api/http_client.dart';
import 'package:wsa_admin/data/api/m22_http_client.dart';
import 'package:wsa_admin/data/models/paginated_response.dart';

class PlatformApi {
  PlatformApi(HttpClient source) : http = M22HttpClient.from(source);

  final M22HttpClient http;

  Future<List<dynamic>> organizations() => http.getList('/platform/organizations');

  Future<PaginatedResponse<Map<String, dynamic>>> adminOrganizationsPage({
    String? search,
    bool? isActive,
    int page = 1,
    int perPage = 25,
  }) async {
    final params = <String, String>{};
    if (search != null && search.isNotEmpty) params['search'] = search;
    if (isActive != null) params['is_active'] = isActive.toString();
    final qs = params.entries.map((e) => '${e.key}=${Uri.encodeComponent(e.value)}').join('&');
    final suffix = qs.isEmpty ? '' : '&$qs';
    return http.getPaginated('/platform/admin/organizations?page=$page&per_page=$perPage$suffix');
  }

  Future<Map<String, dynamic>> createAdminOrganization(Map<String, dynamic> body) =>
      http.postJson('/platform/admin/organizations', body);

  Future<Map<String, dynamic>> updateAdminOrganization(int id, Map<String, dynamic> body) =>
      http.patchJson('/platform/admin/organizations/$id', body);

  Future<Map<String, dynamic>> adminOrganization(int id) =>
      http.getJson('/platform/admin/organizations/$id');

  Future<List<dynamic>> adminOrganizationMembers(int orgId, {int page = 1, int perPage = 25}) =>
      http.getList('/platform/admin/organizations/$orgId/members?page=$page&per_page=$perPage');

  Future<Map<String, dynamic>> addAdminOrganizationMember(int orgId, Map<String, dynamic> body) =>
      http.postJson('/platform/admin/organizations/$orgId/members', body);

  Future<Map<String, dynamic>> updateAdminOrganizationMember(int orgId, int userId, Map<String, dynamic> body) =>
      http.patchJson('/platform/admin/organizations/$orgId/members/$userId', body);

  Future<void> removeAdminOrganizationMember(int orgId, int userId) =>
      http.delete('/platform/admin/organizations/$orgId/members/$userId');

  Future<Map<String, dynamic>> me() => http.getJson('/platform/me');

  Future<Map<String, dynamic>> accessSummary() => http.getJson('/platform/access-summary');

  Future<Map<String, dynamic>> workflowSummary() => http.getJson('/platform/workflow-summary');

  Future<Map<String, dynamic>> dashboard() => http.getJson('/dashboard');

  Future<Map<String, dynamic>> analyticsOverview() => http.getJson('/analytics/overview');

  Future<Map<String, dynamic>> marketingDashboard() => http.getJson('/marketing/dashboard');

  Future<Map<String, dynamic>> monitoringHealth() => http.getJson('/monitoring/health');
}
