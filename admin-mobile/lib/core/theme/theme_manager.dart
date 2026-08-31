import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/theme/admin_theme.dart';

/// Runtime theme manager — applies theme immediately and persists via user settings API.
class ThemeManager extends ChangeNotifier {
  ThemeManager(this._client);

  final ApiClient _client;

  ThemeMode _themeMode = ThemeMode.light;
  Color _primary = AdminTheme.defaultPrimary;
  Color _secondary = AdminTheme.defaultSecondary;
  Color _sidebar = AdminTheme.defaultSidebar;
  bool _loaded = false;

  ThemeMode get themeMode => _themeMode;
  Color get primaryColor => _primary;
  Color get secondaryColor => _secondary;
  Color get sidebarColor => _sidebar;
  bool get isLoaded => _loaded;

  ThemeData get lightTheme => AdminTheme.themed(
        primary: _primary,
        secondary: _secondary,
        sidebar: _sidebar,
        brightness: Brightness.light,
      );

  ThemeData get darkTheme => AdminTheme.themed(
        primary: _primary,
        secondary: _secondary,
        sidebar: _sidebar,
        brightness: Brightness.dark,
      );

  Future<void> loadFromApi() async {
    try {
      final settings = await _client.adminModules.userSettings();
      applyFromSettings(settings);
    } catch (_) {
      _loaded = true;
      notifyListeners();
    }
  }

  void applyFromSettings(Map<String, dynamic> settings) {
    final theme = _read(settings, 'appearance.theme') ?? 'light';
    _themeMode = theme == 'dark' ? ThemeMode.dark : ThemeMode.light;

    final primaryHex = _read(settings, 'appearance.primary_color');
    if (primaryHex != null) _primary = _parseColor(primaryHex, _primary);

    final secondaryHex = _read(settings, 'appearance.secondary_color');
    if (secondaryHex != null) _secondary = _parseColor(secondaryHex, _secondary);

    final sidebarHex = _read(settings, 'appearance.sidebar_color');
    if (sidebarHex != null) _sidebar = _parseColor(sidebarHex, _sidebar);

    _loaded = true;
    notifyListeners();
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    _themeMode = mode;
    notifyListeners();
    await _persist();
  }

  Future<void> setPrimaryColor(Color color) async {
    _primary = color;
    notifyListeners();
    await _persist();
  }

  Future<void> setSecondaryColor(Color color) async {
    _secondary = color;
    notifyListeners();
    await _persist();
  }

  Future<void> _persist() async {
    await _client.adminModules.updateUserSettings({
      'appearance.theme': _themeMode == ThemeMode.dark ? 'dark' : 'light',
      'appearance.primary_color': _colorToHex(_primary),
      'appearance.secondary_color': _colorToHex(_secondary),
      'appearance.sidebar_color': _colorToHex(_sidebar),
    });
  }

  String? _read(Map<String, dynamic> settings, String key) {
    final val = settings[key];
    if (val is Map) return val['value']?.toString();
    return val?.toString();
  }

  Color _parseColor(String hex, Color fallback) {
    final cleaned = hex.replaceAll('#', '');
    if (cleaned.length == 6) {
      final value = int.tryParse('FF$cleaned', radix: 16);
      if (value != null) return Color(value);
    }
    return fallback;
  }

  String _colorToHex(Color color) =>
      '#${color.toARGB32().toRadixString(16).padLeft(8, '0').substring(2)}';
}
