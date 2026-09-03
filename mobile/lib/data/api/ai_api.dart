import 'package:wsa_enterprise/data/api/http_client.dart';
import 'package:wsa_enterprise/data/models/models.dart';

class AiApi {
  AiApi(this.http);

  final HttpClient http;

  Future<Map<String, dynamic>> provider() => http.getJson('/ai/provider');

  Future<ApiAiRequest> createRequest({
    required String requestType,
    required Map<String, dynamic> input,
  }) async {
    final payload = await http.postJson('/ai/requests', {
      'request_type': requestType,
      'input': input,
    });
    return aiRequestFromJson(payload);
  }

  Future<ApiAiRequest> fetchRequest(int id) async {
    final payload = await http.getJson('/ai/requests/$id');
    return aiRequestFromJson(payload);
  }

  Future<List<ApiAiRequest>> listRequests() async {
    final rows = await http.getList('/ai/requests');
    return rows
        .map((row) => aiRequestFromJson(Map<String, dynamic>.from(row as Map)))
        .toList();
  }

  Future<Map<String, dynamic>> usageSummary() => http.getJson('/ai/usage');
}
