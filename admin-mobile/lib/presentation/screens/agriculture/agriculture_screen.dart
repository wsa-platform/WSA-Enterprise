import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';
import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';
import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';
import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';
import 'package:wsa_admin/presentation/widgets/metric_card.dart';
import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';
import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';

class AgricultureScreen extends StatefulWidget {
  const AgricultureScreen({super.key, required this.client});
  final ApiClient client;

  @override
  State<AgricultureScreen> createState() => _AgricultureScreenState();
}

class _AgricultureScreenState extends State<AgricultureScreen> {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _cropTypes = [];
  List<Map<String, dynamic>> _farms = [];
  final _listKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final cropsPage = await widget.client.adminModules.cropTypesPage();
      final farms = await widget.client.adminModules.farms();
      if (!mounted) return;
      setState(() {
        _cropTypes = cropsPage.data;
        _farms = farms.map((r) => Map<String, dynamic>.from(r as Map)).toList();
        _loading = false;
      });
      _listKey.currentState?.reload();
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  Future<void> _addCropType() async {
    final data = await SimpleFormDialog.show(context,
      title: Ar.addCropType,
      fields: const [
        FormFieldDef(key: 'code', label: Ar.colCode),
        FormFieldDef(key: 'name', label: Ar.colName),
        FormFieldDef(key: 'scientific_name', label: Ar.colScientificName, required: false),
      ],
    );
    if (data == null) return;
    try {
      await widget.client.adminModules.createCropType(data);
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved))); _load(); }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final canManage = widget.client.hasPermission('crop.manage');
    return ModuleScreenLayout(
      title: Ar.agricultureOverview,
      loading: _loading,
      error: _error,
      empty: _cropTypes.isEmpty && _farms.isEmpty,
      onRetry: _load,
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ResponsiveMetricGrid(children: [
            MetricCard(label: Ar.metricFarms, value: '${_farms.length}', icon: Icons.agriculture, tone: MetricTone.green),
            MetricCard(label: Ar.metricCrops, value: '${_cropTypes.length}', icon: Icons.grass, tone: MetricTone.primary),
          ]),
          const SizedBox(height: 20),
          CrudActionBar(canManage: canManage, onAdd: _addCropType, addLabel: Ar.addCropType),
          Text(Ar.metricCrops, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          PaginatedDataList<Map<String, dynamic>>(
            key: _listKey,
            fetchPage: (page, perPage) => widget.client.adminModules.cropTypesPage(page: page, perPage: perPage),
            columns: [
              (item) => AdminDataColumn(label: Ar.colCode, cellBuilder: (_, __) => Text(item['code']?.toString() ?? '')),
              (item) => AdminDataColumn(label: Ar.colName, cellBuilder: (_, __) => Text(item['name']?.toString() ?? '')),
              (item) => AdminDataColumn(label: Ar.colScientificName, cellBuilder: (_, __) => Text(item['scientific_name']?.toString() ?? '')),
            ],
          ),
          const SizedBox(height: 24),
          Text(Ar.metricFarms, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          AdminDataList(
            rowCount: _farms.length,
            emptyMessage: Ar.emptyData,
            columns: [
              AdminDataColumn(label: Ar.colCode, cellBuilder: (_, i) => Text(_farms[i]['code']?.toString() ?? '')),
              AdminDataColumn(label: Ar.colName, cellBuilder: (_, i) => Text(_farms[i]['name']?.toString() ?? '')),
            ],
          ),
        ],
      ),
    );
  }
}
