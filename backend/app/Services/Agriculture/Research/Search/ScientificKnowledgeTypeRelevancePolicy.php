<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Knowledge-type-aware relevance policy for Stage-3 preliminary gating.
 *
 * Extends shared entity/topic rules without replacing ScientificEvidenceRelevanceGate
 * and without softening Stage-4 validation.
 */
final class ScientificKnowledgeTypeRelevancePolicy
{
    /**
     * Knowledge types the question primarily seeks (ordered, primary first).
     *
     * @return list<string>
     */
    public function requiredTypes(KnowledgeQueryPlan $plan): array
    {
        $intent = (string) $plan->researchIntent;
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        $topics = is_array($topics) ? $topics : [];
        $question = mb_strtolower(trim($plan->normalizedQuery->originalQuestion));

        $types = [];

        if (in_array($intent, ['plant_family_members', 'species_list_or_taxonomy'], true)
            || $sense === 'plant_family_members'
            || $this->questionSignalsTaxonomy($question, $topics)) {
            $types[] = ScientificKnowledgeType::TAXONOMIC_IDENTITY;
            $types[] = ScientificKnowledgeType::BIODIVERSITY_OCCURRENCE;
        }

        if ($intent === 'land_classification' || $sense === 'land_classification') {
            $types[] = ScientificKnowledgeType::SCHOLARLY_EVIDENCE;
            $types[] = ScientificKnowledgeType::AGRICULTURAL_PRACTICE;
        }

        if ($this->questionSignalsTraditionalUse($question, $intent)) {
            $types[] = ScientificKnowledgeType::TRADITIONAL_USE;
            $types[] = ScientificKnowledgeType::CHEMICAL_ASSOCIATION;
        }

        if ($this->questionSignalsChemical($question, $intent, $topics)) {
            $types[] = ScientificKnowledgeType::CHEMICAL_ASSOCIATION;
        }

        if ($this->questionSignalsHeritage($question)) {
            $types[] = ScientificKnowledgeType::BIBLIOGRAPHIC_HERITAGE;
        }

        if ($this->hasQuantitativeStatisticalNeed($plan)) {
            $types[] = ScientificKnowledgeType::STATISTICAL_OBSERVATION;
        }

        // Agronomy / cultivation practice questions need AGRIS-class corpora.
        // Do not attach AGRICULTURAL_PRACTICE to every empty scholarly default —
        // that would early-stop-protect AGRIS on pure physiology Q&A.
        if ($this->questionSignalsAgriculturalPractice($question, $intent)) {
            $types[] = ScientificKnowledgeType::AGRICULTURAL_PRACTICE;
        }

        if ($types === []) {
            $types[] = ScientificKnowledgeType::SCHOLARLY_EVIDENCE;
            $types[] = ScientificKnowledgeType::EXPERIMENTAL_EVIDENCE;
            $types[] = ScientificKnowledgeType::REVIEW_EVIDENCE;
        }

        return array_values(array_unique($types));
    }

    public function recordType(string $sourceKey, ?array $relevanceMetadata = null): string
    {
        $declared = is_string($relevanceMetadata['knowledge_type'] ?? null)
            ? trim((string) $relevanceMetadata['knowledge_type'])
            : '';
        if ($declared !== '' && in_array($declared, ScientificKnowledgeType::ALL, true)) {
            return $declared;
        }

        // Dr. Duke ethnobot rows are traditional use, not clinical efficacy.
        $modality = (string) ($relevanceMetadata['evidence_modality'] ?? '');
        $table = (string) ($relevanceMetadata['duke_table'] ?? $relevanceMetadata['projection_table'] ?? '');
        if ($sourceKey === 'dr_duke' && (str_contains(mb_strtolower($table), 'ethnobot') || $modality === 'ethnobotanical')) {
            return ScientificKnowledgeType::TRADITIONAL_USE;
        }

        return ScientificKnowledgeType::forSourceKey($sourceKey);
    }

