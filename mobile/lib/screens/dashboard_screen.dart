import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? dashboard;
  Map<String, dynamic>? summary;
  String? error;
  bool loading = false;

  @override
  void initState() {
    super.initState();
    load();
  }

  @override
  void didUpdateWidget(covariant DashboardScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.client.organizationId != widget.client.organizationId) {
      load();
    }
  }

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final results = await Future.wait([
        widget.client.fetchDashboard(),
        widget.client.fetchWorkflowSummary(),
      ]);
      dashboard = results[0];
      summary = results[1];
    } on ApiException catch (e) {
      error = e.toString();
    } catch (e) {
      error = e.toString();
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final organization = dashboard?['organization'] as Map<String, dynamic>? ?? {};
    final metrics = dashboard?['metrics'] as Map<String, dynamic>? ?? {};

    return AsyncState(
      loading: loading && dashboard == null,
      error: error,
      onRetry: load,
      child: RefreshIndicator(
        onRefresh: load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text(organization['name']?.toString() ?? 'Dashboard', style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 8),
            Text('Signed in as ${widget.client.user?['name'] ?? 'User'}'),
            const SizedBox(height: 16),
            Wrap(
              spacing: 12,
              runSpacing: 12,
              children: [
                _MetricCard(label: 'Open tasks', value: '${metrics['open_tasks'] ?? 0}'),
                _MetricCard(label: 'Farms', value: '${summary?['farms'] ?? 0}'),
                _MetricCard(label: 'Diagnosis', value: '${summary?['diagnosis_requests'] ?? 0}'),
                _MetricCard(label: 'Library', value: '${summary?['library_items'] ?? 0}'),
                _MetricCard(label: 'Courses', value: '${summary?['training_courses'] ?? 0}'),
                _MetricCard(label: 'Enrollments', value: '${summary?['active_enrollments'] ?? 0}'),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 150,
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: Theme.of(context).textTheme.bodySmall),
              const SizedBox(height: 8),
              Text(value, style: Theme.of(context).textTheme.headlineMedium),
            ],
          ),
        ),
      ),
    );
  }
}
