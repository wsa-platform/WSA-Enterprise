<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;
use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ScientificQuestionSemantics;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;

/**
 * Relevance gate: candidate topical relevance before scientific evaluation (Phase 4).
 *
 * Ownership:
 * - Answers: "Is this result relevant enough to remain a candidate?"
 * - Does NOT decide: scientific truth, claim_relation, sufficiency, disposition, Library-save.
 * - Ranker may consume Gate for preliminary ranking exclusion; validation remains authoritative.
 *
 * DOI alone is never sufficient for crop- or topic-constrained questions.
 * Bare entity + weak topic keyword is insufficient when crop+topic are required.
 */
class ScientificEvidenceRelevanceGate
{
    public function __construct(
        private AnswerExpressionAccuracyGate $expressionAccuracyGate = new AnswerExpressionAccuracyGate,
        private ScientificKnowledgeTypeRelevancePolicy $knowledgeTypePolicy = new ScientificKnowledgeTypeRelevancePolicy,
    ) {}

    private const MIN_SCORE_CROP_TOPIC = 70.0;

    private const SPECIES_EXACT = 'exact_species';

    private const SPECIES_GENUS_ONLY = 'genus_only';

    private const SPECIES_DIFFERENT = 'different_species';

    private const SPECIES_ENTITY_LESS = 'entity_less';

