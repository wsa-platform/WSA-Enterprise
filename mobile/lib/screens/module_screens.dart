import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';
import 'package:wsa_enterprise/widgets/record_form.dart';

class ModuleTab {
  const ModuleTab({required this.label, required this.path, this.createFields = const []});

  final String label;
  final String path;
  final List<FormFieldConfig> createFields;
}

class TabbedModuleScreen extends StatelessWidget {
  const TabbedModuleScreen({super.key, required this.client, required this.tabs});

  final ApiClient client;
  final List<ModuleTab> tabs;

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: tabs.length,
      child: Column(
        children: [
          TabBar(
            isScrollable: tabs.length > 3,
            tabs: [for (final tab in tabs) Tab(text: tab.label)],
          ),
          Expanded(
            child: TabBarView(
              children: [
                for (final tab in tabs)
                  ModuleListScreen(
                    key: ValueKey('${tab.path}-${client.organizationId}'),
                    client: client,
                    title: tab.label,
                    path: tab.path,
                    createFields: tab.createFields,
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class ModuleListScreen extends StatefulWidget {
  const ModuleListScreen({
    super.key,
    required this.client,
    required this.title,
    required this.path,
    this.createFields = const [],
    this.allowDelete = false,
    this.emptyMessage = 'No records found.',
  });

  final ApiClient client;
  final String title;
  final String path;
  final List<FormFieldConfig> createFields;
  final bool allowDelete;
  final String emptyMessage;

  @override
  State<ModuleListScreen> createState() => _ModuleListScreenState();
}

class _ModuleListScreenState extends State<ModuleListScreen> {
  List<dynamic> rows = [];
  String? error;
  bool loading = false;

  @override
  void initState() {
    super.initState();
    load();
  }

  @override
  void didUpdateWidget(covariant ModuleListScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.client.organizationId != widget.client.organizationId ||
        oldWidget.path != widget.path) {
      load();
    }
  }

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      rows = await widget.client.fetchList(widget.path);
    } on ApiException catch (e) {
      error = e.toString();
      rows = [];
    } catch (e) {
      error = e.toString();
      rows = [];
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> createRecord(Map<String, String> values) async {
    await widget.client.createRecord(widget.path.split('?').first, values);
    await load();
  }

  Future<void> deleteRecord(int id) async {
    await widget.client.deleteRecord(widget.path.split('?').first, id);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Record deleted.')),
      );
    }
    await load();
  }

  @override
  Widget build(BuildContext context) {
    final showEmptyOnly = !loading && rows.isEmpty && widget.createFields.isEmpty;

    return AsyncState(
      loading: loading && rows.isEmpty && widget.createFields.isEmpty,
      error: error,
      empty: showEmptyOnly,
      emptyMessage: widget.emptyMessage,
      onRetry: load,
      child: RefreshIndicator(
        onRefresh: load,
        child: ListView(
          children: [
            if (widget.createFields.isNotEmpty)
              RecordForm(
                title: 'Create ${widget.title.toLowerCase()}',
                fields: widget.createFields,
                submitLabel: 'Create',
                onSubmit: createRecord,
              ),
            if (!loading && rows.isEmpty && widget.createFields.isNotEmpty)
              Padding(
                padding: const EdgeInsets.all(24),
                child: Text(widget.emptyMessage, textAlign: TextAlign.center),
              ),
            for (var index = 0; index < rows.length; index++)
              _RecordTile(
                row: rows[index] as Map<String, dynamic>,
                allowDelete: widget.allowDelete,
                onDelete: widget.allowDelete
                    ? () => deleteRecord(rows[index]['id'] as int)
                    : null,
              ),
          ],
        ),
      ),
    );
  }
}

class _RecordTile extends StatelessWidget {
  const _RecordTile({required this.row, required this.allowDelete, this.onDelete});

  final Map<String, dynamic> row;
  final bool allowDelete;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    final title = row['title_ar'] ?? row['title'] ?? row['reference'] ?? row['name'] ?? row['code'] ?? 'Record';
    final subtitle = row['summary_ar'] ?? row['summary'] ?? row['notes'] ?? row['status'] ?? '';
    return ListTile(
      title: Text('$title', textDirection: TextDirection.rtl),
      subtitle: '$subtitle'.isEmpty ? null : Text('$subtitle', textDirection: TextDirection.rtl),
      trailing: allowDelete && row['id'] != null
          ? IconButton(
              icon: const Icon(Icons.delete_outline),
              onPressed: onDelete,
            )
          : null,
      onTap: () {
        showModalBottomSheet<void>(
          context: context,
          showDragHandle: true,
          builder: (context) => Padding(
            padding: const EdgeInsets.all(16),
            child: SingleChildScrollView(
              child: Text(const JsonEncoder.withIndent('  ').convert(row)),
            ),
          ),
        );
      },
    );
  }
}

const farmTabs = [
  ModuleTab(label: 'Farms', path: '/farm/farms', createFields: [
    FormFieldConfig(name: 'code', label: 'Code', required: true),
    FormFieldConfig(name: 'name', label: 'Name', required: true),
  ]),
  ModuleTab(label: 'Regions', path: '/farm/regions'),
  ModuleTab(label: 'Fields', path: '/farm/fields'),
  ModuleTab(label: 'Blocks', path: '/farm/blocks'),
  ModuleTab(label: 'Greenhouses', path: '/farm/greenhouses'),
  ModuleTab(label: 'Irrigation', path: '/farm/irrigation-zones'),
];

const cropTabs = [
  ModuleTab(label: 'Types', path: '/crop/types', createFields: [
    FormFieldConfig(name: 'code', label: 'Code', required: true),
    FormFieldConfig(name: 'name', label: 'Name', required: true),
  ]),
  ModuleTab(label: 'Varieties', path: '/crop/varieties'),
  ModuleTab(label: 'Seasons', path: '/crop/seasons'),
  ModuleTab(label: 'Growth stages', path: '/crop/growth-stages'),
  ModuleTab(label: 'Harvests', path: '/crop/harvests'),
  ModuleTab(label: 'Yields', path: '/crop/yields'),
];

const soilTabs = [
  ModuleTab(label: 'Analyses', path: '/soil/analyses', createFields: [
    FormFieldConfig(name: 'sample_reference', label: 'Sample reference', required: true),
    FormFieldConfig(name: 'sampled_at', label: 'Sampled at (YYYY-MM-DD)', required: true),
  ]),
  ModuleTab(label: 'Nutrients', path: '/soil/nutrients'),
  ModuleTab(label: 'Recommendations', path: '/soil/recommendations'),
];

const diagnosisTabs = [
  ModuleTab(label: 'Requests', path: '/diagnosis/requests'),
  ModuleTab(label: 'Categories', path: '/diagnosis/categories'),
  ModuleTab(label: 'Symptoms', path: '/diagnosis/symptoms'),
  ModuleTab(label: 'Diseases', path: '/diagnosis/diseases'),
];

const trainingTabs = [
  ModuleTab(label: 'Courses', path: '/training/courses', createFields: [
    FormFieldConfig(name: 'code', label: 'Code', required: true),
    FormFieldConfig(name: 'title', label: 'Title', required: true),
    FormFieldConfig(name: 'title_ar', label: 'Arabic title'),
  ]),
  ModuleTab(label: 'Lessons', path: '/training/lessons'),
  ModuleTab(label: 'Enrollments', path: '/training/enrollments'),
];

const libraryTabs = [
  ModuleTab(label: 'Published', path: '/library/items?publication_status=published'),
  ModuleTab(label: 'Categories', path: '/library/categories'),
  ModuleTab(label: 'Tags', path: '/library/tags'),
];

const businessTabs = [
  ModuleTab(label: 'Customers', path: '/catalog/customers', createFields: [
    FormFieldConfig(name: 'code', label: 'Code', required: true),
    FormFieldConfig(name: 'name', label: 'Name', required: true),
  ]),
  ModuleTab(label: 'Products', path: '/catalog/products', createFields: [
    FormFieldConfig(name: 'sku', label: 'SKU', required: true),
    FormFieldConfig(name: 'name', label: 'Name', required: true),
  ]),
  ModuleTab(label: 'Companies', path: '/directory/companies', createFields: [
    FormFieldConfig(name: 'name', label: 'Company name', required: true),
  ]),
  ModuleTab(label: 'Inventory', path: '/inventory'),
  ModuleTab(label: 'Sales orders', path: '/sales-orders'),
];

class FarmsScreen extends StatelessWidget {
  const FarmsScreen({super.key, required this.client});
  final ApiClient client;
  @override
  Widget build(BuildContext context) => TabbedModuleScreen(client: client, tabs: farmTabs);
}

class CropsScreen extends StatelessWidget {
  const CropsScreen({super.key, required this.client});
  final ApiClient client;
  @override
  Widget build(BuildContext context) => TabbedModuleScreen(client: client, tabs: cropTabs);
}

class SoilScreen extends StatelessWidget {
  const SoilScreen({super.key, required this.client});
  final ApiClient client;
  @override
  Widget build(BuildContext context) => TabbedModuleScreen(client: client, tabs: soilTabs);
}

class BusinessScreen extends StatelessWidget {
  const BusinessScreen({super.key, required this.client});
  final ApiClient client;
  @override
  Widget build(BuildContext context) => TabbedModuleScreen(client: client, tabs: businessTabs);
}