    /**
     * Whether a record knowledge type is compatible with required types.
     * Compatibility ≠ therapeutic efficacy endorsement.
     */
    public function isCompatible(array $requiredTypes, string $recordType): bool
    {
        if ($requiredTypes === [] || $recordType === ScientificKnowledgeType::UNKNOWN) {
            return true;
        }

        if (in_array($recordType, $requiredTypes, true)) {
            return true;
        }

        // Scholarly corpora may still carry taxonomic/chemical mentions.
        if ($recordType === ScientificKnowledgeType::SCHOLARLY_EVIDENCE) {
            return true;
        }

        // Taxonomic identity questions accept biodiversity occurrence records.
        if (in_array(ScientificKnowledgeType::TAXONOMIC_IDENTITY, $requiredTypes, true)
            && $recordType === ScientificKnowledgeType::BIODIVERSITY_OCCURRENCE) {
            return true;
        }

        // Chemical association questions may accept traditional-use chemical rows as association only.
        if (in_array(ScientificKnowledgeType::CHEMICAL_ASSOCIATION, $requiredTypes, true)
            && $recordType === ScientificKnowledgeType::TRADITIONAL_USE) {
            return true;
        }

        return false;
    }

    /**
     * Identity-oriented records: entity match can satisfy relevance without
     * scholarly topic/sense scoring that was designed for articles.
     */
    public function allowsIdentityOrientedRelevance(
        KnowledgeQueryPlan $plan,
        string $recordType,
        bool $entityMatched,
    ): bool {
        if (! $entityMatched) {
            return false;
        }

        $required = $this->requiredTypes($plan);
        if (! $this->isCompatible($required, $recordType)) {
            return false;
        }

        if (in_array($recordType, [
            ScientificKnowledgeType::TAXONOMIC_IDENTITY,
            ScientificKnowledgeType::BIODIVERSITY_OCCURRENCE,
            ScientificKnowledgeType::BIBLIOGRAPHIC_HERITAGE,
            ScientificKnowledgeType::CHEMICAL_ASSOCIATION,
            ScientificKnowledgeType::TRADITIONAL_USE,
            ScientificKnowledgeType::STATISTICAL_OBSERVATION,
        ], true)) {
            return true;
        }

        // Taxonomy questions often retrieve OpenAlex/Crossref scholarly articles that
        // discuss the taxon. When the entity matches and TAXONOMIC_IDENTITY is required,
        // allow identity-oriented relevance so Stage-3 does not demand land/variety
        // topic inventories that emptied survivors (GS-06 Mentha).
        return $recordType === ScientificKnowledgeType::SCHOLARLY_EVIDENCE
            && in_array(ScientificKnowledgeType::TAXONOMIC_IDENTITY, $required, true);
    }

    /**
     * Sources that must not be early-stopped away when the question needs their
     * distinctive knowledge type. Scholarly defaults must NOT protect every
     * governed corpus — that would defeat latency early-stop for normal Q&A.
     *
     * @return list<string>
     */
    public function protectedSourceKeys(KnowledgeQueryPlan $plan): array
    {
        $required = $this->requiredTypes($plan);
        $protected = [];
        foreach ([
            'gbif' => [ScientificKnowledgeType::TAXONOMIC_IDENTITY, ScientificKnowledgeType::BIODIVERSITY_OCCURRENCE],
            // Heritage literature often carries taxonomic descriptions; do not early-stop BHL
            // away when the question needs taxonomic identity (Mentha multi-source restoration).
            'bhl' => [
                ScientificKnowledgeType::BIBLIOGRAPHIC_HERITAGE,
                ScientificKnowledgeType::TAXONOMIC_IDENTITY,
            ],
            'dr_duke' => [ScientificKnowledgeType::CHEMICAL_ASSOCIATION, ScientificKnowledgeType::TRADITIONAL_USE],
            'fao_stat' => [ScientificKnowledgeType::STATISTICAL_OBSERVATION],
            // FAO/AGRIS-class agricultural literature must not be skipped once OA+CR fill
            // the raw hit budget when the plan explicitly needs agricultural practice.
            'agris' => [ScientificKnowledgeType::AGRICULTURAL_PRACTICE],
            'organic_eprints' => [ScientificKnowledgeType::AGRICULTURAL_PRACTICE],
            'osmia' => [ScientificKnowledgeType::AGRICULTURAL_PRACTICE],
        ] as $key => $types) {
            if (array_intersect($required, $types) !== []) {
                $protected[] = $key;
            }
        }

        return $protected;
    }

