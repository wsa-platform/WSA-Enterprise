import 'package:flutter/material.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';

class PartialFailureBanner extends StatelessWidget {
  const PartialFailureBanner({
    super.key,
    required this.onRetry,
    this.message = Ar.dashboardPartialFailure,
  });

  final VoidCallback onRetry;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.amber.shade50,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        child: Row(
          children: [
            Icon(Icons.warning_amber_rounded, color: Colors.amber.shade800),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                message,
                style: TextStyle(color: Colors.amber.shade900),
              ),
            ),
            TextButton(onPressed: onRetry, child: const Text(Ar.retry)),
          ],
        ),
      ),
    );
  }
}
