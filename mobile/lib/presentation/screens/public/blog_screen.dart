import 'package:flutter/material.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/widgets/public_async_body.dart';

class BlogScreen extends StatelessWidget {
  const BlogScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const UnavailableServiceScreen(
      title: ArStrings.blog,
      detail: 'لا توجد واجهة مدونة عامة مستقلة في واجهة البرمجة الحالية.',
    );
  }
}
