import 'package:flutter/material.dart';

import 'package:wsa_admin/data/api/api_client.dart';

import 'package:wsa_admin/l10n/m22_strings.dart';

import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';

import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';

import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';



class RolesScreen extends StatefulWidget {

  const RolesScreen({super.key, required this.client});

  final ApiClient client;



  @override

  State<RolesScreen> createState() => _RolesScreenState();

}



class _RolesScreenState extends State<RolesScreen> {

  bool _loading = true;

  String? _error;

  List<Map<String, dynamic>> _roles = [];

  Map<String, dynamic> _catalog = {};

  String _permSearch = '';

  int? _selectedRoleId;

  final Map<int, Set<String>> _matrix = {};

  bool _savingMatrix = false;



  @override

  void initState() {

    super.initState();

    _load();

  }



  Future<void> _load() async {

    setState(() { _loading = true; _error = null; });

    try {

      final rolesPage = await widget.client.adminModules.rolesPage(perPage: 100);

      final catalog = await widget.client.adminModules.permissionsCatalog();

      if (!mounted) return;

      final roles = rolesPage.data;

      for (final role in roles) {

        final perms = (role['permissions'] as List<dynamic>? ?? [])

            .map((p) => (p as Map)['name']?.toString() ?? '')

            .where((name) => name.isNotEmpty)

            .toSet();

        _matrix[role['id'] as int] = perms;

      }

      setState(() {

        _roles = roles;

        _catalog = catalog;

        _selectedRoleId ??= roles.isNotEmpty ? roles.first['id'] as int? : null;

        _loading = false;

      });

    } catch (e) {

      if (!mounted) return;

      setState(() { _error = e.toString(); _loading = false; });

    }

  }



  Future<void> _addRole() async {

    final data = await SimpleFormDialog.show(context,

      title: Ar.addRole,

      fields: const [

        FormFieldDef(key: 'slug', label: Ar.colSlug),

        FormFieldDef(key: 'name', label: Ar.colName),

        FormFieldDef(key: 'description', label: Ar.colDescription, required: false),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.adminModules.createRole(data);

      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved))); _load(); }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Map<String, List<Map<String, dynamic>>> get _filteredGroups {

    final groups = _catalog['groups'] as Map<String, dynamic>? ?? {};

    final result = <String, List<Map<String, dynamic>>>{};

    final query = _permSearch.trim().toLowerCase();



    groups.forEach((group, items) {

      final rows = (items as List<dynamic>).map((item) => Map<String, dynamic>.from(item as Map)).where((item) {

        if (query.isEmpty) return true;

        final name = item['name']?.toString().toLowerCase() ?? '';

        return name.contains(query);

      }).toList();

      if (rows.isNotEmpty) result[group] = rows;

    });



    return result;

  }



  Set<String> get _selectedPermissions {

    if (_selectedRoleId == null) return {};

    return _matrix[_selectedRoleId!] ?? {};

  }



  Future<void> _saveMatrix() async {

    if (_selectedRoleId == null) return;

    setState(() => _savingMatrix = true);

    try {

      final role = _roles.firstWhere((r) => r['id'] == _selectedRoleId);

      final allPerms = await widget.client.adminModules.permissionsPage(perPage: 200);

      final selectedNames = _matrix[_selectedRoleId!] ?? {};

      final permissionIds = allPerms.data

          .where((p) => selectedNames.contains(p['name']?.toString()))

          .map((p) => p['id'] as int)

          .toList();



      await widget.client.adminModules.updateRole(_selectedRoleId!, {

        'name': role['name'],

        'permission_ids': permissionIds,

      });



      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    } finally {

      if (mounted) setState(() => _savingMatrix = false);

    }

  }



  @override

  Widget build(BuildContext context) {

    final canManage = widget.client.hasPermission('access.manage');

    final hasWildcard = widget.client.hasPermission('*');

    final groups = _filteredGroups;



    return ModuleScreenLayout(

      title: Ar.rolesOverview,

      loading: _loading,

      error: _error,

      empty: _roles.isEmpty,

      onRetry: _load,

      body: Column(

        crossAxisAlignment: CrossAxisAlignment.start,

        children: [

          CrudActionBar(canManage: canManage, onAdd: _addRole, addLabel: Ar.addRole),

          AdminDataList(

            rowCount: _roles.length,

            emptyMessage: Ar.emptyData,

            columns: [

              AdminDataColumn(label: Ar.colName, cellBuilder: (_, i) => Text(_roles[i]['name']?.toString() ?? '')),

              AdminDataColumn(label: Ar.colSlug, cellBuilder: (_, i) => Text(_roles[i]['slug']?.toString() ?? '')),

              AdminDataColumn(label: Ar.colDescription, cellBuilder: (_, i) => Text(_roles[i]['description']?.toString() ?? '')),

            ],

          ),

          const SizedBox(height: 24),

          Text(Ar.roleMatrix, style: Theme.of(context).textTheme.titleMedium),

          if (hasWildcard)

            Padding(

              padding: const EdgeInsets.only(top: 4),

              child: Text('* ${Ar.permissionsOverview}', style: Theme.of(context).textTheme.bodySmall),

            ),

          const SizedBox(height: 12),

          if (_roles.isNotEmpty) ...[

            DropdownButtonFormField<int>(

              value: _selectedRoleId,

              decoration: const InputDecoration(labelText: Ar.filterRole),

              items: [

                for (final role in _roles)

                  DropdownMenuItem(value: role['id'] as int, child: Text(role['name']?.toString() ?? '')),

              ],

              onChanged: canManage ? (v) => setState(() => _selectedRoleId = v) : null,

            ),

            const SizedBox(height: 8),

            TextField(

              decoration: const InputDecoration(labelText: Ar.searchPermissions, prefixIcon: Icon(Icons.search)),

              onChanged: (v) => setState(() => _permSearch = v),

            ),

            const SizedBox(height: 8),

            Row(

              children: [

                TextButton(onPressed: canManage ? () {

                  setState(() {

                    final all = (_catalog['catalog'] as List<dynamic>? ?? []).map((e) => '$e').toSet();

                    if (_selectedRoleId != null) _matrix[_selectedRoleId!] = all;

                  });

                } : null, child: const Text(Ar.selectAll)),

                TextButton(onPressed: canManage ? () {

                  setState(() { if (_selectedRoleId != null) _matrix[_selectedRoleId!] = {}; });

                } : null, child: const Text(Ar.deselectAll)),

                const Spacer(),

                FilledButton(

                  onPressed: canManage && !_savingMatrix ? _saveMatrix : null,

                  child: _savingMatrix

                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))

                      : const Text(Ar.saveMatrix),

                ),

              ],

            ),

            const SizedBox(height: 8),

            ...groups.entries.map((entry) => Card(

              child: ExpansionTile(

                title: Text(entry.key),

                children: entry.value.map((perm) {

                  final name = perm['name']?.toString() ?? '';

                  final selected = _selectedPermissions.contains(name);

                  return CheckboxListTile(

                    value: selected,

                    onChanged: canManage ? (v) {

                      setState(() {

                        final set = _matrix[_selectedRoleId!] ?? {};

                        if (v == true) {

                          set.add(name);

                        } else {

                          set.remove(name);

                        }

                        _matrix[_selectedRoleId!] = set;

                      });

                    } : null,

                    title: Text(name),

                    subtitle: Text(perm['description']?.toString() ?? ''),

                  );

                }).toList(),

              ),

            )),

          ],

        ],

      ),

    );

  }

}

