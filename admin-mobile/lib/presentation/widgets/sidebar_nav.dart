import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';

enum SidebarNavMode { desktop, tablet, drawer, bottomNav }

class SidebarNav extends StatelessWidget {
  const SidebarNav({
    super.key,
    required this.client,
    required this.selectedIndex,
    required this.onDestinationSelected,
    required this.mode,
  });

  final ApiClient client;
  final int selectedIndex;
  final ValueChanged<int> onDestinationSelected;
  final SidebarNavMode mode;

  static const _allDestinations = <NavDestination>[
    NavDestination(
      path: AppRoutes.dashboard,
      label: Ar.navDashboard,
      icon: Icons.dashboard_outlined,
      anyPermissions: ['platform.view', 'access.manage', 'services.supervise'],
    ),
    NavDestination(
      path: AppRoutes.organizations,
      label: Ar.navOrganizations,
      icon: Icons.business_outlined,
      anyPermissions: ['platform.view', 'access.manage'],
    ),
    NavDestination(
      path: AppRoutes.users,
      label: Ar.navUsers,
      icon: Icons.people_outline,
      permission: 'access.manage',
    ),
    NavDestination(
      path: AppRoutes.roles,
      label: Ar.navRoles,
      icon: Icons.admin_panel_settings_outlined,
      permission: 'access.manage',
    ),
    NavDestination(
      path: AppRoutes.agriculture,
      label: Ar.navAgriculture,
      icon: Icons.agriculture_outlined,
      anyPermissions: ['farm.view', 'crop.view', 'soil.view', 'diagnosis.view'],
    ),
    NavDestination(
      path: AppRoutes.content,
      label: Ar.navContent,
      icon: Icons.article_outlined,
      anyPermissions: ['training.view', 'library.view'],
    ),
    NavDestination(
      path: AppRoutes.store,
      label: Ar.navStore,
      icon: Icons.storefront_outlined,
      anyPermissions: ['business.view', 'platform.view'],
    ),
    NavDestination(
      path: AppRoutes.marketing,
      label: Ar.navMarketing,
      icon: Icons.campaign_outlined,
      anyPermissions: ['marketing.view', 'marketing.manage', 'marketing.admin'],
    ),
    NavDestination(
      path: AppRoutes.communications,
      label: Ar.navCommunications,
      icon: Icons.forum_outlined,
      anyPermissions: ['platform.view', 'marketing.view'],
    ),
    NavDestination(
      path: AppRoutes.ai,
      label: Ar.navAi,
      icon: Icons.smart_toy_outlined,
      anyPermissions: ['ai.use', 'ai.assistant', 'ai.vision'],
    ),
    NavDestination(
      path: AppRoutes.reports,
      label: Ar.navReports,
      icon: Icons.analytics_outlined,
      permission: 'platform.view',
    ),
    NavDestination(
      path: AppRoutes.notifications,
      label: Ar.navNotifications,
      icon: Icons.notifications_outlined,
      permission: 'platform.view',
    ),
    NavDestination(
      path: AppRoutes.audit,
      label: Ar.navAudit,
      icon: Icons.receipt_long_outlined,
      permission: 'access.manage',
    ),
    NavDestination(
      path: AppRoutes.monitoring,
      label: Ar.navMonitoring,
      icon: Icons.monitor_heart_outlined,
      anyPermissions: ['monitoring.view', 'access.manage', 'services.supervise'],
    ),
    NavDestination(
      path: AppRoutes.settings,
      label: Ar.navSettings,
      icon: Icons.settings_outlined,
      permission: 'platform.view',
    ),
  ];

  static const bottomNavPaths = [
    AppRoutes.dashboard,
    AppRoutes.users,
    AppRoutes.reports,
    AppRoutes.notifications,
    AppRoutes.settings,
  ];

  static List<NavDestination> visibleDestinations(ApiClient client) {
    return _allDestinations.where((item) => item.isVisible(client)).toList(growable: false);
  }

  static List<NavDestination> bottomDestinations(ApiClient client) {
    final visible = visibleDestinations(client);
    return visible.where((item) => bottomNavPaths.contains(item.path)).toList(growable: false);
  }

  static int indexForLocation(String location, ApiClient client) {
    final destinations = visibleDestinations(client);
    final index = destinations.indexWhere((item) => item.path == location);
    return index >= 0 ? index : 0;
  }

