import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/widgets/public_async_body.dart';

class LibraryPublicScreen extends StatefulWidget {
  const LibraryPublicScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<LibraryPublicScreen> createState() => _LibraryPublicScreenState();
}

class _LibraryPublicScreenState extends State<LibraryPublicScreen> {
  FieldCropTaxonomy? taxonomy;
  List<dynamic> items = [];
  List<LibraryCropFile> files = [];
  String? error;
  bool loading = true;
  String? categoryId;
  String? cropId;
  String section = 'farming-needs';
  String? fileMessage;

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final results = await Future.wait([
        widget.client.publicApi.taxonomy(),
        widget.client.publicApi.publishedLibraryItems(locale: 'ar'),
      ]);
      taxonomy = results[0] as FieldCropTaxonomy;
      items = results[1] as List<dynamic>;
    } on ApiException catch (e) {
      error = e.isNetworkFailure ? ArStrings.networkError : e.toString();
    } catch (_) {
      error = ArStrings.networkError;
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> loadFiles() async {
    if (categoryId == null || cropId == null) return;
    setState(() {
      loading = true;
      error = null;
      fileMessage = null;
    });
    try {
      files = await widget.client.publicApi.cropFiles(
        categoryId: categoryId!,
        cropId: cropId!,
        section: section,
      );
    } on ApiException catch (e) {
      error = e.isNetworkFailure ? ArStrings.networkError : e.toString();
      files = [];
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> openFile(LibraryCropFile file) async {
    setState(() => fileMessage = null);
    try {
      await widget.client.publicApi.cropFileContent(file.id);
      final url =
          '${widget.client.baseUrl}${widget.client.publicApi.cropFileContentPath(file.id)}';
      setState(() {
        fileMessage = file.isPdf || file.previewMode == 'inline_browser'
            ? '${ArStrings.pdfOpenHint}\n$url'
            : '${ArStrings.openFile}: $url';
      });
    } on ApiException catch (e) {
      setState(() => fileMessage =
          e.isNetworkFailure ? ArStrings.networkError : e.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    final data = taxonomy;
    return PublicAsyncBody(
      loading: loading && data == null,
      error: error,
      onRetry: load,
      child: data == null
          ? const SizedBox.shrink()
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(ArStrings.library,
                    style: Theme.of(context).textTheme.titleLarge),
                const SizedBox(height: 8),
                for (final category in data.libraryCategories)
                  ListTile(
                    title: Text(category.name),
                    selected: categoryId == category.id,
                    onTap: () {
                      setState(() {
                        categoryId = category.id;
                        cropId = null;
                        files = [];
                      });
                    },
                  ),
                if (categoryId != null) ...[
                  const Divider(),
                  Text('المحاصيل',
                      style: Theme.of(context).textTheme.titleMedium),
                  for (final crop in data.categoryById(categoryId!)?.crops ??
                      const <TaxonomyCrop>[])
                    ListTile(
                      title: Text(crop.name),
                      subtitle: Text(crop.scientificName),
                      selected: cropId == crop.id,
                      onTap: () {
                        setState(() => cropId = crop.id);
                        loadFiles();
                      },
                    ),
                ],
                if (cropId != null) ...[
                  const Divider(),
                  Wrap(
                    spacing: 8,
                    children: [
                      for (final item in const [
                        ('farming-needs', 'زراعة واحتياجات المحصول'),
                        ('scientific-research', 'الأبحاث العلمية'),
                        ('industries', 'الصناعات'),
                        ('other', 'أخرى'),
                      ])
                        ChoiceChip(
                          label: Text(item.$2),
                          selected: section == item.$1,
                          onSelected: (_) {
                            setState(() => section = item.$1);
                            loadFiles();
                          },
                        ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  if (files.isEmpty) const Text(ArStrings.empty),
                  for (final file in files)
                    ListTile(
                      title: Text(file.title),
                      subtitle: Text(file.extension),
                      trailing: const Text(ArStrings.openFile),
                      onTap: () => openFile(file),
                    ),
                  if (fileMessage != null) Text(fileMessage!),
                ],
                const Divider(),
                Text('منشورات المكتبة',
                    style: Theme.of(context).textTheme.titleMedium),
                if (items.isEmpty) const Text(ArStrings.empty),
                for (final row in items)
                  ListTile(
                    title: Text(
                        '${(row as Map)['title_ar'] ?? row['title'] ?? 'مادة'}'),
                    subtitle:
                        Text('${row['summary_ar'] ?? row['summary'] ?? ''}'),
                  ),
              ],
            ),
    );
  }
}
