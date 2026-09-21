<?php

/**
 * Verified FAOSTAT QCL label → code map (Phase 3-A).
 *
 * Expansion rules:
 * - Add only ADR / offline-reviewed / live-validated codes.
 * - Never invent numeric codes.
 * - Never probe the Developer Portal at search time for unknown labels.
 * - Multilingual aliases are first-class keys of the same code.
 * - Numeric WSA crop taxonomy IDs must never appear as keys or values here.
 *
 * Authoritative import path (offline):
 * 1. Export Developer Portal `/codes/{dimension}` for domain QCL.
 * 2. Human-review uniqueness and agricultural relevance.
 * 3. Commit aliases into this versioned file (or a successor JSON with the same schema).
 * 4. Bump `version` and document provenance in ADR-004.
 *
 * @return array{
 *   version: string,
 *   provenance: string,
 *   areas: array<string, string>,
 *   items: array<string, string>
 * }
 */
return [
    'version' => '2026-09-21.phase3a',
    'provenance' => 'ADR-004 + Phase 2/3 live-validated QCL fixtures (Developer Portal). Offline-reviewed only.',
    'areas' => [
        'italy' => '106',
        'italia' => '106',
        'italien' => '106',
        'إيطاليا' => '106',
        'france' => '68',
        'فرنسا' => '68',
        'brazil' => '21',
        'brasil' => '21',
        'البرازيل' => '21',
        'afghanistan' => '2',
        'أفغانستان' => '2',
        'egypt' => '59',
        'مصر' => '59',
        'united states' => '231',
        'united states of america' => '231',
        'usa' => '231',
        'us' => '231',
        'الولايات المتحدة' => '231',
    ],
    'items' => [
        'wheat' => '15',
        'triticum aestivum' => '15',
        'قمح' => '15',
        'القمح' => '15',
        'maize' => '56',
        'corn' => '56',
        'zea mays' => '56',
        'ذرة' => '56',
        'الذرة' => '56',
        'apples' => '515',
        'apple' => '515',
        'تفاح' => '515',
        'التفاح' => '515',
    ],
];
