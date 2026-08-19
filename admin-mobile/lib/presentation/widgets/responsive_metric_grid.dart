import 'package:flutter/material.dart';
import 'package:wsa_admin/core/layout/breakpoints.dart';

/// Responsive grid for metric cards — 1 col mobile, 2 tablet, 3–4 desktop.
class ResponsiveMetricGrid extends StatelessWidget {
  const ResponsiveMetricGrid({
    super.key,
    required this.children,
  });

  final List<Widget> children;

  static int columnCount(double width) {
    if (AdminBreakpoints.isDesktop(width)) {
      return width >= 1200 ? 4 : 3;
    }
    if (AdminBreakpoints.isTablet(width)) {
      return 2;
    }
    return 1;
  }

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final crossAxisCount = columnCount(constraints.maxWidth);

        return GridView.count(
          crossAxisCount: crossAxisCount,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 16,
          crossAxisSpacing: 16,
          childAspectRatio: crossAxisCount == 1 ? 2.4 : 1.6,
          children: children,
        );
      },
    );
  }
}
