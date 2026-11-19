<?php

namespace App\Services\Agriculture\Research\Evaluation;

/**
 * Diversified scientific search matrix for general restoration (Rev 4.6).
 *
 * Held-out cases must not be used to invent question-specific hardcoding.
 *
 * @phpstan-type MatrixCase array{
 *   id: string,
 *   domain: string,
 *   language: string,
 *   question: string,
 *   held_out: bool,
 *   expected_intent: string,
 *   expected_eligible_any_of: list<string>,
 *   expected_not_eligible?: list<string>,
 *   expected_sufficiency: 'sufficient'|'insufficient'|'none'|'any',
 *   expected_behavior: string
 * }
 */
final class GeneralScientificSearchMatrix
{
    /**
     * @return list<MatrixCase>
     */
    public static function cases(): array
    {
        return [
            [
                'id' => 'GS-01',
                'domain' => 'crop_physiology',
                'language' => 'en',
                'question' => 'What is the effect of temperature on wheat growth?',
                'held_out' => false,
                'expected_intent' => 'plant_growth_temperature',
                'expected_eligible_any_of' => ['openalex', 'crossref', 'agris'],
                'expected_not_eligible' => [],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'retrieve_and_answer_or_honest_insufficient',
            ],
            [
                'id' => 'GS-02',
                'domain' => 'crop_physiology',
                'language' => 'ar',
                'question' => 'ما هو تأثير درجة الحرارة على نمو القمح؟',
                'held_out' => false,
                'expected_intent' => 'plant_growth_temperature',
                'expected_eligible_any_of' => ['openalex', 'crossref', 'agris'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'same_scientific_identity_as_english_peer',
            ],
            [
                'id' => 'GS-03',
                'domain' => 'irrigation',
                'language' => 'en',
                'question' => 'How does deficit irrigation affect tomato yield?',
                'held_out' => false,
                'expected_intent' => 'irrigation_yield',
                'expected_eligible_any_of' => ['openalex', 'crossref', 'agris'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'scholarly_sources_preferred',
            ],
            [
                'id' => 'GS-04',
                'domain' => 'soil_fertility',
                'language' => 'en',
                'question' => 'What is the effect of nitrogen fertilizer on maize grain yield?',
                'held_out' => false,
                'expected_intent' => 'fertilizer_yield',
                'expected_eligible_any_of' => ['openalex', 'crossref', 'agris'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'retrieve_and_answer_or_honest_insufficient',
            ],
            [
                'id' => 'GS-05',
                'domain' => 'plant_pathology',
                'language' => 'en',
                'question' => 'How does rust disease affect wheat yield?',
                'held_out' => false,
                'expected_intent' => 'disease_yield',
                'expected_eligible_any_of' => ['openalex', 'crossref', 'agris'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'retrieve_and_answer_or_honest_insufficient',
            ],
            [
                'id' => 'GS-06',
                'domain' => 'taxonomy',
                'language' => 'en',
                'question' => 'What is the taxonomic classification of Mentha piperita?',
                'held_out' => false,
                'expected_intent' => 'taxonomy',
                'expected_eligible_any_of' => ['gbif', 'bhl'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'taxonomy_sources_eligible',
            ],
            [
                'id' => 'GS-07',
                'domain' => 'phytochemistry',
                'language' => 'en',
                'question' => 'Which phytochemicals are reported in Mentha piperita?',
                'held_out' => false,
                'expected_intent' => 'phytochemistry',
                'expected_eligible_any_of' => ['dr_duke', 'openalex'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'no_clinical_efficacy_invention',
            ],
            [
                'id' => 'GS-08',
                'domain' => 'organic_agriculture',
                'language' => 'en',
                'question' => 'What are organic farming effects on wheat yield?',
                'held_out' => false,
                'expected_intent' => 'organic_yield',
                'expected_eligible_any_of' => ['organic_eprints', 'openalex', 'crossref', 'agris'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'organic_scope_preserved',
            ],
            [
                'id' => 'GS-09',
                'domain' => 'livestock',
                'language' => 'en',
                'question' => 'How does heat stress affect dairy cow milk yield?',
                'held_out' => false,
                'expected_intent' => 'livestock_heat_stress',
                'expected_eligible_any_of' => ['openalex', 'crossref'],
                'expected_not_eligible' => ['dr_duke'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'retrieve_and_answer_or_honest_insufficient',
            ],
            [
                'id' => 'GS-10',
                'domain' => 'negative_none',
                'language' => 'en',
                'question' => 'xyzzy quux agrarian phonon lattice 9f3k2',
                'held_out' => false,
                'expected_intent' => 'nonsense_or_unmatchable',
                'expected_eligible_any_of' => [],
                'expected_sufficiency' => 'none',
                'expected_behavior' => 'no_invented_answer',
            ],
            [
                'id' => 'GS-11',
                'domain' => 'heritage_literature',
                'language' => 'en',
                'question' => 'What historical botanical literature describes Triticum aestivum?',
                'held_out' => false,
                'expected_intent' => 'heritage_literature',
                'expected_eligible_any_of' => ['bhl', 'gbif'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'bhl_or_gbif_eligible',
            ],
            [
                'id' => 'GS-12',
                'domain' => 'crop_physiology',
                'language' => 'en',
                'question' => 'How does temperature influence wheat plant development?',
                'held_out' => false,
                'expected_intent' => 'plant_growth_temperature',
                'expected_eligible_any_of' => ['openalex', 'crossref', 'agris'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'paraphrase_of_gs01_same_identity',
            ],
            // Held-out (≥4) — evaluation only; do not hardcode mappings for these.
            [
                'id' => 'GS-H1',
                'domain' => 'irrigation',
                'language' => 'ar',
                'question' => 'كيف يؤثر الإجهاد المائي على إنتاجية الذرة؟',
                'held_out' => true,
                'expected_intent' => 'water_stress_yield',
                'expected_eligible_any_of' => ['openalex', 'crossref', 'agris'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'held_out_generalization',
            ],
            [
                'id' => 'GS-H2',
                'domain' => 'plant_pathology',
                'language' => 'en',
                'question' => 'What management practices reduce late blight severity in potato?',
                'held_out' => true,
                'expected_intent' => 'disease_management',
                'expected_eligible_any_of' => ['openalex', 'crossref', 'agris'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'held_out_generalization',
            ],
            [
                'id' => 'GS-H3',
                'domain' => 'poultry',
                'language' => 'en',
                'question' => 'How does stocking density affect broiler chicken growth performance?',
                'held_out' => true,
                'expected_intent' => 'poultry_performance',
                'expected_eligible_any_of' => ['openalex', 'crossref'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'held_out_generalization',
            ],
            [
                'id' => 'GS-H4',
                'domain' => 'taxonomy',
                'language' => 'en',
                'question' => 'What is the accepted scientific name and family of Olea europaea?',
                'held_out' => true,
                'expected_intent' => 'taxonomy',
                'expected_eligible_any_of' => ['gbif', 'bhl'],
                'expected_sufficiency' => 'any',
                'expected_behavior' => 'held_out_generalization',
            ],
        ];
    }

    /**
     * @return list<MatrixCase>
     */
    public static function developmentCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (array $c): bool => ($c['held_out'] ?? false) !== true,
        ));
    }

    /**
     * @return list<MatrixCase>
     */
    public static function heldOutCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (array $c): bool => ($c['held_out'] ?? false) === true,
        ));
    }
}
