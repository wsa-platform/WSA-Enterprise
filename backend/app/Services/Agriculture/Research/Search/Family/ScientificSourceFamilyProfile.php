<?php

namespace App\Services\Agriculture\Research\Search\Family;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;

/**
 * Per-source configuration of a reusable source-family adapter (ADR-023 §8.39, increment R5).
 *
 * A profile states only what the provider documents. It names configuration keys, never
 * credential values, so it can be logged, audited and exported.
 */
interface ScientificSourceFamilyProfile
{
    public function sourceKey(): string;

    public function displayName(): string;

    public function accessFamily(): ScientificSourceAccessFamily;

    public function licensing(): ScientificSourceLicensingDeclaration;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