    /**
     * @return array{
     *     relevant: bool,
     *     score: float,
     *     entity_matched: bool,
     *     topic_matched: bool,
     *     sense_matched: bool,
     *     context_matched: bool,
     *     context_adequate: bool,
     *     requires_entity: bool,
     *     requires_topic: bool,
     *     species_relation: string,
     *     exact_species_matched: bool,
     *     rejection_reasons: list<string>,
     *     factors: array<string, mixed>
     * }
     */
    public function assess(
        KnowledgeQueryPlan $plan,
        string $title,
        ?string $abstract = null,
        ?string $doi = null,
        ?string $extraText = null,
        ?string $sourceKey = null,
        ?array $existingRelevanceMetadata = null,
    ): array {
        $primaryHaystack = $this->buildHaystack([$title, $abstract]);
        $fullHaystack = $this->buildHaystack([$title, $abstract, $extraText]);
        // Prefer title+abstract for sense/context; fall back to keywords/concepts only as supplement.
        $senseHaystack = $primaryHaystack !== '' ? $primaryHaystack : $fullHaystack;

        $requiresEntity = $this->requiresEntity($plan);
        $requiresTopic = $this->requiresTopic($plan);
        $entityMatched = $this->matchesEntity($plan, $fullHaystack !== '' ? $fullHaystack : $senseHaystack);
        $requiredKnowledgeTypes = $this->knowledgeTypePolicy->requiredTypes($plan);
        $recordKnowledgeType = $sourceKey !== null && trim($sourceKey) !== ''
            ? $this->knowledgeTypePolicy->recordType(trim($sourceKey), $existingRelevanceMetadata)
            : ScientificKnowledgeType::UNKNOWN;
        $identityOriented = $this->knowledgeTypePolicy->allowsIdentityOrientedRelevance(
            $plan,
            $recordKnowledgeType,
            $entityMatched,
        );
        $taxonomyAnswerabilityHaystack = $fullHaystack !== '' ? $fullHaystack : $senseHaystack;
        // Scholarly mentions of a species are not taxonomic classification evidence.
        if ($identityOriented
            && $recordKnowledgeType === ScientificKnowledgeType::SCHOLARLY_EVIDENCE
            && in_array(ScientificKnowledgeType::TAXONOMIC_IDENTITY, $requiredKnowledgeTypes, true)
            && ! $this->hasTaxonomicAnswerabilityCues($taxonomyAnswerabilityHaystack)) {
            $identityOriented = false;
        }
        // Identity / heritage / chemical association records: do not force scholarly topic rules.
        if ($identityOriented) {
            $requiresTopic = false;
        }
        $topicStrength = $this->topicMatchStrength($plan, $senseHaystack);
        if ($topicStrength === 'none' && $extraText !== null && trim($extraText) !== '') {
            $topicStrength = $this->topicMatchStrength($plan, $fullHaystack);
        }
        $topicMatched = $topicStrength !== 'none';
        $senseMatched = $topicStrength === 'strong';
        // Taxonomy identity path: entity + taxonomic answerability satisfies topic for Stage-4 eligibility.
        if ($identityOriented
            && in_array(ScientificKnowledgeType::TAXONOMIC_IDENTITY, $requiredKnowledgeTypes, true)
            && $this->hasTaxonomicAnswerabilityCues($taxonomyAnswerabilityHaystack)) {
            $topicMatched = true;
            if ($topicStrength === 'none') {
                $topicStrength = 'weak';
            }
        }
        // Biodiversity / hymenoptera faunistic path (OSMIA et al.): entity + occurrence/host
        // answerability satisfies topic when scholarly topic labels are absent from abstracts.
        if ($entityMatched
            && (
                $identityOriented
                || in_array(ScientificKnowledgeType::BIODIVERSITY_OCCURRENCE, $requiredKnowledgeTypes, true)
                || $sourceKey === 'osmia'
            )
            && $this->hasFaunisticOccurrenceAnswerabilityCues($taxonomyAnswerabilityHaystack)) {
            $topicMatched = true;
            if ($topicStrength === 'none') {
                $topicStrength = 'weak';
            }
        }
        $contextMatched = $this->matchesAgriculturalContext($senseHaystack)
            || ($extraText !== null && $this->matchesAgriculturalContext(mb_strtolower(trim($extraText))));
        $domainHaystack = $senseHaystack !== '' ? $senseHaystack : $fullHaystack;
        $domainAssessment = $this->assessDomainRelevance(
            $plan,
            $domainHaystack,
            $entityMatched,
            $topicMatched,
            $contextMatched,
            $senseMatched,
        );
        $offDomain = $domainAssessment['hard_reject'];
        $blockedSenses = $this->blockedNegativeSenses($plan, $domainHaystack);
        $crossDomainBlocked = $this->blockedCrossDomainProductionSenses($plan, $domainHaystack);

        $contextAdequate = ! $requiresEntity || ! $requiresTopic
            || $contextMatched
            || $senseMatched
            || $this->queryRequestsPostHarvestSense($plan);

        $rejectionReasons = [];
        $score = 0.0;

        if ($entityMatched) {
            $score += 50.0;
        }
        if ($topicMatched) {
            $score += $senseMatched ? 30.0 : 20.0;
        }
        if ($contextMatched) {
            $score += 15.0;
        }
        if ($this->matchesIntentSense($plan, $senseHaystack)) {
            $score += 10.0;
        }
        if ($doi !== null && trim($doi) !== '') {
            $score += 1.0;
        }

        if ($blockedSenses !== []) {
            $rejectionReasons[] = 'negative_context_sense';
            $score -= 45.0 * count($blockedSenses);
        }

        if ($crossDomainBlocked !== []) {
            $rejectionReasons[] = 'cross_domain_production_sense';
            $score -= 50.0;
        }

        if ($domainAssessment['penalty'] > 0.0) {
            $score -= $domainAssessment['penalty'];
        }

        if ($offDomain) {
            $rejectionReasons[] = 'irrelevant_domain';
            $score = min($score, 1.0);
        }

        if ($requiresEntity && ! $entityMatched) {
            $rejectionReasons[] = 'missing_crop_or_entity';
        }

        if ($requiresTopic && ! $topicMatched) {
            $rejectionReasons[] = 'missing_topic_or_factor';
        }

        if ($requiresEntity && $requiresTopic && $entityMatched && $topicMatched && ! $contextAdequate) {
            $rejectionReasons[] = 'insufficient_topic_sense';
        }

        if ($requiresEntity && $requiresTopic && $score < self::MIN_SCORE_CROP_TOPIC) {
            $rejectionReasons[] = 'below_minimum_relevance_score';
        }

        $hardReject = ($requiresEntity && ! $entityMatched)
            || ($requiresTopic && ! $topicMatched)
            || $offDomain
            || $blockedSenses !== []
            || $crossDomainBlocked !== []
            || ($requiresEntity && $requiresTopic && ! $contextAdequate)
            || ($requiresEntity && $requiresTopic && $score < self::MIN_SCORE_CROP_TOPIC);

        // Knowledge-type identity path: entity-matched taxonomic/heritage/chemical records
        // remain candidates when compatible with required knowledge types. Still not Stage-4 truth.
        if ($identityOriented && $entityMatched) {
            $hardReject = ($requiresEntity && ! $entityMatched)
                || $crossDomainBlocked !== [];
            $rejectionReasons = array_values(array_filter(
                $rejectionReasons,
                static fn (string $reason): bool => ! in_array($reason, [
                    'missing_topic_or_factor',
                    'insufficient_topic_sense',
                    'below_minimum_relevance_score',
                    'irrelevant_domain',
                    'negative_context_sense',
                ], true),
            ));
            if ($score < 50.0) {
                $score = 55.0;
            }
        }

        // Incompatible knowledge type (e.g. traditional-use row for efficacy-only question): soft demote only when entity matched but type mismatch — leave hard reject to scholarly rules above.
        $knowledgeCompatible = $sourceKey === null || $sourceKey === ''
            || $this->knowledgeTypePolicy->isCompatible($requiredKnowledgeTypes, $recordKnowledgeType);

        $factors = [
            'doi_alone_insufficient' => true,
            'crop_id' => $plan->normalizedQuery->cropId,
            'scientific_name' => $plan->normalizedQuery->scientificName,
            'scientific_topics' => $plan->normalizedQuery->constraints['scientific_topics'] ?? [],
            'topic_strength' => $topicStrength,
            'sense_matched' => $senseMatched,
            'context_matched' => $contextMatched,
            'context_adequate' => $contextAdequate,
            'blocked_senses' => $blockedSenses,
            'cross_domain_blocked' => $crossDomainBlocked,
            'research_intent' => $plan->researchIntent,
            'domain_class' => $domainAssessment['domain_class'],
            'domain_penalty' => $domainAssessment['penalty'],
            'domain_hard_reject' => $offDomain,
            'required_knowledge_types' => $requiredKnowledgeTypes,
            'record_knowledge_type' => $recordKnowledgeType,
            'knowledge_type_compatible' => $knowledgeCompatible,
            'identity_oriented_relevance' => $identityOriented,
            'source_key' => $sourceKey,
        ];

        $species = $this->classifySpeciesRelation(
            $plan,
            $fullHaystack !== '' ? $fullHaystack : $senseHaystack,
            trim(implode(' ', array_filter(
                [$title, $abstract, $extraText],
                static fn ($part): bool => is_string($part) && trim($part) !== '',
            ))),
            $entityMatched,
        );
        $factors['species_relation'] = $species['relation'];
        $factors['exact_species_matched'] = $species['exact'];

        if ($hardReject) {
            return [
                'relevant' => false,
                'score' => max(0.0, $score),
                'entity_matched' => $entityMatched,
                'topic_matched' => $topicMatched,
                'sense_matched' => $senseMatched,
                'context_matched' => $contextMatched,
                'context_adequate' => $contextAdequate,
                'requires_entity' => $requiresEntity,
                'requires_topic' => $requiresTopic,
                'species_relation' => $species['relation'],
                'exact_species_matched' => $species['exact'],
                'rejection_reasons' => array_values(array_unique($rejectionReasons)),
                'factors' => $factors,
            ];
        }

        return [
            'relevant' => true,
            'score' => $score,
            'entity_matched' => $entityMatched,
            'topic_matched' => $topicMatched,
            'sense_matched' => $senseMatched,
            'context_matched' => $contextMatched,
            'context_adequate' => $contextAdequate,
            'requires_entity' => $requiresEntity,
            'requires_topic' => $requiresTopic,
            'species_relation' => $species['relation'],
            'exact_species_matched' => $species['exact'],
            'rejection_reasons' => [],
            'factors' => $factors,
        ];
    }

