import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:http/http.dart' show ClientException;
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/layout/breakpoints.dart';
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
  final _formKey = GlobalKey<FormState>();
  bool _loading = false;
  String? _error;
  String? _info;

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

  String _formatError(Object error) {
    if (error is ApiException) return error.toString();
    if (error is ClientException) return Ar.connectionError;
    final message = error.toString();
    if (message.contains('ClientException') || message.contains('Failed to fetch')) {
      return Ar.connectionError;
    }
    return Ar.unknownError;
  }

  Future<void> _afterAuth() async {
    if (!mounted) return;
    if (widget.auth.hasAdminAccess) {
      context.go(AppRoutes.dashboard);
    } else {
      context.go(AppRoutes.accessDenied);
    }
  }

  Future<void> _signInEmail() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _loading = true;
      _error = null;
      _info = null;
    });
    try {
      await widget.auth.login(_email.text.trim(), _password.text);
      await _afterAuth();
    } catch (error) {
      setState(() => _error = _formatError(error));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _forgotPassword() async {
    if (_email.text.trim().isEmpty) {
      setState(() => _error = Ar.emailRequiredForReset);
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
      _info = null;
    });
    try {
      await widget.auth.client.auth.forgotPassword(_email.text.trim());
      setState(() => _info = Ar.passwordResetSent);
    } catch (error) {
      setState(() => _error = _formatError(error));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final wide = MediaQuery.sizeOf(context).width >= AdminBreakpoints.desktop;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: BoxConstraints(maxWidth: wide ? 520 : 420),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        Ar.appTitle,
                        style: Theme.of(context).textTheme.headlineSmall,
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        Ar.loginSubtitle,
                        textAlign: TextAlign.center,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                      const SizedBox(height: 24),
                      Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            TextFormField(
                              controller: _email,
                              decoration: const InputDecoration(labelText: Ar.email),
                              keyboardType: TextInputType.emailAddress,
                              textInputAction: TextInputAction.next,
                              autofillHints: const [AutofillHints.email],
                              validator: (v) =>
                                  (v == null || v.trim().isEmpty) ? Ar.fieldRequired : null,
                            ),
                            const SizedBox(height: 12),
                            TextFormField(
                              controller: _password,
                              decoration: const InputDecoration(labelText: Ar.password),
                              obscureText: true,
                              textInputAction: TextInputAction.done,
                              autofillHints: const [AutofillHints.password],
                              onFieldSubmitted: (_) => _loading ? null : _signInEmail(),
                              validator: (v) =>
                                  (v == null || v.isEmpty) ? Ar.fieldRequired : null,
                            ),
                            const SizedBox(height: 8),
                            Align(
                              alignment: AlignmentDirectional.centerStart,
                              child: TextButton(
                                onPressed: _loading ? null : _forgotPassword,
                                child: const Text(Ar.forgotPassword),
                              ),
                            ),
                            const SizedBox(height: 8),
                            FilledButton(
                              onPressed: _loading ? null : _signInEmail,
                              child: Text(_loading ? Ar.loggingIn : Ar.login),
                            ),
                          ],
                        ),
                      ),
                      if (_error != null) ...[
                        const SizedBox(height: 12),
                        Text(
                          _error!,
                          style: TextStyle(color: Theme.of(context).colorScheme.error),
                          textAlign: TextAlign.center,
                        ),
                      ],
                      if (_info != null) ...[
                        const SizedBox(height: 12),
                        Text(
                          _info!,
                          style: TextStyle(color: Colors.blue.shade700),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
