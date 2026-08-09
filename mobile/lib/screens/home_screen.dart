import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int tab = 0;
  List<dynamic> rows = [];
  String? error;
  bool loading = false;

  @override
  void initState() {
    super.initState();
    loadTab();
  }

  Future<void> loadTab() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final paths = ['/diagnosis/requests', '/training/courses', '/library/items?publication_status=published'];
      rows = await widget.client.fetchList(paths[tab]);
    } catch (e) {
      error = e.toString();
      rows = [];
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final titles = ['Diagnosis', 'Training', 'Library'];
    return Scaffold(
      appBar: AppBar(title: Text('WSA ${titles[tab]}')),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : error != null
              ? Center(child: Text(error!))
              : ListView.builder(
                  itemCount: rows.length,
                  itemBuilder: (context, index) {
                    final row = rows[index] as Map<String, dynamic>;
                    final title = row['title_ar'] ?? row['title'] ?? row['reference'] ?? row['name'] ?? 'Record';
                    final subtitle = row['summary_ar'] ?? row['summary'] ?? row['status'] ?? '';
                    return ListTile(
                      title: Text('$title', textDirection: TextDirection.rtl),
                      subtitle: subtitle.toString().isEmpty ? null : Text('$subtitle', textDirection: TextDirection.rtl),
                    );
                  },
                ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: tab,
        onDestinationSelected: (value) {
          setState(() => tab = value);
          loadTab();
        },
        destinations: const [
          NavigationDestination(icon: Icon(Icons.biotech_outlined), label: 'Diagnosis'),
          NavigationDestination(icon: Icon(Icons.school_outlined), label: 'Training'),
          NavigationDestination(icon: Icon(Icons.menu_book_outlined), label: 'Library'),
        ],
      ),
    );
  }
}
