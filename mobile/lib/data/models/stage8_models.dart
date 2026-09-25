class TaxonomyCrop {
  const TaxonomyCrop(
      {required this.id, required this.name, this.scientificName = ''});

  final String id;
  final String name;
  final String scientificName;

  factory TaxonomyCrop.fromJson(Map<String, dynamic> json) => TaxonomyCrop(
        id: json['id']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
        scientificName: json['scientific_name']?.toString() ?? '',
      );
}

class TaxonomyCategory {
  const TaxonomyCategory(
      {required this.id, required this.name, required this.crops});

  final String id;
  final String name;
  final List<TaxonomyCrop> crops;

  factory TaxonomyCategory.fromJson(Map<String, dynamic> json) =>
      TaxonomyCategory(
        id: json['id']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
        crops: (json['crops'] as List<dynamic>? ?? const [])
            .whereType<Map>()
            .map((row) => TaxonomyCrop.fromJson(Map<String, dynamic>.from(row)))
            .toList(),
      );
}

class PlantProductionSection {
  const PlantProductionSection({
    required this.id,
    required this.name,
    required this.libraryCategoryIds,
  });

  final String id;
  final String name;
  final List<String> libraryCategoryIds;

  factory PlantProductionSection.fromJson(Map<String, dynamic> json) =>
      PlantProductionSection(
        id: json['id']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
        libraryCategoryIds:
            (json['library_category_ids'] as List<dynamic>? ?? const [])
                .map((item) => '$item')
                .toList(),
      );
}

class FieldCropTaxonomy {
  const FieldCropTaxonomy({
    required this.sections,
    required this.libraryCategories,
    required this.categories,
  });

  final List<PlantProductionSection> sections;
  final List<TaxonomyCategory> libraryCategories;
  final List<TaxonomyCategory> categories;

