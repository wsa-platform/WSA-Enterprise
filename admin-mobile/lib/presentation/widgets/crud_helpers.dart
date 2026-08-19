import 'package:flutter/material.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';

/// Reusable form dialog for create/edit operations.
class SimpleFormDialog extends StatefulWidget {
  const SimpleFormDialog({
    super.key,
    required this.title,
    required this.fields,
    this.initialValues = const {},
    this.submitLabel = Ar.save,
  });

  final String title;
  final List<FormFieldDef> fields;
  final Map<String, String> initialValues;
  final String submitLabel;

  static Future<Map<String, String>?> show(
    BuildContext context, {
    required String title,
    required List<FormFieldDef> fields,
    Map<String, String> initialValues = const {},
    String submitLabel = Ar.save,
  }) {
    return showDialog<Map<String, String>>(
      context: context,
      builder: (_) => SimpleFormDialog(
        title: title,
        fields: fields,
        initialValues: initialValues,
        submitLabel: submitLabel,
      ),
    );
  }

  @override
  State<SimpleFormDialog> createState() => _SimpleFormDialogState();
}

class _SimpleFormDialogState extends State<SimpleFormDialog> {
  final _formKey = GlobalKey<FormState>();
  late final Map<String, TextEditingController> _controllers;

  @override
  void initState() {
    super.initState();
    _controllers = {
      for (final field in widget.fields)
        field.key: TextEditingController(text: widget.initialValues[field.key] ?? ''),
    };
  }

  @override
  void dispose() {
    for (final c in _controllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(widget.title),
      content: SizedBox(
        width: 400,
        child: Form(
          key: _formKey,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                for (final field in widget.fields) ...[
                  TextFormField(
                    controller: _controllers[field.key],
                    decoration: InputDecoration(labelText: field.label),
                    obscureText: field.obscure,
                    keyboardType: field.numeric ? TextInputType.number : TextInputType.text,
                    validator: field.required
                        ? (v) => (v == null || v.trim().isEmpty) ? Ar.fieldRequired : null
                        : null,
                  ),
                  const SizedBox(height: 12),
                ],
              ],
            ),
          ),
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text(Ar.cancel)),
        FilledButton(
          onPressed: () {
            if (!_formKey.currentState!.validate()) return;
            Navigator.pop(context, {
              for (final field in widget.fields) field.key: _controllers[field.key]!.text.trim(),
            });
          },
          child: Text(widget.submitLabel),
        ),
      ],
    );
  }
}

class FormFieldDef {
  const FormFieldDef({
    required this.key,
    required this.label,
    this.required = true,
    this.obscure = false,
    this.numeric = false,
  });

  final String key;
  final String label;
  final bool required;
  final bool obscure;
  final bool numeric;
}

/// Action bar with add button for CRUD screens.
class CrudActionBar extends StatelessWidget {
  const CrudActionBar({
    super.key,
    required this.canManage,
    required this.onAdd,
    this.addLabel = Ar.add,
  });

  final bool canManage;
  final VoidCallback onAdd;
  final String addLabel;

  @override
  Widget build(BuildContext context) {
    if (!canManage) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Align(
        alignment: AlignmentDirectional.centerStart,
        child: FilledButton.icon(
          onPressed: onAdd,
          icon: const Icon(Icons.add),
          label: Text(addLabel),
        ),
      ),
    );
  }
}

/// Confirm delete dialog.
Future<bool> confirmDelete(BuildContext context, {String? itemName}) async {
  final result = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: const Text(Ar.confirmDelete),
      content: Text(itemName != null ? '${Ar.confirmDeleteItem}: $itemName' : Ar.confirmDeleteItem),
      actions: [
        TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text(Ar.cancel)),
        FilledButton(
          onPressed: () => Navigator.pop(ctx, true),
          style: FilledButton.styleFrom(backgroundColor: Colors.red),
          child: const Text(Ar.delete),
        ),
      ],
    ),
  );
  return result ?? false;
}
