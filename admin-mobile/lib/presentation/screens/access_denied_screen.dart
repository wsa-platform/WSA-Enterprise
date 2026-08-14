import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/l10n/strings.dart';

class AccessDeniedScreen extends StatelessWidget {
  const AccessDeniedScreen({super.key, required this.auth});

  final AuthController auth;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 480),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.lock_outline, size: 56, color: Colors.orange),
                const SizedBox(height: 16),
                Text(
                  Ar.accessDenied,
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),
                const Text(Ar.accessDeniedHint, textAlign: TextAlign.center),
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: () async {
                    await auth.logout();
                    if (context.mounted) context.go(AppRoutes.login);
                  },
                  child: const Text(Ar.logout),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
