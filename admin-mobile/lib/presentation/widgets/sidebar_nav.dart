import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/l10n/strings.dart';

class SidebarNav extends StatelessWidget {
  const SidebarNav({
    super.key,
    required this.selectedIndex,
    required this.onDestinationSelected,
    required this.extended,
  });

  final int selectedIndex;
  final ValueChanged<int> onDestinationSelected;
  final bool extended;

  static const destinations = <_NavItem>[
    _NavItem(AppRoutes.dashboard, Ar.navDashboard, Icons.dashboard_outlined),
    _NavItem(AppRoutes.organizations, Ar.navOrganizations, Icons.business_outlined),
    _NavItem(AppRoutes.users, Ar.navUsers, Icons.people_outline),
    _NavItem(AppRoutes.roles, Ar.navRoles, Icons.admin_panel_settings_outlined),
    _NavItem(AppRoutes.agriculture, Ar.navAgriculture, Icons.agriculture_outlined),
    _NavItem(AppRoutes.content, Ar.navContent, Icons.article_outlined),
    _NavItem(AppRoutes.store, Ar.navStore, Icons.storefront_outlined),
    _NavItem(AppRoutes.marketing, Ar.navMarketing, Icons.campaign_outlined),
    _NavItem(AppRoutes.communications, Ar.navCommunications, Icons.forum_outlined),
    _NavItem(AppRoutes.ai, Ar.navAi, Icons.smart_toy_outlined),
    _NavItem(AppRoutes.reports, Ar.navReports, Icons.analytics_outlined),
    _NavItem(AppRoutes.notifications, Ar.navNotifications, Icons.notifications_outlined),
    _NavItem(AppRoutes.audit, Ar.navAudit, Icons.receipt_long_outlined),
    _NavItem(AppRoutes.monitoring, Ar.navMonitoring, Icons.monitor_heart_outlined),
    _NavItem(AppRoutes.settings, Ar.navSettings, Icons.settings_outlined),
  ];

  static int indexForLocation(String location) {
    final index = destinations.indexWhere((item) => item.path == location);
    return index >= 0 ? index : 0;
  }

  static String pathForIndex(int index) {
    if (index < 0 || index >= destinations.length) {
      return AppRoutes.dashboard;
    }
    return destinations[index].path;
  }

  @override
  Widget build(BuildContext context) {
    return NavigationRail(
      extended: extended,
      selectedIndex: selectedIndex,
      onDestinationSelected: onDestinationSelected,
      labelType: extended ? NavigationRailLabelType.none : NavigationRailLabelType.selected,
      destinations: [
        for (final item in destinations)
          NavigationRailDestination(
            icon: Icon(item.icon),
            label: Text(item.label),
          ),
      ],
    );
  }
}

class _NavItem {
  const _NavItem(this.path, this.label, this.icon);
  final String path;
  final String label;
  final IconData icon;
}

extension SidebarNavigation on BuildContext {
  void goNavIndex(int index) => go(SidebarNav.pathForIndex(index));
}
