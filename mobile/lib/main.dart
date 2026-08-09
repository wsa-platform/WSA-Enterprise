import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/screens/home_screen.dart';

void main() => runApp(const WsaEnterpriseApp());

class WsaEnterpriseApp extends StatelessWidget {
  const WsaEnterpriseApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'WSA Enterprise',
      theme: ThemeData(colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF6D28D9))),
      home: BootstrapScreen(client: ApiClient()),
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

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    try {
      await widget.client.restoreSession();
    } on ApiException catch (e) {
      error = e.toString();
    } catch (e) {
      error = e.toString();
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (widget.client.token != null) {
      return HomeScreen(
        client: widget.client,
        onSignedOut: () {
          setState(() {});
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => LoginScreen(client: widget.client)),
          );
        },
      );
    }

    return LoginScreen(client: widget.client, bootstrapError: error);
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.client, this.bootstrapError});

  final ApiClient client;
  final String? bootstrapError;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final email = TextEditingController(text: 'admin@wsa.test');
  final password = TextEditingController(text: 'password');
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
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => HomeScreen(
            client: widget.client,
            onSignedOut: () {
              Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (_) => LoginScreen(client: widget.client)),
              );
            },
          ),
        ),
      );
    } on ApiException catch (e) {
      setState(() => error = e.toString());
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 360),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text('WSA Enterprise', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                const Text('Sign in to access dashboard, farms, crops, soil, diagnosis, training, library, and AI services.'),
                const SizedBox(height: 24),
                TextField(controller: email, decoration: const InputDecoration(labelText: 'Email')),
                const SizedBox(height: 12),
                TextField(controller: password, decoration: const InputDecoration(labelText: 'Password'), obscureText: true),
                if (error != null) ...[const SizedBox(height: 12), Text(error!, style: const TextStyle(color: Colors.red))],
                const SizedBox(height: 20),
                FilledButton(onPressed: loading ? null : signIn, child: Text(loading ? 'Signing in…' : 'Sign in')),
                const SizedBox(height: 12),
                const Text('Demo: admin@wsa.test / password', style: TextStyle(fontSize: 12)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
