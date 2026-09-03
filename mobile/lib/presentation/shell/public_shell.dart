import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/core/routing/app_routes.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/screens/public/about_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/beekeeping_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/blog_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/home_public_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/library_public_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/plant_diagnosis_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/plant_production_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/research_agent_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/services_portal_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/store_screen.dart';
import 'package:wsa_enterprise/presentation/screens/public/training_public_screen.dart';
import 'package:wsa_enterprise/screens/home_screen.dart';

class PublicShell extends StatefulWidget {
  const PublicShell({super.key, required this.client, this.onOpenWorkspace});

  final ApiClient client;
  final VoidCallback? onOpenWorkspace;

  @override
  State<PublicShell> createState() => _PublicShellState();
}

class _PublicShellState extends State<PublicShell> {
  AppSection section = AppSection.home;
  final scaffoldKey = GlobalKey<ScaffoldState>();

  static const _nav = [
    (AppSection.home, ArStrings.home, Icons.home_outlined),
    (
      AppSection.plantProduction,
      ArStrings.plantProduction,
      Icons.grass_outlined
    ),
    (AppSection.honeyBees, ArStrings.honeyBees, Icons.emoji_nature_outlined),
    (AppSection.servicesPortal, ArStrings.servicesPortal, Icons.apps_outlined),
    (AppSection.training, ArStrings.training, Icons.school_outlined),
    (AppSection.library, ArStrings.library, Icons.menu_book_outlined),
    (AppSection.blog, ArStrings.blog, Icons.article_outlined),
    (AppSection.store, ArStrings.store, Icons.storefront_outlined),
    (AppSection.about, ArStrings.about, Icons.info_outline),
  ];

  String get title {
    switch (section) {
      case AppSection.home:
        return ArStrings.home;
      case AppSection.plantProduction:
        return ArStrings.plantProduction;
      case AppSection.honeyBees:
        return ArStrings.honeyBees;
      case AppSection.servicesPortal:
        return ArStrings.servicesPortal;
      case AppSection.training:
        return ArStrings.training;
      case AppSection.library:
        return ArStrings.library;
      case AppSection.blog:
        return ArStrings.blog;
      case AppSection.store:
        return ArStrings.store;
      case AppSection.about:
        return ArStrings.about;
      case AppSection.researchAgent:
        return ArStrings.researchAgent;
      case AppSection.plantDiagnosis:
        return ArStrings.plantDiagnosis;
      case AppSection.workspace:
        return ArStrings.workspace;
    }
  }

  void go(AppSection next) => setState(() => section = next);

  Widget get body {
    final client = widget.client;
    switch (section) {
      case AppSection.home:
        return HomePublicScreen(onNavigate: go);
      case AppSection.plantProduction:
        return PlantProductionScreen(client: client);
      case AppSection.honeyBees:
        return BeekeepingScreen(client: client);
      case AppSection.servicesPortal:
        return ServicesPortalScreen(onNavigate: go);
      case AppSection.training:
        return TrainingPublicScreen(client: client);
      case AppSection.library:
        return LibraryPublicScreen(client: client);
      case AppSection.blog:
        return const BlogScreen();
      case AppSection.store:
        return StoreScreen(client: client);
      case AppSection.about:
        return AboutScreen(client: client);
      case AppSection.researchAgent:
        return ResearchAgentScreen(client: client);
      case AppSection.plantDiagnosis:
        return PlantDiagnosisScreen(client: client);
      case AppSection.workspace:
        return HomeScreen(client: client);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: scaffoldKey,
      appBar: AppBar(
        title: Text(title),
        actions: [
          TextButton(
            onPressed: widget.onOpenWorkspace,
            child: const Text(ArStrings.signIn,
                style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
      drawer: Drawer(
        child: SingleChildScrollView(
          child: Column(
            children: [
            const DrawerHeader(
              child: Align(
                alignment: Alignment.bottomRight,
                child: Text(ArStrings.appTitle,
                    style:
                        TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
              ),
            ),
            for (final item in _nav)
              ListTile(
                leading: Icon(item.$3),
                title: Text(item.$2),
                selected: section == item.$1,
                onTap: () {
                  go(item.$1);
                  Navigator.of(context).pop();
                },
              ),
            const Divider(),
            ListTile(
              leading: const Icon(Icons.psychology_outlined),
              title: const Text(ArStrings.researchAgent),
              selected: section == AppSection.researchAgent,
              onTap: () {
                go(AppSection.researchAgent);
                Navigator.of(context).pop();
              },
            ),
            ListTile(
              leading: const Icon(Icons.biotech_outlined),
              title: const Text(ArStrings.plantDiagnosis),
              selected: section == AppSection.plantDiagnosis,
              onTap: () {
                go(AppSection.plantDiagnosis);
                Navigator.of(context).pop();
              },
            ),
          ],
        ),
      ),
    ),
      body: KeyedSubtree(key: ValueKey(section), child: body),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _bottomIndex,
        onDestinationSelected: (index) {
          if (index == 4) {
            scaffoldKey.currentState?.openDrawer();
            return;
          }
          go(_bottomSections[index]);
        },
        destinations: const [
          NavigationDestination(
              icon: Icon(Icons.home_outlined), label: ArStrings.home),
          NavigationDestination(
              icon: Icon(Icons.grass_outlined),
              label: ArStrings.plantProduction),
          NavigationDestination(
              icon: Icon(Icons.menu_book_outlined), label: ArStrings.library),
          NavigationDestination(
              icon: Icon(Icons.storefront_outlined), label: ArStrings.store),
          NavigationDestination(
              icon: Icon(Icons.menu_outlined), label: ArStrings.more),
        ],
      ),
    );
  }

  static const _bottomSections = [
    AppSection.home,
    AppSection.plantProduction,
    AppSection.library,
    AppSection.store,
  ];

  int get _bottomIndex {
    if (_bottomSections.contains(section)) {
      return _bottomSections.indexOf(section);
    }
    return 4;
  }
}
