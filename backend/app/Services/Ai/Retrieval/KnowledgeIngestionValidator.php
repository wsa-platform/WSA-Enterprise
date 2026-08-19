<?php

namespace App\Services\Ai\Retrieval;

use Illuminate\Validation\ValidationException;

class KnowledgeIngestionValidator
{
    private const TITLE_MAX = 255;

    private const SUMMARY_MAX = 10000;

    private const BODY_MAX = 100000;

    private const SLUG_MAX = 128;

    private const SOURCE_MAX = 255;

    /** @var list<string> */
    private const PUBLICATION_STATES = ['draft', 'published'];

    /** @var list<string> */
    private const SOURCE_TYPES = ['library_items', 'bee_knowledge_topics'];

    public function __construct(private KnowledgeTextNormalizer $normalizer) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validateLibrary(int $organizationId, array $payload): array
    {
        $this->assertTenantNotOverridden($organizationId, $payload);
        $this->assertSourceType($payload, 'library_items');

        $slug = $this->slug($payload['slug'] ?? null);
        $title = $this->requiredText('title', $payload['title'] ?? null, self::TITLE_MAX);
        $summary = $this->optionalText('summary', $payload['summary'] ?? null, self::SUMMARY_MAX);
        $body = $this->optionalText('content', $payload['content'] ?? $payload['body'] ?? null, self::BODY_MAX);
        $titleAr = $this->optionalText('title_ar', $payload['title_ar'] ?? null, self::TITLE_MAX);
        $summaryAr = $this->optionalText('summary_ar', $payload['summary_ar'] ?? null, self::SUMMARY_MAX);
        $contentAr = $this->optionalText('content_ar', $payload['content_ar'] ?? null, self::BODY_MAX);

        return [
            'slug' => $slug,
            'title' => $title,
            'title_ar' => $titleAr,
            'summary' => $summary,
            'summary_ar' => $summaryAr,
            'content' => $body,
            'content_ar' => $contentAr,
            'source' => $this->sourceAttribution($payload),
            'publication_status' => $this->publicationStatus($payload['publication_status'] ?? 'draft'),
            'item_type' => $this->optionalShort('item_type', $payload['item_type'] ?? 'article', 32) ?: 'article',
            'locale' => $this->optionalShort('locale', $payload['locale'] ?? 'ar', 8) ?: 'ar',
            'metadata' => $this->safeMetadata($payload['metadata'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validateBeeTopic(array $payload): array
    {
        if (array_key_exists('organization_id', $payload) && $payload['organization_id'] !== null) {
            throw ValidationException::withMessages([
                'organization_id' => ['Bee knowledge topics are a platform catalog and cannot be assigned a tenant.'],
            ]);
        }
        $this->assertSourceType($payload, 'bee_knowledge_topics');

        return [
            'slug' => $this->slug($payload['slug'] ?? null),
            'category' => $this->requiredText('category', $payload['category'] ?? null, 64),
            'title_key' => $this->requiredText('title_key', $payload['title_key'] ?? $payload['title'] ?? null, 255),
            'summary_key' => $this->optionalText('summary_key', $payload['summary_key'] ?? $payload['summary'] ?? null, 255),
            'body' => $this->optionalText('body', $payload['body'] ?? $payload['content'] ?? null, self::BODY_MAX),
            'tags' => $this->tags($payload['tags'] ?? null),
            'metadata' => $this->safeBeeMetadata($payload['metadata'] ?? null),
            'is_active' => $this->boolean($payload['is_active'] ?? $payload['published'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function assertTenantNotOverridden(int $organizationId, array $payload): void
    {
        if (! array_key_exists('organization_id', $payload) || $payload['organization_id'] === null) {
            return;
        }

        if ((int) $payload['organization_id'] !== $organizationId) {
            throw ValidationException::withMessages([
                'organization_id' => ['Client-supplied tenant identifiers cannot override authenticated tenant context.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertSourceType(array $payload, string $expected): void
    {
        if (! array_key_exists('source_type', $payload) || $payload['source_type'] === null || $payload['source_type'] === '') {
            return;
        }

        $type = (string) $payload['source_type'];
        if (! in_array($type, self::SOURCE_TYPES, true)) {
            throw ValidationException::withMessages([
                'source_type' => ['Unsupported knowledge source type.'],
            ]);
        }
        if ($type !== $expected) {
            throw ValidationException::withMessages([
                'source_type' => ['Source type does not match this ingestion operation.'],
            ]);
        }
    }

    private function slug(mixed $value): string
    {
        $slug = $this->normalizer->searchable((string) $value);
        $slug = str_replace(' ', '-', $slug);
        $slug = preg_replace('/[^a-z0-9-]+/', '', $slug) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '' || mb_strlen($slug) > self::SLUG_MAX) {
            throw ValidationException::withMessages([
                'slug' => ['A valid source slug is required.'],
            ]);
        }

        return $slug;
    }

    private function requiredText(string $field, mixed $value, int $max): string
    {
        $text = $this->optionalText($field, $value, $max);
        if ($text === null || $text === '') {
            throw ValidationException::withMessages([
                $field => ['This field is required.'],
            ]);
        }

        return $text;
    }

    private function optionalText(string $field, mixed $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        if (! is_string($value) && ! is_numeric($value)) {
            throw ValidationException::withMessages([
                $field => ['Malformed input was rejected.'],
            ]);
        }
        $text = $this->normalizer->clean((string) $value);
        if (mb_strlen($text) > $max) {
            throw ValidationException::withMessages([
                $field => ["This field may not exceed {$max} characters."],
            ]);
        }

        return $text === '' ? null : $text;
    }

    private function optionalShort(string $field, mixed $value, int $max): ?string
    {
        $text = $this->optionalText($field, $value, $max);
        if ($text === null) {
            return null;
        }
        $text = preg_replace('/[^a-z0-9_-]+/i', '', $text) ?? '';

        return $text === '' ? null : mb_substr($text, 0, $max);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sourceAttribution(array $payload): ?string
    {
        $source = $payload['source'] ?? null;
        if (! is_string($source)) {
            return null;
        }
        $source = $this->normalizer->clean($source);
        if ($source === '') {
            return null;
        }
        if ($this->looksLikeUrl($source)) {
            return $this->isHttpUrl($source) ? mb_substr($source, 0, self::SOURCE_MAX) : null;
        }

        return mb_substr($source, 0, self::SOURCE_MAX);
    }

    private function looksLikeUrl(string $value): bool
    {
        $lower = mb_strtolower($value);

        return str_contains($value, '://')
            || str_starts_with($lower, 'www.')
            || str_starts_with($lower, 'javascript:')
            || str_starts_with($lower, 'data:');
    }

    private function isHttpUrl(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $parts = parse_url($value);
        if (! is_array($parts)) {
            return false;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        return in_array($scheme, ['http', 'https'], true)
            && $host !== ''
            && ! in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
    }

    private function publicationStatus(mixed $value): string
    {
        $status = is_string($value) ? strtolower(trim($value)) : '';
        if (! in_array($status, self::PUBLICATION_STATES, true)) {
            throw ValidationException::withMessages([
                'publication_status' => ['Publication state is invalid.'],
            ]);
        }

        return $status;
    }

    /**
     * @return list<string>|null
     */
    private function tags(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (! is_array($value)) {
            throw ValidationException::withMessages([
                'tags' => ['Tags must be an array of strings.'],
            ]);
        }
        $tags = [];
        foreach ($value as $tag) {
            if (! is_string($tag) && ! is_numeric($tag)) {
                continue;
            }
            $clean = $this->normalizer->clean((string) $tag);
            if ($clean === '' || mb_strlen($clean) > 64) {
                continue;
            }
            $tags[$clean] = $clean;
        }

        return $tags === [] ? null : array_values($tags);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeMetadata(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (! is_array($value)) {
            throw ValidationException::withMessages([
                'metadata' => ['Metadata must be an object.'],
            ]);
        }

        $safe = [];
        foreach (['locale', 'item_kind'] as $key) {
            if (isset($value[$key]) && is_string($value[$key])) {
                $clean = $this->normalizer->clean($value[$key]);
                if ($clean !== '' && mb_strlen($clean) <= 64) {
                    $safe[$key] = $clean;
                }
            }
        }

        return $safe === [] ? null : $safe;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeBeeMetadata(mixed $value): ?array
    {
        if ($value === null) {
            return ['rag_ready' => false];
        }
        if (! is_array($value)) {
            throw ValidationException::withMessages([
                'metadata' => ['Metadata must be an object.'],
            ]);
        }

        return [
            'rag_ready' => $this->boolean($value['rag_ready'] ?? false),
        ];
    }

    private function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
