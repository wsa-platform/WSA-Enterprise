import 'package:flutter/material.dart';

import 'package:wsa_admin/data/api/api_client.dart';

import 'package:wsa_admin/l10n/m22_strings.dart';

import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';

import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';

import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';



class AuditScreen extends StatefulWidget {

  const AuditScreen({super.key, required this.client});

  final ApiClient client;



  @override

  State<AuditScreen> createState() => _AuditScreenState();

}



class _AuditScreenState extends State<AuditScreen> {

  String _actionFilter = '';

  final _listKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();



  @override

  Widget build(BuildContext context) {

    return ModuleScreenLayout(

      title: Ar.auditOverview,

      loading: false,

      error: null,

      empty: false,

      onRetry: () => _listKey.currentState?.reload(),

      searchHint: Ar.filterByAction,

      onSearchChanged: (v) {

        setState(() => _actionFilter = v);

        _listKey.currentState?.reload();

      },

      body: PaginatedDataList<Map<String, dynamic>>(

        key: _listKey,

        fetchPage: (page, perPage) => widget.client.adminModules.auditLogsPage(

          action: _actionFilter.isEmpty ? null : _actionFilter,

          page: page,

          perPage: perPage,

        ),

        columns: [

          (log) => AdminDataColumn(label: Ar.colAction, cellBuilder: (_, __) => Text(log['action']?.toString() ?? '')),

          (log) => AdminDataColumn(label: Ar.colUser, cellBuilder: (_, __) {

            final user = log['user'] as Map<String, dynamic>?;

            return Text(user?['name']?.toString() ?? Ar.notAvailable);

          }),

          (log) => AdminDataColumn(label: Ar.colAuditable, cellBuilder: (_, __) => Text(

            '${log['auditable_type'] ?? ''} #${log['auditable_id'] ?? ''}',

          )),

          (log) => AdminDataColumn(label: Ar.colDate, cellBuilder: (_, __) => Text(log['created_at']?.toString() ?? '')),

        ],

      ),

    );

  }

}

