import 'package:flutter/material.dart';

class MetricCard extends StatelessWidget {
  const MetricCard({
    super.key,
    required this.label,
    required this.value,
    this.subtitle,
    this.icon,
    this.tone = MetricTone.primary,
  });

  final String label;
  final String value;
  final String? subtitle;
  final IconData? icon;
  final MetricTone tone;

  @override
  Widget build(BuildContext context) {
    final colors = _toneColors(tone);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                if (icon != null) ...[
                  Icon(icon, color: colors.foreground, size: 20),
                  const SizedBox(width: 8),
                ],
                Expanded(
                  child: Text(
                    label,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Colors.black54,
                          fontWeight: FontWeight.w600,
                        ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              value,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    color: colors.foreground,
                    fontWeight: FontWeight.bold,
                  ),
            ),
            if (subtitle != null) ...[
              const SizedBox(height: 6),
              Text(subtitle!, style: Theme.of(context).textTheme.bodySmall),
            ],
          ],
        ),
      ),
    );
  }

  _ToneColors _toneColors(MetricTone tone) {
    switch (tone) {
      case MetricTone.green:
        return const _ToneColors(Color(0xFF2D6A4F));
      case MetricTone.blue:
        return const _ToneColors(Color(0xFF1D4E89));
      case MetricTone.amber:
        return const _ToneColors(Color(0xFFB08900));
      case MetricTone.red:
        return const _ToneColors(Color(0xFF9B2226));
      case MetricTone.primary:
        return const _ToneColors(Color(0xFF1B4332));
    }
  }
}

enum MetricTone { primary, green, blue, amber, red }

class _ToneColors {
  const _ToneColors(this.foreground);
  final Color foreground;
}
