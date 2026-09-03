import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/widgets/async_state.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic>? me;
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
      me = await widget.client.platform.me();
    } on ApiException catch (e) {
      error = e.toString();
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = widget.client.typedUser;
    final permissions = (me?['permissions'] as List<dynamic>?)
            ?.map((item) => '$item')
            .toList() ??
        const <String>[];
    final roles = (me?['roles'] as List<dynamic>?) ?? const [];

    return AsyncState(
      loading: loading,
      error: error,
      onRetry: load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: ListTile(
              leading: const CircleAvatar(child: Icon(Icons.person)),
              title: Text(
                  user?.name ?? me?['user']?['name']?.toString() ?? 'User'),
              subtitle:
                  Text(user?.email ?? me?['user']?['email']?.toString() ?? ''),
            ),
          ),
          const SizedBox(height: 12),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Organization',
                      style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  Text(
                      'Organization ID: ${me?['organization_id'] ?? widget.client.organizationId ?? '—'}'),
                  Text('Membership role: ${me?['membership_role'] ?? '—'}'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Roles', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  if (roles.isEmpty) const Text('No roles loaded.'),
                  for (final role in roles)
                    Text('• ${(role as Map)['name'] ?? role['slug'] ?? role}'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Permissions',
                      style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  if (permissions.isEmpty) const Text('No permissions loaded.'),
                  for (final permission in permissions) Text('• $permission'),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
