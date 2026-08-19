import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';
import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';
import 'package:wsa_admin/presentation/widgets/metric_card.dart';
import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';

class MonitoringScreen extends StatefulWidget {
  const MonitoringScreen({super.key, required this.client});
  final ApiClient client;

  @override
  State<MonitoringScreen> createState() => _MonitoringScreenState();
}

class _MonitoringScreenState extends State<MonitoringScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic> _health = {};
  List<Map<String, dynamic>> _incidents = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final health = await widget.client.platform.monitoringHealth();
      final incidents = await widget.client.adminModules.monitoringIncidents();
      if (!mounted) return;
      setState(() {
        _health = health;
        _incidents = incidents.map((r) => Map<String, dynamic>.from(r as Map)).toList();
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  Future<void> _resolve(int id) async {
    try {
      await widget.client.adminModules.resolveIncident(id);
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved))); _load(); }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = _health['status']?.toString();
    final healthLabel = status == 'healthy' ? Ar.systemHealthy : status == 'degraded' ? Ar.systemDegraded : Ar.systemUnknown;
    final canResolve = widget.client.hasPermission('access.manage');

    return ModuleScreenLayout(
      title: Ar.monitoringOverview,
      loading: _loading,
      error: _error,
      empty: _incidents.isEmpty && _health.isEmpty,
      onRetry: _load,
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ResponsiveMetricGrid(children: [
            MetricCard(label: Ar.monitoringHealth, value: healthLabel, icon: Icons.monitor_heart, tone: MetricTone.green),
            MetricCard(label: Ar.monitoringIncidents, value: '${_incidents.length}', icon: Icons.warning_amber, tone: MetricTone.amber),
          ]),
          if (_health['components'] is Map || _health['checks'] is List || _health['checks'] is Map) ...[
            const SizedBox(height: 16),
            Text(Ar.dashboardHealthDetails, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            ..._healthCheckEntries().map((check) {
              final status = check['status']?.toString();
              final ok = status == 'healthy' || status == 'ok';
              return ListTile(
                leading: Icon(ok ? Icons.check_circle : Icons.error, color: ok ? Colors.green : Colors.red),
                title: Text(check['component']?.toString() ?? ''),
                subtitle: Text(check['message']?.toString() ?? status ?? ''),
              );
            }),
          ],
          const SizedBox(height: 20),
          Text(Ar.monitoringIncidents, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          AdminDataList(
            rowCount: _incidents.length,
            emptyMessage: Ar.emptyData,
            columns: [
              AdminDataColumn(label: Ar.colComponent, cellBuilder: (_, i) => Text(_incidents[i]['component']?.toString() ?? '')),
              AdminDataColumn(label: Ar.colSeverity, cellBuilder: (_, i) => Text(_incidents[i]['severity']?.toString() ?? '')),
              AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, i) => Text(_incidents[i]['status']?.toString() ?? '')),
              AdminDataColumn(label: Ar.colDate, cellBuilder: (_, i) => Text(_incidents[i]['detected_at']?.toString() ?? '')),
              if (canResolve)
                AdminDataColumn(label: Ar.colAction, cellBuilder: (_, i) {
                  if (_incidents[i]['resolved_at'] != null) return const SizedBox.shrink();
                  return TextButton(
                    onPressed: () => _resolve(_incidents[i]['id'] as int),
                    child: const Text(Ar.resolveIncident),
                  );
                }),
            ],
          ),
        ],
      ),
    );
  }

  List<Map<String, dynamic>> _healthCheckEntries() {
    final components = _health['components'];
    if (components is Map) {
      return components.entries
          .map((e) => {'component': e.key, ...(Map<String, dynamic>.from(e.value as Map))})
          .toList();
    }
    final checks = _health['checks'];
    if (checks is List) {
      return checks.map((c) => Map<String, dynamic>.from(c as Map)).toList();
    }
    if (checks is Map) {
      return checks.entries
          .map((e) => {'component': e.key, ...(Map<String, dynamic>.from(e.value as Map))})
          .toList();
    }
    return [];
  }
}
