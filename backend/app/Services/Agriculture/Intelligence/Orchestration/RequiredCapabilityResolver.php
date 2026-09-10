<?php

namespace App\Services\Agriculture\Intelligence\Orchestration;

use App\Services\Agriculture\Intelligence\Contracts\ProviderCapability;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Maps question meaning (intent / type / requested information) to capabilities.
 * No crop names and no question-specific branches.
 */
final class RequiredCapabilityResolver
{
    /**
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    public function resolve(KnowledgeQueryPlan $plan, array $input = []): array
    {
        $explicit = $input['required_capabilities'] ?? null;
        if (is_array($explicit) && $explicit !== []) {
            return $this->uniqueStrings($explicit);
        }

        $intent = strtolower(trim((string) $plan->researchIntent));
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];
        $questionType = strtolower(trim((string) ($constraints['question_type'] ?? '')));
        $sense = strtolower(trim((string) ($constraints['scientific_sense'] ?? '')));
        $blob = strtolower(trim(implode(' ', array_filter([
            $query->originalQuestion,
            $query->normalizedQuestion,
            $query->topic,
            (string) ($query->subtopic ?? ''),
            $intent,
            $questionType,
            $sense,
            implode(' ', $plan->topics),
            implode(' ', $plan->subtopics),
            implode(' ', $query->requestedInformation),
        ]))));

        $caps = [];

        if ($plan->isInternetFirst()) {
            $caps[] = ProviderCapability::WEB_SEARCH;
        }

        $wantsStats = $this->wantsOfficialStatistics($questionType, $intent, $blob);
        $wantsWeather = $this->wantsWeather($intent, $blob);
        $wantsDisease = $this->wantsDisease($intent, $blob);
        $wantsScientific = $this->wantsScientificLiterature($plan, $intent, $blob, $wantsWeather, $wantsStats);

        if ($wantsScientific) {
            $caps[] = ProviderCapability::SCIENTIFIC_SEARCH;
            $caps[] = ProviderCapability::SCHOLARLY_EVIDENCE;
            $caps[] = ProviderCapability::CITATION_METADATA;
        }
        if ($wantsStats) {
            $caps[] = ProviderCapability::OFFICIAL_AGRICULTURAL_DATA;
            $caps[] = ProviderCapability::AGRICULTURAL_STATISTICS;
        }
        if ($wantsWeather) {
            $caps[] = ProviderCapability::WEATHER;
        }
        if ($wantsDisease) {
            $caps[] = ProviderCapability::PLANT_DISEASE_ANALYSIS;
        }
        if ($this->hasCapabilityToken($blob, ['field sensor', 'soil moisture sensor', 'in-field observation'])) {
            $caps[] = ProviderCapability::FIELD_SENSORS;
        }
        if ($this->hasCapabilityToken($blob, ['market signal', 'supply chain signal', 'price signal'])) {
            $caps[] = ProviderCapability::MARKET_SIGNALS;
        }

        if ($caps === [] && $plan->isInternetFirst()) {
            $caps = [ProviderCapability::WEB_SEARCH, ProviderCapability::SCIENTIFIC_SEARCH];
        }

        return $this->uniqueStrings($caps);
    }

    private function wantsOfficialStatistics(string $questionType, string $intent, string $blob): bool
    {
        if ($questionType === 'statistical' || $intent === 'agricultural_economics') {
            return true;
        }

        return $this->hasCapabilityToken($blob, [
            'faostat',
            'fao stat',
            'agricultural statistics',
            'crop statistics',
            'livestock statistics',
            'trade statistics',
            'import export',
            'exports',
            'imports',
            'harvested area',
            'production statistics',
            'national production',
            'إحصاء',
            'احصاء',
            'إحصاءات',
            'احصاءات',
            'صادرات',
            'واردات',
            'إنتاجية',
            'انتاجية',
        ]);
    }

    private function wantsWeather(string $intent, string $blob): bool
    {
        if (str_contains($intent, 'weather') || str_contains($intent, 'climate')) {
            return true;
        }

        return $this->hasCapabilityToken($blob, [
            'weather',
            'climate',
            'forecast',
            'open-meteo',
            'open meteo',
            'طقس',
            'مناخ',
            'تنبؤ',
            'توقعات الطقس',
        ]);
    }

    private function wantsDisease(string $intent, string $blob): bool
    {
        if (str_contains($intent, 'disease') || str_contains($intent, 'pest')) {
            return true;
        }

        return $this->hasCapabilityToken($blob, [
            'plant disease',
            'pathogen',
            ' pests',
            'pest ',
            'infestation',
            'مرض نبات',
            'أمراض النبات',
            'امراض النبات',
            'آفة',
            'افات',
            'آفات',
        ]);
    }

    private function wantsScientificLiterature(
        KnowledgeQueryPlan $plan,
        string $intent,
        string $blob,
        bool $wantsWeather,
        bool $wantsStats,
    ): bool {
        if (! $plan->isInternetFirst() && ! $plan->readyForStage3) {
            return false;
        }

        if ($this->isPureLiveWeather($blob, $wantsWeather, $wantsStats, $intent)) {
            return false;
        }

        return $plan->normalizedQuery->researchRequired
            || $plan->readyForStage3
            || str_contains($intent, 'scientific')
            || $this->hasCapabilityToken($blob, ['peer review', 'literature', 'doi', 'بحث علمي']);
    }

    private function isPureLiveWeather(string $blob, bool $wantsWeather, bool $wantsStats, string $intent): bool
    {
        if (! $wantsWeather || $wantsStats) {
            return false;
        }
        if (str_contains($intent, 'scientific')) {
            return false;
        }

        return $this->hasCapabilityToken($blob, [
            'forecast',
            'tomorrow',
            'current weather',
            'today weather',
            'غدا',
            'غداً',
            'اليوم',
            'توقعات الطقس',
        ]);
    }

    /**
     * @param  list<string>  $tokens
     */
    private function hasCapabilityToken(string $blob, array $tokens): bool
    {
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token !== '' && str_contains($blob, strtolower($token))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function uniqueStrings(array $values): array
    {
        $out = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }
            $value = trim($value);
            if ($value !== '') {
                $out[] = $value;
            }
        }

        return array_values(array_unique($out));
    }
}
