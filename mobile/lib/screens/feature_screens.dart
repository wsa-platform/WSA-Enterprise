import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/data/models/models.dart';
import 'package:wsa_enterprise/domain/use_cases/poll_ai_request_use_case.dart';
import 'package:wsa_enterprise/screens/module_screens.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';
import 'package:wsa_enterprise/widgets/record_form.dart';

class DiagnosisScreen extends StatefulWidget {
  const DiagnosisScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<DiagnosisScreen> createState() => _DiagnosisScreenState();
}

class _DiagnosisScreenState extends State<DiagnosisScreen> {
  int reloadToken = 0;

  Future<void> submitRequest(Map<String, String> values) async {
    await widget.client.createDiagnosisRequest(
      reference: values['reference'] ??
          'DX-MOB-${DateTime.now().millisecondsSinceEpoch}',
      notes: values['notes'],
    );
    setState(() => reloadToken++);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        RecordForm(
          title: 'Submit diagnosis case',
          submitLabel: 'Submit case',
          fields: const [
            FormFieldConfig(
                name: 'reference', label: 'Reference', required: true),
            FormFieldConfig(
                name: 'notes',
                label: 'Symptom notes',
                maxLines: 3,
                initialValue: 'بقع بنية على أوراق الطماطم'),
          ],
          onSubmit: submitRequest,
        ),
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 16),
          child: Text(
            'Diagnosis outputs are agricultural decision support only.',
            style: TextStyle(fontSize: 12),
          ),
        ),
        Expanded(
          child: TabbedModuleScreen(
            key: ValueKey(
                'diagnosis-$reloadToken-${widget.client.organizationId}'),
            client: widget.client,
            tabs: diagnosisTabs,
          ),
        ),
      ],
    );
  }
}

class LibraryScreen extends StatefulWidget {
  const LibraryScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<LibraryScreen> createState() => _LibraryScreenState();
}

class _LibraryScreenState extends State<LibraryScreen> {
  final queryController = TextEditingController(text: 'طماطم');
  List<dynamic> searchRows = [];
  String? searchError;
  bool searching = false;
  int reloadToken = 0;

  @override
  void dispose() {
    queryController.dispose();
    super.dispose();
  }

  Future<void> runSearch() async {
    setState(() {
      searching = true;
      searchError = null;
    });
    try {
      searchRows =
          await widget.client.searchLibrary(queryController.text.trim());
    } on ApiException catch (e) {
      searchError = e.toString();
      searchRows = [];
    } finally {
      if (mounted) setState(() => searching = false);
    }
  }

  Future<void> createItem(Map<String, String> values) async {
    await widget.client.createRecord('/library/items', {
      ...values,
      'publication_status': 'published',
    });
    setState(() => reloadToken++);
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 4,
      child: Column(
        children: [
          RecordForm(
            title: 'Create library item',
            submitLabel: 'Publish item',
            fields: const [
              FormFieldConfig(name: 'slug', label: 'Slug', required: true),
              FormFieldConfig(name: 'title', label: 'Title', required: true),
              FormFieldConfig(name: 'title_ar', label: 'Arabic title'),
              FormFieldConfig(name: 'summary', label: 'Summary'),
            ],
            onSubmit: createItem,
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: queryController,
                    decoration:
                        const InputDecoration(labelText: 'Search library'),
                    textDirection: TextDirection.rtl,
                  ),
                ),
                const SizedBox(width: 8),
                FilledButton(
                    onPressed: searching ? null : runSearch,
                    child: const Text('Search')),
              ],
            ),
          ),
          const TabBar(
            isScrollable: true,
            tabs: [
              Tab(text: 'Published'),
              Tab(text: 'Categories'),
              Tab(text: 'Tags'),
              Tab(text: 'Search results'),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                ModuleListScreen(
                  key: ValueKey(
                      'library-published-$reloadToken-${widget.client.organizationId}'),
                  client: widget.client,
                  title: 'Published',
                  path: '/library/items?publication_status=published',
                ),
                ModuleListScreen(
                  key: ValueKey(
                      'library-categories-${widget.client.organizationId}'),
                  client: widget.client,
                  title: 'Categories',
                  path: '/library/categories',
                ),
                ModuleListScreen(
                  key: ValueKey('library-tags-${widget.client.organizationId}'),
                  client: widget.client,
                  title: 'Tags',
                  path: '/library/tags',
                ),
                AsyncState(
                  loading: searching,
                  error: searchError,
                  empty: !searching && searchRows.isEmpty,
                  emptyMessage: 'No search results.',
                  onRetry: runSearch,
                  child: ListView.builder(
                    itemCount: searchRows.length,
                    itemBuilder: (context, index) {
                      final row = searchRows[index] as Map<String, dynamic>;
                      return ListTile(
                        title: Text(
                            '${row['title_ar'] ?? row['title'] ?? 'Item'}',
                            textDirection: TextDirection.rtl),
                        subtitle: Text('${row['summary'] ?? ''}',
                            textDirection: TextDirection.rtl),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class AiScreen extends StatefulWidget {
  const AiScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<AiScreen> createState() => _AiScreenState();
}

class _AiScreenState extends State<AiScreen> {
  Map<String, dynamic>? provider;
  String? error;
  bool loading = false;
  int reloadToken = 0;
  ApiAiRequest? latestRequest;
  String? pollMessage;

  @override
  void initState() {
    super.initState();
    loadProvider();
  }

  Future<void> loadProvider() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      provider = await widget.client.fetchAiProvider();
    } on ApiException catch (e) {
      error = e.toString();
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> submitRequest(Map<String, String> values) async {
    setState(() {
      pollMessage = 'Submitting AI request…';
      latestRequest = null;
    });

    final created = await widget.client.createAiRequest(
      requestType: 'library_qa',
      input: {'query': values['question'] ?? ''},
    );

    final poller = PollAiRequestUseCase(aiApi: widget.client.ai);
    final completed = await poller.execute(created['id'] as int);

    if (!mounted) return;
    setState(() {
      latestRequest = completed;
      pollMessage = 'Latest request ${completed.status}.';
      reloadToken++;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        AsyncState(
          loading: loading,
          error: error,
          onRetry: loadProvider,
          child: provider == null
              ? const SizedBox.shrink()
              : Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text(
                      provider!['decision_support_notice']?.toString() ??
                          'AI decision support only.'),
                ),
        ),
        RecordForm(
          title: 'Ask the agricultural library',
          submitLabel: 'Submit AI request',
          fields: const [
            FormFieldConfig(
                name: 'question', label: 'Question', required: true),
          ],
          onSubmit: submitRequest,
        ),
        if (pollMessage != null)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(pollMessage!, style: const TextStyle(fontSize: 12)),
          ),
        if (latestRequest != null)
          Padding(
            padding: const EdgeInsets.all(16),
            child: Text('Response status: ${latestRequest!.status}'),
          ),
        Expanded(
          child: ModuleListScreen(
            key: ValueKey('ai-$reloadToken-${widget.client.organizationId}'),
            client: widget.client,
            title: 'AI requests',
            path: '/ai/requests',
            emptyMessage: 'No AI requests yet.',
          ),
        ),
      ],
    );
  }
}

class TrainingScreen extends StatelessWidget {
  const TrainingScreen({super.key, required this.client});

  final ApiClient client;

  @override
  Widget build(BuildContext context) {
    return TabbedModuleScreen(client: client, tabs: trainingTabs);
  }
}
