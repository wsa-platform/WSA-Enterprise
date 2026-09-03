import 'package:flutter/material.dart';
import 'package:wsa_enterprise/core/routing/app_routes.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';

class HomePublicScreen extends StatelessWidget {
  const HomePublicScreen({super.key, required this.onNavigate});

  final ValueChanged<AppSection> onNavigate;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text('منصة زراعية متكاملة',
            style: Theme.of(context).textTheme.headlineSmall),
        const SizedBox(height: 8),
        const Text('للزراعة الحديثة والمستدامة'),
        const SizedBox(height: 16),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            _HomeCard(
              title: ArStrings.researchAgent,
              onTap: () => onNavigate(AppSection.researchAgent),
            ),
            _HomeCard(
              title: ArStrings.plantDiagnosis,
              onTap: () => onNavigate(AppSection.plantDiagnosis),
            ),
            _HomeCard(
              title: ArStrings.library,
              onTap: () => onNavigate(AppSection.library),
            ),
            _HomeCard(
              title: ArStrings.plantProduction,
              onTap: () => onNavigate(AppSection.plantProduction),
            ),
            _HomeCard(
              title: ArStrings.training,
              onTap: () => onNavigate(AppSection.training),
            ),
            _HomeCard(
              title: ArStrings.store,
              onTap: () => onNavigate(AppSection.store),
            ),
          ],
        ),
      ],
    );
  }
}

class _HomeCard extends StatelessWidget {
  const _HomeCard({required this.title, required this.onTap});

  final String title;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 160,
      child: Card(
        child: InkWell(
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Text(title, textAlign: TextAlign.center),
          ),
        ),
      ),
    );
  }
}
