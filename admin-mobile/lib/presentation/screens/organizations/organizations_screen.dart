import 'package:flutter/material.dart';

import 'package:wsa_admin/data/api/api_client.dart';

import 'package:wsa_admin/l10n/m22_strings.dart';

import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';

import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';

import 'package:wsa_admin/presentation/widgets/metric_card.dart';

import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';

import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';



class OrganizationsScreen extends StatefulWidget {

  const OrganizationsScreen({super.key, required this.client});



  final ApiClient client;



  @visibleForTesting

  static Future<List<OrgRow>> Function(ApiClient client)? debugLoader;



  @override

  State<OrganizationsScreen> createState() => _OrganizationsScreenState();

}



class _OrganizationsScreenState extends State<OrganizationsScreen> {

  bool _loading = true;

  String? _error;

  List<OrgRow> _organizations = [];

  Map<String, dynamic>? _currentOrgDetails;

  String _search = '';

  String? _statusFilter;

  final _paginatedKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();



  bool get _isPlatformAdmin => widget.client.hasPermission('*');



  @override

  void initState() {

    super.initState();

    _load();

  }



  Future<void> _load() async {

    if (_isPlatformAdmin) {

      setState(() { _loading = false; _error = null; });

      _paginatedKey.currentState?.reload();

      return;

    }



    setState(() { _loading = true; _error = null; });

    try {

      final loader = OrganizationsScreen.debugLoader ?? OrgRow.loadAll;

      final rows = await loader(widget.client);

      Map<String, dynamic>? details;

      try {

        details = await widget.client.adminModules.organization();

      } catch (_) {}

      if (!mounted) return;

      setState(() {

        _organizations = rows;

        _currentOrgDetails = details;

        _loading = false;

      });

    } catch (error) {

      if (!mounted) return;

      setState(() { _error = error.toString(); _loading = false; });

    }

  }



  List<OrgRow> get _filtered {

    if (_search.trim().isEmpty) return _organizations;

    final query = _search.trim().toLowerCase();

    return _organizations.where((org) =>

      org.name.toLowerCase().contains(query) || org.slug.toLowerCase().contains(query)

    ).toList();

  }



