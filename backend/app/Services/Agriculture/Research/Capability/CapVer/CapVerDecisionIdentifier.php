<?php

declare(strict_types=1);

namespace App\Services\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Formats and parses CapVer decision identifiers (ADR-023 OD-A-ID).
 *
 * Format: capver-decision-{adr_id}-{dimension}-{UTC timestamp}
 * A well-formed identifier is not authorization; it must resolve to a registered assessment.
 */
final readonly class CapVerDecisionIdentifier
{
    public const PREFIX = 'capver-decision-';

    private function __construct(
        public string $value,
        public string $adrId,
        public string $dimensionToken,
        public string $issuedAtUtc,
    ) {}

    public static function mint(string $adrId, CapabilityDimension $dimension, DateTimeImmutable $issuedAt): self
    {
        $adr = self::normalizeAdrId($adrId);
        $token = self::dimensionToken($dimension);
        // Milliseconds keep UTC form and avoid same-second collisions for distinct assessments.
        $utc = $issuedAt->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THisv\Z');

        return new self(self::PREFIX.$adr.'-'.$token.'-'.$utc, $adr, $token, $utc);
    }

    public static function parse(string $value): self
    {
        $trimmed = trim($value);
        if ($trimmed === '' || ! str_starts_with($trimmed, self::PREFIX)) {
            throw new CapabilityInvariantViolation('CapVer decision identifier is missing or malformed.');
        }

        if (preg_match(
            '/^capver-decision-((?:G[1-6]-\d{2})|(?:[A-Z][A-Z0-9_]*))-(.+)-(\d{8}T\d{9}Z)$/',
            $trimmed,
            $matches,
        ) !== 1) {
            throw new CapabilityInvariantViolation('CapVer decision identifier is malformed.');
        }

        return new self($trimmed, $matches[1], $matches[2], $matches[3]);
    }

    public static function dimensionToken(CapabilityDimension $dimension): string
    {
        return str_replace('|', '~', $dimension->key());
    }

    public function matchesDimension(CapabilityDimension $dimension): bool
    {
        return $this->dimensionToken === self::dimensionToken($dimension);
    }

    private static function normalizeAdrId(string $adrId): string
    {
        $adr = trim($adrId);
        if (preg_match('/^(?:G[1-6]-\d{2}|[A-Z][A-Z0-9_]*)$/', $adr) !== 1) {
            throw new CapabilityInvariantViolation('CapVer decision identifier adr_id is invalid.');
        }

        return $adr;
    }
}
