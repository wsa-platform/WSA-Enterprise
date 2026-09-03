import 'dart:convert';
import 'dart:typed_data';

class DiagnosisImageSelection {
  const DiagnosisImageSelection({
    required this.bytes,
    required this.fileName,
    this.mimeType = 'image/png',
  });

  final Uint8List bytes;
  final String fileName;
  final String mimeType;

  String get base64 => base64Encode(bytes);
}

/// Camera/gallery access. Default implementation is unavailable until an image-picker package is approved.
abstract class DiagnosisImagePicker {
  Future<DiagnosisImageSelection?> pickFromGallery();
  Future<DiagnosisImageSelection?> pickFromCamera();
}

class UnavailableDiagnosisImagePicker implements DiagnosisImagePicker {
  const UnavailableDiagnosisImagePicker();

  @override
  Future<DiagnosisImageSelection?> pickFromCamera() async => null;

  @override
  Future<DiagnosisImageSelection?> pickFromGallery() async => null;
}

class DiagnosisImageValidationException implements Exception {
  DiagnosisImageValidationException(this.message);
  final String message;

  @override
  String toString() => message;
}

class DiagnosisImageValidator {
  const DiagnosisImageValidator({
    this.maxBytes = 5242880,
    this.minWidth = 64,
    this.minHeight = 64,
  });

  static const allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

  final int maxBytes;
  final int minWidth;
  final int minHeight;

  void validate(DiagnosisImageSelection image, {int? width, int? height}) {
    if (image.bytes.isEmpty) {
      throw DiagnosisImageValidationException('الصورة فارغة.');
    }
    if (image.bytes.length > maxBytes) {
      throw DiagnosisImageValidationException(
          'حجم الصورة أكبر من الحد المسموح.');
    }
    final mime = detectMime(image.bytes) ?? image.mimeType.toLowerCase();
    if (!allowedMimes.contains(mime)) {
      throw DiagnosisImageValidationException(
          'نوع الصورة غير مدعوم. المسموح: JPEG و PNG و WebP.');
    }
    if (width != null &&
        height != null &&
        (width < minWidth || height < minHeight)) {
      throw DiagnosisImageValidationException(
          'دقة الصورة منخفضة جداً للتحليل.');
    }
  }

  static String? detectMime(Uint8List bytes) {
    if (bytes.length >= 3 &&
        bytes[0] == 0xFF &&
        bytes[1] == 0xD8 &&
        bytes[2] == 0xFF) {
      return 'image/jpeg';
    }
    if (bytes.length >= 8 &&
        bytes[0] == 0x89 &&
        bytes[1] == 0x50 &&
        bytes[2] == 0x4E &&
        bytes[3] == 0x47) {
      return 'image/png';
    }
    if (bytes.length >= 12 &&
        bytes[0] == 0x52 &&
        bytes[1] == 0x49 &&
        bytes[2] == 0x46 &&
        bytes[3] == 0x46 &&
        bytes[8] == 0x57 &&
        bytes[9] == 0x45 &&
        bytes[10] == 0x42 &&
        bytes[11] == 0x50) {
      return 'image/webp';
    }
    return null;
  }
}
