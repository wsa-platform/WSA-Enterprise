import 'package:flutter/material.dart';
import 'package:wsa_enterprise/api/api_client.dart';

class FormFieldConfig {
  const FormFieldConfig({
    required this.name,
    required this.label,
    this.required = false,
    this.initialValue,
    this.maxLines = 1,
  });

  final String name;
  final String label;
  final bool required;
  final String? initialValue;
  final int maxLines;
}

class RecordForm extends StatefulWidget {
  const RecordForm({
    super.key,
    required this.title,
    required this.fields,
    required this.onSubmit,
    this.submitLabel = 'Save',
  });

  final String title;
  final List<FormFieldConfig> fields;
  final Future<void> Function(Map<String, String> values) onSubmit;
  final String submitLabel;

  @override
  State<RecordForm> createState() => _RecordFormState();
}

class _RecordFormState extends State<RecordForm> {
  final _formKey = GlobalKey<FormState>();
  late final Map<String, TextEditingController> _controllers;
  bool loading = false;
  String? error;

  @override
  void initState() {
    super.initState();
    _controllers = {
      for (final field in widget.fields)
        field.name: TextEditingController(text: field.initialValue ?? ''),
    };
  }

  @override
  void dispose() {
    for (final controller in _controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      loading = true;
      error = null;
    });
    try {
      final values = {
        for (final field in widget.fields)
          field.name: _controllers[field.name]!.text.trim(),
      };
      await widget.onSubmit(values);
      for (final controller in _controllers.values) {
        controller.clear();
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('${widget.title} saved.')),
        );
      }
    } on ApiException catch (e) {
      setState(() => error = e.toString());
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(widget.title,
                  style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 12),
              for (final field in widget.fields)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: TextFormField(
                    controller: _controllers[field.name],
                    decoration: InputDecoration(labelText: field.label),
                    maxLines: field.maxLines,
                    validator: field.required
                        ? (value) => (value == null || value.trim().isEmpty)
                            ? '${field.label} is required'
                            : null
                        : null,
                  ),
                ),
              if (error != null) ...[
                Text(error!, style: const TextStyle(color: Colors.red)),
                const SizedBox(height: 8),
              ],
              FilledButton(
                onPressed: loading ? null : _submit,
                child: Text(loading ? 'Saving…' : widget.submitLabel),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
