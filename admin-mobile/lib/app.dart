import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';
import 'package:flutter_quill/flutter_quill.dart';
import 'package:wsa_admin/core/auth/auth_controller.dart';
import 'package:wsa_admin/core/routing/app_router.dart';
import 'package:wsa_admin/core/theme/theme_manager.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/strings.dart';
import 'package:wsa_admin/presentation/screens/splash_screen.dart';

class WsaAdminApp extends StatefulWidget {
  const WsaAdminApp({super.key});

  @override
  State<WsaAdminApp> createState() => _WsaAdminAppState();
}

class _WsaAdminAppState extends State<WsaAdminApp> {
  late final ApiClient _client;
  late final AuthController _auth;
  late final ThemeManager _themeManager;
  late final _router = createAppRouter(auth: _auth, client: _client);
  bool _ready = false;

  @override
  void initState() {
    super.initState();
    _client = ApiClient();
    _auth = AuthController(_client);
    _themeManager = ThemeManager(_client);
    _client.onUnauthorized = _auth.handleUnauthorized;
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    await _auth.bootstrap();
    if (_auth.status == AuthStatus.authenticated) {
      await _themeManager.loadFromApi();
    }
    if (mounted) setState(() => _ready = true);
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: MultiProvider(
        providers: [
          Provider<ApiClient>.value(value: _client),
          ChangeNotifierProvider<AuthController>.value(value: _auth),
          ChangeNotifierProvider<ThemeManager>.value(value: _themeManager),
        ],
        child: Consumer<ThemeManager>(
          builder: (context, themeManager, _) {
            return MaterialApp.router(
              title: Ar.appTitle,
              theme: themeManager.lightTheme,
              darkTheme: themeManager.darkTheme,
              themeMode: themeManager.themeMode,
              locale: const Locale('ar'),
              supportedLocales: const [Locale('ar'), Locale('en')],
              localizationsDelegates: const [
                GlobalMaterialLocalizations.delegate,
                GlobalWidgetsLocalizations.delegate,
                GlobalCupertinoLocalizations.delegate,
                FlutterQuillLocalizations.delegate,
              ],
              routerConfig: _router,
              builder: (context, child) {
                if (!_ready || _auth.status == AuthStatus.unknown) {
                  return const SplashScreen();
                }
                return child ?? const SizedBox.shrink();
              },
            );
          },
        ),
      ),
    );
  }
}
