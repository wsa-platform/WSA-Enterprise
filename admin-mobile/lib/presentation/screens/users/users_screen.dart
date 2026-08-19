import 'package:flutter/material.dart';

import 'package:wsa_admin/data/api/api_client.dart';

import 'package:wsa_admin/l10n/m22_strings.dart';

import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';

import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';

import 'package:wsa_admin/presentation/widgets/metric_card.dart';

import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';

import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';

import 'package:wsa_admin/presentation/widgets/responsive_metric_grid.dart';



class UsersScreen extends StatefulWidget {

  const UsersScreen({super.key, required this.client});



  final ApiClient client;



  @visibleForTesting

  static Future<UsersData> Function(ApiClient client)? debugLoader;



  @override

  State<UsersScreen> createState() => _UsersScreenState();

}



class _UsersScreenState extends State<UsersScreen> {

  bool _loading = true;

  String? _error;

  bool _partialFailure = false;

  late UsersData _data;

  String _search = '';

  final _listKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();



  @override

  void initState() {

    super.initState();

    _data = UsersData.empty();

    _loadSummary();

  }



  Future<void> _loadSummary() async {

    setState(() {

      _loading = true;

      _error = null;

      _partialFailure = false;

    });



    try {

      final loader = UsersScreen.debugLoader ?? UsersData.load;

      final data = await loader(widget.client);

      if (!mounted) return;

      if (data.allFailed) {

        setState(() {

          _error = Ar.dashboardLoadFailed;

          _loading = false;

        });

        return;

      }

      setState(() {

        _data = data;

        _partialFailure = data.partialFailure;

        _loading = false;

      });

      _listKey.currentState?.reload();

    } catch (error) {

      if (!mounted) return;

      setState(() {

        _error = error.toString();

        _loading = false;

      });

    }

  }



