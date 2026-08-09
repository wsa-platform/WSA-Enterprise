import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/screens/dashboard_screen.dart';
import 'package:wsa_enterprise/screens/feature_screens.dart';
import 'package:wsa_enterprise/screens/module_screens.dart';
import 'package:wsa_enterprise/widgets/org_switcher.dart';

enum AppModule {
  dashboard,
  farms,
  crops,
  soil,
  diagnosis,
  training,
  library,
  ai,
  business,
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.client, this.onSignedOut});

  final ApiClient client;
  final VoidCallback? onSignedOut;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  AppModule module = AppModule.dashboard;
  int reloadToken = 0;

  void reloadAll() => setState(() => reloadToken++);

  Future<void> signOut() async {
    await widget.client.logout();
    widget.onSignedOut?.call();
  }

  String get title {
    switch (module) {
      case AppModule.dashboard:
        return 'Dashboard';
      case AppModule.farms:
        return 'Farms';
      case AppModule.crops:
        return 'Crops';
      case AppModule.soil:
        return 'Soil';
      case AppModule.diagnosis:
        return 'Diagnosis';
      case AppModule.training:
        return 'Training';
      case AppModule.library:
        return 'Library';
      case AppModule.ai:
        return 'AI Services';
      case AppModule.business:
        return 'Business';
    }
  }

  Widget get screen {
    final key = ValueKey('$module-$reloadToken-${widget.client.organizationId}');
    switch (module) {
      case AppModule.dashboard:
        return DashboardScreen(key: key, client: widget.client);
      case AppModule.farms:
        return FarmsScreen(key: key, client: widget.client);
      case AppModule.crops:
        return CropsScreen(key: key, client: widget.client);
      case AppModule.soil:
        return SoilScreen(key: key, client: widget.client);
      case AppModule.diagnosis:
        return DiagnosisScreen(key: key, client: widget.client);
      case AppModule.training:
        return TrainingScreen(key: key, client: widget.client);
      case AppModule.library:
        return LibraryScreen(key: key, client: widget.client);
      case AppModule.ai:
        return AiScreen(key: key, client: widget.client);
      case AppModule.business:
        return BusinessScreen(key: key, client: widget.client);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('WSA $title'),
        actions: [
          OrgSwitcher(client: widget.client, onChanged: reloadAll),
          IconButton(onPressed: () => signOut(), icon: const Icon(Icons.logout), tooltip: 'Sign out'),
        ],
      ),
      drawer: Drawer(
        child: ListView(
          children: [
            UserAccountsDrawerHeader(
              accountName: Text(widget.client.user?['name']?.toString() ?? 'WSA User'),
              accountEmail: Text(widget.client.user?['email']?.toString() ?? ''),
              currentAccountPicture: const CircleAvatar(child: Icon(Icons.person)),
            ),
            for (final entry in _drawerItems)
              ListTile(
                leading: Icon(entry.icon),
                selected: module == entry.module,
                title: Text(entry.label),
                onTap: () {
                  setState(() => module = entry.module);
                  Navigator.of(context).pop();
                },
              ),
          ],
        ),
      ),
      body: screen,
      bottomNavigationBar: NavigationBar(
        selectedIndex: _bottomIndex,
        onDestinationSelected: (index) {
          if (index == 4) {
            Scaffold.of(context).openDrawer();
            return;
          }
          setState(() => module = _bottomModules[index]);
        },
        destinations: const [
          NavigationDestination(icon: Icon(Icons.dashboard_outlined), label: 'Dashboard'),
          NavigationDestination(icon: Icon(Icons.agriculture_outlined), label: 'Farms'),
          NavigationDestination(icon: Icon(Icons.grass_outlined), label: 'Crops'),
          NavigationDestination(icon: Icon(Icons.biotech_outlined), label: 'Diagnosis'),
          NavigationDestination(icon: Icon(Icons.menu_outlined), label: 'More'),
        ],
      ),
    );
  }

  int get _bottomIndex {
    const quick = [AppModule.dashboard, AppModule.farms, AppModule.crops, AppModule.diagnosis];
    if (quick.contains(module)) return quick.indexOf(module);
    return 4;
  }

  static const _bottomModules = [
    AppModule.dashboard,
    AppModule.farms,
    AppModule.crops,
    AppModule.diagnosis,
    AppModule.library,
  ];
}

class _DrawerItem {
  const _DrawerItem(this.module, this.label, this.icon);
  final AppModule module;
  final String label;
  final IconData icon;
}

const _drawerItems = [
  _DrawerItem(AppModule.dashboard, 'Dashboard', Icons.dashboard_outlined),
  _DrawerItem(AppModule.farms, 'Farms', Icons.agriculture_outlined),
  _DrawerItem(AppModule.crops, 'Crops', Icons.grass_outlined),
  _DrawerItem(AppModule.soil, 'Soil', Icons.water_drop_outlined),
  _DrawerItem(AppModule.diagnosis, 'Diagnosis', Icons.biotech_outlined),
  _DrawerItem(AppModule.training, 'Training', Icons.school_outlined),
  _DrawerItem(AppModule.library, 'Library', Icons.menu_book_outlined),
  _DrawerItem(AppModule.ai, 'AI Services', Icons.psychology_outlined),
  _DrawerItem(AppModule.business, 'Business', Icons.storefront_outlined),
];