  static String pathForIndex(int index, ApiClient client) {
    final destinations = visibleDestinations(client);
    if (index < 0 || index >= destinations.length) {
      return AppRoutes.dashboard;
    }
    return destinations[index].path;
  }

  static int bottomIndexForLocation(String location, ApiClient client) {
    final destinations = bottomDestinations(client);
    final index = destinations.indexWhere((item) => item.path == location);
    return index >= 0 ? index : 0;
  }

  static String bottomPathForIndex(int index, ApiClient client) {
    final destinations = bottomDestinations(client);
    if (index < 0 || index >= destinations.length) {
      return AppRoutes.dashboard;
    }
    return destinations[index].path;
  }

  List<NavDestination> get _destinations => visibleDestinations(client);

  @override
  Widget build(BuildContext context) {
    final destinations = _destinations;

    switch (mode) {
      case SidebarNavMode.desktop:
        return SizedBox(
          width: 260,
          child: Material(
            color: Theme.of(context).colorScheme.surfaceContainerLow,
            child: ListView(
              padding: const EdgeInsets.symmetric(vertical: 12),
              children: [
                Padding(
                  padding: const EdgeInsetsDirectional.fromSTEB(20, 8, 20, 16),
                  child: Text(
                    Ar.appTitle,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                  ),
                ),
                for (var index = 0; index < destinations.length; index++)
                  _SidebarTile(
                    destination: destinations[index],
                    selected: selectedIndex == index,
                    onTap: () => onDestinationSelected(index),
                  ),
              ],
            ),
          ),
        );
      case SidebarNavMode.tablet:
        return NavigationRail(
          extended: false,
          selectedIndex: selectedIndex,
          onDestinationSelected: onDestinationSelected,
          labelType: NavigationRailLabelType.selected,
          destinations: [
            for (final item in destinations)
              NavigationRailDestination(
                icon: Icon(item.icon),
                label: Text(item.label),
              ),
          ],
        );
      case SidebarNavMode.drawer:
        return ListView(
          padding: const EdgeInsets.symmetric(vertical: 8),
          children: [
            DrawerHeader(
              margin: EdgeInsets.zero,
              child: Align(
                alignment: AlignmentDirectional.bottomStart,
                child: Text(
                  Ar.appTitle,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                ),
              ),
            ),
            for (var index = 0; index < destinations.length; index++)
              ListTile(
                leading: Icon(destinations[index].icon),
                title: Text(destinations[index].label),
                selected: selectedIndex == index,
                onTap: () => onDestinationSelected(index),
              ),
          ],
        );
      case SidebarNavMode.bottomNav:
        final bottomItems = bottomDestinations(client);
        final bottomIndex = bottomIndexForLocation(
          GoRouterState.of(context).matchedLocation,
          client,
        );
        return NavigationBar(
          selectedIndex: bottomIndex,
          onDestinationSelected: (index) => onDestinationSelected(index),
          destinations: [
            for (final item in bottomItems)
              NavigationDestination(
                icon: Icon(item.icon),
                label: item.label,
              ),
          ],
        );
    }
  }
}

class NavDestination {
  const NavDestination({
    required this.path,
    required this.label,
    required this.icon,
    this.permission,
    this.anyPermissions = const [],
  });

  final String path;
  final String label;
  final IconData icon;
  final String? permission;
  final List<String> anyPermissions;

  bool isVisible(ApiClient client) {
    if (permission != null && client.hasPermission(permission!)) {
      return true;
    }
    if (anyPermissions.isNotEmpty && client.hasAnyPermission(anyPermissions)) {
      return true;
    }
    return permission == null && anyPermissions.isEmpty;
  }
}

class _SidebarTile extends StatelessWidget {
  const _SidebarTile({
    required this.destination,
    required this.selected,
    required this.onTap,
  });

  final NavDestination destination;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
      child: ListTile(
        leading: Icon(destination.icon),
        title: Text(destination.label),
        selected: selected,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        selectedTileColor: colorScheme.primaryContainer,
        onTap: onTap,
      ),
    );
  }
}

extension SidebarNavigation on BuildContext {
  void goNavIndex(int index, ApiClient client) => go(SidebarNav.pathForIndex(index, client));

  void goBottomNavIndex(int index, ApiClient client) => go(SidebarNav.bottomPathForIndex(index, client));
}
