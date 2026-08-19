import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';
import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';
import 'package:wsa_admin/presentation/widgets/metric_card.dart';
import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';

class AiScreen extends StatefulWidget {
  const AiScreen({super.key, required this.client});
  final ApiClient client;

  @override
  State<AiScreen> createState() => _AiScreenState();
}

class _AiScreenState extends State<AiScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic> _provider = {};
  Map<String, dynamic> _usage = {};
  List<Map<String, dynamic>> _requests = [];
  int? _conversationId;
  final _chatController = TextEditingController();
  final List<Map<String, String>> _chatMessages = [];
  bool _chatLoading = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _chatController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final provider = await widget.client.adminModules.aiProvider();
      final usage = await widget.client.adminModules.aiUsage();
      final requests = await widget.client.adminModules.aiRequests();
      if (!mounted) return;
      setState(() {
        _provider = provider;
        _usage = usage;
        _requests = requests.map((r) => Map<String, dynamic>.from(r as Map)).toList();
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  Future<void> _sendChat() async {
    final text = _chatController.text.trim();
    if (text.isEmpty || _chatLoading) return;
    setState(() {
      _chatMessages.add({'role': 'user', 'text': text});
      _chatLoading = true;
    });
    _chatController.clear();
    try {
      if (_conversationId == null) {
        final created = await widget.client.adminModules.createAiConversation(title: 'Admin Chat');
        _conversationId = (created['conversation']?['id'] ?? created['id']) as int?;
      }
      if (_conversationId == null) throw Exception(Ar.unknownError);
      final response = await widget.client.adminModules.aiAssistantMessage(_conversationId!, text);
      final reply = response['assistant_message']?.toString()
          ?? response['message']?['content']?.toString()
          ?? response['content']?.toString()
          ?? Ar.notAvailable;
      setState(() => _chatMessages.add({'role': 'assistant', 'text': reply}));
    } catch (e) {
      setState(() => _chatMessages.add({'role': 'assistant', 'text': '$e'}));
    } finally {
      if (mounted) setState(() => _chatLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final connected = _provider['connected'] == true || _provider['provider']?.toString() != 'none';
    return ModuleScreenLayout(
      title: Ar.aiOverview,
      loading: _loading,
      error: _error,
      empty: _requests.isEmpty && _provider.isEmpty,
      onRetry: _load,
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ResponsiveMetricGrid(children: [
            MetricCard(
              label: Ar.aiProvider,
              value: connected ? (_provider['provider']?.toString() ?? Ar.providerConnected) : Ar.providerDisconnected,
              icon: Icons.cloud_outlined,
              tone: MetricTone.primary,
            ),
            MetricCard(
              label: Ar.aiUsage,
              value: _usage['used']?.toString() ?? _usage['requests_total']?.toString() ?? Ar.notAvailable,
              icon: Icons.smart_toy_outlined,
              tone: MetricTone.blue,
            ),
            MetricCard(
              label: Ar.metricAiUsage,
              value: '${_requests.length}',
              icon: Icons.list_alt,
              tone: MetricTone.green,
            ),
          ]),
          const SizedBox(height: 20),
          Text(Ar.aiRequests, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          AdminDataList(
            rowCount: _requests.length,
            emptyMessage: Ar.emptyData,
            columns: [
              AdminDataColumn(label: Ar.colRequestType, cellBuilder: (_, i) => Text(_requests[i]['request_type']?.toString() ?? '')),
              AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, i) => Text(_requests[i]['status']?.toString() ?? '')),
              AdminDataColumn(label: Ar.colDate, cellBuilder: (_, i) => Text(_requests[i]['created_at']?.toString() ?? '')),
            ],
          ),
          const SizedBox(height: 24),
          Text(Ar.aiChat, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 8),
          Container(
            height: 180,
            decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade300), borderRadius: BorderRadius.circular(8)),
            child: ListView.builder(
              padding: const EdgeInsets.all(8),
              itemCount: _chatMessages.length,
              itemBuilder: (_, i) {
                final msg = _chatMessages[i];
                final isUser = msg['role'] == 'user';
                return Align(
                  alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
                  child: Container(
                    margin: const EdgeInsets.symmetric(vertical: 4),
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: isUser ? Colors.blue.shade50 : Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(msg['text'] ?? ''),
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _chatController,
                  decoration: const InputDecoration(hintText: Ar.aiChatHint),
                  onSubmitted: (_) => _sendChat(),
                ),
              ),
              IconButton(onPressed: _chatLoading ? null : _sendChat, icon: const Icon(Icons.send)),
            ],
          ),
        ],
      ),
    );
  }
}
