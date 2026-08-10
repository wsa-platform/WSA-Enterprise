import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  Map<String, dynamic>? usage;
  String? error;
  bool loading = false;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      usage = await widget.client.ai.usageSummary();
    } on ApiException catch (e) {
      error = e.toString();
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final quota = usage;
    return AsyncState(
      loading: loading,
      error: error,
      onRetry: load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Session', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  Text('API: ${widget.client.baseUrl}'),
                  Text('Organization ID: ${widget.client.organizationId ?? '—'}'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('AI quota', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  if (quota == null) const Text('Quota summary unavailable.'),
                  if (quota != null) ...[
                    Text('Enabled: ${quota['enabled'] ?? false}'),
                    Text('Used: ${quota['used'] ?? 0}'),
                    Text('Limit: ${quota['limit'] ?? 'Unlimited'}'),
                    Text('Remaining: ${quota['remaining'] ?? '—'}'),
                  ],
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          const Card(
            child: Padding(
              padding: EdgeInsets.all(16),
              child: Text(
                'Mobile settings are read-only in M6. Organization operational settings are managed in the web billing workspace.',
              ),
            ),
          ),
        ],
      ),
    );
  }
}
