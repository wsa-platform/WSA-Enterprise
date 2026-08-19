import 'package:flutter/material.dart';

import 'package:wsa_admin/data/api/api_client.dart';

import 'package:wsa_admin/l10n/m22_strings.dart';

import 'package:wsa_admin/presentation/widgets/admin_data_list.dart';

import 'package:wsa_admin/presentation/widgets/crud_helpers.dart';

import 'package:wsa_admin/presentation/widgets/module_screen_layout.dart';

import 'package:wsa_admin/presentation/widgets/paginated_data_list.dart';



class NotificationsScreen extends StatefulWidget {

  const NotificationsScreen({super.key, required this.client});

  final ApiClient client;



  @override

  State<NotificationsScreen> createState() => _NotificationsScreenState();

}



class _NotificationsScreenState extends State<NotificationsScreen> {

  final _listKey = GlobalKey<PaginatedDataListState<Map<String, dynamic>>>();



  Future<void> _send() async {

    final data = await SimpleFormDialog.show(context,

      title: Ar.addNotification,

      fields: const [

        FormFieldDef(key: 'title', label: Ar.colTitle),

        FormFieldDef(key: 'body', label: Ar.colDescription),

      ],

    );

    if (data == null) return;

    try {

      await widget.client.adminModules.createNotification(data);

      if (mounted) {

        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text(Ar.saved)));

        _listKey.currentState?.reload();

      }

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _markAllRead() async {

    try {

      await widget.client.adminModules.markAllNotificationsRead();

      if (mounted) _listKey.currentState?.reload();

    } catch (e) {

      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));

    }

  }



  Future<void> _markRead(int id) async {

    try {

      await widget.client.adminModules.readNotification(id);

      if (mounted) _listKey.currentState?.reload();

    } catch (_) {}

  }



  @override

  Widget build(BuildContext context) {

    final canManage = widget.client.hasPermission('access.manage');

    return ModuleScreenLayout(

      title: Ar.notificationsOverview,

      loading: false,

      error: null,

      empty: false,

      onRetry: () => _listKey.currentState?.reload(),

      body: Column(

        crossAxisAlignment: CrossAxisAlignment.start,

        children: [

          Row(

            children: [

              CrudActionBar(canManage: canManage, onAdd: _send, addLabel: Ar.addNotification),

              const SizedBox(width: 8),

              TextButton.icon(

                onPressed: _markAllRead,

                icon: const Icon(Icons.done_all),

                label: const Text(Ar.markAllRead),

              ),

            ],

          ),

          PaginatedDataList<Map<String, dynamic>>(

            key: _listKey,

            fetchPage: (page, perPage) => widget.client.adminModules.notificationsPage(page: page, perPage: perPage),

            columns: [

              (item) => AdminDataColumn(label: Ar.colTitle, cellBuilder: (_, __) => Text(item['title']?.toString() ?? '')),

              (item) => AdminDataColumn(label: Ar.colType, cellBuilder: (_, __) => Text(item['type']?.toString() ?? '')),

              (item) => AdminDataColumn(label: Ar.colRead, cellBuilder: (_, __) => Text(

                item['read_at'] != null ? Ar.statusActive : Ar.statusInactive,

              )),

              (item) => AdminDataColumn(label: Ar.colDate, cellBuilder: (_, __) => Text(item['created_at']?.toString() ?? '')),

              (item) => AdminDataColumn(label: Ar.colAction, cellBuilder: (_, __) {

                if (item['read_at'] != null) return const SizedBox.shrink();

                return IconButton(

                  icon: const Icon(Icons.mark_email_read_outlined),

                  onPressed: () => _markRead(item['id'] as int),

                );

              }),

            ],

          ),

        ],

      ),

    );

  }

}

