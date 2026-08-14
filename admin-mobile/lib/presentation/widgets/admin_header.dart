import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
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
    final userName = client.user?['name']?.toString() ?? '';

    return AppBar(
      leading: onMenuPressed != null
          ? IconButton(
              tooltip: Ar.navMenu,
              onPressed: onMenuPressed,
              icon: const Icon(Icons.menu),
            )
          : null,
      title: Text(compact ? Ar.appTitleShort : Ar.appTitle),
      actions: [
        if (!compact) ...[
          OrgSwitcher(
            client: client,
            onChanged: () => auth.onOrganizationChanged(),
          ),
          const SizedBox(width: 8),
        ],
        if (userName.isNotEmpty && !compact)
          Padding(
            padding: const EdgeInsetsDirectional.only(end: 8),
            child: Center(
              child: Text(
                userName,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ),
          ),
        if (compact)
          PopupMenuButton<String>(
            tooltip: Ar.navMenu,
            onSelected: (value) async {
              if (value == 'logout') {
                await auth.logout();
                if (context.mounted) context.go(AppRoutes.login);
              }
            },
            itemBuilder: (context) => [
              PopupMenuItem<String>(
                enabled: false,
                child: Text(userName.isEmpty ? Ar.appTitle : userName),
              ),
              const PopupMenuDivider(),
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
        else
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
    );
  }
}
