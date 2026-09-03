import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/data/models/models.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  List<ApiNotification> rows = [];
  String? error;
  bool loading = false;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      rows = await widget.client.fetchNotifications();
    } on ApiException catch (e) {
      error = e.toString();
      rows = [];
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> markRead(ApiNotification notification) async {
    if (notification.isRead) return;
    try {
      final updated = await widget.client.markNotificationRead(notification.id);
      setState(() {
        rows = rows.map((row) => row.id == updated.id ? updated : row).toList();
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  @override
  Widget build(BuildContext context) {
    return AsyncState(
      loading: loading,
      error: error,
      empty: !loading && rows.isEmpty,
      emptyMessage: 'No notifications yet.',
      onRetry: load,
      child: RefreshIndicator(
        onRefresh: load,
        child: ListView.builder(
          itemCount: rows.length,
          itemBuilder: (context, index) {
            final notification = rows[index];
            return ListTile(
              leading: Icon(notification.isRead
                  ? Icons.mark_email_read_outlined
                  : Icons.mark_email_unread_outlined),
              title: Text(notification.title),
              subtitle: Text(notification.body),
              trailing: notification.isRead
                  ? null
                  : const Icon(Icons.circle,
                      size: 10, color: Colors.deepPurple),
              onTap: () => markRead(notification),
            );
          },
        ),
      ),
    );
  }
}
