import 'package:flutter/material.dart';
import 'package:wsa_enterprise/core/citations/citation_launcher.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';
import 'package:wsa_enterprise/l10n/research_ui_strings.dart';

/// Internal WSA research/source view. Not a publisher replica.
class ResearchSourceViewerScreen extends StatelessWidget {
  const ResearchSourceViewerScreen({
    super.key,
    required this.citation,
    required this.uiLang,
    this.answer,
    this.citationLauncher,
  });

  final ResearchCitation citation;
  final String uiLang;
  final String? answer;
  final CitationLauncher? citationLauncher;

  @override
  Widget build(BuildContext context) {
    final originalUrl = citation.url?.trim();
    final hasOriginal = originalUrl != null && originalUrl.isNotEmpty;

    return Scaffold(
      appBar: AppBar(
        title: Text(ResearchUiStrings.sources(uiLang)),
      ),
      body: ListView(
        key: const Key('research-source-viewer'),
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            citation.title,
            key: const Key('research-source-viewer-title'),
            style: Theme.of(context).textTheme.titleLarge,
          ),
          if (citation.authors.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(citation.authors.join(', ')),
          ],
          if (citation.organization != null &&
              citation.organization!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(citation.organization!),
          ],
          if (citation.journal != null && citation.journal!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(citation.journal!),
          ],
          if (citation.publicationYear != null) ...[
            const SizedBox(height: 8),
            Text('${citation.publicationYear}'),
          ],
          if (citation.doi != null && citation.doi!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text('DOI: ${citation.doi}'),
          ],
          if (answer != null && answer!.trim().isNotEmpty) ...[
            const SizedBox(height: 16),
            Text(
              ResearchUiStrings.answer(uiLang),
              style: Theme.of(context).textTheme.titleMedium,
            ),
            Text(answer!, key: const Key('research-source-viewer-answer')),
          ],
          const SizedBox(height: 16),
          if (hasOriginal)
            FilledButton(
              key: const Key('research-source-viewer-original'),
              onPressed: () {
                (citationLauncher ?? CitationLauncher()).openDirectUrl(originalUrl);
              },
              child: Text(ResearchUiStrings.openSource(uiLang)),
            )
          else
            Text(
              ResearchUiStrings.citationUnavailable(uiLang),
              key: const Key('research-source-viewer-no-original'),
            ),
        ],
      ),
    );
  }
}
