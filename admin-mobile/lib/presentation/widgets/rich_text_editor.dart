import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_quill/flutter_quill.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';

/// Arabic RTL rich text editor backed by flutter_quill.
class RichTextEditor extends StatefulWidget {
  const RichTextEditor({
    super.key,
    this.initialContent,
    this.onChanged,
    this.minHeight = 220,
  });

  final String? initialContent;
  final ValueChanged<String>? onChanged;
  final double minHeight;

  @override
  State<RichTextEditor> createState() => RichTextEditorState();
}

class RichTextEditorState extends State<RichTextEditor> {
  late final QuillController _controller;
  final FocusNode _focusNode = FocusNode();
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    Document document;
    if (widget.initialContent != null && widget.initialContent!.trim().isNotEmpty) {
      try {
        final decoded = jsonDecode(widget.initialContent!);
        if (decoded is List) {
          document = Document.fromJson(decoded);
        } else if (decoded is Map<String, dynamic> && decoded['ops'] is List) {
          document = Document.fromJson(decoded['ops'] as List);
        } else {
          document = Document()..insert(0, widget.initialContent!);
        }
      } catch (_) {
        document = Document()..insert(0, widget.initialContent!);
      }
    } else {
      document = Document();
    }

    _controller = QuillController(
      document: document,
      selection: const TextSelection.collapsed(offset: 0),
    );
    _controller.addListener(_emitChange);
  }

  void _emitChange() {
    final delta = jsonEncode(_controller.document.toDelta().toJson());
    widget.onChanged?.call(delta);
  }

  String get contentJson => jsonEncode(_controller.document.toDelta().toJson());

  String get plainText => _controller.document.toPlainText();

  @override
  void dispose() {
    _controller.removeListener(_emitChange);
    _controller.dispose();
    _focusNode.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          QuillSimpleToolbar(
            controller: _controller,
            config: QuillSimpleToolbarConfig(
              showAlignmentButtons: true,
              showDirection: true,
              showLink: true,
              showSearchButton: false,
              multiRowsDisplay: false,
            ),
          ),
          const SizedBox(height: 8),
          Container(
            constraints: BoxConstraints(minHeight: widget.minHeight),
            decoration: BoxDecoration(
              border: Border.all(color: Theme.of(context).dividerColor),
              borderRadius: BorderRadius.circular(8),
            ),
            padding: const EdgeInsets.all(8),
            child: QuillEditor(
              focusNode: _focusNode,
              scrollController: _scrollController,
              controller: _controller,
              config: QuillEditorConfig(
                placeholder: Ar.richTextPlaceholder,
                expands: false,
                autoFocus: false,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
