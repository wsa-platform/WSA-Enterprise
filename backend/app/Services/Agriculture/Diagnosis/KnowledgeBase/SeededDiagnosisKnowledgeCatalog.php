<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Seeded verified diagnosis knowledge catalog (in-code, no DB migration).
 * Crop examples are fixtures for coverage — architecture is crop-generic.
 */
class SeededDiagnosisKnowledgeCatalog
{
    public function __construct(
        private readonly InMemoryDiagnosisKnowledgeStore $store,
        private readonly DiagnosisKnowledgeRecordValidator $validator,
    ) {}

    public function seed(): void
    {
        if ($this->store->countVerified() > 0) {
            return;
        }

        foreach ($this->definitions() as $definition) {
            $record = DiagnosisKnowledgeRecord::fromArray($definition);
            $validation = $this->validator->validate($record);
            if (! $validation->valid) {
                continue;
            }
            $this->store->putVerified($record);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        $curated = static fn (string $label): array => [
            'label' => $label,
            'type' => 'curated_catalog',
            'institution' => 'WSA curated diagnosis catalog',
            'url' => null,
            'doi' => null,
            'publication_year' => null,
            'claims_scientific_evidence' => false,
        ];

        return [
            [
                'id' => 'kb_foliar_leaf_spot_syndrome',
                'common_name' => 'Foliar leaf spot syndrome',
                'scientific_name' => null,
                'scientific_name_verified' => false,
                'category' => 'disease',
                'causal_class' => 'biotic',
                'pathogen_type' => 'fungal',
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'crop_keys' => ['tomato', 'wheat', 'citrus', 'generic'],
                'common_names' => ['Leaf spot', 'بقع الأوراق'],
                'aliases' => ['leaf spotting', 'foliar spots', 'بقع ورقية'],
                'symptoms' => ['leaf spot', 'lesion', 'blotch', 'necrosis'],
                'plant_parts' => ['leaf', 'foliage'],
                'observation_patterns' => [
                    [
                        'id' => 'leaf_spot_visual',
                        'keywords' => ['spot', 'lesion', 'blotch', 'halo', 'necrosis'],
                        'plant_parts' => ['leaf'],
                        'negative_keywords' => ['powdery', 'white coating'],
                        'observation_types' => ['leaf_spot', 'lesion'],
                    ],
                ],
                'differentials' => [
                    ['id' => 'early_blight_like', 'common_name' => 'Early blight-like pattern', 'relation' => 'alternative'],
                    ['id' => 'bacterial_spot', 'common_name' => 'Bacterial leaf spot', 'relation' => 'alternative'],
                    ['id' => 'abiotic_scorch', 'common_name' => 'Abiotic leaf scorch', 'relation' => 'contradicting', 'notes' => 'Uniform margin burn without discrete lesions may contradict classic spotting.'],
                    ['id' => 'nutrient', 'common_name' => 'Nutrient deficiency', 'relation' => 'alternative'],
                ],
                'sources' => [$curated('Curated foliar leaf-spot diagnostic pattern')],
                'supporting_evidence_notes' => ['Discrete foliar lesions/spots are consistent with leaf spot syndromes.'],
                'contradicting_evidence_notes' => ['Uniform whole-leaf yellowing without lesions may favor abiotic or nutrient causes.'],
                'recommended_additional_observations' => ['close-up of lesion margins', 'upper vs lower leaf surfaces'],
                'safety_notices' => ['Image-only leaf spot ID is decision support, not laboratory confirmation.'],
                'management_references' => [
                    [
                        'summary' => 'Improve canopy airflow and remove severely affected leaves where appropriate; confirm before chemical interventions.',
                        'safety_notes' => ['No pesticide rates provided.'],
                        'requires_local_advisor' => true,
                    ],
                ],
                'version' => '1.0',
                'freshness_label' => 'curated-static',
            ],
            [
                'id' => 'kb_alternaria_early_blight_like',
                'common_name' => 'Early blight-like foliar disease',
                'scientific_name' => 'Alternaria solani',
                'scientific_name_verified' => true,
                'category' => 'disease',
                'causal_class' => 'biotic',
                'pathogen_type' => 'fungal',
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'crop_keys' => ['tomato', 'potato', 'solanaceous'],
                'common_names' => ['Early blight', 'اللفحة المبكرة'],
                'aliases' => ['alternaria leaf spot', 'target spot-like'],
                'symptoms' => ['target lesion', 'concentric rings', 'yellow halo', 'leaf spot'],
                'plant_parts' => ['leaf', 'lower leaves'],
                'observation_patterns' => [
                    [
                        'id' => 'early_blight_pattern',
                        'keywords' => ['yellow halo', 'concentric', 'target', 'brown', 'spot', 'lesion', 'lower leaves'],
                        'plant_parts' => ['leaf', 'lower leaves'],
                        'negative_keywords' => ['powdery', 'mosaic'],
                        'observation_types' => ['leaf_spot', 'chlorosis'],
                    ],
                ],
                'differentials' => [
                    ['id' => 'septoria', 'common_name' => 'Septoria leaf spot-like', 'relation' => 'alternative'],
                    ['id' => 'bacterial', 'common_name' => 'Bacterial spot', 'relation' => 'alternative'],
                    ['id' => 'nutrient', 'common_name' => 'Nutrient stress', 'relation' => 'alternative'],
                ],
                'sources' => [[
                    'label' => 'Curated solanaceous early-blight pattern with verified binomial from diagnostic teaching catalog',
                    'type' => 'curated_catalog',
                    'institution' => 'WSA curated diagnosis catalog',
                    'url' => null,
                    'doi' => null,
                    'claims_scientific_evidence' => true,
                ]],
                'supporting_evidence_notes' => ['Brown lesions with yellow halos on lower leaves are commonly associated with early blight-like disease.'],
                'contradicting_evidence_notes' => ['Powdery white coatings contradict typical early blight presentation.'],
                'recommended_additional_observations' => ['stem lesions', 'fruit symptoms', 'crop history'],
                'safety_notices' => ['Binomial attribution remains provisional without lab confirmation.'],
                'management_references' => [
                    [
                        'summary' => 'Reduce leaf wetness duration and avoid overhead irrigation late in the day where feasible.',
                        'requires_local_advisor' => true,
                    ],
                ],
                'version' => '1.0',
                'freshness_label' => 'curated-static',
            ],
            [
                'id' => 'kb_wheat_leaf_rust_like',
                'common_name' => 'Wheat leaf rust-like symptoms',
                'scientific_name' => 'Puccinia triticina',
                'scientific_name_verified' => true,
                'category' => 'disease',
                'causal_class' => 'biotic',
                'pathogen_type' => 'fungal',
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'crop_keys' => ['wheat', 'cereal'],
                'common_names' => ['Leaf rust', 'صدأ الأوراق'],
                'aliases' => ['brown rust', 'wheat rust'],
                'symptoms' => ['pustule', 'rust', 'orange spore', 'leaf rust'],
                'plant_parts' => ['leaf'],
                'observation_patterns' => [
                    [
                        'id' => 'rust_pustules',
                        'keywords' => ['rust', 'pustule', 'orange', 'uredinia'],
                        'plant_parts' => ['leaf'],
                        'negative_keywords' => ['powdery white', 'mosaic'],
                        'observation_types' => ['rust', 'pustule', 'leaf_spot'],
                    ],
                ],
                'differentials' => [
                    ['id' => 'stripe_rust', 'common_name' => 'Stripe rust-like', 'relation' => 'alternative'],
                    ['id' => 'tan_spot', 'common_name' => 'Tan spot', 'relation' => 'alternative'],
                ],
                'sources' => [[
                    'label' => 'Curated cereal rust diagnostic pattern',
                    'type' => 'curated_catalog',
                    'institution' => 'WSA curated diagnosis catalog',
                    'claims_scientific_evidence' => true,
                ]],
                'supporting_evidence_notes' => ['Orange-brown leaf pustules support rust-like candidates.'],
                'contradicting_evidence_notes' => [],
                'recommended_additional_observations' => ['pustule color and arrangement', 'cultivar identity'],
                'safety_notices' => ['Rust species confirmation may require specialist review.'],
                'management_references' => [
                    ['summary' => 'Monitor disease progress and consult local cereal pathology guidance before control decisions.'],
                ],
                'version' => '1.0',
                'freshness_label' => 'curated-static',
            ],
            [
                'id' => 'kb_citrus_leaf_symptom_complex',
                'common_name' => 'Citrus foliar symptom complex',
                'scientific_name' => null,
                'scientific_name_verified' => false,
                'category' => 'disorder',
                'causal_class' => 'unspecified',
                'pathogen_type' => null,
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'crop_keys' => ['citrus', 'orange', 'lemon'],
                'common_names' => ['Citrus leaf symptoms', 'أعراض أوراق الحمضيات'],
                'aliases' => ['citrus chlorosis', 'citrus spotting'],
                'symptoms' => ['chlorosis', 'leaf spot', 'blotch', 'vein clearing'],
                'plant_parts' => ['leaf'],
                'observation_patterns' => [
                    [
                        'id' => 'citrus_foliar',
                        'keywords' => ['chlorosis', 'spot', 'citrus', 'blotch', 'vein'],
                        'plant_parts' => ['leaf'],
                        'negative_keywords' => [],
                        'observation_types' => ['chlorosis', 'leaf_spot'],
                    ],
                ],
                'differentials' => [
                    ['id' => 'citrus_greening_like', 'common_name' => 'Huanglongbing-like pattern', 'relation' => 'alternative'],
                    ['id' => 'nutrient', 'common_name' => 'Micronutrient deficiency', 'relation' => 'alternative'],
                    ['id' => 'fungal', 'common_name' => 'Fungal leaf spot', 'relation' => 'alternative'],
                    ['id' => 'abiotic', 'common_name' => 'Abiotic stress', 'relation' => 'alternative'],
                ],
                'sources' => [$curated('Curated citrus foliar differential pattern')],
                'supporting_evidence_notes' => ['Citrus foliar symptoms often have multiple plausible causes.'],
                'contradicting_evidence_notes' => ['A single image rarely separates biotic vs abiotic citrus disorders.'],
                'recommended_additional_observations' => ['fruit symptoms', 'vector history', 'soil/water notes'],
                'safety_notices' => ['High ambiguity — human review recommended for quarantine-relevant citrus diseases.'],
                'management_references' => [
                    ['summary' => 'Collect additional diagnostic context before management; escalate quarantine-suspect cases.'],
                ],
                'version' => '1.0',
                'freshness_label' => 'curated-static',
            ],
            [
                'id' => 'kb_nitrogen_deficiency_like',
                'common_name' => 'Nitrogen deficiency-like chlorosis',
                'scientific_name' => null,
                'scientific_name_verified' => false,
                'category' => 'nutrient_deficiency',
                'causal_class' => 'abiotic',
                'pathogen_type' => null,
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'crop_keys' => ['generic', 'tomato', 'wheat', 'citrus'],
                'common_names' => ['Nitrogen deficiency', 'نقص النيتروجين'],
                'aliases' => ['N deficiency', 'general yellowing'],
                'symptoms' => ['chlorosis', 'yellowing', 'pale leaves', 'uniform yellow'],
                'plant_parts' => ['leaf', 'older leaves'],
                'observation_patterns' => [
                    [
                        'id' => 'n_def_pattern',
                        'keywords' => ['yellow', 'chlorosis', 'pale', 'uniform yellowing', 'older leaves'],
                        'plant_parts' => ['leaf', 'older leaves'],
                        'negative_keywords' => ['pustule', 'powdery', 'chewing', 'holes'],
                        'observation_types' => ['chlorosis', 'yellowing'],
                    ],
                ],
                'differentials' => [
                    ['id' => 'water_stress', 'common_name' => 'Water stress', 'relation' => 'alternative'],
                    ['id' => 'iron', 'common_name' => 'Iron chlorosis-like', 'relation' => 'alternative'],
                    ['id' => 'disease', 'common_name' => 'Systemic disease', 'relation' => 'alternative'],
                    ['id' => 'salinity', 'common_name' => 'Salinity stress', 'relation' => 'alternative'],
                ],
                'sources' => [$curated('Curated nutrient deficiency diagnostic pattern')],
                'supporting_evidence_notes' => ['Uniform chlorosis of older leaves can support nitrogen deficiency-like candidates.'],
                'contradicting_evidence_notes' => ['Discrete necrotic spots may contradict simple nutrient deficiency.'],
                'recommended_additional_observations' => ['which leaves yellow first', 'recent fertility history'],
                'safety_notices' => ['Tissue testing may be required to confirm nutrient status.'],
                'management_references' => [
                    ['summary' => 'Review fertility program with a local advisor; do not assume rates from images.'],
                ],
                'version' => '1.0',
                'freshness_label' => 'curated-static',
            ],
            [
                'id' => 'kb_salinity_stress',
                'common_name' => 'Salinity stress',
                'scientific_name' => null,
                'scientific_name_verified' => false,
                'category' => 'abiotic_stress',
                'causal_class' => 'abiotic',
                'pathogen_type' => null,
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'crop_keys' => ['generic', 'tomato', 'citrus', 'wheat'],
                'common_names' => ['Salt stress', 'إجهاد الملوحة'],
                'aliases' => ['salinity', 'salt injury', 'saline stress'],
                'symptoms' => ['marginal burn', 'leaf tip burn', 'stunting', 'chlorosis'],
                'plant_parts' => ['leaf', 'root'],
                'observation_patterns' => [
                    [
                        'id' => 'salinity_pattern',
                        'keywords' => ['salinity', 'salt', 'marginal burn', 'tip burn', 'stunting'],
                        'plant_parts' => ['leaf'],
                        'negative_keywords' => ['pustule', 'insect frass'],
                        'observation_types' => ['chlorosis', 'necrosis', 'abiotic'],
                    ],
                ],
                'differentials' => [
                    ['id' => 'drought', 'common_name' => 'Drought stress', 'relation' => 'alternative'],
                    ['id' => 'nutrient', 'common_name' => 'Nutrient toxicity', 'relation' => 'alternative'],
                    ['id' => 'herbicide', 'common_name' => 'Herbicide injury', 'relation' => 'alternative'],
                ],
                'sources' => [$curated('Curated abiotic salinity stress pattern')],
                'supporting_evidence_notes' => ['Marginal leaf burn with salinity context supports abiotic salt stress.'],
                'contradicting_evidence_notes' => ['Active insect feeding signs may indicate a different primary cause.'],
                'recommended_additional_observations' => ['irrigation water EC', 'soil salinity notes'],
                'safety_notices' => ['Confirm with soil/water measurements when available.'],
                'management_references' => [
                    ['summary' => 'Evaluate irrigation water quality and leaching fraction with local guidance.'],
                ],
                'version' => '1.0',
                'freshness_label' => 'curated-static',
            ],
            [
                'id' => 'kb_insect_chewing_damage',
                'common_name' => 'Insect chewing damage',
                'scientific_name' => null,
                'scientific_name_verified' => false,
                'category' => 'pest',
                'causal_class' => 'biotic',
                'pathogen_type' => 'insect',
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'crop_keys' => ['generic', 'tomato', 'wheat', 'citrus'],
                'common_names' => ['Chewing insect damage', 'ضرر الحشرات القارضة'],
                'aliases' => ['insect feeding', 'defoliation', 'caterpillar damage'],
                'symptoms' => ['holes', 'chewing', 'frass', 'defoliation'],
                'plant_parts' => ['leaf', 'stem'],
                'observation_patterns' => [
                    [
                        'id' => 'chewing_pattern',
                        'keywords' => ['chew', 'hole', 'insect', 'frass', 'bite', 'mines'],
                        'plant_parts' => ['leaf'],
                        'negative_keywords' => ['powdery', 'rust pustule'],
                        'observation_types' => ['insect_damage', 'holes', 'chewing'],
                    ],
                ],
                'differentials' => [
                    ['id' => 'slug', 'common_name' => 'Slug/snail damage', 'relation' => 'alternative'],
                    ['id' => 'hail', 'common_name' => 'Hail injury', 'relation' => 'alternative'],
                    ['id' => 'mechanical', 'common_name' => 'Mechanical damage', 'relation' => 'contradicting', 'notes' => 'Linear tears without frass may be mechanical.'],
                ],
                'sources' => [$curated('Curated insect damage diagnostic pattern')],
                'supporting_evidence_notes' => ['Irregular holes with frass support insect feeding damage.'],
                'contradicting_evidence_notes' => [],
                'recommended_additional_observations' => ['time-of-day feeding', 'pest life stage photos'],
                'safety_notices' => ['Species ID may require closer inspection of the insect.'],
                'management_references' => [
                    ['summary' => 'Identify the pest before selecting controls; avoid unverified pesticide recommendations.'],
                ],
                'version' => '1.0',
                'freshness_label' => 'curated-static',
            ],
            [
                'id' => 'kb_powdery_mildew_like',
                'common_name' => 'Powdery mildew-like symptoms',
                'scientific_name' => null,
                'scientific_name_verified' => false,
                'category' => 'disease',
                'causal_class' => 'biotic',
                'pathogen_type' => 'fungal',
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'crop_keys' => ['generic', 'tomato', 'wheat', 'citrus'],
                'common_names' => ['Powdery mildew', 'البياض الدقيقي'],
                'aliases' => ['white powder', 'mildew'],
                'symptoms' => ['powdery', 'white coating', 'dusty mycelium'],
                'plant_parts' => ['leaf'],
                'observation_patterns' => [
                    [
                        'id' => 'pm_pattern',
                        'keywords' => ['powder', 'white coating', 'mildew', 'dusty'],
                        'plant_parts' => ['leaf'],
                        'negative_keywords' => ['orange pustule'],
                        'observation_types' => ['powdery', 'fungal_growth'],
                    ],
                ],
                'differentials' => [
                    ['id' => 'downy', 'common_name' => 'Downy mildew-like', 'relation' => 'alternative'],
                    ['id' => 'dust', 'common_name' => 'Dust/residue', 'relation' => 'contradicting', 'notes' => 'Easily wiped inert residue may not be pathogen growth.'],
                ],
                'sources' => [$curated('Curated powdery mildew-like pattern')],
                'supporting_evidence_notes' => ['White powdery coatings support powdery mildew-like candidates.'],
                'contradicting_evidence_notes' => [],
                'recommended_additional_observations' => ['upper vs lower leaf surface', 'wipe test'],
                'safety_notices' => ['Confirm living fungal growth versus residue.'],
                'management_references' => [
                    ['summary' => 'Reduce humidity in dense canopies where practical; seek local confirmation before fungicide use.'],
                ],
                'version' => '1.0',
                'freshness_label' => 'curated-static',
            ],
            [
                'id' => 'kb_vascular_wilt_like',
                'common_name' => 'Vascular wilt-like syndrome',
                'scientific_name' => null,
                'scientific_name_verified' => false,
                'category' => 'disease',
                'causal_class' => 'biotic',
                'pathogen_type' => 'fungal',
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'crop_keys' => ['generic', 'tomato'],
                'common_names' => ['Wilt', 'ذبول'],
                'aliases' => ['vascular wilt', 'fusarium-like', 'verticillium-like'],
                'symptoms' => ['wilt', 'wilting', 'vascular discoloration', 'collapse'],
                'plant_parts' => ['stem', 'vascular', 'whole plant'],
                'observation_patterns' => [
                    [
                        'id' => 'wilt_pattern',
                        'keywords' => ['wilt', 'wilting', 'collapse', 'drooping', 'vascular'],
                        'plant_parts' => ['stem', 'whole plant'],
                        'negative_keywords' => ['powdery coating'],
                        'observation_types' => ['wilting', 'vascular_discoloration'],
                    ],
                ],
                'differentials' => [
                    ['id' => 'drought', 'common_name' => 'Drought stress', 'relation' => 'alternative'],
                    ['id' => 'root_rot', 'common_name' => 'Root rot-like', 'relation' => 'alternative'],
                    ['id' => 'bacterial_wilt', 'common_name' => 'Bacterial wilt-like', 'relation' => 'alternative'],
                ],
                'sources' => [$curated('Curated wilt differential pattern')],
                'supporting_evidence_notes' => ['Wilting with possible vascular discoloration supports wilt-like candidates.'],
                'contradicting_evidence_notes' => ['Rapid recovery after irrigation may favor water stress over vascular wilt.'],
                'recommended_additional_observations' => ['stem cross-section', 'root health'],
                'safety_notices' => ['Pathogen species ID typically needs lab methods.'],
                'management_references' => [
                    ['summary' => 'Assess soil moisture and root health; seek laboratory diagnosis for persistent wilt.'],
                ],
                'version' => '1.0',
                'freshness_label' => 'curated-static',
            ],
        ];
    }
}
