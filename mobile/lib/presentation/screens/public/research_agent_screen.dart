import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/core/citations/citation_launcher.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/l10n/research_ui_strings.dart';
import 'package:wsa_enterprise/presentation/screens/public/research_source_viewer_screen.dart';
import 'package:wsa_enterprise/presentation/widgets/public_async_body.dart';

class ResearchAgentScreen extends StatefulWidget {
  const ResearchAgentScreen({
    super.key,
    required this.client,
    this.citationLauncher,
  });

  final ApiClient client;
  final CitationLauncher? citationLauncher;

  @override
  State<ResearchAgentScreen> createState() => _ResearchAgentScreenState();
}

class _ResearchAgentScreenState extends State<ResearchAgentScreen> {
  final questionController = TextEditingController();
  late final CitationLauncher _launcher =
      widget.citationLauncher ?? CitationLauncher();
  ResearchAgentResult? result;
  String? error;
  String? citationMessage;
  bool loading = false;

  @override
  void dispose() {
    questionController.dispose();
    super.dispose();
  }

  String get _uiLang => widget.client.languageState.uiLocale;

  Future<void> submit() async {
    final question = questionController.text.trim();
    if (question.isEmpty) return;
    setState(() {
      loading = true;
      error = null;
      citationMessage = null;
    });
    try {
      result = await widget.client.publicApi.researchQuery(question);
    } on ApiException catch (e) {
      error = e.isNetworkFailure ? ArStrings.networkError : e.toString();
      result = null;
    } catch (_) {
      error = ArStrings.networkError;
      result = null;
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  void _openInternalViewer(ResearchCitation citation) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => ResearchSourceViewerScreen(
          citation: citation,
          uiLang: _uiLang,
          answer: result?.canonicalAnswerText,
          citationLauncher: _launcher,
        ),
      ),
    );
  }

  Future<void> _openOriginalSource(ResearchCitation citation) async {
    final launch = await _launcher.openDirectUrl(citation.url);
    if (!mounted) return;
    setState(() {
      citationMessage = launch.ok
          ? null
          : ResearchUiStrings.citationUnavailable(_uiLang);
    });
  }

  @override
  Widget build(BuildContext context) {
    final current = result;
    final answerText = current?.canonicalAnswerText;
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(ArStrings.researchAgent),
              const SizedBox(height: 8),
              TextField(
                controller: questionController,
                decoration:
                    const InputDecoration(labelText: ArStrings.askQuestion),
                minLines: 2,
                maxLines: 4,
              ),
              const SizedBox(height: 8),
              FilledButton(
                onPressed: loading ? null : submit,
                child: Text(loading ? ArStrings.loading : ArStrings.submit),
              ),
            ],
          ),
        ),
        Expanded(
          child: PublicAsyncBody(
            loading: loading,
            error: error,
            empty: !loading && error == null && current == null,
            emptyMessage: 'أدخل سؤالاً لطلب إجابة من وكيل البحث في الخادم.',
            onRetry: submit,
            child: current == null
                ? const SizedBox.shrink()
                : ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      Text('السؤال',
                          style: Theme.of(context).textTheme.titleMedium),
                      Text(current.question),
                      const SizedBox(height: 12),
                      if (current.status != null) ...[
                        Text(
                          '${ResearchUiStrings.status(_uiLang)}: ${current.status}',
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        const SizedBox(height: 8),
                      ],
                      if (current.language != null &&
                          current.language!.isNotEmpty) ...[
                        Text(
                          '${ResearchUiStrings.answerLanguage(_uiLang)}: ${current.language}',
                          key: const Key('research-answer-language'),
                        ),
                        const SizedBox(height: 8),
                      ],
                      if (current.insufficientEvidence) ...[
                        Text(
                          ArStrings.insufficientEvidence,
                          key: const Key('research-insufficient'),
                        ),
                        const SizedBox(height: 12),
                      ],
                      Text(ResearchUiStrings.answer(_uiLang),
                          style: Theme.of(context).textTheme.titleMedium),
                      Text(
                        answerText ?? ArStrings.insufficientEvidence,
                        key: const Key('research-canonical-answer'),
                      ),
                      const SizedBox(height: 12),
                      if (current.limitations.isNotEmpty) ...[
                        Text(ResearchUiStrings.limitations(_uiLang),
                            style: Theme.of(context).textTheme.titleMedium),
                        for (final item in current.limitations)
                          Text('• $item'),
                        const SizedBox(height: 12),
                      ],
                      if (current.uncertainty != null &&
                          current.uncertainty!.isNotEmpty) ...[
                        Text(ResearchUiStrings.uncertainty(_uiLang),
                            style: Theme.of(context).textTheme.titleMedium),
                        Text(current.uncertainty!,
                            key: const Key('research-uncertainty')),
                        const SizedBox(height: 12),
                      ],
                      if (current.conflicts.isNotEmpty) ...[
                        Text(ResearchUiStrings.conflicts(_uiLang),
                            style: Theme.of(context).textTheme.titleMedium),
                        for (final item in current.conflicts)
                          Text('• $item', key: const Key('research-conflict')),
                        const SizedBox(height: 12),
                      ],
                      if (current.claims.isNotEmpty) ...[
                        Text(ResearchUiStrings.claims(_uiLang),
                            style: Theme.of(context).textTheme.titleMedium),
                        for (final claim in current.claims)
                          Text(
                            [
                              claim.claimText ?? claim.claimId ?? 'claim',
                              if (claim.questionClaimId != null)
                                'question_claim_id=${claim.questionClaimId}',
                              if (claim.evidenceIds.isNotEmpty)
                                'evidence_ids=${claim.evidenceIds.join(',')}',
                            ].join(' · '),
                          ),
                        const SizedBox(height: 12),
                      ],
                      Text(ResearchUiStrings.sources(_uiLang),
                          style: Theme.of(context).textTheme.titleMedium),
                      if (current.citations.isEmpty)
                        const Text(ArStrings.noCitations),
                      for (final citation in current.citations)
                        ListTile(
                          key: Key(
                            'research-citation-${citation.citationId ?? citation.title}',
                          ),
                          title: Text(citation.title),
                          subtitle: Text([
                            if (citation.doi != null &&
                                citation.doi!.isNotEmpty)
                              'DOI: ${citation.doi}',
                            if (citation.evidenceId != null)
                              'evidence: ${citation.evidenceId}',
                            if (citation.sourceType != null)
                              citation.sourceType!,
                          ].join('\n')),
                          onTap: () => _openInternalViewer(citation),
                          trailing: citation.url == null ||
                                  citation.url!.trim().isEmpty
                              ? null
                              : TextButton(
                                  onPressed: () =>
                                      _openOriginalSource(citation),
                                  child: Text(
                                      ResearchUiStrings.openSource(_uiLang)),
                                ),
                        ),
                      if (citationMessage != null) ...[
                        const SizedBox(height: 8),
                        Text(citationMessage!,
                            style: const TextStyle(color: Colors.red)),
                      ],
                      const SizedBox(height: 12),
                      Text(ArStrings.evidence,
                          style: Theme.of(context).textTheme.titleMedium),
                      if (current.evidence.isEmpty) const Text(ArStrings.empty),
                      for (final item in current.evidence) Text('• $item'),
                    ],
                  ),
          ),
        ),
      ],
    );
  }
}
