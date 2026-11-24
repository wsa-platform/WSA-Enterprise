<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalDomainCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Scientific relevance gate for GOVERNED_EXPANSION seats (Rev 11.0.10 §16).
 *
 * Capability authorization remains separate (ScientificSourceCapabilityPolicy).
 * This class only answers: "given this plan, is this governed source scientifically
 * appropriate to query?" Unknown governed keys fail open so future seats are not
 * silently blocked. Non-governed keys are never evaluated here.
 *
 * Conservative inclusion: ambiguous agricultural literature prefers AGRIS;
 * taxonomy/biodiversity prefers GBIF/BHL; phytochemistry prefers Dr. Duke;
 * Organic Eprints requires organic/agroecology signals rather than every agronomy query.
 */
final class GovernedScientificSourceEligibility
{
    /** @var list<string> */
    public const PROFILED_KEYS = ['agris', 'organic_eprints', 'dr_duke', 'gbif', 'bhl', 'osmia'];

    public function isEligible(string $sourceKey, KnowledgeQueryPlan $plan): bool
    {
        return match ($sourceKey) {
            'agris' => $this->agrisEligible($plan),
            'organic_eprints' => $this->organicEprintsEligible($plan),
            'dr_duke' => $this->drDukeEligible($plan),
            'gbif' => $this->gbifEligible($plan),
            'bhl' => $this->bhlEligible($plan),
            'osmia' => $this->osmiaEligible($plan),
            default => true,
        };
    }

    /**
     * Observability reason code for selectionTrace (not a public API envelope).
     */
    public function exclusionReason(string $sourceKey, KnowledgeQueryPlan $plan): ?string
    {
        if ($this->isEligible($sourceKey, $plan)) {
            return null;
        }

        return match ($sourceKey) {
            'agris' => 'agris_not_agricultural_literature_scope',
            'organic_eprints' => 'organic_eprints_requires_organic_or_agroecology_signal',
            'dr_duke' => 'dr_duke_requires_phytochemical_or_ethnobotany_scope',
            'gbif' => 'gbif_requires_taxonomy_occurrence_or_biodiversity_scope',
            'bhl' => 'bhl_requires_biodiversity_heritage_or_taxonomic_literature_scope',
            'osmia' => 'osmia_requires_bee_pollinator_or_hymenoptera_scope',
            default => 'governed_source_not_relevant',
        };
    }

    private function osmiaEligible(KnowledgeQueryPlan $plan): bool
    {
        $bag = $this->cueBag($plan);

        if ($this->hasAny($bag, [
            'bee', 'bees', 'honeybee', 'honey bee', 'bumblebee', 'pollinator', 'pollinators',
            'pollination', 'hymenoptera', 'apoidea', 'apidae', 'apidolog', 'mellitolog',
            'wild bee', 'solitary bee', 'osmia', 'megachile', 'andrena', 'nomada',
            'halictus', 'lasioglossum', 'beekeeping', 'apiculture',
            'نحل', 'تلقيح', 'ملقحات', 'arı', 'arıcılık', 'polinisateur', 'abeille', 'abeilles',
        ])) {
            return true;
        }

        if ($this->domainIn($plan, [
            AgriculturalDomainCatalog::BEEKEEPING,
        ])) {
            return true;
        }

        return $this->intentIn($plan, [
            'beekeeping', 'pollination', 'pollinator', 'apiculture',
        ]);
    }

