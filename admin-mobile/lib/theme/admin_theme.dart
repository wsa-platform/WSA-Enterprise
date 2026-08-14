import 'package:flutter/material.dart';

abstract final class AdminTheme {
  static const _primary = Color(0xFF1B4332);
  static const _secondary = Color(0xFF40916C);
  static const _sidebar = Color(0xFF0D2818);
  static const _surface = Color(0xFFF8FAF7);
  static const _outline = Color(0xFFD8E2DC);

  static ThemeData light() {
    final base = ColorScheme.fromSeed(
      seedColor: _primary,
      primary: _primary,
      secondary: _secondary,
      surface: _surface,
      brightness: Brightness.light,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: base,
      scaffoldBackgroundColor: _surface,
      appBarTheme: const AppBarTheme(
        backgroundColor: _surface,
        foregroundColor: _primary,
        elevation: 0,
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: const BorderSide(color: _outline),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      ),
      navigationRailTheme: const NavigationRailThemeData(
        backgroundColor: _sidebar,
        indicatorColor: _secondary,
        selectedIconTheme: IconThemeData(color: Colors.white),
        unselectedIconTheme: IconThemeData(color: Color(0xFFB7C9BC)),
        selectedLabelTextStyle: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
        unselectedLabelTextStyle: TextStyle(color: Color(0xFFB7C9BC)),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: _primary,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      ),
      dividerTheme: const DividerThemeData(color: _outline),
      fontFamily: 'Segoe UI',
    );
  }
}
