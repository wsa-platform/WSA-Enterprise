/// Realistic Stage 5 fixtures aligned with P7-U1 dual-emit Crop contract
/// (root canonical fields + optional legacy compatibility keys).
Map<String, dynamic> stage5CanonicalFixture({
  String? status = 'completed',
  Object? answer = 'Scientific answer in English.',
  String? conciseSummary = 'Short English summary.',
  String language = 'en',
  double? confidence = 0.81,
  List<String>? limitations,
  Object? uncertainty,
  List<dynamic>? conflicts,
  Object? primaryAnswer,
  bool includeUserPresentation = true,
}) {
  final resolvedPrimary = includeUserPresentation
      ? (primaryAnswer is String
          ? (primaryAnswer.trim().isEmpty ? null : primaryAnswer.trim())
          : (answer is String && answer.trim().isNotEmpty ? answer : null))
      : null;
  return {
    'status': status,
    'stage': 5,
    'synthesis': {
      'performed': status == 'completed',
      'confidence': confidence,
      'language': language,
    },
    'answer': answer,
    'concise_summary': conciseSummary,
    if (includeUserPresentation)
      'user_presentation': {
        'primary_answer': resolvedPrimary,
        'human_status': resolvedPrimary == null ? 'insufficient' : 'answered',
        'user_notice_code':
            resolvedPrimary == null ? 'insufficient_direct_evidence' : null,
        'candidates': <Map<String, dynamic>>[],
        'sources': <Map<String, dynamic>>[],
      },
    'detailed_explanation': answer,
    'key_findings': ['Finding A'],
    'claims': [
      {
        'claim_id': 'claim-1',
        'claim_text': 'Wheat needs drainage',
        'question_claim_id': 'qc-1',
        'evidence_ids': ['ev-1'],
        'source_ids': ['src-1'],
        'validation_status': status,
        'claim_relationship': 'supported',
        'confidence': confidence,
      },
    ],
    'citations': [
      {
        'citation_id': 'cite-1',
        'source_id': 'src-1',
        'evidence_id': 'ev-1',
        'title': 'Wheat Study',
        'authors': ['Author'],
        'organization': 'Org',
        'doi': '10.1000/wheat',
        'url': 'https://example.org/paper',
        'publication_year': 2020,
        'source_type': 'journal',
      },
    ],
    'evidence_references': [
      {'evidence_id': 'ev-1', 'title': 'Wheat Study'},
    ],
    'confidence': confidence,
    'limitations': limitations ?? ['limited_geo_coverage'],
    'uncertainty': uncertainty,
    'conflicts': conflicts ?? const [],
    'language': language,
    'research_metadata': {'direct_evidence_gate': 'PASSED'},
    'observability': {'composer': 'test'},
    'query_understanding': {
      'original_question': 'How should wheat be irrigated?',
    },
  };
}

/// Legacy dual-emit shape: canonical root + legacy Crop profile keys.
Map<String, dynamic> stage5LegacyCompatibleFixture() {
  return {
    ...stage5CanonicalFixture(
      answer: 'Legacy-compatible scientific answer.',
      conciseSummary: 'Legacy summary',
      confidence: 0.55,
    ),
    'crop': {
      'id': 'wheat',
      'name': 'القمح',
      'category_id': 'grains',
    },
    'service_option': 'farming-needs',
    'title': 'زراعة واحتياجات محصول القمح',
    'load_state': 'library_complete',
    'sections': [
      {
        'key': 'soil',
        'title': 'التربة',
        'content': 'LEGACY_SECTION_CONTENT_ONLY',
      },
    ],
    'references': [
      {'title': 'Legacy Source', 'url': 'https://library.example/legacy'},
    ],
    'citations': [
      {
        'title': 'Legacy Source',
        'url': 'https://example.org/legacy-cite',
        'doi': null,
      },
    ],
  };
}
