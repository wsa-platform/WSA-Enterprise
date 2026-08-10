import 'package:wsa_enterprise/data/api/http_client.dart';
import 'package:wsa_enterprise/data/models/models.dart';

class PlatformApi {
  PlatformApi(this.http);

  final HttpClient http;

  Future<List<ApiOrganization>> organizations() async {
    final rows = await http.getList('/platform/organizations');
    return rows.map((row) => organizationFromJson(Map<String, dynamic>.from(row as Map))).toList();
  }

  Future<ApiDashboard> dashboard() async {
    final payload = await http.getJson('/dashboard');
    return dashboardFromJson(payload);
  }

  Future<Map<String, dynamic>> workflowSummary() => http.getJson('/platform/workflow-summary');

  Future<Map<String, dynamic>> me() => http.getJson('/platform/me');
}
