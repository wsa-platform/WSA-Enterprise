import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/widgets/public_async_body.dart';

class ResearchAgentScreen extends StatefulWidget {
  const ResearchAgentScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<ResearchAgentScreen> createState() => _ResearchAgentScreenState();
}

class _ResearchAgentScreenState extends State<ResearchAgentScreen> {
  final questionController = TextEditingController();
  ResearchAgentResult? result;
  String? error;
  bool loading = false;

  @override
  void dispose() {
    questionController.dispose();
    super.dispose();
  }

  Future<void> submit() async {
    final question = questionController.text.trim();
    if (question.isEmpty) return;
    setState(() {
      loading = true;
      error = null;
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

  @override
  Widget build(BuildContext context) {
    final current = result;
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
                      if (current.insufficientEvidence) ...[
                        const Text(ArStrings.insufficientEvidence),
                        const SizedBox(height: 12),
                      ],
                      Text('الإجابة',
                          style: Theme.of(context).textTheme.titleMedium),
                      Text(current.answer ?? ArStrings.insufficientEvidence),
                      const SizedBox(height: 12),
                      Text(ArStrings.confidence,
                          style: Theme.of(context).textTheme.titleMedium),
                      Text(current.confidence == null
                          ? '—'
                          : current.confidence!.toStringAsFixed(2)),
                      const SizedBox(height: 12),
                      Text(ArStrings.sources,
                          style: Theme.of(context).textTheme.titleMedium),
                      if (current.citations.isEmpty)
                        const Text(ArStrings.noCitations),
                      for (final citation in current.citations)
                        ListTile(
                          title: Text(citation.title),
                          subtitle: Text([
                            if (citation.doi != null &&
                                citation.doi!.isNotEmpty)
                              'DOI: ${citation.doi}',
                            if (citation.url != null &&
                                citation.url!.isNotEmpty)
                              citation.url!,
                            if (citation.sourceType != null)
                              citation.sourceType!,
                          ].join('\n')),
                        ),
                      const SizedBox(height: 12),
                      Text(ArStrings.evidence,
                          style: Theme.of(context).textTheme.titleMedium),
                      if (current.evidence.isEmpty) const Text(ArStrings.empty),
                      for (final item in current.evidence) Text('• $item'),
                      const SizedBox(height: 12),
                      Text(ArStrings.limitations,
                          style: Theme.of(context).textTheme.titleMedium),
                      for (final item in current.limitations) Text('• $item'),
                    ],
                  ),
          ),
        ),
      ],
    );
  }
}
