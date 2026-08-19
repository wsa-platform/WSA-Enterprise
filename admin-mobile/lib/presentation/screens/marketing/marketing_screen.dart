import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';
import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';
import 'package:wsa_admin/presentation/widgets/metric_card.dart';
import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';
import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';
import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';

class MarketingScreen extends StatefulWidget {
  const MarketingScreen({super.key, required this.client});
  final ApiClient client;

  @override
  State<MarketingScreen> createState() => _MarketingScreenState();
}

class _MarketingScreenState extends State<MarketingScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic> _dashboard = {};
  Map<String, dynamic> _providers = {};
  final _listKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final dashboard = await widget.client.platform.marketingDashboard();
      if (!mounted) return;
      setState(() {
        _dashboard = dashboard;
        _providers = Map<String, dynamic>.from(dashboard['providers'] as Map? ?? {});
        _loading = false;
      });
      _listKey.currentState?.reload();
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  Future<void> _add() async {
    final data = await SimpleFormDialog.show(context,
      title: Ar.addCampaign,
      fields: const [
        FormFieldDef(key: 'name', label: Ar.colName),
        FormFieldDef(key: 'channel', label: Ar.colChannel),
        FormFieldDef(key: 'subject', label: Ar.colTitle, required: false),
      ],
    );
    if (data == null) return;
    try {
      await widget.client.adminModules.createCampaign({...data, 'status': 'draft'});
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved))); _load(); }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final canManage = widget.client.hasAnyPermission(['marketing.manage', 'marketing.admin']);
    return ModuleScreenLayout(
      title: Ar.marketingOverview,
      loading: _loading,
      error: _error,
      empty: false,
      onRetry: _load,
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ResponsiveMetricGrid(children: [
            MetricCard(label: Ar.metricCampaigns, value: '${_dashboard['campaigns'] ?? 0}', icon: Icons.campaign, tone: MetricTone.primary),
            MetricCard(label: Ar.colStatus, value: '${_dashboard['sent_deliveries'] ?? 0}', icon: Icons.send, tone: MetricTone.green),
          ]),
          if (_providers.isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(Ar.providerStatus, style: Theme.of(context).textTheme.titleSmall),
            Wrap(
              spacing: 8,
              runSpacing: 4,
              children: _providers.entries.map((e) {
                final info = Map<String, dynamic>.from(e.value as Map);
                final connected = info['connected'] == true;
                return Chip(
                  label: Text('${e.key}: ${info['label'] ?? (connected ? Ar.providerConnected : Ar.providerDisconnected)}'),
                  backgroundColor: connected ? Colors.green.shade50 : Colors.orange.shade50,
                );
              }).toList(),
            ),
          ],
          const SizedBox(height: 20),
          CrudActionBar(canManage: canManage, onAdd: _add, addLabel: Ar.addCampaign),
          PaginatedDataList<Map<String, dynamic>>(
            key: _listKey,
            fetchPage: (page, perPage) => widget.client.adminModules.campaignsPage(page: page, perPage: perPage),
            columns: [
              (item) => AdminDataColumn(label: Ar.colName, cellBuilder: (_, __) => Text(item['name']?.toString() ?? '')),
              (item) => AdminDataColumn(label: Ar.colChannel, cellBuilder: (_, __) => Text(item['channel']?.toString() ?? '')),
              (item) => AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, __) => Text(item['status']?.toString() ?? '')),
              (item) => AdminDataColumn(label: Ar.colDate, cellBuilder: (_, __) => Text(item['created_at']?.toString() ?? '')),
            ],
          ),
        ],
      ),
    );
  }
}
