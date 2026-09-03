import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';
import 'package:wsa_enterprise/presentation/widgets/public_async_body.dart';

class PlantProductionScreen extends StatefulWidget {
  const PlantProductionScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<PlantProductionScreen> createState() => _PlantProductionScreenState();
}

class _PlantProductionScreenState extends State<PlantProductionScreen> {
  FieldCropTaxonomy? taxonomy;
  String? error;
  bool loading = true;
  String? selectedSectionId;
  String? selectedCategoryId;

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
      taxonomy = await widget.client.publicApi.taxonomy();
      selectedSectionId ??=
          taxonomy!.sections.isNotEmpty ? taxonomy!.sections.first.id : null;
    } on ApiException catch (e) {
      error = e.isNetworkFailure ? ArStrings.networkError : e.toString();
      taxonomy = null;
    } catch (e) {
      error = ArStrings.networkError;
      taxonomy = null;
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final data = taxonomy;
    return PublicAsyncBody(
      loading: loading,
      error: error,
      empty:
          !loading && error == null && (data == null || data.sections.isEmpty),
      onRetry: load,
      child: data == null
          ? const SizedBox.shrink()
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(ArStrings.plantProduction,
                    style: Theme.of(context).textTheme.titleLarge),
                const SizedBox(height: 12),
                for (final section in data.sections)
                  ListTile(
                    title: Text(section.name),
                    selected: selectedSectionId == section.id,
                    onTap: () => setState(() {
                      selectedSectionId = section.id;
                      selectedCategoryId = null;
                    }),
                  ),
                const Divider(),
                ..._categoryTiles(data),
              ],
            ),
    );
  }

  List<Widget> _categoryTiles(FieldCropTaxonomy data) {
    PlantProductionSection? section;
    for (final item in data.sections) {
      if (item.id == selectedSectionId) {
        section = item;
        break;
      }
    }
    if (section == null) return [const Text(ArStrings.empty)];
    final widgets = <Widget>[];
    for (final categoryId in section.libraryCategoryIds) {
      final category = data.categoryById(categoryId);
      if (category == null) continue;
      widgets.add(ExpansionTile(
        title: Text(category.name),
        initiallyExpanded: selectedCategoryId == category.id,
        onExpansionChanged: (open) {
          if (open) setState(() => selectedCategoryId = category.id);
        },
        children: [
          if (category.crops.isEmpty)
            const ListTile(title: Text(ArStrings.empty))
          else
            for (final crop in category.crops)
              ListTile(
                title: Text(crop.name),
                subtitle: crop.scientificName.isEmpty
                    ? null
                    : Text(crop.scientificName),
              ),
        ],
      ));
    }
    return widgets;
  }
}