    private function agrisEligible(KnowledgeQueryPlan $plan): bool
    {
        $bag = $this->cueBag($plan);

        if ($this->hasAny($bag, [
            'agriculture', 'agricultural', 'agronomy', 'crop', 'crops', 'soil', 'irrigation',
            'fertiliz', 'fertilis', 'pest', 'disease', 'yield', 'production', 'cultivation',
            'farming', 'farmer', 'livestock', 'poultry', 'aquaculture', 'land', 'harvest',
            'nutrition', 'plant production', 'field crop', 'vegetable', 'fruit',
            'wheat', 'barley', 'maize', 'rice', 'cotton', 'deficit irrigation',
        ])) {
            return true;
        }

        if ($this->domainIn($plan, [
            AgriculturalDomainCatalog::PLANT_PRODUCTION,
            AgriculturalDomainCatalog::FIELD_CROPS,
            AgriculturalDomainCatalog::VEGETABLES,
            AgriculturalDomainCatalog::FRUIT_TREES,
            AgriculturalDomainCatalog::ORNAMENTAL_PLANTS,
            AgriculturalDomainCatalog::MEDICINAL_AROMATIC_PLANTS,
            AgriculturalDomainCatalog::SOIL,
            AgriculturalDomainCatalog::IRRIGATION_WATER,
            AgriculturalDomainCatalog::FERTILIZATION,
            AgriculturalDomainCatalog::PLANT_NUTRITION,
            AgriculturalDomainCatalog::PESTS_DISEASES,
            AgriculturalDomainCatalog::ANIMAL_PRODUCTION,
            AgriculturalDomainCatalog::POULTRY,
            AgriculturalDomainCatalog::BEEKEEPING,
            AgriculturalDomainCatalog::AQUACULTURE,
            AgriculturalDomainCatalog::AGRICULTURAL_ECONOMICS,
            AgriculturalDomainCatalog::AGRICULTURAL_INDUSTRIES,
            AgriculturalDomainCatalog::AGRICULTURAL_RESEARCH,
            AgriculturalDomainCatalog::GENERAL_AGRICULTURE,
            'agronomy',
        ])) {
            return true;
        }

        return $this->intentIn($plan, [
            'irrigation', 'cultivation', 'land_classification', 'statistical_lookup',
            'production', 'fertilization', 'pest_management', 'general_knowledge',
            'scientific_research', 'crop_water_requirement',
        ]);
    }

    private function organicEprintsEligible(KnowledgeQueryPlan $plan): bool
    {
        $bag = $this->cueBag($plan);

        return $this->hasAny($bag, [
            'organic', 'organics', 'agroecology', 'agroecological', 'regenerative',
            'biodynamic', 'orgprints', 'organic farming', 'organic agriculture',
            'organic production', 'compost', 'cover crop', 'green manure',
            // Multilingual organic cues (ISS-AR-002) — cue bag is lowercased; keep AR/TR/FR surfaces.
            'عضوي', 'عضوية', 'العضوية', 'الزراعة العضوية', 'زراعة عضوية',
            'organik', 'organik tarım', 'organik tarim',
            'agriculture biologique', 'biologique',
        ]);
    }

    private function drDukeEligible(KnowledgeQueryPlan $plan): bool
    {
        $bag = $this->cueBag($plan);

        if ($this->hasAny($bag, [
            'phytochem', 'ethnobotan', 'medicinal', 'aromatic', 'constituent',
            'compound', 'alkaloid', 'flavonoid', 'essential oil', 'dr. duke', 'dr duke',
            'chemical composition', 'bioactive', 'mentha', 'piperita', 'herb',
        ])) {
            return true;
        }

        if ($this->domainIn($plan, [AgriculturalDomainCatalog::MEDICINAL_AROMATIC_PLANTS])) {
            return true;
        }

        return $this->intentIn($plan, [
            'phytochemistry', 'ethnobotany', 'medicinal_plant', 'plant_chemistry',
        ]);
    }

    private function gbifEligible(KnowledgeQueryPlan $plan): bool
    {
        $bag = $this->cueBag($plan);
        $nq = $plan->normalizedQuery;

        // Resolved scientific-name identity warrants taxonomic/occurrence grounding.
        if ($nq->scientificName !== null && trim($nq->scientificName) !== '') {
            return true;
        }

        if ($this->hasAny($bag, [
            'gbif', 'occurrence', 'biodiversity', 'species classification', 'taxonomic',
            'taxonomy', 'species distribution', 'checklist', 'specimen', 'genus versus',
            'scientific name', 'taxon key', ' taxonomic ', ' species ', ' genus ',
        ]) || $this->hasToken($bag, ['taxonomy', 'taxonomic', 'species', 'genus', 'taxon', 'occurrence', 'biodiversity'])) {
            return true;
        }

        // Do not treat land_classification / soil classification as GBIF taxonomy.
        return $this->intentIn($plan, [
            'taxonomy', 'species_lookup', 'plant_family_members',
            'biodiversity', 'occurrence',
        ]);
    }

