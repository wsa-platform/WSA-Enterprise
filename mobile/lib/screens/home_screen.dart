import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/screens/dashboard_screen.dart';
import 'package:wsa_enterprise/screens/module_screens.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int tab = 0;

  @override
  Widget build(BuildContext context) {
    final titles = ['Dashboard', 'Farms', 'Diagnosis', 'Training', 'Library'];
    final screens = [
      DashboardScreen(client: widget.client),
      FarmsScreen(client: widget.client),
      ModuleListScreen(client: widget.client, title: 'Diagnosis', path: '/diagnosis/requests'),
      ModuleListScreen(client: widget.client, title: 'Training', path: '/training/courses'),
      ModuleListScreen(client: widget.client, title: 'Library', path: '/library/items?publication_status=published'),
    ];

    return Scaffold(
      appBar: AppBar(title: Text('WSA ${titles[tab]}')),
      drawer: Drawer(
        child: ListView(
          children: List.generate(titles.length, (index) {
            return ListTile(
              selected: tab == index,
              title: Text(titles[index]),
              onTap: () {
                setState(() => tab = index);
                Navigator.of(context).pop();
              },
            );
          }),
        ),
      ),
      body: screens[tab],
      bottomNavigationBar: NavigationBar(
        selectedIndex: tab.clamp(0, 4),
        onDestinationSelected: (value) => setState(() => tab = value),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.dashboard_outlined), label: 'Dashboard'),
          NavigationDestination(icon: Icon(Icons.agriculture_outlined), label: 'Farms'),
          NavigationDestination(icon: Icon(Icons.biotech_outlined), label: 'Diagnosis'),
          NavigationDestination(icon: Icon(Icons.school_outlined), label: 'Training'),
          NavigationDestination(icon: Icon(Icons.menu_book_outlined), label: 'Library'),
        ],
      ),
    );
  }
}
