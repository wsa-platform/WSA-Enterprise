<?php

namespace App\Services\Agriculture;

use Illuminate\Validation\ValidationException;

/**
 * Phase 6 U6.3 — authoritative Crop selector completeness + taxonomy identity.
 *
 * Uses FieldCropTaxonomyCatalog + FieldCropCategoryCatalog only (no second taxonomy).
 * Does not map WSA crop IDs to FAOSTAT item codes.
 */
final class CropProfileIdentityValidator
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{selected_crop_id: string, selected_crop_name: string, scientific_name: string}
     */
    public static function normalizePair(array $input): array
    {
        $cropId = trim((string) ($input['selected_crop_id'] ?? ''));
        $cropName = trim((string) ($input['selected_crop_name'] ?? ''));
        $scientificProvided = trim((string) ($input['scientific_name'] ?? ''));

        if ($cropId === '' || $cropName === '') {
            throw ValidationException::withMessages([
                'selected_crop_id' => ['Crop profile requires both selected_crop_id and selected_crop_name.'],
                'selected_crop_name' => ['Crop profile requires both selected_crop_id and selected_crop_name.'],
            ]);
        }

        $entry = FieldCropTaxonomyCatalog::entryFor($cropId);
        if ($entry === null) {
            throw ValidationException::withMessages([
                'selected_crop_id' => ['Unknown crop identity. selected_crop_id is not in the authoritative taxonomy.'],
            ]);
        }

        if (! self::nameMatchesIdentity($cropId, $cropName, $entry)) {
            throw ValidationException::withMessages([
                'selected_crop_name' => ['selected_crop_name does not match the authoritative identity for selected_crop_id.'],
            ]);
        }

        $canonicalName = self::canonicalDisplayName($cropId, $entry);
        $scientific = FieldCropTaxonomyCatalog::resolveScientificName($cropId, $scientificProvided);

        return [
            'selected_crop_id' => $cropId,
            'selected_crop_name' => $canonicalName !== '' ? $canonicalName : $cropName,
            'scientific_name' => $scientific,
        ];
    }

    /**
     * True when exactly one of id/name is non-empty (incomplete Crop intent signal).
     *
     * @param  array<string, mixed>  $input
     */
    public static function isIncompleteCropSelector(array $input): bool
    {
        $cropId = trim((string) ($input['selected_crop_id'] ?? ''));
        $cropName = trim((string) ($input['selected_crop_name'] ?? ''));

        return ($cropId === '') !== ($cropName === '');
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function assertNotIncompleteCropSelector(array $input): void
    {
        if (! self::isIncompleteCropSelector($input)) {
            return;
        }

        throw ValidationException::withMessages([
            'selected_crop_id' => ['Incomplete Crop selector: both selected_crop_id and selected_crop_name are required together.'],
            'selected_crop_name' => ['Incomplete Crop selector: both selected_crop_id and selected_crop_name are required together.'],
        ]);
    }

    /**
     * @param  array{scientific_name: string, synonyms: list<string>, category?: string}  $entry
     */
    private static function nameMatchesIdentity(string $cropId, string $cropName, array $entry): bool
    {
        $needle = self::normalizeLabel($cropName);
        if ($needle === '') {
            return false;
        }

        $candidates = array_merge(
            [$cropId],
            [(string) ($entry['scientific_name'] ?? '')],
            is_array($entry['synonyms'] ?? null) ? $entry['synonyms'] : [],
            FieldCropTaxonomyCatalog::searchTermsFor($cropId),
            self::categoryDisplayNamesFor($cropId),
        );

        foreach ($candidates as $candidate) {
            if (self::normalizeLabel((string) $candidate) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{scientific_name: string, synonyms: list<string>, category?: string}  $entry
     */
    private static function canonicalDisplayName(string $cropId, array $entry): string
    {
        $fromCategory = self::categoryDisplayNamesFor($cropId)[0] ?? '';
        if ($fromCategory !== '') {
            return $fromCategory;
        }

        $synonyms = is_array($entry['synonyms'] ?? null) ? $entry['synonyms'] : [];
        foreach ($synonyms as $synonym) {
            $synonym = trim((string) $synonym);
            if ($synonym !== '') {
                return $synonym;
            }
        }

        return $cropId;
    }

    /**
     * @return list<string>
     */
    private static function categoryDisplayNamesFor(string $cropId): array
    {
        $names = [];
        foreach (FieldCropCategoryCatalog::categories() as $category) {
            foreach ($category['crops'] ?? [] as $crop) {
                if ((string) ($crop['id'] ?? '') !== $cropId) {
                    continue;
                }
                $name = trim((string) ($crop['name'] ?? ''));
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
    }

    private static function normalizeLabel(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }
}
