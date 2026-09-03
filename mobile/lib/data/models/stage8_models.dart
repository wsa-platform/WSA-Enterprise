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
  });

  final String title;
  final String? doi;
  final String? url;
  final String? sourceType;
  final List<String> authors;

  factory ResearchCitation.fromJson(Map<String, dynamic> json) =>
      ResearchCitation(
        title: json['title']?.toString() ?? '',
        doi: json['doi']?.toString(),
        url: json['url']?.toString(),
        sourceType: json['source_type']?.toString(),
        authors: (json['authors'] as List<dynamic>? ?? const [])
            .map((item) => '$item')
            .toList(),
      );
}

class ResearchAgentResult {
  const ResearchAgentResult({
    required this.question,
    this.answer,
    this.confidence,
    this.limitations = const [],
    this.citations = const [],
    this.evidence = const [],
    this.status,
    this.insufficientEvidence = false,
    this.raw = const {},
  });

  final String question;
  final String? answer;
  final double? confidence;
  final List<String> limitations;
  final List<ResearchCitation> citations;
  final List<String> evidence;
  final String? status;
  final bool insufficientEvidence;
  final Map<String, dynamic> raw;

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

    final evidence = <String>[];
    final refs = json['evidence_references'];
    if (refs is List) {
      for (final item in refs) {
        if (item is Map) {
          final title = item['title'] ?? item['claim'] ?? item['evidence_id'];
          if (title != null) evidence.add('$title');
        } else if (item != null) {
          evidence.add('$item');
        }
      }
    }

    final limitations = (json['limitations'] as List<dynamic>? ?? const [])
        .map((item) => '$item')
        .toList();
    final answer =
        json['answer']?.toString() ?? json['concise_summary']?.toString();
    final status = json['status']?.toString();
    final confidenceRaw = json['confidence'] ??
        (json['synthesis'] is Map ? json['synthesis']['confidence'] : null);
    final confidence = confidenceRaw is num ? confidenceRaw.toDouble() : null;
    final insufficient = status == 'insufficient_evidence' ||
        status == 'insufficient_verified_sources' ||
        (json['synthesis'] is Map &&
            json['synthesis']['performed'] == false &&
            answer == null) ||
        (answer == null || answer.isEmpty) && citations.isEmpty;

    return ResearchAgentResult(
      question: question,
      answer: (answer == null || answer.isEmpty) ? null : answer,
      confidence: confidence,
      limitations: limitations,
      citations: citations,
      evidence: evidence,
      status: status,
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
