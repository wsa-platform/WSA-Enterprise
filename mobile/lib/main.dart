import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/shell/public_shell.dart';
import 'package:wsa_enterprise/screens/home_screen.dart';

const bool _showDemoHint =
    bool.fromEnvironment('SHOW_DEMO_HINT', defaultValue: false);

void main() => runApp(const WsaEnterpriseApp());

class WsaEnterpriseApp extends StatelessWidget {
  const WsaEnterpriseApp({super.key, this.client});

  final ApiClient? client;

  @override
  Widget build(BuildContext context) {
    final apiClient = client ?? ApiClient();
    return MaterialApp(
      title: ArStrings.appTitle,
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      builder: (context, child) => Directionality(
        textDirection: TextDirection.rtl,
        child: child ?? const SizedBox.shrink(),
      ),
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF6D28D9)),
        useMaterial3: true,
      ),
      home: BootstrapScreen(client: apiClient),
    );
  }
}

class BootstrapScreen extends StatefulWidget {
  const BootstrapScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<BootstrapScreen> createState() => _BootstrapScreenState();
}

class _BootstrapScreenState extends State<BootstrapScreen> {
  bool loading = true;
  String? error;
  bool showWorkspace = false;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    try {
      await widget.client.restoreSession();
    } on ApiException catch (e) {
      error = e.isNetworkFailure ? ArStrings.networkError : e.toString();
    } catch (e) {
      error = e.toString();
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  void _openWorkspace() {
    if (widget.client.token != null) {
      setState(() => showWorkspace = true);
      return;
    }
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => LoginScreen(
          client: widget.client,
          bootstrapError: error,
          onSignedIn: () {
            Navigator.of(context).pop();
            setState(() => showWorkspace = true);
          },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (showWorkspace && widget.client.token != null) {
      widget.client.onUnauthorized = () {
        if (!mounted) return;
        setState(() => showWorkspace = false);
      };
      return HomeScreen(
        client: widget.client,
        onSignedOut: () => setState(() => showWorkspace = false),
      );
    }

    return PublicShell(
      client: widget.client,
      onOpenWorkspace: _openWorkspace,
    );
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen(
      {super.key, required this.client, this.bootstrapError, this.onSignedIn});

  final ApiClient client;
  final String? bootstrapError;
  final VoidCallback? onSignedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final email = TextEditingController();
  final password = TextEditingController();
  bool loading = false;
  String? error;

  @override
  void initState() {
    super.initState();
    error = widget.bootstrapError;
  }

  Future<void> signIn() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      await widget.client.login(email.text.trim(), password.text);
      if (!mounted) return;
      if (widget.onSignedIn != null) {
        widget.onSignedIn!();
        return;
      }
      widget.client.onUnauthorized = () {
        if (!mounted) return;
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => LoginScreen(
              client: widget.client,
              bootstrapError: 'Your session has expired. Please sign in again.',
            ),
          ),
        );
      };
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => HomeScreen(
            client: widget.client,
            onSignedOut: () {
              Navigator.of(context).pushReplacement(
                MaterialPageRoute(
                  builder: (_) => LoginScreen(
                    client: widget.client,
                    bootstrapError:
                        'Your session has expired. Please sign in again.',
                  ),
                ),
              );
            },
          ),
        ),
      );
    } on ApiException catch (e) {
      setState(() =>
          error = e.isNetworkFailure ? ArStrings.networkError : e.toString());
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text(ArStrings.signIn)),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 360),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(ArStrings.appTitle,
                    style:
                        TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                const Text(
                    'تسجيل الدخول للوصول إلى لوحة المؤسسة والخدمات المحمية.'),
                const SizedBox(height: 24),
                TextField(
                    controller: email,
                    decoration:
                        const InputDecoration(labelText: 'البريد الإلكتروني')),
                const SizedBox(height: 12),
                TextField(
                  controller: password,
                  decoration: const InputDecoration(labelText: 'كلمة المرور'),
                  obscureText: true,
                ),
                if (error != null) ...[
                  const SizedBox(height: 12),
                  Text(error!, style: const TextStyle(color: Colors.red))
                ],
                const SizedBox(height: 20),
                FilledButton(
                  onPressed: loading ? null : signIn,
                  child: Text(loading ? ArStrings.loading : ArStrings.signIn),
                ),
                if (_showDemoHint) ...[
                  const SizedBox(height: 12),
                  const Text(
                      'Staging demo credentials are documented in README.md.',
                      style: TextStyle(fontSize: 12)),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
