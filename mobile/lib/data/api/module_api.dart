import 'package:wsa_enterprise/data/api/http_client.dart';

class ModuleApi {
  ModuleApi(this.http);

  final HttpClient http;

  Future<List<dynamic>> fetchList(String path) => http.getList(path);

  Future<Map<String, dynamic>> createRecord(String path, Map<String, dynamic> payload) =>
      http.postJson(path.split('?').first, payload);

  Future<Map<String, dynamic>> updateRecord(String path, int id, Map<String, dynamic> payload) =>
      http.putJson('${path.split('?').first}/$id', payload);

  Future<void> deleteRecord(String path, int id) => http.delete('${path.split('?').first}/$id');

  Future<Map<String, dynamic>> createDiagnosisRequest({
    required String reference,
    String? notes,
    int? cropTypeId,
  }) =>
      createRecord('/diagnosis/requests', {
        'reference': reference,
        if (notes != null) 'notes': notes,
        if (cropTypeId != null) 'crop_type_id': cropTypeId,
      });

  Future<List<dynamic>> searchLibrary(String query) => http.getList('/library/search?q=${Uri.encodeQueryComponent(query)}');
}
