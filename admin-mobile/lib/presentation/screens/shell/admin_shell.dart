import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/layout/breakpoints.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/presentation/widgets/admin_header.dart';
import 'package:wsa_admin/presentation/widgets/org_switcher.dart';
import 'package:wsa_admin/presentation/widgets/sidebar_nav.dart';

class AdminShell extends StatefulWidget {
  const AdminShell({
    super.key,
    required this.auth,
    required this.client,
    required this.child,
  });

  final AuthController auth;
  final ApiClient client;
  final Widget child;

  @override
  State<AdminShell> createState() => _AdminShellState();
}

class _AdminShellState extends State<AdminShell> {
  final _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).matchedLocation;
    final width = MediaQuery.sizeOf(context).width;
    final isDesktop = AdminBreakpoints.isDesktop(width);
    final isTablet = AdminBreakpoints.isTablet(width);
    final isMobile = AdminBreakpoints.isMobile(width);

    final selectedIndex = SidebarNav.indexForLocation(location, widget.client);
    final bottomItems = SidebarNav.bottomDestinations(widget.client);

    void navigateTo(int index) {
      context.goNavIndex(index, widget.client);
      if (isMobile) {
        _scaffoldKey.currentState?.closeDrawer();
      }
    }

    void navigateBottom(int index) {
      context.goBottomNavIndex(index, widget.client);
    }

    if (isDesktop) {
      return Scaffold(
        appBar: AdminHeader(auth: widget.auth, client: widget.client),
        body: Row(
          children: [
            SidebarNav(
              client: widget.client,
              mode: SidebarNavMode.desktop,
              selectedIndex: selectedIndex,
              onDestinationSelected: navigateTo,
            ),
            const VerticalDivider(width: 1),
            Expanded(child: widget.child),
          ],
        ),
      );
    }

    if (isTablet) {
      return Scaffold(
        appBar: AdminHeader(auth: widget.auth, client: widget.client),
        body: Row(
          children: [
            SidebarNav(
              client: widget.client,
              mode: SidebarNavMode.tablet,
              selectedIndex: selectedIndex,
              onDestinationSelected: navigateTo,
            ),
            const VerticalDivider(width: 1),
            Expanded(child: widget.child),
          ],
        ),
      );
    }

    return Scaffold(
      key: _scaffoldKey,
      appBar: AdminHeader(
        auth: widget.auth,
        client: widget.client,
        compact: true,
        onMenuPressed: () => _scaffoldKey.currentState?.openDrawer(),
      ),
      drawer: Drawer(
        child: SafeArea(
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.all(16),
                child: OrgSwitcher(
                  client: widget.client,
                  onChanged: () => widget.auth.onOrganizationChanged(),
                ),
              ),
              Expanded(
                child: SidebarNav(
                  client: widget.client,
                  mode: SidebarNavMode.drawer,
                  selectedIndex: selectedIndex,
                  onDestinationSelected: navigateTo,
                ),
              ),
            ],
          ),
        ),
      ),
      body: widget.child,
      bottomNavigationBar: bottomItems.isEmpty
          ? null
          : NavigationBar(
              selectedIndex: SidebarNav.bottomIndexForLocation(location, widget.client),
              onDestinationSelected: navigateBottom,
              destinations: [
                for (final item in bottomItems)
                  NavigationDestination(
                    icon: Icon(item.icon),
                    label: item.label,
                  ),
              ],
            ),
    );
  }
}
