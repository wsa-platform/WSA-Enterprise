<?php

namespace App\Services\Agriculture\Research\Path;

/**
 * Frozen path families P01–P18 (Path Model Design §9).
 *
 * Labels document design meaning only — no runtime execution attached.
 */
enum PathFamily: string
{
    case P01 = 'P01';
    case P02 = 'P02';
    case P03 = 'P03';
    case P04 = 'P04';
    case P05 = 'P05';
    case P06 = 'P06';
    case P07 = 'P07';
    case P08 = 'P08';
    case P09 = 'P09';
    case P10 = 'P10';
    case P11 = 'P11';
    case P12 = 'P12';
    case P13 = 'P13';
    case P14 = 'P14';
    case P15 = 'P15';
    case P16 = 'P16';
    case P17 = 'P17';
    case P18 = 'P18';

    public function designLabel(): string
    {
        return match ($this) {
            self::P01 => 'REST/API',
            self::P02 => 'GraphQL/other machine protocol',
            self::P03 => 'OAI-PMH',
            self::P04 => 'RSS',
            self::P05 => 'Atom',
            self::P06 => 'Bulk download',
            self::P07 => 'Static export',
            self::P08 => 'Web UI search',
            self::P09 => 'Metadata export',
            self::P10 => 'Full-text access',
            self::P11 => 'DOI/Crossref enrich (EXTERNAL unless ADR seat)',
            self::P12 => 'OpenAlex discovery (EXTERNAL)',
            self::P13 => 'Semantic Scholar discovery (EXTERNAL)',
            self::P14 => 'Local index',
            self::P15 => 'Hybrid API+bulk',
            self::P16 => 'Hybrid aggregator+native',
            self::P17 => 'Protocol-family + Projection (CGHIA T2 shape)',
            self::P18 => 'Manual/human',
        };
    }

    public function isExternalDiscoveryFamily(): bool
    {
        return match ($this) {
            self::P11, self::P12, self::P13 => true,
            default => false,
        };
    }

    public function isManualFamily(): bool
    {
        return $this === self::P18;
    }

    /**
     * @return list<self>
     */
    public static function allOrdered(): array
    {
        return self::cases();
    }
}