    public function isRelevant(
        KnowledgeQueryPlan $plan,
        string $title,
        ?string $abstract = null,
        ?string $doi = null,
        ?string $extraText = null,
        ?string $sourceKey = null,
        ?array $existingRelevanceMetadata = null,
    ): bool {
        return $this->assess(
            $plan,
            $title,
            $abstract,
            $doi,
            $extraText,
            $sourceKey,
            $existingRelevanceMetadata,
        )['relevant'];
    }

    /**
     * Lexical cues that a record actually supplies taxonomic classification content
     * (not merely mentions a species in an agronomic/chemical study).
     */
    public function hasTaxonomicAnswerabilityCues(string $haystack): bool
    {
        $hay = mb_strtolower(trim($haystack));
        if ($hay === '') {
            return false;
        }

        // Do NOT treat a bare "species" mention as taxonomic answerability — agronomic
        // Mentha papers often say "species" without supplying classification hierarchy.
        return preg_match(
            '/\b(?:taxonomy|taxonomic\s+classification|taxonomic|phylogen(?:y|etic)|binomial|nomenclature|'
            .'scientific\s+name|accepted\s+name|taxonomic\s+status|taxon(?:omic)?\s+rank|'
            .'kingdom\s*:|phylum\s*:|class\s*:|order\s*:|family\s*:|genus\s*:|species\s*:|'
            .'belongs\s+to\s+(?:the\s+)?(?:family|genus|order)|'
            .'family\s+[A-Z][a-z]+aceae|lamiaceae|poaceae|fabaceae|asteraceae|'
            .'تصنيف|الاسم\s+العلمي|فصيلة|رتبة)\b/u',
            $hay,
        ) === 1
            || preg_match('/\b(?:kingdom|phylum|order|family|genus)\s*:\s*\S+/u', $hay) === 1
            || preg_match('/\btaxonomic\s+classification\b/u', $hay) === 1;
    }

    /**
     * Faunistic / occurrence answerability for hymenoptera and wild-bee literature.
     * Distinct from taxonomic hierarchy inventories.
     */
    public function hasFaunisticOccurrenceAnswerabilityCues(string $haystack): bool
    {
        $hay = mb_strtolower(trim($haystack));
        if ($hay === '') {
            return false;
        }

        return preg_match(
            '/\b(?:host(?:s)?|hôte|hôtes|parasitic\s+bee|cleptoparasit|'
            .'habitat|habitats|nest(?:ing)?|occurrence|distribution|rediscover(?:y|ed)|'
            .'first\s+(?:record|mention)|premi[eè]re\s+mention|d[eé]couverte|'
            .'red[eé]couverte|new\s+species|esp[eè]ce\s+nouvelle|'
            .'abeille(?:s)?|wild\s+bee|solitary\s+bee|hymenoptera|apoidea|apidae|'
            .'andrenidae|megachilidae|halictidae|nomada|osmia|megachile)\b/u',
            $hay,
        ) === 1;
    }

    /**
     * @param  list<string|null>  $parts
     */
    private function buildHaystack(array $parts): string
    {
        return mb_strtolower(trim(implode(' ', array_filter($parts, static fn ($part): bool => is_string($part) && trim($part) !== ''))));
    }

    private function requiresEntity(KnowledgeQueryPlan $plan): bool
    {
        if ($plan->normalizedQuery->isEntityDependent()) {
            return true;
        }

        $subjectType = is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? null) : null;