  Future<void> _editCurrent() async {

    if (_currentOrgDetails == null) return;

    final data = await SimpleFormDialog.show(context,

      title: Ar.editOrganization,

      initialValues: {

        'name': _currentOrgDetails!['name']?.toString() ?? '',

        'slug': _currentOrgDetails!['slug']?.toString() ?? '',

      },

      fields: const [

        FormFieldDef(key: 'name', label: Ar.colName),

        FormFieldDef(key: 'slug', label: Ar.colSlug),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.adminModules.updateOrganization(data);

      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved))); _load(); }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _createOrg() async {

    final data = await SimpleFormDialog.show(context,

      title: Ar.addOrganization,

      fields: const [

        FormFieldDef(key: 'name', label: Ar.colName),

        FormFieldDef(key: 'slug', label: Ar.colSlug, required: false),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.platform.createAdminOrganization(data);

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _paginatedKey.currentState?.reload();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _editOrg(Map<String, dynamic> org) async {

    final data = await SimpleFormDialog.show(context,

      title: Ar.editOrganization,

      initialValues: {

        'name': org['name']?.toString() ?? '',

        'slug': org['slug']?.toString() ?? '',

      },

      fields: const [

        FormFieldDef(key: 'name', label: Ar.colName),

        FormFieldDef(key: 'slug', label: Ar.colSlug),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.platform.updateAdminOrganization(org['id'] as int, data);

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _paginatedKey.currentState?.reload();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _toggleOrgStatus(Map<String, dynamic> org) async {

    final active = org['is_active'] == true;

    final confirm = await showDialog<bool>(

      context: context,

      builder: (ctx) => AlertDialog(

        title: Text(active ? Ar.confirmDeactivate : Ar.activate),

        content: Text(org['name']?.toString() ?? ''),

        actions: [

          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text(Ar.cancel)),

          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: Text(active ? Ar.deactivate : Ar.activate)),

        ],

      ),

    );

    if (confirm != true) return;

    try {

      await widget.client.platform.updateAdminOrganization(org['id'] as int, {'is_active': !active});

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _paginatedKey.currentState?.reload();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _showMembers(Map<String, dynamic> org) async {

    try {

      final members = await widget.client.platform.adminOrganizationMembers(org['id'] as int);

      if (!mounted) return;

      await showDialog<void>(

        context: context,

        builder: (ctx) => AlertDialog(

          title: Text('${Ar.orgMembers}: ${org['name']}'),

          content: SizedBox(

            width: 420,

            child: members.isEmpty

                ? const Text(Ar.emptyData)

                : ListView.separated(

                    shrinkWrap: true,

                    itemCount: members.length,

                    separatorBuilder: (_, __) => const Divider(height: 1),

                    itemBuilder: (_, i) {

                      final member = Map<String, dynamic>.from(members[i] as Map);

                      return ListTile(

                        title: Text(member['name']?.toString() ?? ''),

                        subtitle: Text(member['email']?.toString() ?? ''),

                        trailing: Text(

                          member['is_active'] == true ? Ar.statusActive : Ar.statusInactive,

                        ),

                      );

                    },

                  ),

          ),

          actions: [

            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text(Ar.cancel)),

          ],

        ),

      );

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  @override

  Widget build(BuildContext context) {

    if (_isPlatformAdmin) {

      return ModuleScreenLayout(

        title: Ar.navOrganizations,

        loading: false,

        error: _error,

        empty: false,

        onRetry: _load,

        searchHint: Ar.searchOrganizations,

        onSearchChanged: (value) {

          setState(() => _search = value);

          _paginatedKey.currentState?.reload();

        },

        filterChips: [

          ChoiceChip(label: const Text(Ar.filterAll), selected: _statusFilter == null, onSelected: (_) { setState(() => _statusFilter = null); _paginatedKey.currentState?.reload(); }),

          ChoiceChip(label: const Text(Ar.filterActive), selected: _statusFilter == 'active', onSelected: (_) { setState(() => _statusFilter = 'active'); _paginatedKey.currentState?.reload(); }),

          ChoiceChip(label: const Text(Ar.filterInactive), selected: _statusFilter == 'inactive', onSelected: (_) { setState(() => _statusFilter = 'inactive'); _paginatedKey.currentState?.reload(); }),

        ],

        body: Column(

          crossAxisAlignment: CrossAxisAlignment.start,

          children: [

            CrudActionBar(canManage: true, onAdd: _createOrg, addLabel: Ar.addOrganization),

            PaginatedDataList<Map<String, dynamic>>(

              key: _paginatedKey,

              fetchPage: (page, perPage) => widget.client.platform.adminOrganizationsPage(

                search: _search.trim().isEmpty ? null : _search.trim(),

                isActive: _statusFilter == 'active' ? true : (_statusFilter == 'inactive' ? false : null),

                page: page,

                perPage: perPage,

              ),

              columns: [

                (item) => AdminDataColumn(label: Ar.colName, cellBuilder: (_, __) => Text(item['name']?.toString() ?? '', style: const TextStyle(fontWeight: FontWeight.w600))),

                (item) => AdminDataColumn(label: Ar.colSlug, cellBuilder: (_, __) => Text(item['slug']?.toString() ?? '')),

                (item) => AdminDataColumn(label: Ar.colMembers, cellBuilder: (_, __) => Text('${item['members_count'] ?? 0}')),

                (item) => AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, __) => Text(item['is_active'] == true ? Ar.statusActive : Ar.statusInactive)),

                (item) => AdminDataColumn(label: Ar.edit, cellBuilder: (_, __) => Row(

                  mainAxisSize: MainAxisSize.min,

                  children: [

                    IconButton(icon: const Icon(Icons.people_outline), tooltip: Ar.orgMembers, onPressed: () => _showMembers(item)),

                    IconButton(icon: const Icon(Icons.edit), onPressed: () => _editOrg(item)),

                    IconButton(

                      icon: Icon(item['is_active'] == true ? Icons.block : Icons.check_circle_outline),

                      onPressed: () => _toggleOrgStatus(item),

                    ),

                  ],

                )),

              ],

            ),

          ],

        ),

      );

    }



    final rows = _filtered;

    final canManage = widget.client.hasPermission('access.manage');



    return ModuleScreenLayout(

      title: Ar.navOrganizations,

      loading: _loading,

      error: _error,

      empty: rows.isEmpty && !_loading && _error == null,

      onRetry: _load,

      searchHint: Ar.searchOrganizations,

      onSearchChanged: (value) => setState(() => _search = value),

      body: Column(

        crossAxisAlignment: CrossAxisAlignment.start,

        children: [

          Text(Ar.orgOverview, style: Theme.of(context).textTheme.titleMedium),

          const SizedBox(height: 12),

          MetricCard(label: Ar.orgCount, value: '${rows.length}', icon: Icons.business_outlined, tone: MetricTone.primary),

          if (_currentOrgDetails != null) ...[

            const SizedBox(height: 12),

            Card(

              child: ListTile(

                title: Text(_currentOrgDetails!['name']?.toString() ?? ''),

                subtitle: Text('${Ar.colMembers}: ${_currentOrgDetails!['members_count'] ?? Ar.notAvailable}'),

                trailing: canManage ? IconButton(icon: const Icon(Icons.edit), onPressed: _editCurrent) : null,

              ),

            ),

          ],

          const SizedBox(height: 24),

          AdminDataList(

            rowCount: rows.length,

            emptyMessage: Ar.emptyData,

            columns: [

              AdminDataColumn(label: Ar.colName, cellBuilder: (_, i) => Text(rows[i].name, style: const TextStyle(fontWeight: FontWeight.w600))),

              AdminDataColumn(label: Ar.colSlug, cellBuilder: (_, i) => Text(rows[i].slug)),

              AdminDataColumn(label: Ar.colRole, cellBuilder: (_, i) => Text(rows[i].role)),

            ],

          ),

        ],

      ),

    );

  }

}



class OrgRow {

  OrgRow({required this.name, required this.slug, required this.role});



  final String name;

  final String slug;

  final String role;



  static Future<List<OrgRow>> loadAll(ApiClient client) async {

    final rows = await client.platform.organizations();

    return rows.map((row) {

      final org = Map<String, dynamic>.from(row as Map);

      return OrgRow(

        name: org['name']?.toString() ?? Ar.notAvailable,

        slug: org['slug']?.toString() ?? Ar.notAvailable,

        role: org['role']?.toString() ?? Ar.notAvailable,

      );

    }).toList();

  }

}