  factory FieldCropTaxonomy.fromJson(Map<String, dynamic> json) {
    return FieldCropTaxonomy(
      sections: (json['plant_production_sections'] as List<dynamic>? ??
              const [])
          .whereType<Map>()
          .map((row) =>
              PlantProductionSection.fromJson(Map<String, dynamic>.from(row)))
          .toList(),
      libraryCategories:
          (json['library_categories'] as List<dynamic>? ?? const [])
              .whereType<Map>()
              .map((row) => TaxonomyCategory.fromJson({
                    ...Map<String, dynamic>.from(row),
                    'crops': const [],
                  }))
              .toList(),
      categories: (json['categories'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((row) =>
              TaxonomyCategory.fromJson(Map<String, dynamic>.from(row)))
          .toList(),
    );
  }

  TaxonomyCategory? categoryById(String id) {
    for (final category in categories) {
      if (category.id == id) return category;
    }
    return null;
  }
}

class ResearchCitation {
  const ResearchCitation({
    required this.title,
    this.doi,
    this.url,
    this.sourceType,
    this.authors = const [],
    this.citationId,
    this.sourceId,
    this.evidenceId,
    this.organization,
    this.journal,
    this.publicationYear,
  });

  final String title;
  final String? doi;
  final String? url;
  final String? sourceType;
  final List<String> authors;
  final String? citationId;
  final String? sourceId;
  final String? evidenceId;
  final String? organization;
  final String? journal;
  final int? publicationYear;

  factory ResearchCitation.fromJson(Map<String, dynamic> json) =>
      ResearchCitation(
        title: json['title']?.toString() ?? '',
        doi: json['doi']?.toString(),
        url: json['url']?.toString(),
        sourceType: json['source_type']?.toString(),
        authors: (json['authors'] as List<dynamic>? ?? const [])
            .map((item) => '$item')
            .toList(),
        citationId: json['citation_id']?.toString(),
        sourceId: json['source_id']?.toString(),
        evidenceId: json['evidence_id']?.toString(),
        organization: json['organization']?.toString(),
        journal: json['journal']?.toString(),
        publicationYear: json['publication_year'] is num
            ? (json['publication_year'] as num).toInt()
            : int.tryParse('${json['publication_year'] ?? ''}'),
      );
}

class ResearchAnswerClaim {
  const ResearchAnswerClaim({
    this.claimId,
    this.claimText,
    this.evidenceIds = const [],
    this.sourceIds = const [],
    this.validationStatus,
    this.claimRelationship,
    this.confidence,
    this.questionClaimId,
  });

  final String? claimId;
  final String? claimText;
  final List<String> evidenceIds;
  final List<String> sourceIds;
  final String? validationStatus;
  final String? claimRelationship;
  final double? confidence;
  final String? questionClaimId;

  factory ResearchAnswerClaim.fromJson(Map<String, dynamic> json) =>
      ResearchAnswerClaim(
        claimId: json['claim_id']?.toString(),
        claimText: json['claim_text']?.toString(),
        evidenceIds: (json['evidence_ids'] as List<dynamic>? ?? const [])
            .map((item) => '$item')
            .toList(),
        sourceIds: (json['source_ids'] as List<dynamic>? ?? const [])
            .map((item) => '$item')
            .toList(),
        validationStatus: json['validation_status']?.toString(),
        claimRelationship: json['claim_relationship']?.toString(),
        confidence:
            json['confidence'] is num ? (json['confidence'] as num).toDouble() : null,
        questionClaimId: json['question_claim_id']?.toString(),
      );
}

class ResearchAgentResult {
  const ResearchAgentResult({
    required this.question,
    this.answer,
    this.primaryAnswer,
    this.conciseSummary,
    this.detailedExplanation,
    this.keyFindings = const [],
    this.confidence,
    this.limitations = const [],
    this.uncertainty,
    this.conflicts = const [],
    this.claims = const [],
    this.citations = const [],
    this.evidence = const [],
    this.evidenceReferences = const [],
    this.status,
    this.stage,
    this.language,
    this.insufficientEvidence = false,
    this.raw = const {},
  });

  final String question;
  final String? answer;
  final String? primaryAnswer;
  final String? conciseSummary;
  final String? detailedExplanation;
  final List<String> keyFindings;
  final double? confidence;
  final List<String> limitations;
  final String? uncertainty;
  final List<String> conflicts;
  final List<ResearchAnswerClaim> claims;
  final List<ResearchCitation> citations;
  final List<String> evidence;
  final List<Map<String, dynamic>> evidenceReferences;
  final String? status;
  final int? stage;
  final String? language;
  final bool insufficientEvidence;
  final Map<String, dynamic> raw;

  /// Authoritative final answer: user_presentation.primary_answer only.
  String? get canonicalAnswerText {
    final primary = primaryAnswer?.trim();
    if (primary != null && primary.isNotEmpty) return primary;
    return null;
  }

  factory ResearchAgentResult.fromJson(Map<String, dynamic> json,
      {required String fallbackQuestion}) {
    final understanding = json['query_understanding'];
    String question = fallbackQuestion;
    if (understanding is Map) {
      question = understanding['original_question']?.toString() ??
          understanding['normalized_question']?.toString() ??
          fallbackQuestion;
    }

    final citations = <ResearchCitation>[];
    final rawCitations = json['citations'];
    if (rawCitations is List) {
      for (final item in rawCitations) {
        if (item is Map) {
          citations
              .add(ResearchCitation.fromJson(Map<String, dynamic>.from(item)));
        }
      }
    }

    final claims = <ResearchAnswerClaim>[];
    final rawClaims = json['claims'];
    if (rawClaims is List) {
      for (final item in rawClaims) {
        if (item is Map) {
          claims.add(
              ResearchAnswerClaim.fromJson(Map<String, dynamic>.from(item)));
        }
      }
    }

    final evidence = <String>[];
    final evidenceReferences = <Map<String, dynamic>>[];
    final refs = json['evidence_references'];
    if (refs is List) {
      for (final item in refs) {
        if (item is Map) {
          final map = Map<String, dynamic>.from(item);
          evidenceReferences.add(map);
          final title = map['title'] ?? map['claim'] ?? map['evidence_id'];
          if (title != null) evidence.add('$title');
        } else if (item != null) {
          evidence.add('$item');
        }
      }
    }

    final limitations = (json['limitations'] as List<dynamic>? ?? const [])
        .map((item) => '$item')
        .toList();
    final keyFindings = (json['key_findings'] as List<dynamic>? ?? const [])
        .map((item) => '$item')
        .toList();
    final conflicts = <String>[];
    final rawConflicts = json['conflicts'];
    if (rawConflicts is List) {
      for (final item in rawConflicts) {
        if (item is String) {
          conflicts.add(item);
        } else if (item is Map) {
          final detail = item['detail'] ??
              item['relationship'] ??
              item['type'] ??
              item.toString();
          conflicts.add('$detail');
        } else if (item != null) {
          conflicts.add('$item');
        }
      }
    }

    final answerRaw = json['answer']?.toString();
    final concise = json['concise_summary']?.toString();
    String? primaryAnswer;
    final presentation = json['user_presentation'];
    if (presentation is Map) {
      final rawPrimary = presentation['primary_answer']?.toString();
      if (rawPrimary != null && rawPrimary.trim().isNotEmpty) {
        primaryAnswer = rawPrimary.trim();
      }
    }
    final status = json['status']?.toString();
    final confidenceRaw = json['confidence'] ??
        (json['synthesis'] is Map ? json['synthesis']['confidence'] : null);
    final confidence = confidenceRaw is num ? confidenceRaw.toDouble() : null;
    final language = json['language']?.toString() ??
        (json['synthesis'] is Map
            ? (json['synthesis'] as Map)['language']?.toString()
            : null);
    final stageRaw = json['stage'];
    final stage = stageRaw is num
        ? stageRaw.toInt()
        : int.tryParse('${stageRaw ?? ''}');
    final insufficient = primaryAnswer == null;

    return ResearchAgentResult(
      question: question,
      answer: (answerRaw != null && answerRaw.trim().isNotEmpty)
          ? answerRaw
          : null,
      primaryAnswer: primaryAnswer,
      conciseSummary:
          (concise != null && concise.trim().isNotEmpty) ? concise : null,
      detailedExplanation: json['detailed_explanation']?.toString(),
      keyFindings: keyFindings,
      confidence: confidence,
      limitations: limitations,
      uncertainty: json['uncertainty']?.toString(),
      conflicts: conflicts,
      claims: claims,
      citations: citations,
      evidence: evidence,
      evidenceReferences: evidenceReferences,
      status: status,
      stage: stage,
      language: language,
      insufficientEvidence: insufficient,
      raw: json,
    );
  }
}

class DiagnosisCandidate {
  const DiagnosisCandidate({
    required this.label,
    this.confidenceScore,
    this.confidenceBand,
  });

  final String label;
  final double? confidenceScore;
  final String? confidenceBand;

  factory DiagnosisCandidate.fromJson(Map<String, dynamic> json) =>
      DiagnosisCandidate(
        label: json['label']?.toString() ??
            json['name']?.toString() ??
            json['disease']?.toString() ??
            json['candidate']?.toString() ??
            'مرشح',
        confidenceScore: json['confidence_score'] is num
            ? (json['confidence_score'] as num).toDouble()
            : null,
        confidenceBand: json['confidence_band']?.toString(),
      );
}

class PlantDiagnosisResult {
  const PlantDiagnosisResult({
    required this.status,
    required this.message,
    this.observations = const [],
    this.candidates = const [],
    this.safetyStatements = const [],
    this.additionalInfo = const [],
    this.independentOfResearchAgent = true,
    this.raw = const {},
  });

  final String status;
  final String message;
  final List<String> observations;
  final List<DiagnosisCandidate> candidates;
  final List<String> safetyStatements;
  final List<String> additionalInfo;
  final bool independentOfResearchAgent;
  final Map<String, dynamic> raw;

  factory PlantDiagnosisResult.fromJson(Map<String, dynamic> json) {
    final observations = <String>[];
    final rawObservations = json['observations'];
    if (rawObservations is List) {
      for (final item in rawObservations) {
        if (item is Map) {
          observations.add(
            item['description']?.toString() ??
                item['label']?.toString() ??
                item.toString(),
          );
        }
      }
    }

    final candidates = (json['candidates'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((row) =>
            DiagnosisCandidate.fromJson(Map<String, dynamic>.from(row)))
        .toList();

    final safety = json['safety'];
    final safetyStatements = <String>[];
    if (safety is Map && safety['statements'] is List) {
      safetyStatements
          .addAll((safety['statements'] as List).map((item) => '$item'));
    }

    final additional = <String>[];
    for (final item
        in json['additional_info_requests'] as List<dynamic>? ?? const []) {
      if (item is Map) {
        additional.add(item['prompt']?.toString() ??
            item['reason']?.toString() ??
            item.toString());
      }
    }

    return PlantDiagnosisResult(
      status: json['status']?.toString() ?? '',
      message: json['message']?.toString() ?? '',
      observations: observations,
      candidates: candidates,
      safetyStatements: safetyStatements,
      additionalInfo: additional,
      independentOfResearchAgent: json['independent_of_research_agent'] == true,
      raw: json,
    );
  }
}

class LibraryCropFile {
  const LibraryCropFile({
    required this.id,
    required this.title,
    this.extension = '',
    this.previewMode = 'download_only',
  });

  final int id;
  final String title;
  final String extension;
  final String previewMode;

  bool get isPdf => extension.toLowerCase() == 'pdf';

  factory LibraryCropFile.fromJson(Map<String, dynamic> json) =>
      LibraryCropFile(
        id: json['id'] is int
            ? json['id'] as int
            : int.tryParse('${json['id']}') ?? 0,
        title:
            json['title_ar']?.toString() ?? json['title']?.toString() ?? 'ملف',
        extension: json['extension']?.toString() ?? '',
        previewMode: json['preview_mode']?.toString() ?? 'download_only',
      );
}
