import 'package:flutter/material.dart';
import 'package:wsa_enterprise/core/routing/app_routes.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';

class ServicesPortalScreen extends StatelessWidget {
  const ServicesPortalScreen({super.key, required this.onNavigate});

  final ValueChanged<AppSection> onNavigate;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text(ArStrings.servicesPortal,
            style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 12),
        ListTile(
          title: const Text(ArStrings.plantDiagnosis),
          onTap: () => onNavigate(AppSection.plantDiagnosis),
        ),
        ListTile(
          title: const Text(ArStrings.researchAgent),
          onTap: () => onNavigate(AppSection.researchAgent),
        ),
        ListTile(
          title: const Text(ArStrings.store),
          onTap: () => onNavigate(AppSection.store),
        ),
        const ListTile(
          title: Text('التوظيف'),
          subtitle: Text(ArStrings.unavailable),
        ),
        const ListTile(
          title: Text('المشاريع'),
          subtitle: Text(ArStrings.unavailable),
        ),
      ],
    );
  }
}
