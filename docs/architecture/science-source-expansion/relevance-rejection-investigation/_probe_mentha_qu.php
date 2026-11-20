<?php

declare(strict_types=1);

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$question = $argv[1] ?? 'What is the taxonomic classification of Mentha piperita?';

$svc = $app->make(App\Services\Agriculture\Research\QueryUnderstandingService::class);
$nq = $svc->understand(['query' => $question]);

$out = [
    'original' => $nq->originalQuestion,
    'normalized' => $nq->normalizedQuestion,
    'crop' => $nq->crop,
    'crop_id' => $nq->cropId,
    'scientific_name' => $nq->scientificName,
    'topic' => $nq->topic,
    'subtopic' => $nq->subtopic,
    'research_intent' => $nq->researchIntent ?? null,
    'constraints' => [
        'question_type' => $nq->constraints['question_type'] ?? null,
        'required_evidence_type' => $nq->constraints['required_evidence_type'] ?? null,
        'scientific_sense' => $nq->constraints['scientific_sense'] ?? null,
        'scientific_topics' => $nq->constraints['scientific_topics'] ?? null,
        'named_entity_surface' => $nq->constraints['named_entity_surface'] ?? null,
        'entity_resolution' => $nq->constraints['entity_resolution'] ?? null,
        'resolved_entity_id' => $nq->constraints['resolved_entity_id'] ?? null,
        'primary_user_act' => $nq->constraints['primary_user_act'] ?? null,
        'required_evidence_characteristics' => $nq->constraints['required_evidence_characteristics'] ?? null,
    ],
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

// Also probe CSQ if available
try {
    $csq = $app->make(App\Services\Agriculture\Research\CanonicalScientificQuestion::class);
    if (method_exists($csq, 'fromNormalizedQuery')) {
        // skip — unknown API
    }
} catch (Throwable $e) {
    // ignore
}

// Probe relevance gate with a Mentha-like title against the plan
$planner = $app->make(App\Services\Agriculture\Research\AgriculturalResearchPlanner::class);
$plan = $planner->plan($nq);
$gate = $app->make(App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate::class);

$samples = [
    [
        'id' => 'sample_mentha_taxonomy_paper',
        'title' => 'Taxonomic classification and phylogeny of Mentha piperita L.',
        'abstract' => 'Mentha piperita (peppermint) belongs to Lamiaceae. We review genus Mentha taxonomy, species delimitation, and classification.',
        'source_key' => 'openalex',
    ],
    [
        'id' => 'sample_mentha_oil',
        'title' => 'Essential oil composition of Mentha piperita under drought',
        'abstract' => 'Essential oil yield and menthol content of peppermint were measured under water stress.',
        'source_key' => 'openalex',
    ],
    [
        'id' => 'sample_unrelated',
        'title' => 'Soil types and land classification inventory of Morocco',
        'abstract' => 'A nationwide soil map and land capability classification for Morocco.',
        'source_key' => 'openalex',
    ],
];

$ledger = [];
foreach ($samples as $sample) {
    $a = $gate->assess(
        $plan,
        $sample['title'],
        $sample['abstract'],
        null,
        null,
        $sample['source_key'],
        null,
    );
    $ledger[] = [
        'candidate_id' => $sample['id'],
        'title' => $sample['title'],
        'relevant' => $a['relevant'],
        'score' => $a['score'],
        'entity_matched' => $a['entity_matched'],
        'topic_matched' => $a['topic_matched'],
        'rejection_reasons' => $a['rejection_reasons'],
        'species_relation' => $a['species_relation'] ?? null,
        'factors_identity_oriented' => $a['factors']['identity_oriented_relevance'] ?? null,
        'required_knowledge_types' => $a['factors']['required_knowledge_types'] ?? null,
        'record_knowledge_type' => $a['factors']['record_knowledge_type'] ?? null,
    ];
}

echo "---GATE_LEDGER---\n";
echo json_encode([
    'plan_crop_id' => $plan->normalizedQuery->cropId,
    'plan_scientific_name' => $plan->normalizedQuery->scientificName,
    'plan_intent' => $plan->researchIntent,
    'ledger' => $ledger,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
