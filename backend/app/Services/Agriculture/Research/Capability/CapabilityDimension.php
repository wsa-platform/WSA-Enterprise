<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Typed Cap cell dimension — not a free-form string bag.
 *
 * OD: exact facet taxonomy freeze remains population-time; facet codes are
 * non-empty opaque identifiers from Cap Design's identity-critical facet list
 * (support claims only — Cap never writes CSQ).
 */
final readonly class CapabilityDimension
{
    public const UNKNOWN_METADATA = 'UNKNOWN';

    private function __construct(
        public CapabilityDimensionFamily $family,
        public string $code,
    ) {}

    public static function accessMethod(CapabilityAccessMethod $method): self
    {
        return new self(CapabilityDimensionFamily::ACCESS_METHOD, $method->value);
    }

    public static function scientificFacetContentAbout(string $facetCode): self
    {
        return new self(
            CapabilityDimensionFamily::SCIENTIFIC_FACET_CONTENT_ABOUT,
            self::normalizeCode($facetCode, 'scientific facet content_about code'),
        );
    }

    public static function scientificFacetQueryConstrainable(string $facetCode): self
    {
        return new self(
            CapabilityDimensionFamily::SCIENTIFIC_FACET_QUERY_CONSTRAINABLE,
            self::normalizeCode($facetCode, 'scientific facet query_constrainable code'),
        );
    }

    public static function licenseConstraint(string $constraintCode): self
    {
        return new self(
            CapabilityDimensionFamily::LICENSE_CONSTRAINT,
            self::normalizeCode($constraintCode, 'license constraint code'),
        );
    }

    public function equals(self $other): bool
    {
        return $this->family === $other->family && $this->code === $other->code;
    }

    public function key(): string
    {
        return $this->family->value.'|'.$this->code;
    }

    /**
     * CAP-INV-009: content_about and query_constrainable are distinct dimensions.
     */
    public function isContentAboutFacet(): bool
    {
        return $this->family === CapabilityDimensionFamily::SCIENTIFIC_FACET_CONTENT_ABOUT;
    }

    public function isQueryConstrainableFacet(): bool
    {
        return $this->family === CapabilityDimensionFamily::SCIENTIFIC_FACET_QUERY_CONSTRAINABLE;
    }

    private static function normalizeCode(string $code, string $label): string
    {
        $trimmed = trim($code);
        if ($trimmed === '') {
            throw new CapabilityInvariantViolation("{$label} must be a non-empty string.");
        }

        return $trimmed;
    }
}
