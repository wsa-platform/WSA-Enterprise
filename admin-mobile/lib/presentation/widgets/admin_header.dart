import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/routing/route_titles.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';
import 'package:wsa_admin/presentation/widgets/org_switcher.dart';

class AdminHeader extends StatelessWidget implements PreferredSizeWidget {
  const AdminHeader({
    super.key,
    required this.auth,
    required this.client,
    this.onMenuPressed,
    this.compact = false,
  });

  final AuthController auth;
  final ApiClient client;
  final VoidCallback? onMenuPressed;
  final bool compact;

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).matchedLocation;
    final sectionTitle = RouteTitles.forLocation(location);
    final userName = client.user?['name']?.toString() ?? '';
    final userInitial = userName.isNotEmpty ? userName.characters.first : '?';

    return AppBar(
      leading: onMenuPressed != null
          ? IconButton(
              tooltip: Ar.navMenu,
              onPressed: onMenuPressed,
              icon: const Icon(Icons.menu),
            )
          : null,
      title: compact
          ? Text(sectionTitle)
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  Ar.appTitle,
                  style: Theme.of(context).textTheme.labelSmall?.copyWith(color: Colors.white70),
                ),
                Text(sectionTitle, style: Theme.of(context).textTheme.titleMedium),
              ],
            ),
      actions: [
        if (!compact) ...[
          OrgSwitcher(
            client: client,
            onChanged: () => auth.onOrganizationChanged(),
          ),
          const SizedBox(width: 4),
        ],
        IconButton(
          tooltip: Ar.navNotificationsBtn,
          onPressed: client.hasPermission('platform.view')
              ? () => context.go(AppRoutes.notifications)
              : null,
          icon: const Icon(Icons.notifications_outlined),
        ),
        IconButton(
          tooltip: Ar.navSettings,
          onPressed: client.hasPermission('platform.view')
              ? () => context.go(AppRoutes.settings)
              : null,
          icon: const Icon(Icons.settings_outlined),
        ),
        if (compact)
          PopupMenuButton<String>(
            tooltip: Ar.navProfile,
            icon: CircleAvatar(
              radius: 14,
              child: Text(userInitial, style: const TextStyle(fontSize: 12)),
            ),
            onSelected: (value) async {
              if (value == 'logout') {
                await auth.logout();
                if (context.mounted) context.go(AppRoutes.login);
              } else if (value == 'settings') {
                if (context.mounted) context.go(AppRoutes.settings);
              }
            },
            itemBuilder: (context) => [
              PopupMenuItem<String>(
                enabled: false,
                child: Text(userName.isEmpty ? Ar.appTitle : userName),
              ),
              const PopupMenuDivider(),
              const PopupMenuItem<String>(
                value: 'settings',
                child: Row(
                  children: [
                    Icon(Icons.settings_outlined, size: 18),
                    SizedBox(width: 8),
                    Text(Ar.navSettings),
                  ],
                ),
              ),
              const PopupMenuItem<String>(
                value: 'logout',
                child: Row(
                  children: [
                    Icon(Icons.logout, size: 18),
                    SizedBox(width: 8),
                    Text(Ar.logout),
                  ],
                ),
              ),
            ],
          )
        else ...[
          if (userName.isNotEmpty)
            Padding(
              padding: const EdgeInsetsDirectional.only(end: 4),
              child: Chip(
                avatar: CircleAvatar(child: Text(userInitial)),
                label: Text(userName),
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
        ],
        const SizedBox(width: 4),
      ],
    );
  }
}
