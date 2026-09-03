import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';
import 'package:wsa_enterprise/data/media/diagnosis_image.dart';
import 'package:wsa_enterprise/data/models/stage8_models.dart';
import 'package:wsa_enterprise/l10n/ar_strings.dart';

class PlantDiagnosisScreen extends StatefulWidget {
  const PlantDiagnosisScreen({super.key, required this.client});

  final ApiClient client;

  @override
  State<PlantDiagnosisScreen> createState() => _PlantDiagnosisScreenState();
}

class _PlantDiagnosisScreenState extends State<PlantDiagnosisScreen> {
  final notesController = TextEditingController();
  final plantController = TextEditingController();
  DiagnosisImageSelection? image;
  PlantDiagnosisResult? result;
  String? error;
  String? pickerMessage;
  bool loading = false;

  final _validator = const DiagnosisImageValidator();

  @override
  void dispose() {
    notesController.dispose();
    plantController.dispose();
    super.dispose();
  }

  Future<void> pick(Future<DiagnosisImageSelection?> Function() picker) async {
    setState(() {
      pickerMessage = null;
      error = null;
    });
    final selected = await picker();
    if (selected == null) {
      setState(() => pickerMessage = ArStrings.imagePickerUnavailable);
      return;
    }
    try {
      _validator.validate(selected);
      setState(() => image = selected);
    } on DiagnosisImageValidationException catch (e) {
      setState(() {
        image = null;
        error = e.message;
      });
    }
  }

  Future<void> analyze() async {
    final selected = image;
    if (selected == null) {
      setState(() => error = ArStrings.imageInvalid);
      return;
    }
    setState(() {
      loading = true;
      error = null;
    });
    try {
      result = await widget.client.publicApi.analyzeDiagnosis(
        image: selected,
        plantName: plantController.text.trim(),
        notes: notesController.text.trim(),
      );
    } on ApiException catch (e) {
      error = e.isNetworkFailure ? ArStrings.networkError : e.toString();
      result = null;
    } catch (_) {
      error = ArStrings.networkError;
      result = null;
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final current = result;
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
        Text(ArStrings.plantDiagnosis,
            style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 8),
        const Text(ArStrings.diagnosisIndependent),
        const SizedBox(height: 12),
        TextField(
          controller: plantController,
          decoration: const InputDecoration(labelText: 'اسم النبات'),
        ),
        const SizedBox(height: 8),
        TextField(
          controller: notesController,
          decoration: const InputDecoration(labelText: 'ملاحظات'),
          maxLines: 3,
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: OutlinedButton(
                onPressed: () =>
                    pick(widget.client.diagnosisImagePicker.pickFromGallery),
                child: const Text(ArStrings.pickGallery),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: OutlinedButton(
                onPressed: () =>
                    pick(widget.client.diagnosisImagePicker.pickFromCamera),
                child: const Text(ArStrings.pickCamera),
              ),
            ),
          ],
        ),
        if (pickerMessage != null) ...[
          const SizedBox(height: 8),
          Text(pickerMessage!),
        ],
        if (image != null) ...[
          const SizedBox(height: 12),
          const Text(ArStrings.imagePreview),
          const SizedBox(height: 8),
          Image.memory(image!.bytes, height: 180, fit: BoxFit.contain),
        ],
        const SizedBox(height: 12),
        FilledButton(
          onPressed: loading ? null : analyze,
          child: Text(loading ? ArStrings.loading : ArStrings.analyze),
        ),
        const SizedBox(height: 16),
        if (error != null) Text(error!, style: const TextStyle(color: Colors.red)),
        if (!loading && error == null && current == null)
          const Text('حمّل صورة صالحة ثم ابدأ التحليل.'),
        if (current != null) ...[
          Text(current.message),
          const SizedBox(height: 8),
          Text(ArStrings.observations, style: Theme.of(context).textTheme.titleMedium),
          for (final item in current.observations) Text('• $item'),
          const SizedBox(height: 8),
          Text(ArStrings.candidates, style: Theme.of(context).textTheme.titleMedium),
          for (final item in current.candidates)
            Text(
              '• ${item.label} — ${ArStrings.confidence}: ${item.confidenceBand ?? item.confidenceScore ?? '—'}',
            ),
          const SizedBox(height: 8),
          Text(ArStrings.safety, style: Theme.of(context).textTheme.titleMedium),
          for (final item in current.safetyStatements) Text('• $item'),
          const SizedBox(height: 8),
          Text(ArStrings.additionalInfo, style: Theme.of(context).textTheme.titleMedium),
          for (final item in current.additionalInfo) Text('• $item'),
          if (current.independentOfResearchAgent)
            const Padding(
              padding: EdgeInsets.only(top: 8),
              child: Text(ArStrings.diagnosisIndependent),
            ),
        ],
        ],
      ),
    );
  }
}