    private function bhlEligible(KnowledgeQueryPlan $plan): bool
    {
        $bag = $this->cueBag($plan);
        $nq = $plan->normalizedQuery;

        if ($this->hasAny($bag, [
            'bhl', 'biodiversity heritage', 'heritage library', 'historical botan',
            'historical zoolog', 'ocr', 'page', 'volume', 'taxonomy literature',
            'botanical literature', 'zoological literature', 'species description',
            'nomenclature', 'flora', 'fauna',
        ])) {
            return true;
        }

        // Historical botanical/zoological literature for named taxa or phytochemical ethnobotany.
        // Use token matching for short stems so "requirements" does not match "species".
        if ($nq->scientificName !== null && trim($nq->scientificName) !== ''
            && (
                $this->hasAny($bag, [
                    'phytochem', 'ethnobotan', 'medicinal', 'taxonomy', 'taxonomic',
                    'biodiversity', 'nomenclature', 'botanical literature',
                ])
                || $this->hasToken($bag, [
                    'species', 'taxonomy', 'taxonomic', 'biodiversity', 'classification',
                    'literature', 'nomenclature', 'flora', 'fauna',
                ])
            )) {
            return true;
        }

        // BHL shares GBIF taxonomic scope only with heritage/library literature cues.
        // Do not treat generic evidence_requirements like peer_reviewed_publication as BHL scope.
        if ($this->gbifEligible($plan) && (
            $this->hasAny($bag, [
                'historical', 'archive', 'heritage library', 'biodiversity heritage',
                'treatise', 'monograph', 'botanical literature', 'zoological literature',
            ])
            || $this->hasToken($bag, ['historical', 'archive', 'monograph', 'treatise', 'flora', 'fauna'])
        )) {
            return true;
        }

        // Pure taxonomic classification questions still benefit from BHL historical literature.
        return $this->intentIn($plan, [
            'taxonomy', 'species_lookup', 'biodiversity',
            'plant_family_members',
        ]) && $this->hasToken($bag, [
            'species', 'taxonomy', 'taxonomic', 'biodiversity', 'genus', 'flora',
        ]);
    }

    /** @param  list<string>  $tokens */
    private function hasToken(string $bag, array $tokens): bool
    {
        $padded = ' '.$bag.' ';
        foreach ($tokens as $token) {
            $t = mb_strtolower(trim($token));
            if ($t === '') {
                continue;
            }
            if (str_contains($padded, ' '.$t.' ')
                || str_contains($padded, ' '.$t.'_')
                || str_contains($padded, '_'.$t.' ')) {
                return true;
            }
        }

        return false;
    }

    /** @return string lowercased cue bag */
    private function cueBag(KnowledgeQueryPlan $plan): string
    {
        $nq = $plan->normalizedQuery;
        $parts = [
            $nq->originalQuestion,
            $nq->normalizedQuestion,
            $nq->topic,
            (string) $nq->subtopic,
            $nq->crop ?? '',
            $nq->scientificName ?? '',
            $nq->agriculturalDomain,
            $nq->researchIntent,
            $plan->researchIntent,
            $plan->agriculturalDomain,
            implode(' ', $plan->topics),
            implode(' ', $plan->subtopics),
            implode(' ', $plan->requestedInformation),
            implode(' ', $plan->evidenceRequirements),
            (string) ($nq->constraints['scientific_sense'] ?? ''),
            (string) ($nq->constraints['question_type'] ?? ''),
            is_array($nq->subject) ? (string) ($nq->subject['value'] ?? '').' '.(string) ($nq->subject['label'] ?? '') : '',
            is_array($plan->subjectEntity) ? (string) ($plan->subjectEntity['value'] ?? '') : '',
        ];

        return mb_strtolower(implode(' ', array_filter($parts, static fn (string $p): bool => trim($p) !== '')));
    }

    /** @param  list<string>  $needles */
    private function hasAny(string $bag, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($bag, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<string>  $domains */
    private function domainIn(KnowledgeQueryPlan $plan, array $domains): bool
    {
        $domain = mb_strtolower(trim($plan->agriculturalDomain !== ''
            ? $plan->agriculturalDomain
            : $plan->normalizedQuery->agriculturalDomain));

        foreach ($domains as $candidate) {
            if ($domain === mb_strtolower($candidate)) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<string>  $intents */
    private function intentIn(KnowledgeQueryPlan $plan, array $intents): bool
    {
        $intent = mb_strtolower(trim($plan->researchIntent !== ''
            ? $plan->researchIntent
            : $plan->normalizedQuery->researchIntent));
        $sense = mb_strtolower(trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? '')));

        foreach ($intents as $candidate) {
            $c = mb_strtolower($candidate);
            if ($intent === $c || $sense === $c) {
                return true;
            }
        }

        return false;
    }
}
