import 'package:flutter/material.dart';
import 'package:wsa_admin/l10n/strings.dart';

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key, this.message});

  final String? message;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const CircularProgressIndicator(),
            const SizedBox(height: 16),
            Text(message ?? Ar.loading),
          ],
        ),
      ),
    );
  }
}
