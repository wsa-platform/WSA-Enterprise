import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/routing/routes.dart';
import 'package:wsa_admin/data/api/api_exception.dart';
import 'package:wsa_admin/l10n/strings.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.auth});

  final AuthController auth;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _error = widget.auth.bootstrapError;
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _signIn() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await widget.auth.login(_email.text.trim(), _password.text);
      if (!mounted) return;
      if (widget.auth.hasAdminAccess) {
        context.go(AppRoutes.dashboard);
      } else {
        context.go(AppRoutes.accessDenied);
      }
    } on ApiException catch (error) {
      setState(() => _error = error.toString());
    } catch (error) {
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 420),
          child: Card(
            margin: const EdgeInsets.all(24),
            child: Padding(
              padding: const EdgeInsets.all(28),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    Ar.appTitle,
                    style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.bold),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    Ar.loginSubtitle,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Colors.black54),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 28),
                  TextField(
                    controller: _email,
                    decoration: const InputDecoration(labelText: Ar.email),
                    keyboardType: TextInputType.emailAddress,
                    textInputAction: TextInputAction.next,
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _password,
                    decoration: const InputDecoration(labelText: Ar.password),
                    obscureText: true,
                    onSubmitted: (_) => _loading ? null : _signIn(),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 14),
                    Text(_error!, style: const TextStyle(color: Colors.red), textAlign: TextAlign.center),
                  ],
                  const SizedBox(height: 22),
                  FilledButton(
                    onPressed: _loading ? null : _signIn,
                    child: Text(_loading ? Ar.loggingIn : Ar.login),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
