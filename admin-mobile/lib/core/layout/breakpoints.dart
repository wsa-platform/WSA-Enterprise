/// Responsive layout breakpoints for phone, tablet, and desktop.
abstract final class AdminBreakpoints {
  static const desktop = 900.0;
  static const tablet = 600.0;

  static bool isDesktop(double width) => width >= desktop;

  static bool isTablet(double width) => width >= tablet && width < desktop;

  static bool isMobile(double width) => width < tablet;
}
