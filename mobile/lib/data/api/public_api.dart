import 'package:wsa_enterprise/config/app_config.dart';
import 'package:wsa_enterprise/data/api/http_client.dart';
import 'package:wsa_enterprise/data/media/diagnosis_image.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';

class PublicPlatformApi {
  PublicPlatformApi(this.http, {AppConfig config = AppConfig.current})
      : publicOrganizationSlug = config.publicOrganizationSlug,
        _researchTimeout = config.researchTimeout,
        _diagnosisTimeout = config.diagnosisTimeout;

  final HttpClient http;
  final String publicOrganizationSlug;
  final Duration _researchTimeout;
  final Duration _diagnosisTimeout;

  Map<String, String> get _orgQuery => {'organization': publicOrganizationSlug};

  Future<Map<String, dynamic>> serviceCatalog() =>
      http.getJson('/public/services');

  Future<FieldCropTaxonomy> taxonomy() async {
    final payload = await http.getJson('/public/field-crops/taxonomy');
    return FieldCropTaxonomy.fromJson(payload);
  }

  Future<List<dynamic>> publishedLibraryItems({String? locale}) => http.getList(
        '/public/library/items',
        query: {
          ..._orgQuery,
          if (locale != null) 'locale': locale,
        },
      );

  Future<List<LibraryCropFile>> cropFiles({
    required String categoryId,
    required String cropId,
    required String section,
  }) async {
    final rows = await http.getList(
      '/public/library/crop-files',
      query: {
        ..._orgQuery,
        'plant_production_category_id': categoryId,
        'field_crop_id': cropId,
        'library_file_section': section,
      },
    );
    return rows
        .whereType<Map>()
        .map((row) => LibraryCropFile.fromJson(Map<String, dynamic>.from(row)))
        .toList();
  }

  String cropFileContentPath(int fileId) =>
      '/public/library/crop-files/$fileId/content?organization=$publicOrganizationSlug';

  Future<List<int>> cropFileContent(int fileId) => http.getBytes(
        '/public/library/crop-files/$fileId/content',
        query: _orgQuery,
      );

  Future<List<dynamic>> publishedTrainingCourses() => http.getList(
        '/public/training/courses',
        query: _orgQuery,
      );

  Future<List<dynamic>> marketListings() =>
      http.getList('/public/market/listings');

  Future<List<dynamic>> marketCategories() =>
      http.getList('/public/market/categories');

  Future<ResearchAgentResult> researchQuery(String query,
      {Map<String, dynamic>? extra}) async {
    final payload = await http.postJson(
      '/public/research-agent/query',
      {
        'organization': publicOrganizationSlug,
        'query': query,
        ...?extra,
      },
      timeout: _researchTimeout,
    );
    return ResearchAgentResult.fromJson(payload, fallbackQuestion: query);
  }

  Future<PlantDiagnosisResult> analyzeDiagnosis({
    required DiagnosisImageSelection image,
    String? plantName,
    String? notes,
    List<String>? symptoms,
  }) async {
    final payload = await http.postJson(
      '/public/plant-diagnosis/analyze',
      {
        'organization': publicOrganizationSlug,
        'image_base64': image.base64,
        'image_name': image.fileName,
        'image_mime': image.mimeType,
        if (plantName != null && plantName.isNotEmpty) 'plant_name': plantName,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
        if (symptoms != null && symptoms.isNotEmpty) 'symptoms': symptoms,
        'locale': 'ar',
      },
      timeout: _diagnosisTimeout,
    );
    return PlantDiagnosisResult.fromJson(payload);
  }

  Future<Map<String, dynamic>> diagnosisKnowledge(Map<String, dynamic> body) =>
      http.postJson('/public/plant-diagnosis/knowledge', body);
}
