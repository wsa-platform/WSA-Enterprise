import 'dart:typed_data';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:wsa_enterprise/data/api/api_exception.dart';
import 'package:wsa_enterprise/data/media/diagnosis_image.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';

import 'helpers/test_fakes.dart';

Uint8List pngMagic() =>
    Uint8List.fromList([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A, 0x00]);

void main() {
  test(
      'research result maps question answer sources evidence confidence limitations',
      () {
    final result = ResearchAgentResult.fromJson({
      'status': 'completed',
      'answer': 'ري القمح يحتاج جدولة موثقة.',
      'confidence': 0.72,
      'limitations': ['partial_evidence_support'],
      'citations': [
        {
          'title': 'Irrigation scheduling',
          'doi': '10.1000/xyz',
          'url': 'https://example.test/a',
          'source_type': 'journal'
        },
      ],
      'evidence_references': [
        {'title': 'Validated irrigation evidence'},
      ],
      'query_understanding': {'original_question': 'كيف أروي القمح؟'},
    }, fallbackQuestion: 'fallback');

    expect(result.question, 'كيف أروي القمح؟');
    expect(result.answer, contains('القمح'));
    expect(result.citations.single.doi, '10.1000/xyz');
    expect(result.evidence.single, contains('Validated'));
    expect(result.confidence, 0.72);
    expect(result.limitations, ['partial_evidence_support']);
    expect(result.insufficientEvidence, isFalse);
  });

  test('research result does not invent citations when backend omitted them',
      () {
    final result = ResearchAgentResult.fromJson({
      'status': 'insufficient_evidence',
      'answer': null,
      'citations': [],
    }, fallbackQuestion: 'سؤال');
    expect(result.citations, isEmpty);
    expect(result.insufficientEvidence, isTrue);
  });

  test(
      'diagnosis result maps observations candidates confidence safety additional info',
      () {
    final result = PlantDiagnosisResult.fromJson({
      'status': 'diagnosed',
      'message': 'قرار داعم فقط',
      'independent_of_research_agent': true,
      'observations': [
        {'description': 'بقع على الورق'},
      ],
      'candidates': [
        {
          'label': 'لفحة مبكرة',
          'confidence_score': 0.81,
          'confidence_band': 'high'
        },
      ],
      'safety': {
        'statements': ['Image analysis alone cannot provide 100% certainty.'],
      },
      'additional_info_requests': [
        {'prompt': 'حدد عمر الورقة'},
      ],
    });
    expect(result.observations.single, contains('بقع'));
    expect(result.candidates.single.confidenceBand, 'high');
    expect(result.safetyStatements, isNotEmpty);
    expect(result.additionalInfo.single, contains('عمر'));
    expect(result.independentOfResearchAgent, isTrue);
  });

  test('taxonomy parser maps backend catalog', () {
    final taxonomy = FieldCropTaxonomy.fromJson({
      'plant_production_sections': [
        {
          'id': 'field-crops',
          'name': 'محاصيل الحقل',
          'library_category_ids': ['grains']
        },
      ],
      'library_categories': [
        {'id': 'grains', 'name': 'محاصيل الحبوب'},
      ],
      'categories': [
        {
          'id': 'grains',
          'name': 'محاصيل الحبوب',
          'crops': [
            {
              'id': 'wheat',
              'name': 'القمح',
              'scientific_name': 'Triticum aestivum'
            },
          ],
        },
      ],
    });
    expect(taxonomy.sections.single.id, 'field-crops');
    expect(taxonomy.categoryById('grains')!.crops.single.scientificName,
        'Triticum aestivum');
  });

  test('image validator accepts png magic and rejects empty or huge payloads',
      () {
    const validator = DiagnosisImageValidator();
    validator.validate(
        DiagnosisImageSelection(bytes: pngMagic(), fileName: 'leaf.png'));

    expect(
      () => validator.validate(
          DiagnosisImageSelection(bytes: Uint8List(0), fileName: 'x.png')),
      throwsA(isA<DiagnosisImageValidationException>()),
    );
    expect(
      () => validator.validate(
        DiagnosisImageSelection(
            bytes: Uint8List(6 * 1024 * 1024),
            fileName: 'x.png',
            mimeType: 'image/png'),
      ),
      throwsA(isA<DiagnosisImageValidationException>()),
    );
    expect(
      () => validator.validate(
        DiagnosisImageSelection(
            bytes: Uint8List.fromList([0, 1, 2, 3, 4]),
            fileName: 'x.bin',
            mimeType: 'application/pdf'),
      ),
      throwsA(isA<DiagnosisImageValidationException>()),
    );
  });

  test('public research API posts backend contract and maps response',
      () async {
    final client = testApiClient(
      httpClient: jsonClient({
        '/public/research-agent/query': jsonOk({
          'status': 'completed',
          'answer': 'إجابة موثقة',
          'confidence': 0.5,
          'citations': [
            {'title': 'Source A', 'doi': '10.1/a'},
          ],
          'limitations': ['limited_location_context'],
          'query_understanding': {'original_question': 'ما ري الذرة؟'},
        }),
      }),
    );

    final result = await client.publicApi.researchQuery('ما ري الذرة؟');
    expect(result.answer, 'إجابة موثقة');
    expect(result.citations.single.title, 'Source A');
  });

  test('public diagnosis API posts image_base64 and stays independent',
      () async {
    final client = testApiClient(
      httpClient: jsonClient({
        '/public/plant-diagnosis/analyze': jsonOk({
          'status': 'diagnosed',
          'message': 'ok',
          'independent_of_research_agent': true,
          'observations': [],
          'candidates': [],
          'safety': {'statements': []},
          'additional_info_requests': [],
        }),
      }),
    );

    final result = await client.publicApi.analyzeDiagnosis(
      image: DiagnosisImageSelection(bytes: pngMagic(), fileName: 'leaf.png'),
    );
    expect(result.independentOfResearchAgent, isTrue);
  });

  test('library crop files API uses taxonomy filters', () async {
    final client = testApiClient(
      httpClient: jsonClient({
        '/public/library/crop-files': jsonOk({
          'data': [
            {
              'id': 9,
              'title_ar': 'دليل القمح',
              'extension': 'pdf',
              'preview_mode': 'inline_browser'
            },
          ],
        }),
      }),
    );

    final files = await client.publicApi.cropFiles(
      categoryId: 'grains',
      cropId: 'wheat',
      section: 'farming-needs',
    );
    expect(files.single.isPdf, isTrue);
    expect(files.single.id, 9);
  });

  test('http client maps transport failures to network errors', () async {
    final failing = testHttpClient(
      MockClient((request) async {
        throw http.ClientException('connection refused');
      }),
    );
    expect(
      () => failing.getJson('/health'),
      throwsA(predicate(
          (error) => error is ApiException && error.isNetworkFailure)),
    );
  });

  test('network exception is marked as network failure', () {
    final error = ApiException.network();
    expect(error.isNetworkFailure, isTrue);
    expect(error.toString(), contains('الشبكة'));
  });
}
