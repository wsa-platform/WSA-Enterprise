import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:wsa_enterprise/core/citations/citation_launcher.dart';
import 'package:wsa_enterprise/core/language/client_language_state.dart';
import 'package:wsa_enterprise/core/language/supported_language.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/l10n/research_ui_strings.dart';
import 'package:wsa_enterprise/presentation/screens/public/research_agent_screen.dart';

import 'helpers/p7_u3_fixtures.dart';
import 'helpers/test_fakes.dart';

void main() {
  group('P7-U3 Stage 5 model', () {
    test('A canonical root answer parses', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(),
        fallbackQuestion: 'fallback',
      );
      expect(result.status, 'completed');
      expect(result.stage, 5);
      expect(result.answer, 'Scientific answer in English.');
      expect(result.conciseSummary, 'Short English summary.');
      expect(result.language, 'en');
      expect(result.canonicalAnswerText, 'Scientific answer in English.');
    });

    test('high claim/candidate text cannot substitute for null primary_answer',
        () {
      final result = ResearchAgentResult.fromJson({
        'status': 'completed',
        'answer': 'Non-empty raw synthesis.',
        'confidence': 0.40,
        'claims': [
          {
            'claim_id': 'c-high',
            'claim_text': 'High-confidence claim text.',
            'confidence': 0.90,
          },
        ],
        'user_presentation': {
          'primary_answer': null,
          'human_status': 'insufficient',
          'candidates': [
            {'result_id': 'c-high', 'answer': 'High-confidence claim text.'},
          ],
          'sources': [],
        },
      }, fallbackQuestion: 'q');
      expect(result.answer, 'Non-empty raw synthesis.');
      expect(result.confidence, 0.40);
      expect(result.canonicalAnswerText, isNull);
      expect(result.insufficientEvidence, isTrue);
    });

    test('B concise_summary is not a final-answer substitute', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(answer: null, primaryAnswer: null),
        fallbackQuestion: 'q',
      );
      expect(result.answer, isNull);
      expect(result.conciseSummary, 'Short English summary.');
      expect(result.canonicalAnswerText, isNull);
      expect(result.insufficientEvidence, isTrue);
    });

    test('C confidence parses', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(confidence: 0.81),
        fallbackQuestion: 'q',
      );
      expect(result.confidence, 0.81);
    });

    test('D limitations parse', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(limitations: ['limited_geo_coverage']),
        fallbackQuestion: 'q',
      );
      expect(result.limitations, ['limited_geo_coverage']);
    });

    test('E uncertainty parses', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(uncertainty: 'competing_sources'),
        fallbackQuestion: 'q',
      );
      expect(result.uncertainty, 'competing_sources');
    });

    test('F conflicts parse', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(conflicts: [
          {'detail': 'irrigation amounts differ'},
        ]),
        fallbackQuestion: 'q',
      );
      expect(result.conflicts.single, 'irrigation amounts differ');
    });

    test('G claims parse with IDs', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(),
        fallbackQuestion: 'q',
      );
      final claim = result.claims.single;
      expect(claim.claimId, 'claim-1');
      expect(claim.questionClaimId, 'qc-1');
      expect(claim.evidenceIds, ['ev-1']);
      expect(claim.claimText, 'Wheat needs drainage');
    });

    test('H evidence references parse', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(),
        fallbackQuestion: 'q',
      );
      expect(result.evidenceReferences.single['evidence_id'], 'ev-1');
      expect(result.evidence, isNotEmpty);
    });

    test('I citations parse', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(),
        fallbackQuestion: 'q',
      );
      final citation = result.citations.single;
      expect(citation.citationId, 'cite-1');
      expect(citation.url, 'https://example.org/paper');
      expect(citation.doi, '10.1000/wheat');
      expect(citation.evidenceId, 'ev-1');
      expect(citation.url, isNot(contains('google.com')));
    });

    test('S legacy-compatible response still parses', () {
      final result = ResearchAgentResult.fromJson(
        stage5LegacyCompatibleFixture(),
        fallbackQuestion: 'legacy q',
      );
      expect(result.answer, 'Legacy-compatible scientific answer.');
      expect(result.confidence, 0.55);
      expect(result.citations.single.title, 'Legacy Source');
      expect(result.raw.containsKey('sections'), isTrue);
    });

    test('T Home research response remains compatible', () {
      // Pre-P7 Home-shaped payload (no stage/claims/uncertainty).
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
      expect(result.canonicalAnswerText, isNull);
      expect(result.claims, isEmpty);
      expect(result.uncertainty, isNull);
      expect(result.conflicts, isEmpty);
      expect(result.insufficientEvidence, isTrue);
    });

    test('absent optional fields do not crash', () {
      final result = ResearchAgentResult.fromJson({
        'status': 'insufficient_evidence',
      }, fallbackQuestion: 'q');
      expect(result.answer, isNull);
      expect(result.canonicalAnswerText, isNull);
      expect(result.citations, isEmpty);
      expect(result.confidence, isNull);
      expect(result.limitations, isEmpty);
      expect(result.conflicts, isEmpty);
      expect(result.insufficientEvidence, isTrue);
    });

    test('Q French answer language metadata preserved', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(
          answer: 'Réponse scientifique en français.',
          language: 'fr',
        ),
        fallbackQuestion: 'q',
      );
      expect(result.language, 'fr');
      expect(result.canonicalAnswerText, 'Réponse scientifique en français.');
    });

    test('R Turkish answer language metadata preserved', () {
      final result = ResearchAgentResult.fromJson(
        stage5CanonicalFixture(
          answer: 'Türkçe bilimsel yanıt.',
          language: 'tr',
        ),
        fallbackQuestion: 'q',
      );
      expect(result.language, 'tr');
      expect(result.canonicalAnswerText, 'Türkçe bilimsel yanıt.');
    });
  });

  group('P7-U3 citation launcher', () {
    test('J direct citation URL opens unchanged', () async {
      Uri? launched;
      final launcher = CitationLauncher(
        launchFn: (uri, {LaunchMode mode = LaunchMode.platformDefault}) async {
          launched = uri;
          expect(mode, LaunchMode.externalApplication);
          return true;
        },
      );

      final result =
          await launcher.openDirectUrl('https://example.org/paper');
      expect(result.ok, isTrue);
      expect(launched.toString(), 'https://example.org/paper');
      expect(launched.toString(), isNot(contains('google.com')));
      expect(launched.toString(), isNot(contains('search')));
    });

    test('K invalid/missing citation URL fails gracefully', () async {
      var called = false;
      final launcher = CitationLauncher(
        launchFn: (uri, {LaunchMode mode = LaunchMode.platformDefault}) async {
          called = true;
          return true;
        },
      );

      expect((await launcher.openDirectUrl(null)).ok, isFalse);
      expect((await launcher.openDirectUrl('')).reason, 'missing_url');
      expect((await launcher.openDirectUrl('not a url')).ok, isFalse);
      expect((await launcher.openDirectUrl('ftp://example.org/x')).reason,
          'unsupported_scheme');
      expect(called, isFalse);
    });
  });

  group('P7-U3 Accept-Language', () {
    Future<String?> captureAcceptLanguage(ClientLanguageState state) async {
      String? header;
      final client = testApiClient(
        languageState: state,
        httpClient: MockClient((request) async {
          header = request.headers['Accept-Language'] ??
              request.headers['accept-language'];
          return jsonOk({
            'status': 'completed',
            'answer': 'ok',
            'language': 'en',
            'citations': [],
          });
        }),
      );
      await client.publicApi.researchQuery('wheat irrigation?');
      return header;
    }

    test('L Accept-Language is sent (default ar)', () async {
      final header = await captureAcceptLanguage(ClientLanguageState());
      expect(header, 'ar');
    });

    test('L Accept-Language en/fr/tr', () async {
      expect(
        await captureAcceptLanguage(
          ClientLanguageState(acceptLanguage: SupportedLanguage.english),
        ),
        'en',
      );
      expect(
        await captureAcceptLanguage(
          ClientLanguageState(acceptLanguage: SupportedLanguage.french),
        ),
        'fr',
      );
      expect(
        await captureAcceptLanguage(
          ClientLanguageState(acceptLanguage: SupportedLanguage.turkish),
        ),
        'tr',
      );
    });

    test('M explicit answer language overrides UI locale', () async {
      final state = ClientLanguageState(
        uiLocale: SupportedLanguage.arabic,
        acceptLanguage: SupportedLanguage.english,
      );
      expect(state.uiLocale, 'ar');
      expect(state.acceptLanguage, 'en');
      expect(await captureAcceptLanguage(state), 'en');
    });

    test('N UI locale does not rewrite Accept-Language after explicit set',
        () async {
      final state = ClientLanguageState(
        uiLocale: SupportedLanguage.arabic,
        acceptLanguage: SupportedLanguage.english,
      );
      state.setUiLocale(SupportedLanguage.french);
      expect(state.uiLocale, 'fr');
      expect(state.acceptLanguage, 'en');
      expect(await captureAcceptLanguage(state), 'en');
    });
  });

  group('P7-U3 UI vs answer language presentation', () {
    testWidgets(
        'O Arabic UI labels with English scientific answer (no rewrite)',
        (tester) async {
      final state = ClientLanguageState(
        uiLocale: SupportedLanguage.arabic,
        acceptLanguage: SupportedLanguage.english,
      );
      final client = testApiClient(
        languageState: state,
        httpClient: MockClient((request) async {
          expect(
            request.headers['Accept-Language'] ??
                request.headers['accept-language'],
            'en',
          );
          return jsonOk(stage5CanonicalFixture(
            answer: 'Scientific answer in English.',
            language: 'en',
          ));
        }),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: ResearchAgentScreen(
              client: client,
              citationLauncher: CitationLauncher(
                launchFn: (uri, {LaunchMode mode = LaunchMode.platformDefault}) async =>
                    true,
              ),
            ),
          ),
        ),
      );

      await tester.enterText(find.byType(TextField), 'wheat irrigation');
      await tester.tap(find.text(ArStrings.submit));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('research-canonical-answer')), findsOneWidget);
      expect(find.text('Scientific answer in English.'), findsOneWidget);
      expect(find.text('الإجابة'), findsOneWidget);
      expect(find.text('درجة الثقة'), findsNothing);
      expect(find.byKey(const Key('research-confidence')), findsNothing);
      expect(find.textContaining('en'), findsWidgets);
      // Must not invent Arabic scientific rewrite.
      expect(find.textContaining('إجابة علمية'), findsNothing);
    });

    testWidgets('P English UI labels with Arabic scientific answer',
        (tester) async {
      final state = ClientLanguageState(
        uiLocale: SupportedLanguage.english,
        acceptLanguage: SupportedLanguage.arabic,
      );
      final client = testApiClient(
        languageState: state,
        httpClient: MockClient((request) async {
          expect(
            request.headers['Accept-Language'] ??
                request.headers['accept-language'],
            'ar',
          );
          return jsonOk(stage5CanonicalFixture(
            answer: 'إجابة علمية بالعربية دون ترجمة محلية.',
            language: 'ar',
            confidence: 0.7,
          ));
        }),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: ResearchAgentScreen(client: client),
          ),
        ),
      );

      await tester.enterText(find.byType(TextField), 'سؤال');
      await tester.tap(find.text(ArStrings.submit));
      await tester.pumpAndSettle();

      expect(find.text('Answer'), findsOneWidget);
      expect(find.text('Confidence'), findsNothing);
      expect(find.byKey(const Key('research-confidence')), findsNothing);
      expect(
        find.text('إجابة علمية بالعربية دون ترجمة محلية.'),
        findsOneWidget,
      );
      expect(find.byKey(const Key('research-answer-language')), findsOneWidget);
    });

    testWidgets('screen opens citation URL via launcher without Google',
        (tester) async {
      Uri? launched;
      final client = testApiClient(
        httpClient: MockClient((request) async {
          return jsonOk(stage5CanonicalFixture());
        }),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: ResearchAgentScreen(
              client: client,
              citationLauncher: CitationLauncher(
                launchFn:
                    (uri, {LaunchMode mode = LaunchMode.platformDefault}) async {
                  launched = uri;
                  return true;
                },
              ),
            ),
          ),
        ),
      );

      await tester.enterText(find.byType(TextField), 'q');
      await tester.tap(find.text(ArStrings.submit));
      await tester.pumpAndSettle();

      final openSource = find.text(ResearchUiStrings.openSource('ar'));
      await tester.ensureVisible(openSource);
      await tester.pumpAndSettle();
      await tester.tap(openSource);
      await tester.pumpAndSettle();

      expect(launched.toString(), 'https://example.org/paper');
      expect(launched.toString(), isNot(contains('google')));
    });

    testWidgets('citation title opens internal viewer without launching URL',
        (tester) async {
      Uri? launched;
      final client = testApiClient(
        httpClient: MockClient((request) async {
          return jsonOk(stage5CanonicalFixture());
        }),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: ResearchAgentScreen(
              client: client,
              citationLauncher: CitationLauncher(
                launchFn:
                    (uri, {LaunchMode mode = LaunchMode.platformDefault}) async {
                  launched = uri;
                  return true;
                },
              ),
            ),
          ),
        ),
      );

      await tester.enterText(find.byType(TextField), 'q');
      await tester.tap(find.text(ArStrings.submit));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Wheat Study'));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('research-source-viewer')), findsOneWidget);
      expect(find.byKey(const Key('research-source-viewer-title')), findsOneWidget);
      expect(launched, isNull);
    });

    testWidgets('confidence is not rendered to the user', (tester) async {
      final client = testApiClient(
        httpClient: MockClient((request) async {
          return jsonOk(stage5CanonicalFixture(confidence: 0.82));
        }),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(body: ResearchAgentScreen(client: client)),
        ),
      );
      await tester.enterText(find.byType(TextField), 'q');
      await tester.tap(find.text(ArStrings.submit));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('research-confidence')), findsNothing);
      expect(find.text('درجة الثقة'), findsNothing);
      expect(find.text('0.82'), findsNothing);
      expect(find.text('82%'), findsNothing);
    });

    testWidgets('insufficient evidence shows status not as full conclusion',
        (tester) async {
      final client = testApiClient(
        httpClient: MockClient((request) async {
          return jsonOk({
            'status': 'insufficient_evidence',
            'answer': null,
            'concise_summary': null,
            'confidence': 0.1,
            'limitations': ['insufficient_verified_sources'],
            'uncertainty': 'insufficient_evidence',
            'conflicts': [],
            'citations': [],
            'language': 'en',
            'stage': 5,
          });
        }),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(body: ResearchAgentScreen(client: client)),
        ),
      );
      await tester.enterText(find.byType(TextField), 'q');
      await tester.tap(find.text(ArStrings.submit));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('research-insufficient')), findsOneWidget);
      expect(find.textContaining('insufficient_verified_sources'), findsOneWidget);
      expect(find.byKey(const Key('research-uncertainty')), findsOneWidget);
    });

    testWidgets(
        'raw answer is not the final answer when primary_answer is null',
        (tester) async {
      final client = testApiClient(
        httpClient: MockClient((request) async {
          return jsonOk({
            'status': 'completed',
            'answer': 'Raw ineligible synthesis that must stay hidden.',
            'concise_summary': 'Raw summary must stay hidden.',
            'confidence': 0.40,
            'citations': [
              {'title': 'Source', 'url': 'https://example.org/p'},
            ],
            'user_presentation': {
              'primary_answer': null,
              'human_status': 'insufficient',
              'user_notice_code': 'insufficient_direct_evidence',
              'candidates': [],
              'sources': [],
            },
            'language': 'en',
            'stage': 5,
          });
        }),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(body: ResearchAgentScreen(client: client)),
        ),
      );
      await tester.enterText(find.byType(TextField), 'q');
      await tester.tap(find.text(ArStrings.submit));
      await tester.pumpAndSettle();

      expect(find.text('Raw ineligible synthesis that must stay hidden.'),
          findsNothing);
      expect(find.text('Raw summary must stay hidden.'), findsNothing);
      expect(find.byKey(const Key('research-canonical-answer')), findsOneWidget);
      expect(find.text(ArStrings.insufficientEvidence), findsWidgets);
    });

    testWidgets(
        'primary_answer is displayed as the final answer at 0.50',
        (tester) async {
      final client = testApiClient(
        httpClient: MockClient((request) async {
          return jsonOk({
            'status': 'scientific_generated',
            'answer': 'Raw dump that must not be preferred.',
            'confidence': 0.50,
            'user_presentation': {
              'primary_answer': 'Authoritative final answer.',
              'human_status': 'answered',
              'user_notice_code': null,
              'candidates': [],
              'sources': [],
            },
            'language': 'en',
            'stage': 5,
          });
        }),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(body: ResearchAgentScreen(client: client)),
        ),
      );
      await tester.enterText(find.byType(TextField), 'q');
      await tester.tap(find.text(ArStrings.submit));
      await tester.pumpAndSettle();

      expect(find.text('Authoritative final answer.'), findsOneWidget);
      expect(find.text('Raw dump that must not be preferred.'), findsNothing);
    });
  });

  group('SupportedLanguage', () {
    test('normalizes ar/en/fr/tr and falls back to ar', () {
      expect(SupportedLanguage.normalize('EN-US'), 'en');
      expect(SupportedLanguage.normalize('fr_FR'), 'fr');
      expect(SupportedLanguage.normalize('tr'), 'tr');
      expect(SupportedLanguage.normalize('xx'), 'ar');
    });
  });
}
