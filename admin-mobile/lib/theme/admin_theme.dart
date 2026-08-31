import 'package:flutter/material.dart';

abstract final class AdminTheme {
  static const defaultPrimary = Color(0xFF1B4332);
  static const defaultSecondary = Color(0xFF40916C);
  static const defaultSidebar = Color(0xFF0D2818);
  static const _surface = Color(0xFFF8FAF7);
  static const _outline = Color(0xFFD8E2DC);

  static ThemeData light() => themed(
        primary: defaultPrimary,
        secondary: defaultSecondary,
        sidebar: defaultSidebar,
        brightness: Brightness.light,
      );

  static ThemeData themed({
    required Color primary,
    required Color secondary,
    required Color sidebar,
    required Brightness brightness,
  }) {
    final isDark = brightness == Brightness.dark;
    final surface = isDark ? const Color(0xFF101815) : _surface;

    final base = ColorScheme.fromSeed(
      seedColor: primary,
      primary: primary,
      secondary: secondary,
      surface: surface,
      brightness: brightness,
    );

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: base,
      scaffoldBackgroundColor: surface,
      appBarTheme: AppBarTheme(
        backgroundColor: surface,
        foregroundColor: primary,
        elevation: 0,
      ),
      cardTheme: CardThemeData(
        color: isDark ? const Color(0xFF16201B) : Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: BorderSide(color: isDark ? Colors.white12 : _outline),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: isDark ? const Color(0xFF16201B) : Colors.white,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      ),
      navigationRailTheme: NavigationRailThemeData(
        backgroundColor: sidebar,
        indicatorColor: secondary,
        selectedIconTheme: const IconThemeData(color: Colors.white),
        unselectedIconTheme: const IconThemeData(color: Color(0xFFB7C9BC)),
        selectedLabelTextStyle: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
        unselectedLabelTextStyle: const TextStyle(color: Color(0xFFB7C9BC)),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: primary,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      ),
      dividerTheme: DividerThemeData(color: isDark ? Colors.white12 : _outline),
      fontFamily: 'Segoe UI',
    );
  }
}
