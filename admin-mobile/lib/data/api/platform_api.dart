import 'package:wsa_admin/data/api/http_client.dart';

class PlatformApi {
  PlatformApi(this.http);

  final HttpClient http;

  Future<List<dynamic>> organizations() => http.getList('/platform/organizations');

  Future<Map<String, dynamic>> me() => http.getJson('/platform/me');

  Future<Map<String, dynamic>> accessSummary() => http.getJson('/platform/access-summary');

  Future<Map<String, dynamic>> workflowSummary() => http.getJson('/platform/workflow-summary');

  Future<Map<String, dynamic>> dashboard() => http.getJson('/dashboard');

  Future<Map<String, dynamic>> analyticsOverview() => http.getJson('/analytics/overview');

  Future<Map<String, dynamic>> marketingDashboard() => http.getJson('/marketing/dashboard');

  Future<Map<String, dynamic>> monitoringHealth() => http.getJson('/monitoring/health');
}