        return $plan->normalizedQuery->cropId !== null
            || $plan->normalizedQuery->scientificName !== null
            || in_array((string) $subjectType, ['crop', 'named_entity', 'animal', 'plant_family'], true);
    }

    private function requiresTopic(KnowledgeQueryPlan $plan): bool
    {
        $propertyTerms = $plan->normalizedQuery->constraints['requested_property_query_terms'] ?? [];
        if (is_array($propertyTerms) && $propertyTerms !== []) {
            return true;
        }
        $requestedProperty = trim((string) ($plan->normalizedQuery->constraints['requested_property'] ?? ''));
        if ($requestedProperty !== '' && ! in_array($requestedProperty, ['general', 'definition'], true)) {
            return true;
        }

        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        if (is_array($factors) && $factors !== []) {
            return true;
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense === 'plant_family_members') {
            return true;
        }

        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];

        return is_array($topics) && $topics !== []
            && ((is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? null) : null) === 'plant_family');
    }

    private function matchesEntity(KnowledgeQueryPlan $plan, string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        $needles = [];
        $query = $plan->normalizedQuery;

        if ($query->scientificName !== null && $query->scientificName !== '') {
            $needles[] = $query->scientificName;
            $genus = trim(explode(' ', $query->scientificName)[0] ?? '');
            if ($genus !== '') {
                $needles[] = $genus;
            }
        }

        if ($query->cropId !== null) {
            $needles = array_merge($needles, AgriculturalEntityCatalog::recognitionLabelsForCrop($query->cropId));
            $needles = array_merge($needles, FieldCropTaxonomyCatalog::searchTermsFor($query->cropId));
        }

        if (is_array($plan->subjectEntity) && in_array((string) ($plan->subjectEntity['type'] ?? ''), ['crop', 'named_entity', 'animal'], true)) {
            $needles[] = (string) ($plan->subjectEntity['value'] ?? '');
            $needles[] = (string) ($plan->subjectEntity['label'] ?? '');
        }

        $surface = $plan->normalizedQuery->namedEntitySurface();
        if ($surface !== null) {
            $needles[] = $surface;
        }

        if (is_array($plan->subjectEntity) && ($plan->subjectEntity['type'] ?? '') === 'plant_family') {
            $family = trim((string) ($plan->subjectEntity['value'] ?? $plan->subjectEntity['label'] ?? ''));
            if ($family !== '') {
                $needles = array_merge(
                    $needles,
                    AgriculturalEntityCatalog::recognitionLabelsForBotanicalFamily($family),
                );
            }
        }

        // Latin taxonomic forms in the question (binomial or bare genus) even when QUS has not
        // resolved cropId/scientificName. Bare genera are required for multilingual questions
        // where the Latin taxon is not followed by a Latin-script epithet (FR/AR word order).
        foreach ($this->latinTaxonCandidatesInText((string) $query->originalQuestion) as $taxon) {
            $needles[] = $taxon;
            $genus = trim(explode(' ', $taxon)[0] ?? '');
            if ($genus !== '') {
                $needles[] = $genus;
            }
        }

        foreach (array_unique(array_filter($needles)) as $needle) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim((string) $needle)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Latin-script taxonomic candidates from case-preserving question text.
     *
     * Supports full binomials and bare genera. Bare genera matter when the question
     * language places non-Latin tokens after the taxon (Arabic/French), which previously
     * made entity matching depend on accidental English/Turkish "false binomials".
     *
     * @return list<string>
     */
    private function latinTaxonCandidatesInText(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $out = [];
        foreach ($this->binomialsInText($text) as $binomial) {
            if (! in_array($binomial, $out, true)) {
                $out[] = $binomial;
            }
        }

        if (preg_match_all('/\b([A-Z][a-z]{3,})\b/u', $text, $captures) === 0) {
            return $out;
        }

        foreach ($captures[1] as $token) {
            $token = trim((string) $token);
            if ($token === '' || $this->isNonTaxonCapitalizedToken($token)) {
                continue;
            }
            if (! in_array($token, $out, true)) {
                $out[] = $token;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function binomialsInText(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        if (preg_match_all('/\b([A-Z][a-z]{2,})\s+([a-z]{3,})\b/u', $text, $captures, PREG_SET_ORDER) === 0) {
            return [];
        }
        $out = [];
        foreach ($captures as $capture) {
            $genus = trim((string) ($capture[1] ?? ''));
            $epithet = trim((string) ($capture[2] ?? ''));
            if ($genus === '' || $this->isNonTaxonCapitalizedToken($genus)) {
                continue;
            }
            // Reject English/French discourse pairs mistaken for binomials
            // ("What host", "Nomada wild", "Quelles associations").
            if ($this->isNonTaxonomicBinomialEpithet($epithet)) {
                continue;
            }
            $binomial = $genus.' '.$epithet;
            if (! in_array($binomial, $out, true)) {
                $out[] = $binomial;
            }
        }

        return $out;
    }

    private function isNonTaxonomicBinomialEpithet(string $epithet): bool
    {
        $epithet = mb_strtolower(trim($epithet));
        if ($epithet === '') {
            return true;
        }

        static $junk = null;
        if ($junk === null) {
            $junk = array_fill_keys([
                'host', 'hosts', 'wild', 'bee', 'bees', 'associations', 'association',
                'habitat', 'habitats', 'causes', 'cause', 'disease', 'diseases',
                'sont', 'est', 'des', 'les', 'une', 'aux', 'pour', 'dans', 'sur',
                'about', 'known', 'from', 'with', 'under', 'over', 'into', 'data',
                'yabani', 'arı', 'arilar', 'arılar', 'konukçu', 'konukcu', 'konak',
            ], true);
        }

        return isset($junk[$epithet]);
    }

    /**
     * Capitalized Latin-script tokens that are function words / discourse openers,
     * not biological genera. Shared across AR/EN/FR/TR question surfaces.
     */
    private function isNonTaxonCapitalizedToken(string $token): bool
    {
        static $stop = null;
        if ($stop === null) {
            $stop = array_fill_keys([
                // EN discourse / question openers
                'What', 'Which', 'When', 'Where', 'Why', 'How', 'Who', 'Whom',
                'The', 'This', 'That', 'These', 'Those', 'There', 'Their', 'Then',
                'Based', 'According', 'Recent', 'First', 'After', 'Before', 'During',
                'About', 'Under', 'Over', 'With', 'From', 'Into', 'Only', 'Also',
                'Some', 'Many', 'Most', 'More', 'Less', 'Such', 'Each', 'Both',
                'Does', 'Did', 'Have', 'Has', 'Had', 'Will', 'Would', 'Could', 'Should',
                'Can', 'May', 'Might', 'Must', 'Need', 'Known', 'Effect', 'Effects',
                'Impact', 'Impacts', 'Role', 'Roles', 'Type', 'Types', 'Level', 'Levels',
                // FR openers
                'Quelles', 'Quelle', 'Quels', 'Quel', 'Comment', 'Pourquoi', 'Quand',
                'Dans', 'Pour', 'Sur', 'Avec', 'Sans', 'Chez', 'Entre', 'Après', 'Avant',
                'Selon', 'Parmi', 'Ainsi', 'Aussi', 'Donc', 'Mais', 'Puis', 'Lors',
                'Une', 'Des', 'Les', 'Ces', 'Cet', 'Cette', 'Ceux', 'Celles',
                'Données', 'Donnee', 'Etude', 'Étude', 'Analyse', 'Présence', 'Presence',
                // TR openers / common non-taxa
                'Nedir', 'Nasıl', 'Hangi', 'Neden', 'Ne', 'Bir', 'Bu', 'Şu', 'Su',
                'Için', 'İçin', 'Göre', 'Kadar', 'Daha', 'En', 'Ve', 'Ile', 'İle',
                'Hakkında', 'Olarak', 'Arasında', 'Üzerinde', 'Uzerinde',
                // Common geographic / meta (not biological genera for entity needles)
                'Europe', 'France', 'Spain', 'Italy', 'Africa', 'Asia', 'America',
                'Maine', 'Loire', 'Paris', 'London', 'Cairo', 'Ankara', 'Istanbul',
            ], true);
        }

        return isset($stop[$token]);
    }

    /**
     * Broad entity_matched may include genus relevance. Species identity is separate.
     *
     * @return array{relation: string, exact: bool}
     */
    private function classifySpeciesRelation(
        KnowledgeQueryPlan $plan,
        string $haystack,
        string $originalHaystack,
        bool $entityMatched,
    ): array {
        if (! $this->requiresEntity($plan)) {
            return ['relation' => self::SPECIES_ENTITY_LESS, 'exact' => false];
        }

        $scientific = mb_strtolower(trim((string) ($plan->normalizedQuery->scientificName ?? '')));
        if (preg_match('/^([a-z]{3,})\s+([a-z]{2,})$/u', $scientific, $target) !== 1) {
            return [
                'relation' => $entityMatched ? self::SPECIES_EXACT : self::SPECIES_ENTITY_LESS,
                'exact' => $entityMatched,
            ];
        }

        $genus = $target[1];
        $epithet = $target[2];
        $source = $originalHaystack !== '' ? $originalHaystack : $haystack;

        if ($this->mentionsHybridOfTarget($source, $genus, $epithet)) {
            return ['relation' => self::SPECIES_GENUS_ONLY, 'exact' => false];
        }

        $blob = mb_strtolower($source);
        if ($this->mentionsExactScientificName($blob, $genus, $epithet)) {
            return ['relation' => self::SPECIES_EXACT, 'exact' => true];
        }

        foreach ($this->sameGenusEpithets($source, $genus) as $foundEpithet) {
            if (! in_array($foundEpithet, [$epithet, 'spp', 'sp', 'ssp', 'var'], true)) {
                return ['relation' => self::SPECIES_GENUS_ONLY, 'exact' => false];
            }
            if (in_array($foundEpithet, ['spp', 'sp'], true)) {
                return ['relation' => self::SPECIES_GENUS_ONLY, 'exact' => false];
            }
        }

        if ($this->mentionsForeignBinomial($source, $genus, $plan)) {
            return ['relation' => self::SPECIES_DIFFERENT, 'exact' => false];
        }

        if ($this->mentionsExactCommonName($blob, $plan, $genus, $epithet)) {
            return ['relation' => self::SPECIES_EXACT, 'exact' => true];
        }

        if (AgriculturalEntityCatalog::containsTerm($haystack, $genus)) {
            return ['relation' => self::SPECIES_GENUS_ONLY, 'exact' => false];
        }

        return [
            'relation' => $entityMatched ? self::SPECIES_GENUS_ONLY : self::SPECIES_ENTITY_LESS,
            'exact' => false,
        ];
    }

    private function mentionsExactScientificName(string $blob, string $genus, string $epithet): bool
    {
        return AgriculturalEntityCatalog::containsTerm($blob, $genus.' '.$epithet)
            || preg_match('/\b'.preg_quote($genus[0], '/').'\.\s*'.preg_quote($epithet, '/').'\b/u', $blob) === 1;
    }

    private function mentionsExactCommonName(
        string $blob,
        KnowledgeQueryPlan $plan,
        string $genus,
        string $epithet,
    ): bool {
        $labels = [];
        $cropId = $plan->normalizedQuery->cropId;
        if ($cropId !== null && $cropId !== '') {
            $labels = array_merge(
                AgriculturalEntityCatalog::recognitionLabelsForCrop($cropId),
                FieldCropTaxonomyCatalog::searchTermsFor($cropId),
            );
        }
        $surface = $plan->normalizedQuery->namedEntitySurface();
        if ($surface !== null) {
            $labels[] = $surface;
        }
        foreach ($labels as $label) {
            $normalized = mb_strtolower(trim((string) $label));
            if ($normalized === '' || $normalized === $genus || $normalized === $genus.' '.$epithet) {
                continue;
            }
            if (AgriculturalEntityCatalog::containsTerm($blob, $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function mentionsHybridOfTarget(string $text, string $genus, string $epithet): bool
    {
        $core = preg_quote($genus, '/').'\s+'.preg_quote($epithet, '/');
        $blob = mb_strtolower($text);

        return preg_match('/\b'.$core.'\s*[×x]\s+/u', $blob) === 1
            || preg_match('/\b[×x]\s+'.$core.'\b/u', $blob) === 1;
    }

    /**
     * @return list<string>
     */
    private function sameGenusEpithets(string $text, string $genus): array
    {
        $blob = mb_strtolower($text);
        $epithets = [];
        $patterns = [
            '/\b'.preg_quote($genus, '/').'\s+([a-z]+|spp\.?|sp\.?)\b/u',
            '/\b'.preg_quote($genus[0], '/').'\.\s*([a-z]+)\b/u',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $blob, $matches) === false) {
                continue;
            }
            foreach ($matches[1] as $found) {
                $epithets[] = rtrim(mb_strtolower((string) $found), '.');
            }
        }

        return array_values(array_unique(array_filter($epithets)));
    }

    private function mentionsForeignBinomial(string $original, string $targetGenus, KnowledgeQueryPlan $plan): bool
    {
        if (preg_match_all('/\b([A-Z][a-z]{3,})\s+([a-z]{4,})\b/u', $original, $matches, PREG_SET_ORDER) === false) {
            return false;
        }

        $targetLabels = [];
        $cropId = $plan->normalizedQuery->cropId;
        foreach ([$cropId, $plan->normalizedQuery->crop] as $identityToken) {
            $normalized = mb_strtolower(trim((string) $identityToken));
            if ($normalized !== '') {
                $targetLabels[$normalized] = true;
            }
        }
        if ($cropId !== null && $cropId !== '') {
            foreach (array_merge(
                AgriculturalEntityCatalog::recognitionLabelsForCrop($cropId),
                FieldCropTaxonomyCatalog::searchTermsFor($cropId),
            ) as $label) {
                $targetLabels[mb_strtolower(trim((string) $label))] = true;
            }
        }

        $skip = [
            'temperature' => true, 'germination' => true, 'characteristics' => true,
            'requirements' => true, 'conditions' => true, 'evidence' => true,
            'optimal' => true, 'effect' => true, 'effects' => true, 'study' => true,
            'growth' => true, 'light' => true, 'seed' => true, 'seeds' => true,
            'plant' => true, 'plants' => true, 'range' => true, 'under' => true,
            'controlled' => true, 'measured' => true, 'availability' => true,
            'determination' => true, 'cardinal' => true, 'japanese' => true,
            'sweet' => true, 'white' => true, 'common' => true, 'wild' => true,
        ];
        foreach ($matches as $match) {
            $genus = mb_strtolower($match[1]);
            $epithet = mb_strtolower($match[2]);
            if ($genus === $targetGenus || isset($skip[$genus]) || isset($skip[$epithet])) {
                continue;
            }
            if (isset($targetLabels[$genus]) || isset($targetLabels[$epithet]) || isset($targetLabels[$genus.' '.$epithet])) {
                continue;
            }
            $requestedCropId = mb_strtolower(trim((string) ($cropId ?? '')));
            if ($requestedCropId !== ''
                && AgriculturalEntityCatalog::resolveCanonicalCropIdFromLabel($genus) === $requestedCropId) {
                continue;
            }
            $resolvedPair = AgriculturalEntityCatalog::resolveCanonicalCropIdFromLabel($genus.' '.$epithet);
            if ($requestedCropId !== '' && $resolvedPair === $requestedCropId) {
                continue;
            }
            if ($resolvedPair !== null && $resolvedPair !== $requestedCropId) {
                return true;
            }
            if ($this->isKnownForeignScientificGenus($genus, $targetGenus)) {
                return true;
            }
        }

        return false;
    }

    private function isKnownForeignScientificGenus(string $genus, string $targetGenus): bool
    {
        $genus = mb_strtolower(trim($genus));
        $targetGenus = mb_strtolower(trim($targetGenus));
        if ($genus === '' || $genus === $targetGenus) {
            return false;
        }

        foreach (AgriculturalEntityCatalog::cropRecognitionEntries() as $entry) {
            $cropId = trim((string) ($entry['crop_id'] ?? ''));
            if ($cropId === '') {
                continue;
            }
            $scientific = mb_strtolower(trim(FieldCropTaxonomyCatalog::scientificNameFor($cropId)));
            if ($scientific === '') {
                continue;
            }
            $entryGenus = trim((string) (explode(' ', str_replace(['× ', '×'], '', $scientific))[0] ?? ''));
            if ($entryGenus !== '' && $entryGenus === $genus) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return 'none'|'weak'|'strong'
     */
    private function topicMatchStrength(KnowledgeQueryPlan $plan, string $haystack): string
    {
        if ($haystack === '') {
            return 'none';
        }

        $best = 'none';
        $propertyTerms = $plan->normalizedQuery->constraints['requested_property_query_terms'] ?? [];
        if (is_array($propertyTerms) && $propertyTerms !== []
            && AgriculturalEntityCatalog::haystackAddressesRequestedProperty($haystack, $propertyTerms)) {
            $best = 'weak';
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $senseTerms = [];
        if ($sense !== '') {
            $senseTerms = array_values(array_unique(array_merge(
                AgriculturalEntityCatalog::senseQueryTerms($sense),
                ScientificQuestionSemantics::senseQueryTerms($sense),
            )));
        }

        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        if (is_array($topics)) {
            foreach ($topics as $topic) {
                $normalized = mb_strtolower(trim((string) $topic));
                if ($normalized === '' || in_array($normalized, ['agriculture', 'farming'], true)) {
                    continue;
                }
                // Skip Latin family names here — those are entity needles, not topics.
                if ((is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? '') : '') === 'plant_family'
                    && strcasecmp($normalized, (string) ($plan->subjectEntity['value'] ?? '')) === 0) {
                    continue;
                }
                if (AgriculturalEntityCatalog::containsTerm($haystack, $normalized)) {
                    $best = 'strong';
                    break;
                }
            }
        }

        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        if (! is_array($factors) || $factors === []) {
            if ($sense !== '') {
                foreach ($senseTerms as $term) {
                    if (in_array(mb_strtolower(trim($term)), ['agriculture', 'farming'], true)) {
                        continue;
                    }
                    if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($term)))) {
                        $best = 'strong';
                        break;
                    }
                }
            }
            if ($best === 'none') {
                foreach (AgriculturalEntityCatalog::englishTermsForIntent($plan->researchIntent) as $term) {
                    if (in_array($term, ['agriculture', 'farming'], true)) {
                        continue;
                    }
                    if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($term)))) {
                        $best = 'strong';
                        break;
                    }
                }
            }

            $best = $this->demoteProcessingTemperatureFalsePositive($plan, $haystack, $best);

            return $this->upgradeTopicStrengthWithMeasurementSense($plan, $haystack, $best);
        }

        foreach ($factors as $factor) {
            $strongSignals = AgriculturalEntityCatalog::strongTopicFactorSignals()[$factor] ?? [];
            foreach ($strongSignals as $signal) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                    $best = 'strong';
                    break 2;
                }
            }

            $signals = AgriculturalEntityCatalog::topicFactorSignals()[$factor] ?? [];
            foreach ($signals as $signal) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim((string) $signal)))) {
                    $best = 'weak';
                    break;
                }
            }
            $english = AgriculturalEntityCatalog::topicFactorEnglishLabels()[$factor] ?? null;
            if ($english !== null && AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower($english))) {
                $best = 'weak';
            }
        }

        $best = $this->demoteProcessingTemperatureFalsePositive($plan, $haystack, $best);

        return $this->upgradeTopicStrengthWithMeasurementSense($plan, $haystack, $best);
    }

    /**
     * Plant-growth temperature questions must not treat chemistry/extraction
     * "thermal processing" mentions as a temperature-physiology topic hit.
     *
     * @param  'none'|'weak'|'strong'  $best
     * @return 'none'|'weak'|'strong'
     */
    private function demoteProcessingTemperatureFalsePositive(
        KnowledgeQueryPlan $plan,
        string $haystack,
        string $best,
    ): string {
        if ($best === 'none' || $haystack === '') {
            return $best;
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense !== 'plant_growth' && $plan->researchIntent !== 'environmental_requirements') {
            return $best;
        }

        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        $needles = array_merge(is_array($factors) ? $factors : [], is_array($topics) ? $topics : []);
        $asksTemperature = false;
        foreach ($needles as $needle) {
            if (in_array(mb_strtolower(trim((string) $needle)), ['temperature', 'heat', 'thermal'], true)) {
                $asksTemperature = true;
                break;
            }
        }
        if (! $asksTemperature) {
            return $best;
        }

        $agronomic = [
            'growing temperature', 'optimum temperature', 'optimal temperature',
            'temperature requirement', 'temperature regime', 'heat stress',
            'plant growth', 'rhizome growth', 'crop growth', 'cultivation',
            'field cultivation', 'air temperature', 'soil temperature',
        ];
        foreach ($agronomic as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, $marker)) {
                return $best;
            }
        }

        $processing = [
            'essential oil', 'oleoresin', 'extraction', 'microwave', 'antioxidant',
            'antimicrobial', 'antibacterial', 'anti-cancer', 'anticancer', 'anti-inflammatory',
            'gingerol', 'shogaol', 'bioactivity', 'broiler', 'poultry', 'neurodegenerative',
            'food processing', 'thermal processing', 'thermally unstable', 'thermally labile',
        ];
        foreach ($processing as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, $marker)) {
                return 'none';
            }
        }

        return $best;
    }

    /**
     * @return 'none'|'weak'|'strong'
     */
    private function upgradeTopicStrengthWithMeasurementSense(
        KnowledgeQueryPlan $plan,
        string $haystack,
        string $best,
    ): string {
        if ($this->haystackHasMeasurementClassSense($plan, $haystack)) {
            return 'strong';
        }

        return $best;
    }

    /**
     * Measurement-class topic sense: consume frozen CSQ/RS family, then AccuracyGate
     * unit/class compatibility. Not a property-identity resolver.
     */
    private function haystackHasMeasurementClassSense(KnowledgeQueryPlan $plan, string $haystack): bool
    {
        $family = $this->expressionAccuracyGate->frozenMeasurementFamily($plan);
        if ($family === '') {
            return false;
        }

        return $this->expressionAccuracyGate->haystackHasMeasurementClassSense($haystack, $family);
    }

    private function matchesAgriculturalContext(string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        foreach (AgriculturalEntityCatalog::agriculturalContextSignals() as $signal) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                return true;
            }
        }

        return false;
    }

    private function matchesIntentSense(KnowledgeQueryPlan $plan, string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        foreach (AgriculturalEntityCatalog::englishTermsForIntent($plan->researchIntent) as $term) {
            if (in_array($term, ['agriculture', 'farming'], true)) {
                continue;
            }
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($term)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function blockedNegativeSenses(KnowledgeQueryPlan $plan, string $haystack): array
    {
        if ($haystack === '') {
            return [];
        }

        $blocked = [];
        foreach (AgriculturalEntityCatalog::negativeSenseMarkers() as $sense => $markers) {
            if ($this->queryAllowsSense($plan, $sense)) {
                continue;
            }

            foreach ($markers as $marker) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($marker)))) {
                    $blocked[] = $sense;
                    break;
                }
            }
        }

        return array_values(array_unique($blocked));
    }

    private function queryAllowsSense(KnowledgeQueryPlan $plan, string $sense): bool
    {
        $explicit = $this->explicitPostHarvestSensesFromPlan($plan);
        if ($explicit !== []) {
            return in_array($sense, $explicit, true);
        }

        if (in_array($plan->researchIntent, AgriculturalEntityCatalog::intentsAllowingNegativeSense($sense), true)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function explicitPostHarvestSensesFromPlan(KnowledgeQueryPlan $plan): array
    {
        $found = [];
        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];
        foreach (['drying', 'storage', 'extraction', 'processing'] as $sense) {
            if (is_array($factors) && in_array($sense, $factors, true)) {
                $found[] = $sense;
            }
        }

        $question = mb_strtolower(trim(implode(' ', array_filter([
            $plan->normalizedQuery->normalizedQuestion,
            $plan->normalizedQuery->originalQuestion,
        ]))));

        foreach (AgriculturalEntityCatalog::negativeSenseMarkers() as $sense => $markers) {
            foreach ($markers as $marker) {
                if (AgriculturalEntityCatalog::containsTerm($question, mb_strtolower(trim($marker)))) {
                    $found[] = $sense;
                    break;
                }
            }
        }

        return array_values(array_unique($found));
    }

    private function queryRequestsPostHarvestSense(KnowledgeQueryPlan $plan): bool
    {
        foreach ($this->explicitPostHarvestSensesFromPlan($plan) as $sense) {
            if (in_array($sense, ['drying', 'storage'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Plant-growth / germination / irrigation questions must not accept livestock/poultry
     * feed trials that merely mention the crop name + temperature.
     *
     * @return list<string>
     */
    private function blockedCrossDomainProductionSenses(KnowledgeQueryPlan $plan, string $haystack): array
    {
        if ($haystack === '') {
            return [];
        }

        if (in_array($plan->researchIntent, [
            'animal_production',
            'poultry_production',
            'feed',
            'aquaculture',
            'beekeeping',
        ], true)) {
            return [];
        }

        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $plantSenses = [
            'plant_growth',
            'seed_germination',
            'salinity_physiology',
            'crop_water_requirement',
            'plant_nutrition',
        ];
        if ($sense !== '' && ! in_array($sense, $plantSenses, true)) {
            return [];
        }
        if ($sense === '' && ! $this->requiresEntity($plan)) {
            return [];
        }

        $markers = [
            'broiler' => ['broiler', 'broilers'],
            'poultry' => ['poultry', 'chicken', 'chickens', 'hen ', ' hens'],
            'livestock_feed' => ['livestock feed', 'animal feed', 'cattle diet', 'dairy cow', 'pig diet', 'swine diet'],
        ];
        $blocked = [];
        foreach ($markers as $label => $needles) {
            foreach ($needles as $needle) {
                if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($needle)))) {
                    $blocked[] = $label;
                    break;
                }
            }
        }

        return array_values(array_unique($blocked));
    }

    /**
     * Context-aware domain classification.
     * Ambiguous words (economics, psychology, …) alone never hard-reject;
     * strong unrelated markers still do unless an agricultural rescue applies.
     * Secondary domain mentions with ag+entity+topic get a score penalty only.
     *
     * @return array{
     *     hard_reject: bool,
     *     penalty: float,
     *     domain_class: string|null
     * }
     */
    private function assessDomainRelevance(
        KnowledgeQueryPlan $plan,
        string $haystack,
        bool $entityMatched,
        bool $topicMatched,
        bool $contextMatched,
        bool $senseMatched,
    ): array {
        if ($haystack === '') {
            return ['hard_reject' => false, 'penalty' => 0.0, 'domain_class' => null];
        }

        if ($plan->researchIntent === 'agricultural_economics') {
            return ['hard_reject' => false, 'penalty' => 0.0, 'domain_class' => 'agricultural_economics'];
        }

        $agAnchored = $this->hasAgriculturalDomainAnchor($haystack, $entityMatched, $topicMatched, $contextMatched, $senseMatched);
        $penalty = 0.0;
        $domainClass = null;
        $hardReject = false;

        foreach (AgriculturalEntityCatalog::strongIrrelevantDomainMarkers() as $family => $markers) {
            if (! $this->haystackContainsAny($haystack, $markers)) {
                continue;
            }

            // Clinical/finance markers stay hard-reject unless explicitly agricultural.
            if ($this->hasAgriculturalDomainRescue($haystack, $family)) {
                $domainClass = 'agricultural_'.$family.'_secondary';
                $penalty = max($penalty, 12.0);

                continue;
            }

            $hardReject = true;
            $domainClass = 'unrelated_'.$family;
        }

        if ($hardReject) {
            return ['hard_reject' => true, 'penalty' => 0.0, 'domain_class' => $domainClass];
        }

        foreach (AgriculturalEntityCatalog::ambiguousDomainMarkers() as $family => $markers) {
            if (! $this->haystackContainsAny($haystack, $markers)) {
                continue;
            }

            if ($this->hasAgriculturalDomainRescue($haystack, $family)) {
                $domainClass = 'agricultural_'.$family;
                // Explicit ag economics/psych is on-domain for sciences; light penalty only
                // when the question is a crop-growth/topic question (secondary branch).
                if ($this->requiresEntity($plan) && $this->requiresTopic($plan) && $family === 'economics') {
                    $penalty = max($penalty, 8.0);
                }

                continue;
            }

            if ($agAnchored) {
                // Incidental domain word inside an otherwise on-topic agricultural paper.
                $domainClass = $family.'_incidental';
                $penalty = max($penalty, 12.0);

                continue;
            }

            $hardReject = true;
            $domainClass = 'unrelated_'.$family;
            break;
        }

        if (! $hardReject && $domainClass === null && $this->matchesAgriculturalScienceBranch($haystack)) {
            $domainClass = 'agricultural_sciences';
        }

        return [
            'hard_reject' => $hardReject,
            'penalty' => $hardReject ? 0.0 : $penalty,
            'domain_class' => $domainClass,
        ];
    }

    private function hasAgriculturalDomainAnchor(
        string $haystack,
        bool $entityMatched,
        bool $topicMatched,
        bool $contextMatched,
        bool $senseMatched,
    ): bool {
        if ($this->matchesAgriculturalScienceBranch($haystack)) {
            return true;
        }

        // Crop/topic papers with clear agricultural growth context: domain words are secondary.
        return $entityMatched && $topicMatched && ($contextMatched || $senseMatched);
    }

    private function hasAgriculturalDomainRescue(string $haystack, string $family): bool
    {
        $phrases = AgriculturalEntityCatalog::agriculturalDomainRescuePhrases()[$family] ?? [];

        return $this->haystackContainsAny($haystack, $phrases);
    }

    private function matchesAgriculturalScienceBranch(string $haystack): bool
    {
        foreach (AgriculturalEntityCatalog::agriculturalScienceBranchSignals() as $signal) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $needles
     */
    private function haystackContainsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($needle)))) {
                return true;
            }
        }

        return false;
    }
}
