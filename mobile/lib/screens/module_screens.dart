import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';

class ModuleListScreen extends StatefulWidget {
  const ModuleListScreen({super.key, required this.client, required this.title, required this.path});

  final ApiClient client;
  final String title;
  final String path;

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

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      rows = await widget.client.fetchList(widget.path);
    } catch (e) {
      error = e.toString();
      rows = [];
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading && rows.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }
    if (error != null) {
      return Center(child: Text(error!));
    }

    return RefreshIndicator(
      onRefresh: load,
      child: ListView.builder(
        itemCount: rows.length,
        itemBuilder: (context, index) {
          final row = rows[index] as Map<String, dynamic>;
          final title = row['title_ar'] ?? row['title'] ?? row['reference'] ?? row['name'] ?? row['code'] ?? 'Record';
          final subtitle = row['summary_ar'] ?? row['summary'] ?? row['status'] ?? '';
          return ListTile(
            title: Text('$title', textDirection: TextDirection.rtl),
            subtitle: '$subtitle'.isEmpty ? null : Text('$subtitle', textDirection: TextDirection.rtl),
          );
        },
      ),
    );
  }
}

class FarmsScreen extends StatelessWidget {
  const FarmsScreen({super.key, required this.client});

  final ApiClient client;

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Column(
        children: [
          const TabBar(tabs: [Tab(text: 'Farms'), Tab(text: 'Fields')]),
          Expanded(
            child: TabBarView(
              children: [
                ModuleListScreen(client: client, title: 'Farms', path: '/farm/farms'),
                ModuleListScreen(client: client, title: 'Fields', path: '/farm/fields'),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
