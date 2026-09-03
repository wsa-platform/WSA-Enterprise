import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:wsa_enterprise/data/media/diagnosis_image.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/screens/public/library_public_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/plant_diagnosis_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/plant_production_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/research_agent_screen.dart';

import 'helpers/test_fakes.dart';

void main() {
  testWidgets('research agent shows backend answer', (tester) async {
    final client = testApiClient(
      httpClient: jsonClient({
        '/public/research-agent/query':
            jsonOk({'status': 'completed', 'answer': 'ok', 'citations': []}),
      }),
    );
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(body: ResearchAgentScreen(client: client)),
      ),
    );
    await tester.enterText(find.byType(TextField), 'ري القمح');
    await tester.tap(find.text(ArStrings.submit));
    await tester.pumpAndSettle();
    expect(find.text('ok'), findsOneWidget);
  });

  testWidgets(
      'diagnosis screen shows picker fallback without image_picker package',
      (tester) async {
    final client = testApiClient(httpClient: jsonClient({}));
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(body: PlantDiagnosisScreen(client: client)),
      ),
    );
    await tester.tap(find.text(ArStrings.pickGallery));
    await tester.pumpAndSettle();
    expect(find.text(ArStrings.imagePickerUnavailable), findsOneWidget);
    expect(find.text(ArStrings.diagnosisIndependent), findsWidgets);
  });

  testWidgets('diagnosis fake picker previews image', (tester) async {
    final png = validPngBytes();
    final payload = jsonOk({
      'status': 'diagnosed',
      'message': 'تحليل',
      'independent_of_research_agent': true,
      'observations': [
        {'description': 'اصفرار'},
      ],
      'candidates': [
        {'label': 'نقص نيتروجين', 'confidence_band': 'moderate'},
      ],
      'safety': {
        'statements': ['decision support'],
      },
      'additional_info_requests': [],
    });
    final client = testApiClient(
      httpClient: MockClient((request) async => payload),
      picker: FakeDiagnosisImagePicker(
        DiagnosisImageSelection(bytes: png, fileName: 'leaf.png'),
      ),
    );

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(body: PlantDiagnosisScreen(client: client)),
      ),
    );
    await tester.tap(find.text(ArStrings.pickCamera));
    await tester.pumpAndSettle();
    expect(find.byType(Image), findsOneWidget);
    await tester.ensureVisible(find.text(ArStrings.analyze));
    await tester.tap(find.text(ArStrings.analyze));
    await tester.pumpAndSettle();
    expect(find.textContaining('اصفرار'), findsWidgets);
    expect(find.textContaining('نقص نيتروجين'), findsWidgets);
  });

  testWidgets('plant production loads taxonomy categories from API',
      (tester) async {
    final client = testApiClient(
      httpClient: jsonClient({
        '/public/field-crops/taxonomy': jsonOk({
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
        }),
      }),
    );

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(body: PlantProductionScreen(client: client)),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('محاصيل الحقل'), findsOneWidget);
    await tester.tap(find.text('محاصيل الحقل'));
    await tester.pumpAndSettle();
    expect(find.text('محاصيل الحبوب'), findsOneWidget);
  });

  testWidgets('library screen retries after error', (tester) async {
    final client = testApiClient(httpClient: jsonClient({}));
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(body: LibraryPublicScreen(client: client)),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('not found').first, findsOneWidget);
    expect(find.text(ArStrings.retry), findsOneWidget);
  });
}

Uint8List validPngBytes() => Uint8List.fromList(
      base64Decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
      ),
    );