  Future<void> _addUser() async {

    final data = await SimpleFormDialog.show(context,

      title: Ar.addUser,

      fields: const [

        FormFieldDef(key: 'name', label: Ar.colName),

        FormFieldDef(key: 'email', label: Ar.colEmail),

        FormFieldDef(key: 'password', label: Ar.password, obscure: true),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.adminModules.createUser(data);

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _listKey.currentState?.reload();

        _loadSummary();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _editUser(Map<String, dynamic> user) async {

    final data = await SimpleFormDialog.show(context,

      title: Ar.editUser,

      initialValues: {

        'name': user['name']?.toString() ?? '',

        'email': user['email']?.toString() ?? '',

      },

      fields: const [

        FormFieldDef(key: 'name', label: Ar.colName),

        FormFieldDef(key: 'email', label: Ar.colEmail),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.adminModules.updateUser(user['id'] as int, data);

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _listKey.currentState?.reload();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _toggleActive(Map<String, dynamic> user) async {

    final active = user['is_active'] == true;

    if (active) {

      final confirm = await showDialog<bool>(

        context: context,

        builder: (ctx) => AlertDialog(

          title: const Text(Ar.confirmDeactivate),

          content: const Text(Ar.confirmDeactivateUser),

          actions: [

            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text(Ar.cancel)),

            FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text(Ar.deactivate)),

          ],

        ),

      );

      if (confirm != true) return;

    }

    try {

      await widget.client.adminModules.updateUser(user['id'] as int, {'is_active': !active});

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(active ? Ar.deactivate : Ar.activate)));

        _listKey.currentState?.reload();

        _loadSummary();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  @override

  Widget build(BuildContext context) {

    final canManage = widget.client.hasPermission('access.manage');



    return ModuleScreenLayout(

      title: Ar.navUsers,

      loading: _loading,

      error: _error,

      empty: _data.usersCount == Ar.notAvailable,

      partialFailure: _partialFailure,

      onRetry: _loadSummary,

      searchHint: Ar.searchUsers,

      onSearchChanged: (value) {

        setState(() => _search = value);

        _listKey.currentState?.reload();

      },

      body: Column(

        crossAxisAlignment: CrossAxisAlignment.start,

        children: [

          Text(Ar.usersOverview, style: Theme.of(context).textTheme.titleMedium),

          const SizedBox(height: 12),

          ResponsiveMetricGrid(

            children: [

              MetricCard(label: Ar.usersTotal, value: _data.usersCount, icon: Icons.people_outline, tone: MetricTone.green),

              MetricCard(label: Ar.usersTeams, value: _data.teamsCount, icon: Icons.groups_outlined, tone: MetricTone.blue),

              MetricCard(label: Ar.usersRoles, value: _data.rolesCount, icon: Icons.admin_panel_settings_outlined, tone: MetricTone.primary),

            ],

          ),

          const SizedBox(height: 24),

          CrudActionBar(canManage: canManage, onAdd: _addUser, addLabel: Ar.addUser),

          Text(Ar.usersList, style: Theme.of(context).textTheme.titleMedium),

          const SizedBox(height: 12),

          PaginatedDataList<Map<String, dynamic>>(

            key: _listKey,

            fetchPage: (page, perPage) => widget.client.adminModules.usersPage(

              search: _search.trim().isEmpty ? null : _search.trim(),

              page: page,

              perPage: perPage,

            ),

            columns: [

              (u) => AdminDataColumn(label: Ar.colName, cellBuilder: (_, __) => Text(u['name']?.toString() ?? '')),

              (u) => AdminDataColumn(label: Ar.colEmail, cellBuilder: (_, __) => Text(u['email']?.toString() ?? '')),

              (u) => AdminDataColumn(label: Ar.colRole, cellBuilder: (_, __) => Text(u['membership_role']?.toString() ?? '')),

              (u) => AdminDataColumn(label: Ar.colStatus, cellBuilder: (_, __) => Text(u['is_active'] == true ? Ar.statusActive : Ar.statusInactive)),

              if (canManage)

                (u) => AdminDataColumn(label: Ar.edit, cellBuilder: (_, __) => Row(

                  mainAxisSize: MainAxisSize.min,

                  children: [

                    IconButton(icon: const Icon(Icons.edit), onPressed: () => _editUser(u)),

                    IconButton(

                      icon: Icon(u['is_active'] == true ? Icons.block : Icons.check_circle_outline),

                      onPressed: () => _toggleActive(u),

                    ),

                  ],

                )),

            ],

          ),

        ],

      ),

    );

  }

}



class UsersData {

  UsersData({

    required this.usersCount,

    required this.teamsCount,

    required this.rolesCount,

    required this.recentAudit,

    this.allFailed = false,

    this.partialFailure = false,

  });



  final String usersCount;

  final String teamsCount;

  final String rolesCount;

  final List<AuditRow> recentAudit;

  final bool allFailed;

  final bool partialFailure;



  factory UsersData.empty() => UsersData(

        usersCount: Ar.notAvailable,

        teamsCount: Ar.notAvailable,

        rolesCount: Ar.notAvailable,

        recentAudit: const [],

      );



  static Future<UsersData> load(ApiClient client) async {

    try {

      final summary = await client.platform.accessSummary();

      final auditRaw = summary['recent_audit'] as List<dynamic>?;

      final audit = auditRaw != null

          ? auditRaw.map((row) => AuditRow.fromJson(Map<String, dynamic>.from(row as Map))).toList()

          : <AuditRow>[];



      return UsersData(

        usersCount: summary['users_count']?.toString() ?? Ar.notAvailable,

        teamsCount: summary['teams_count']?.toString() ?? Ar.notAvailable,

        rolesCount: summary['roles_count']?.toString() ?? Ar.notAvailable,

        recentAudit: audit,

      );

    } catch (_) {

      return UsersData.empty().copyWith(allFailed: true);

    }

  }



  UsersData copyWith({

    String? usersCount,

    String? teamsCount,

    String? rolesCount,

    List<AuditRow>? recentAudit,

    bool? allFailed,

    bool? partialFailure,

  }) {

    return UsersData(

      usersCount: usersCount ?? this.usersCount,

      teamsCount: teamsCount ?? this.teamsCount,

      rolesCount: rolesCount ?? this.rolesCount,

      recentAudit: recentAudit ?? this.recentAudit,

      allFailed: allFailed ?? this.allFailed,

      partialFailure: partialFailure ?? this.partialFailure,

    );

  }

}



class AuditRow {

  AuditRow({required this.action, required this.userName, required this.createdAt});



  final String action;

  final String userName;

  final String createdAt;



  factory AuditRow.fromJson(Map<String, dynamic> json) {

    final user = json['user'] as Map<String, dynamic>?;

    return AuditRow(

      action: json['action']?.toString() ?? Ar.notAvailable,

      userName: user?['name']?.toString() ?? Ar.notAvailable,

      createdAt: json['created_at']?.toString() ?? Ar.notAvailable,

    );

  }

}