    /** @param list<mixed> $topics */
    private function questionSignalsTaxonomy(string $question, array $topics): bool
    {
        foreach ($topics as $topic) {
            $t = mb_strtolower(trim((string) $topic));
            if ($t !== '' && preg_match('/taxonomy|species|genus|family|scientific name|تصنيف|فصيلة|نوع/', $t) === 1) {
                return true;
            }
        }

        return preg_match(
            '/\b(taxonomy|taxonomic|scientific name|binomial|species of|genus|family members|what species)\b|تصنيف|الاسم العلمي|فصيلة/',
            $question
        ) === 1;
    }

    private function questionSignalsTraditionalUse(string $question, string $intent): bool
    {
        if (in_array($intent, ['ethnobotany', 'traditional_use', 'medicinal_use'], true)) {
            return true;
        }

        return preg_match('/\b(traditional use|ethnobotan|folk medicine|herbal use)\b|استخدام تقليدي|طب شعبي/', $question) === 1;
    }

    /** @param list<mixed> $topics */
    private function questionSignalsChemical(string $question, string $intent, array $topics): bool
    {
        if (in_array($intent, ['chemical_composition', 'phytochemistry'], true)) {
            return true;
        }
        foreach ($topics as $topic) {
            if (preg_match('/chemical|compound|alkaloid|phytochem|مركب|كيمي/', mb_strtolower((string) $topic)) === 1) {
                return true;
            }
        }

        return preg_match('/\b(chemical|compound|alkaloid|phytochem|constituent)\b|مركبات|قلويد/', $question) === 1;
    }

    private function questionSignalsHeritage(string $question): bool
    {
        return preg_match('/\b(historical|heritage|biodiversity heritage|bhl|19th century)\b|تراث|تاريخي/', $question) === 1;
    }

    private function questionSignalsAgriculturalPractice(string $question, string $intent): bool
    {
        if (in_array($intent, [
            'cultivation', 'irrigation', 'fertilization', 'soil_management',
            'plant_nutrition', 'pest', 'disease',
        ], true)) {
            return true;
        }

        return preg_match(
            '/\b(cultivation|agronom(?:y|ic)|irrigation|fertiliz|farming practice|crop management|'
            .'agricultural research|recommended practices?|best practices?|'
            .'organic(?:\s+farming|\s+agriculture|\s+pest)?|agroecolog(?:y|ical)|'
            .'pest control|integrated pest|deficit irrigation|yield management)\b|'
            .'زراعة|ري|تسميد|إدارة المحصول|ممارسات زراعية|زراعة عضوية|مكافحة آفات/',
            $question
        ) === 1;
    }

    /**
     * Distinctive (non-generic-scholarly) knowledge types required by the plan.
     *
     * @return list<string>
     */
    public function distinctiveRequiredTypes(KnowledgeQueryPlan $plan): array
    {
        $generic = [
            ScientificKnowledgeType::SCHOLARLY_EVIDENCE,
            ScientificKnowledgeType::EXPERIMENTAL_EVIDENCE,
            ScientificKnowledgeType::REVIEW_EVIDENCE,
        ];

        return array_values(array_diff($this->requiredTypes($plan), $generic));
    }

    /**
     * Whether early-stop may skip this selected source without leaving a required
     * distinctive capability unconsidered. Capability-driven — not plant-name lists.
     */
    public function mayEarlyStopSource(string $sourceKey, KnowledgeQueryPlan $plan): bool
    {
        if (in_array($sourceKey, $this->protectedSourceKeys($plan), true)) {
            return false;
        }

        $distinctive = $this->distinctiveRequiredTypes($plan);
        if ($distinctive === []) {
            return true;
        }

        $sourceType = ScientificKnowledgeType::forSourceKey($sourceKey);

        return ! in_array($sourceType, $distinctive, true);
    }

    private function hasQuantitativeStatisticalNeed(KnowledgeQueryPlan $plan): bool
    {
        $resolver = \App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatQclDimensionResolver::class;
        if (! class_exists($resolver)) {
            return false;
        }

        return $resolver::hasQuantitativeStatisticalNeed($plan);
    }
}
