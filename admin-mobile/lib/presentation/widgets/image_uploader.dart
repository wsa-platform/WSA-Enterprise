import 'dart:convert';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:wsa_admin/data/api/api_client.dart';
import 'package:wsa_admin/l10n/m22_strings.dart';

/// Image upload/preview widget backed by the media API.
class ImageUploader extends StatefulWidget {
  const ImageUploader({
    super.key,
    required this.client,
    this.initialUrl,
    this.context = 'general',
    this.onUploaded,
    this.onDeleted,
    this.compact = false,
  });

  final ApiClient client;
  final String? initialUrl;
  final String context;
  final void Function(Map<String, dynamic> upload)? onUploaded;
  final VoidCallback? onDeleted;
  final bool compact;

  @override
  State<ImageUploader> createState() => _ImageUploaderState();
}

class _ImageUploaderState extends State<ImageUploader> {
  bool _uploading = false;
  String? _url;
  int? _uploadId;
  String? _error;

  @override
  void initState() {
    super.initState();
    _url = widget.initialUrl;
  }

  Future<void> _pickAndUpload() async {
    setState(() { _uploading = true; _error = null; });
    try {
      final result = await FilePicker.platform.pickFiles(
        type: FileType.image,
        withData: true,
      );
      if (result == null || result.files.isEmpty) {
        setState(() => _uploading = false);
        return;
      }

      final file = result.files.first;
      final bytes = file.bytes;
      if (bytes == null) throw StateError('Could not read image bytes');

      final upload = await widget.client.adminModules.uploadMedia(
        bytes: bytes,
        filename: file.name,
        context: widget.context,
      );

      if (!mounted) return;
      setState(() {
        _url = upload['url']?.toString();
        _uploadId = upload['id'] as int?;
        _uploading = false;
      });
      widget.onUploaded?.call(upload);
    } catch (error) {
      if (!mounted) return;
      setState(() { _error = error.toString(); _uploading = false; });
    }
  }

  Future<void> _delete() async {
    if (_uploadId == null) {
      setState(() { _url = null; });
      widget.onDeleted?.call();
      return;
    }

    setState(() { _uploading = true; _error = null; });
    try {
      await widget.client.adminModules.deleteMedia(_uploadId!);
      if (!mounted) return;
      setState(() { _url = null; _uploadId = null; _uploading = false; });
      widget.onDeleted?.call();
    } catch (error) {
      if (!mounted) return;
      setState(() { _error = error.toString(); _uploading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final previewHeight = widget.compact ? 96.0 : 180.0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (_url != null)
          Stack(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: Image.network(
                  _url!,
                  height: previewHeight,
                  width: double.infinity,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(
                    height: previewHeight,
                    color: Colors.grey.shade200,
                    alignment: Alignment.center,
                    child: const Icon(Icons.broken_image_outlined),
                  ),
                ),
              ),
              Positioned(
                top: 8,
                left: 8,
                child: IconButton.filledTonal(
                  onPressed: _uploading ? null : _delete,
                  icon: const Icon(Icons.delete_outline),
                ),
              ),
            ],
          )
        else
          Container(
            height: previewHeight,
            width: double.infinity,
            decoration: BoxDecoration(
              border: Border.all(color: Theme.of(context).dividerColor),
              borderRadius: BorderRadius.circular(8),
            ),
            alignment: Alignment.center,
            child: const Icon(Icons.image_outlined, size: 40, color: Colors.black38),
          ),
        const SizedBox(height: 8),
        Row(
          children: [
            FilledButton.icon(
              onPressed: _uploading ? null : _pickAndUpload,
              icon: _uploading
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.upload_file),
              label: Text(_url == null ? Ar.uploadImage : Ar.replaceImage),
            ),
          ],
        ),
        if (_error != null) ...[
          const SizedBox(height: 4),
          Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error, fontSize: 12)),
        ],
      ],
    );
  }
}

/// Multi-image uploader for products.
class MultiImageUploader extends StatefulWidget {
  const MultiImageUploader({
    super.key,
    required this.client,
    required this.productId,
    this.initialImages = const [],
    this.onChanged,
  });

  final ApiClient client;
  final int productId;
  final List<Map<String, dynamic>> initialImages;
  final VoidCallback? onChanged;

  @override
  State<MultiImageUploader> createState() => _MultiImageUploaderState();
}

class _MultiImageUploaderState extends State<MultiImageUploader> {
  late List<Map<String, dynamic>> _images;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _images = List<Map<String, dynamic>>.from(widget.initialImages);
  }

  Future<void> _add() async {
    setState(() => _busy = true);
    try {
      final result = await FilePicker.platform.pickFiles(type: FileType.image, withData: true);
      if (result == null || result.files.isEmpty) return;
      final file = result.files.first;
      final bytes = file.bytes;
      if (bytes == null) return;

      final image = await widget.client.adminModules.uploadProductImage(
        widget.productId,
        bytes: bytes,
        filename: file.name,
      );
      if (!mounted) return;
      setState(() => _images.add(image));
      widget.onChanged?.call();
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _remove(Map<String, dynamic> image) async {
    setState(() => _busy = true);
    try {
      await widget.client.adminModules.deleteProductImage(widget.productId, image['id'] as int);
      if (!mounted) return;
      setState(() => _images.remove(image));
      widget.onChanged?.call();
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            for (final image in _images)
              Stack(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.network(
                      image['url']?.toString() ?? '',
                      width: 96,
                      height: 96,
                      fit: BoxFit.cover,
                    ),
                  ),
                  Positioned(
                    top: 0,
                    left: 0,
                    child: IconButton(
                      visualDensity: VisualDensity.compact,
                      onPressed: _busy ? null : () => _remove(image),
                      icon: const Icon(Icons.close, size: 18),
                    ),
                  ),
                ],
              ),
            OutlinedButton.icon(
              onPressed: _busy ? null : _add,
              icon: const Icon(Icons.add_photo_alternate_outlined),
              label: const Text(Ar.addImage),
            ),
          ],
        ),
      ],
    );
  }
}

/// Encode rich text delta JSON for API persistence.
String encodeRichTextDelta(String deltaJson) => deltaJson;

Map<String, dynamic>? decodeRichTextDelta(String? raw) {
  if (raw == null || raw.isEmpty) return null;
  try {
    return jsonDecode(raw) as Map<String, dynamic>;
  } catch (_) {
    return {'ops': [{'insert': raw}]};
  }
}
