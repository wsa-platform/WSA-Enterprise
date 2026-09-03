import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:wsa_enterprise/data/api/api_exception.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/screens/public/plant_diagnosis_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/research_agent_screen.dart';

import 'helpers/test_fakes.dart';

void main() {
  test('malformed JSON becomes ApiException and invents no citations',
      () async {
    final client = testHttpClient(MockClient((request) async {
      return http.Response('{not-json', 200,
          headers: {'content-type': 'application/json'});
    }));

    expect(
      () => client.getJson('/public/research-agent/query'),
      throwsA(isA<ApiException>()),
    );
  });

  test('HTTP 422 validation errors surface field messages', () async {
    final client = testHttpClient(MockClient((request) async {
      return http.Response(
        jsonEncode({
          'message': 'The given data was invalid.',
          'errors': {
            'query': ['The query field is required.']
          }
        }),
        422,
        headers: {'content-type': 'application/json'},
      );
    }));

    try {
      await client.postJson('/public/research-agent/query', {});
      fail('expected ApiException');
    } on ApiException catch (error) {
      expect(error.statusCode, 422);
      expect(error.toString(), contains('query'));
      expect(error.isNetworkFailure, isFalse);
    }
  });

  test('HTTP 401 maps to unauthorized without fabricating payload', () async {
    final client = testHttpClient(MockClient((request) async {
      return http.Response(jsonEncode({'message': 'Unauthenticated.'}), 401,
          headers: {'content-type': 'application/json'});
    }));

    expect(
      () => client.getJson('/user'),
      throwsA(predicate(
          (error) => error is ApiException && error.statusCode == 401)),
    );
  });

  test('timeouts become Arabic network failures', () async {
    final client = testHttpClient(MockClient((request) async {
      await Future<void>.delayed(const Duration(milliseconds: 30));
      return jsonOk({});
    }));

    expect(
      () => client.getJson('/public/field-crops/taxonomy',
          timeout: const Duration(milliseconds: 1)),
      throwsA(predicate((error) =>
          error is ApiException &&
          error.isNetworkFailure &&
          error.toString() == ArStrings.networkError)),
    );
  });

  test('research parser ignores extra fields and does not invent DOI', () {
    final result = ResearchAgentResult.fromJson({
      'status': 'completed',
      'answer': 'ري موثق',
      'file_path': 'library/secret.pdf',
      'password': 'nope',
      'citations': [
        {'title': 'Known source', 'url': 'https://doi.org/10.1000/known'},
        'not-a-map',
      ],
    }, fallbackQuestion: 'كيف أروي القمح؟');

    expect(result.citations.single.doi, isNull);
    expect(result.citations.single.url, 'https://doi.org/10.1000/known');
    expect(result.raw.containsKey('file_path'), isTrue);
  });

  test('library file parser never maps backend storage path', () {
    final file = LibraryCropFile.fromJson({
      'id': 9,
      'title_ar': 'دليل',
      'extension': 'pdf',
      'preview_mode': 'inline_browser',
      'file_path': 'library/crop-files/hidden.pdf',
      'file_disk': 'local',
    });
    expect(file.isPdf, isTrue);
    expect(file.title, 'دليل');
    expect(file.toString(), isNot(contains('library/crop-files')));
  });

  test('diagnosis parser stays independent when backend omits candidates', () {
    final result = PlantDiagnosisResult.fromJson({
      'status': 'diagnosed',
      'message': 'تحليل',
      'independent_of_research_agent': true,
      'observations': 'not-a-list',
      'candidates': null,
    });
    expect(result.independentOfResearchAgent, isTrue);
    expect(result.candidates, isEmpty);
    expect(result.observations, isEmpty);
  });

  test('public research API posts organization slug only', () async {
    late http.Request captured;
    final client = testApiClient(
      httpClient: MockClient((request) async {
        captured = request;
        return jsonOk({
          'status': 'insufficient_evidence',
          'answer': null,
          'citations': [],
        });
      }),
    );

    final result = await client.publicApi.researchQuery('كيف أزرع القمح؟');
    expect(jsonDecode(captured.body)['organization'], 'wsa-demo');
    expect(jsonDecode(captured.body)['query'], 'كيف أزرع القمح؟');
    expect(result.insufficientEvidence, isTrue);
    expect(result.citations, isEmpty);
  });

  testWidgets('research screen uses RTL and shows loading then error',
      (tester) async {
    final client = testApiClient(
      httpClient: MockClient((request) async {
        await Future<void>.delayed(const Duration(milliseconds: 40));
        return http.Response('not-json', 200);
      }),
    );

    await tester.pumpWidget(
      MaterialApp(
        home: Directionality(
          textDirection: TextDirection.rtl,
          child: Scaffold(body: ResearchAgentScreen(client: client)),
        ),
      ),
    );

    expect(Directionality.of(tester.element(find.byType(ResearchAgentScreen))),
        TextDirection.rtl);
    expect(find.text(ArStrings.submit), findsOneWidget);

    await tester.enterText(find.byType(TextField), 'ري القمح');
    await tester.tap(find.text(ArStrings.submit));
    await tester.pump();
    expect(find.byType(CircularProgressIndicator), findsOneWidget);
    await tester.pumpAndSettle();
    expect(find.text('Unable to complete the request.'), findsOneWidget);
  });

  testWidgets('diagnosis screen keeps independence copy in RTL',
      (tester) async {
    final client = testApiClient(httpClient: jsonClient({}));
    await tester.pumpWidget(
      MaterialApp(
        home: Directionality(
          textDirection: TextDirection.rtl,
          child: Scaffold(body: PlantDiagnosisScreen(client: client)),
        ),
      ),
    );

    expect(find.text(ArStrings.diagnosisIndependent), findsWidgets);
    expect(find.text(ArStrings.pickGallery), findsOneWidget);
  });
}
