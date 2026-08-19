import 'package:wsa_admin/data/api/http_client.dart';
import 'package:wsa_admin/data/api/m22_http_client.dart';
import 'package:wsa_admin/data/models/paginated_response.dart';

/// Job-Seeker / Recruitment API against the M21 `/job-seekers` endpoints.
class ModulesApi {
  ModulesApi(HttpClient source) : http = M22HttpClient.from(source);

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

  // ── Recruitment / Job Seekers ───────────────────────────────────

  Future<PaginatedResponse<Map<String, dynamic>>> jobSeekersPage({
    String? search,
    String? status,
    int page = 1,
    int perPage = 25,
  }) async {
    final params = <String, String>{};
    if (search != null && search.isNotEmpty) params['search'] = search;
    if (status != null && status.isNotEmpty) params['status'] = status;
    return http.getPaginated(paginatedPath('/job-seekers', page: page, perPage: perPage, params: params));
  }

  Future<Map<String, dynamic>> updateJobSeekerStatus(int id, String status, {String? notes}) =>
      http.patchJson('/job-seekers/$id/status', {'status': status, if (notes != null) 'notes': notes});

  Future<Map<String, dynamic>> jobSeekerShow(int id) => http.getJson('/job-seekers/$id');

  Future<PaginatedResponse<Map<String, dynamic>>> jobSeekerNotesPage(int id, {int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/job-seekers/$id/notes', page: page, perPage: perPage));

  Future<Map<String, dynamic>> addJobSeekerNote(int id, String body) =>
      http.postJson('/job-seekers/$id/notes', {'body': body, 'is_private': true});

  Future<PaginatedResponse<Map<String, dynamic>>> jobSeekerHistoryPage(int id, {int page = 1, int perPage = 25}) =>
      http.getPaginated(paginatedPath('/job-seekers/$id/history', page: page, perPage: perPage));

  Future<Map<String, dynamic>> reportsRecruitment({int days = 30}) =>
      http.getJson('/reports/recruitment?days=$days');
}
