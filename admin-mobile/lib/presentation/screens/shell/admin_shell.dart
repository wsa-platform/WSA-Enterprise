import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';
import 'package:wsa_admin/presentation/widgets/org_switcher.dart';
import 'package:wsa_admin/presentation/widgets/sidebar_nav.dart';

class AdminShell extends StatelessWidget {
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
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).matchedLocation;
    final selectedIndex = SidebarNav.indexForLocation(location);
    final wide = MediaQuery.sizeOf(context).width >= 960;

    return Scaffold(
      appBar: AppBar(
        title: const Text(Ar.appTitle),
        actions: [
          OrgSwitcher(
            client: client,
            onChanged: () => auth.onOrganizationChanged(),
          ),
          const SizedBox(width: 8),
          if (client.user != null)
            Padding(
              padding: const EdgeInsetsDirectional.only(end: 8),
              child: Center(
                child: Text(
                  client.user!['name']?.toString() ?? '',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ),
            ),
          IconButton(
            tooltip: Ar.logout,
            onPressed: () async {
              await auth.logout();
              if (context.mounted) context.go(AppRoutes.login);
            },
            icon: const Icon(Icons.logout),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Row(
        children: [
          SidebarNav(
            extended: wide,
            selectedIndex: selectedIndex,
            onDestinationSelected: (index) => context.goNavIndex(index),
          ),
          const VerticalDivider(width: 1),
          Expanded(child: child),
        ],
      ),
    );
  }
}
