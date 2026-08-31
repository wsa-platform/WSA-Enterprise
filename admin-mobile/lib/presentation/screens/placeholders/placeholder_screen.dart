import 'package:flutter/material.dart';
import 'package:wsa_admin/l10n/strings.dart';

class PlaceholderScreen extends StatelessWidget {
  const PlaceholderScreen({super.key, required this.titleKey});

  final String titleKey;

  static const _titles = {
    'organizations': Ar.navOrganizations,
    'users': Ar.navUsers,
    'roles': Ar.navRoles,
    'agriculture': Ar.navAgriculture,
    'content': Ar.navContent,
    'store': Ar.navProducts,
    'marketing': Ar.navMarketing,
    'communications': Ar.navCommunications,
    'ai': Ar.navAi,
    'reports': Ar.navReports,
    'notifications': Ar.navNotifications,
    'audit': Ar.navAudit,
    'monitoring': Ar.navMonitoring,
    'settings': Ar.navSettings,
  };

  @override
  Widget build(BuildContext context) {
    final title = _titles[titleKey] ?? titleKey;

    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.construction_outlined, size: 48, color: Theme.of(context).colorScheme.primary),
          const SizedBox(height: 16),
          Text(title, style: Theme.of(context).textTheme.headlineSmall),
          const SizedBox(height: 8),
          Text(
            Ar.comingSoon,
            style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: Colors.black54),
          ),
        ],
      ),
    );
  }
}
