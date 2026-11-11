<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\OnboardingBlocker;
use App\Services\Agriculture\Research\Onboarding\OnboardingEvidenceStatus;
use App\Services\Agriculture\Research\Onboarding\OnboardingIntegrityFinding;
use App\Services\Agriculture\Research\Onboarding\OnboardingWarningDisposition;
use App\Services\Agriculture\Research\Onboarding\ScientificSourceOnboardingReconciler;
use App\Services\Agriculture\Research\Onboarding\ScientificSourceOnboardingReconciliation;
use App\Services\Agriculture\Research\Onboarding\SeatActivationState;
use App\Services\Agriculture\Research\Onboarding\SeatFamilyState;
use App\Services\Agriculture\Research\Onboarding\SeatReconciliationEntry;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\ScientificSourceProfileCatalog;
use DateTimeImmutable;
use Tests\TestCase;

/**
 * ADR-023 §8.40 (R6): every one of the 109 seats is reconciled against its documentary evidence
 * and the repository. Expectations are recomputed here from the evidence file, not taken from
 * the reconciler. Nothing is bound, registered or activated.
 */
class ScientificSourceOnboardingReconciliationTest extends TestCase
{
    private const DIRECTORY = 'docs/architecture/science-source-expansion/';

    private const REGISTER = 'ADR-023-109-MEMBERSHIP-REGISTER.v1.json';

    private const EVIDENCE = '2026-10-13-PHASE-1-IC-EVIDENCE-REVIEW.v1.json';

    private const REGISTER_SHA256 = '783035186579aa558d637f581c11c6701ae7bb39951cdc085fdb83cad59a6897';

    private const AT = '2026-10-15T00:00:00+00:00';

    public function test_the_inputs_are_the_pinned_register_and_its_evidence_review(): void
    {
        $this->assertSame(self::REGISTER_SHA256, hash('sha256', $this->raw(self::REGISTER)));
        $this->assertSame(self::REGISTER_SHA256, $this->decoded(self::EVIDENCE)['membership_register']['sha256']);
    }

    public function test_all_109_seats_reconcile_without_an_integrity_error(): void
    {
        $reconciliation = $this->reconcile();

        $this->assertSame([], array_map(static fn (OnboardingIntegrityFinding $f): string => $f->code.': '.$f->subject, $reconciliation->errors()));
        $this->assertCount(109, $reconciliation->entries);
        $this->assertSame(array_column($this->decoded(self::REGISTER)['seats'], 'adr_id'), array_keys($reconciliation->entries));
        $this->assertSame(109, $reconciliation->summary()['seats']);
        $this->assertSame(109, array_sum($reconciliation->summary()['documentary_evidence_status']));
    }

    public function test_no_seat_is_verified_bound_selectable_or_eligible(): void
    {
        foreach ($this->reconcile()->entries as $adrId => $entry) {
            $decision = $entry->contract;
            $this->assertNotSame(OnboardingEvidenceStatus::VERIFIED, $entry->status, $adrId);
            $this->assertContains($decision->activationState, [SeatActivationState::NOT_READY, SeatActivationState::DEFERRED], $adrId);
            $this->assertFalse($decision->claimsEligibility(), $adrId);
            $this->assertNull($decision->canonicalIdentityId, $adrId);
            $this->assertNull($decision->stage3Relationship, $adrId);
            $this->assertNull($decision->capabilityDecisionIdentity, $adrId);
            $this->assertSame('NOT_SELECTABLE', $decision->selectorEligibility, $adrId);
            $this->assertTrue($decision->licensing->isFullyUnresolved(), $adrId);
            $this->assertSame(['UNVERIFIED'], array_values(array_unique(array_map(static fn ($s): string => $s->value, $decision->capabilityDimensionStates))), $adrId);
            $this->assertSame("onb-r7-20261015-{$adrId}", $decision->onboardingDecisionId);
            foreach ([OnboardingBlocker::IDENTITY_NOT_VERIFIED, OnboardingBlocker::ENDPOINT_NOT_TESTED, OnboardingBlocker::LICENCE_UNRESOLVED, OnboardingBlocker::CAPABILITY_UNVERIFIED, OnboardingBlocker::ONBOARDING_DECISION_NOT_ACCEPTED] as $blocker) {
                $this->assertTrue($entry->hasBlocker($blocker), "{$adrId} lacks {$blocker->value}");
            }
            $this->assertSame('VERIFIED', $entry->dimension('membership')->status->value, $adrId);
            $this->assertSame('UNAVAILABLE', $entry->dimension('implementation')->status->value, $adrId);
            foreach (['retention', 'indexing', 'excerpt_display', 'user_facing_use', 'pagination', 'filtering', 'query_mapping', 'test_coverage'] as $dimension) {
                $this->assertSame('UNVERIFIED', $entry->dimension($dimension)->status->value, "{$adrId} {$dimension}");
            }
        }
    }

    public function test_seat_status_follows_the_documentary_evidence(): void
    {
        $reconciliation = $this->reconcile();
        $expected = array_fill_keys(array_map(static fn (OnboardingEvidenceStatus $s): string => $s->value, OnboardingEvidenceStatus::cases()), 0);
        foreach ($this->records() as $adrId => $record) {
            $identity = $record['identity']['evidence_state'];
            $candidate = $record['identity']['candidate_url'];
            $unreachable = $candidate !== null && array_filter($record['fetch_failures'], static fn (string $f): bool => str_starts_with($f, $candidate.':')) !== [];
            $status = match (true) {
                $identity === 'AMBIGUOUS' => 'BLOCKED',
                $unreachable => 'UNAVAILABLE',
                $identity !== 'CANDIDATE_EVIDENCED' || $record['access']['evidence_state'] === 'NOT_DOCUMENTED' => 'UNVERIFIED',
                default => 'PARTIALLY_EVIDENCED',
            };
            $expected[$status]++;
            $entry = $reconciliation->entry($adrId);
            $this->assertSame($status, $entry?->status->value, $adrId);
            $this->assertSame($identity === 'AMBIGUOUS' ? 'DEFERRED' : 'NOT_READY', $entry->contract->activationState->value, $adrId);
            $this->assertSame($identity === 'AMBIGUOUS', $entry->hasBlocker(OnboardingBlocker::IDENTITY_AMBIGUOUS), $adrId);
            $this->assertSame($record['fetch_failures'] !== [], $entry->hasBlocker(OnboardingBlocker::EVIDENCE_FETCH_FAILURES), $adrId);
            $this->assertSame(($record['access']['auth_and_rate_limits']['auth_required'] ?? null) === 'YES', $entry->hasBlocker(OnboardingBlocker::CREDENTIALS_REQUIRED), $adrId);
        }
        $this->assertSame($expected, $reconciliation->summary()['documentary_evidence_status']);
        $this->assertSame(16, $expected['BLOCKED']);
    }

    public function test_candidate_families_come_only_from_documented_modalities(): void
    {
        $map = ['api' => 'rest_api', 'oai_pmh' => 'oai_pmh', 'rss' => 'rss_atom', 'atom' => 'rss_atom', 'bulk_download' => 'bulk_dataset', 'static_download' => 'static_dataset'];
        $reconciliation = $this->reconcile();
        foreach ($this->records() as $adrId => $record) {
            $families = [];
            foreach ($record['access']['documented_modalities'] as $modality) {
                if (isset($map[$modality['modality']])) {
                    $families[$map[$modality['modality']]] = true;
                }
            }
            $entry = $reconciliation->entry($adrId);
            $actual = array_map(static fn (ScientificSourceAccessFamily $f): string => $f->value, $entry->candidateFamilies);
            $this->assertEqualsCanonicalizing(array_keys($families), $actual, $adrId);
            $this->assertSame($families === [], $entry->hasBlocker(OnboardingBlocker::NO_MACHINE_ACCESS_DOCUMENTED), $adrId);
            $rssOnly = $families !== [] && array_keys($families) === ['rss_atom'];
            $this->assertSame($rssOnly, $entry->hasBlocker(OnboardingBlocker::NO_FAMILY_IMPLEMENTATION), $adrId);
            if ($rssOnly) {
                $this->assertSame('UNAVAILABLE', $entry->dimension('adapter_compatibility')->status->value, $adrId);
            }
            $localOnly = $families !== [] && ! isset($families['rest_api']) && array_intersect(array_keys($families), ['oai_pmh', 'bulk_dataset', 'static_dataset']) !== [];
            $this->assertSame($localOnly, $entry->hasBlocker(OnboardingBlocker::LOCAL_INDEX_STORE_NOT_DEPLOYED), $adrId);
            $this->assertSame($localOnly, $entry->dimension('ingestion')->status === OnboardingEvidenceStatus::BLOCKED, $adrId);
        }
    }

    public function test_every_seat_has_exactly_one_family_state(): void
    {
        $map = ['api' => 'rest_api', 'oai_pmh' => 'oai_pmh', 'rss' => 'rss_atom', 'atom' => 'rss_atom', 'bulk_download' => 'bulk_dataset', 'static_download' => 'static_dataset'];
        $reconciliation = $this->reconcile();
        $expected = array_fill_keys(array_map(static fn (SeatFamilyState $s): string => $s->value, SeatFamilyState::cases()), 0);
        $assignments = 0;
        foreach ($this->records() as $adrId => $record) {
            $families = [];
            foreach ($record['access']['documented_modalities'] as $modality) {
                if (isset($map[$modality['modality']])) {
                    $families[$map[$modality['modality']]] = true;
                }
            }
            $assignments += count($families);
            $remote = isset($families['rest_api']);
            $local = array_intersect(array_keys($families), ['oai_pmh', 'bulk_dataset', 'static_dataset']) !== [];
            $state = match (true) {
                $remote && $local => 'remote_api_and_local_index',
                $remote => 'remote_api',
                $local => 'local_index',
                $families !== [] => 'unimplemented_family',
                default => 'no_machine_access',
            };
            $expected[$state]++;
            $this->assertSame($state, $reconciliation->entry($adrId)?->familyState()->value, $adrId);
        }
        $summary = $reconciliation->summary();

        $this->assertSame($expected, $summary['family_state']);
        $this->assertSame(109, array_sum($summary['family_state']));
        $this->assertSame($assignments, $summary['candidate_family_assignments']['total']);
        $this->assertSame($assignments, array_sum($summary['candidate_family_assignments']['by_family']));
        $this->assertSame(109 - $expected['no_machine_access'], $summary['candidate_family_assignments']['seats_with_any_family']);
        $this->assertSame($expected['no_machine_access'], $summary['blockers']['no_machine_access_documented']);
        $this->assertSame($expected['unimplemented_family'], $summary['blockers']['no_family_implementation']);
    }

    public function test_evidence_axes_keep_evidence_apart_from_readiness_and_activation(): void
    {
        $reconciliation = $this->reconcile();
        $documentedNotAccepted = 0;
        foreach ($reconciliation->entries as $adrId => $entry) {
            $axes = $entry->evidenceAxes();
            $this->assertSame(['identity_evidence', 'access_evidence', 'licensing_evidence', 'licensing_declaration', 'capability_evidence', 'capability_decision', 'documentary_evidence_status', 'onboarding_readiness', 'operational_activation'], array_keys($axes), $adrId);
            $this->assertSame($entry->dimension('identity')->status->value, $axes['identity_evidence'], $adrId);
            $this->assertSame($entry->dimension('access_method')->status->value, $axes['access_evidence'], $adrId);
            $this->assertSame($entry->dimension('licence')->status->value, $axes['licensing_evidence'], $adrId);
            $this->assertSame('UNRESOLVED', $axes['licensing_declaration'], $adrId);
            $this->assertSame('NONE', $axes['capability_decision'], $adrId);
            $this->assertContains($axes['onboarding_readiness'], ['NOT_READY', 'DEFERRED'], $adrId);
            $this->assertSame('INACTIVE', $axes['operational_activation'], $adrId);
            $documentedNotAccepted += $axes['licensing_evidence'] === 'PARTIALLY_EVIDENCED' ? 1 : 0;
        }
        $this->assertGreaterThan(0, $documentedNotAccepted);
        $this->assertSame(['INACTIVE' => 109], $reconciliation->summary()['operational_activation']);
    }

    public function test_local_index_blockers_reconcile_with_harvest_and_bulk_family_assignments(): void
    {
        $reconciliation = $this->reconcile();
        $local = $reconciliation->localIndexReconciliation();
        $byFamily = $reconciliation->summary()['candidate_family_assignments']['by_family'];

        $this->assertSame(($byFamily['oai_pmh'] ?? 0) + ($byFamily['bulk_dataset'] ?? 0) + ($byFamily['static_dataset'] ?? 0), $local['family_assignment_total']);
        $this->assertSame(
            $local['family_assignment_total'],
            count($local['seats_with_local_index_family']) + array_sum(array_map(static fn (array $f): int => count($f) - 1, $local['seats_with_several_local_index_families'])),
        );
        $this->assertSame(array_values(array_diff($local['seats_with_local_index_family'], $local['seats_with_remote_api_alternative'])), $local['store_not_deployed_blocked_seats']);
        $this->assertSame(count($local['store_not_deployed_blocked_seats']), $reconciliation->summary()['blockers']['local_index_store_not_deployed']);
        $this->assertSame(count($local['store_not_deployed_blocked_seats']), $reconciliation->summary()['dimensions']['ingestion']['BLOCKED']);
        $this->assertEqualsCanonicalizing($local['seats_with_local_index_family'], array_merge($local['next_gate_candidates'], array_keys($local['held_by_seat_specific_blockers'])));
        foreach ($local['store_not_deployed_blocked_seats'] as $adrId) {
            $this->assertSame(SeatFamilyState::LOCAL_INDEX, $reconciliation->entry($adrId)?->familyState(), $adrId);
        }
        foreach ($local['next_gate_candidates'] as $adrId) {
            $this->assertSame('NOT_READY', $reconciliation->entry($adrId)?->contract->activationState->value, $adrId);
        }
    }

    public function test_every_warning_has_a_disposition(): void
    {
        $reconciliation = $this->reconcile();
        $dispositions = $reconciliation->warningDispositions();

        $this->assertCount(count(array_filter($reconciliation->findings, static fn (OnboardingIntegrityFinding $f): bool => $f->severity === OnboardingIntegrityFinding::WARNING)), $dispositions);
        // AGRIS is governed/registered under NextGate; warning dispositions cover unbound stubs only.
        $this->assertEqualsCanonicalizing(['pubmed', 'europe_pmc', 'agricola'], array_column($dispositions, 'subject'));
        foreach ($dispositions as $disposition) {
            $this->assertSame(['code', 'subject', 'evidence', 'root_cause', 'impact', 'corrective_action', 'verification', 'limitation'], array_keys($disposition), $disposition['subject']);
            $this->assertContains($disposition['code'], OnboardingWarningDisposition::codes());
            $this->assertFalse(ScientificSourceProfileCatalog::get($disposition['subject'])?->active, $disposition['subject']);
            $this->assertFalse(ScientificSourceCatalog::default()->has($disposition['subject']), $disposition['subject']);
        }
    }

    public function test_documented_licence_text_is_evidence_but_never_permission(): void
    {
        $reconciliation = $this->reconcile();
        $documented = 0;
        foreach ($this->records() as $adrId => $record) {
            $licence = $record['access']['license_and_reuse'];
            $isDocumented = is_array($licence) && ($licence['text'] ?? null) !== null && ($licence['evidence_url'] ?? null) !== null;
            $documented += $isDocumented ? 1 : 0;
            $entry = $reconciliation->entry($adrId);
            $this->assertSame($isDocumented ? 'PARTIALLY_EVIDENCED' : 'UNVERIFIED', $entry->dimension('licence')->status->value, $adrId);
            $this->assertSame('UNRESOLVED', $entry->contract->toArray()['license_state'], $adrId);
            $this->assertStringContainsString('unknown is not permission', (string) $entry->dimension('excerpt_display')->note, $adrId);
        }
        $this->assertGreaterThan(0, $documented);
        $this->assertSame($documented, $reconciliation->summary()['dimensions']['licence']['PARTIALLY_EVIDENCED']);
    }

    public function test_dual_peers_stay_two_independent_seats(): void
    {
        $reconciliation = $this->reconcile();
        $pairs = $this->decoded(self::REGISTER)['dual_pairs'];
        $this->assertCount(10, $pairs);
        foreach ($pairs as $pair) {
            $a = $reconciliation->entry($pair['seat_a']);
            $b = $reconciliation->entry($pair['seat_b']);
            $this->assertSame($pair['seat_b'], $a?->dualPeerAdrId);
            $this->assertSame($pair['seat_a'], $b?->dualPeerAdrId);
            $this->assertNotSame($a->contract->onboardingDecisionId, $b->contract->onboardingDecisionId);
            $this->assertNull($a->contract->canonicalIdentityId);
            $this->assertNull($b->contract->canonicalIdentityId);
        }
        foreach ($reconciliation->entries as $adrId => $entry) {
            if ($entry->evidenceBasis === 'DUAL_PEER_EVIDENCE_REFERENCE') {
                $this->assertSame($entry->dualPeerAdrId, $entry->evidenceAdrId, $adrId);
            }
        }
    }

    public function test_registry_and_profile_integrity_findings(): void
    {
        $reconciliation = $this->reconcile();
        $subjects = static fn (string $code): array => array_map(static fn (OnboardingIntegrityFinding $f): string => $f->subject, $reconciliation->findingsWithCode($code));

        $this->assertEqualsCanonicalizing(['openalex', 'crossref', 'semantic_scholar', 'consensus', 'fao_stat'], $subjects('registration_external_only'));
        // Governed bindings expanded under NextGate/NearReady catalogs (AGRIS, Duke, GBIF, …).
        $this->assertEqualsCanonicalizing(
            ['agris', 'dr_duke', 'gbif', 'organic_eprints', 'bhl'],
            $subjects('registration_governed_bound'),
        );
        $this->assertSame([], $subjects('registration_bound_without_onboarding'));
        $this->assertSame([], $subjects('active_profile_unregistered'));
        $this->assertSame([], $subjects('registration_without_query_profile'));
        $this->assertSame(['consensus'], $subjects('registered_profile_inactive'));
        $this->assertEqualsCanonicalizing(['pubmed', 'europe_pmc', 'agricola'], $subjects('unbound_query_profile_stub'));
        $this->assertEqualsCanonicalizing(
            ['agris/G1-01', 'agricola/G5-02', 'gbif/G5-04', 'bhl/G6-20', 'dr_duke/G1-17', 'dr_duke/G6-06', 'organic_eprints/G1-02'],
            $subjects('profile_seat_name_correspondence'),
        );
        $this->assertSame(['agris'], $reconciliation->entry('G1-01')?->profileNameCorrespondences);
        $this->assertSame(['rss_atom'], $subjects('family_without_implementation'));
        $this->assertSame([], $subjects('dead_family_implementation'));
        foreach ($subjects('stub_declares_unverified_query_capabilities') as $stub) {
            $this->assertFalse(ScientificSourceProfileCatalog::get($stub)?->active);
        }
    }

    public function test_inconsistent_inputs_are_reported_not_reconciled(): void
    {
        $register = $this->decoded(self::REGISTER);
        $evidence = $this->decoded(self::EVIDENCE);
        $evidence['records'][0]['display_name'] = 'Renamed seat';
        $evidence['records'][1]['same_as'] = 'G1-01';
        $evidence['records'][2]['identity']['identity_verified'] = true;
        $evidence['records'][3]['runtime']['binding'] = 'BOUND';
        $orphan = $evidence['records'][4];
        $orphan['adr_id'] = 'G9-01';
        $evidence['records'][] = $orphan;
        array_splice($evidence['records'], 5, 1);

        $reconciliation = (new ScientificSourceOnboardingReconciler(ScientificSourceOnboardingReconciler::repositoryFamilyImplementations()))
            ->reconcile($register, str_repeat('0', 64), $evidence, ScientificSourceCatalog::default(), ScientificSourceProfileCatalog::all(), new DateTimeImmutable(self::AT));
        $codes = array_map(static fn (OnboardingIntegrityFinding $f): string => $f->code.'@'.$f->subject, $reconciliation->findings);

        foreach (['register_hash_mismatch@evidence', 'evidence_seat_mismatch@G1-01', 'same_as_asserted@G1-02', 'unsupported_verification_claim@G1-03', 'evidence_runtime_claim@G1-04', 'orphan_evidence_record@G9-01', 'missing_evidence_record@G1-06'] as $expected) {
            $this->assertContains($expected, $codes);
        }
        $this->assertNull($reconciliation->entry('G1-06'));
        $this->assertNotSame(OnboardingEvidenceStatus::VERIFIED, $reconciliation->entry('G1-03')?->status);
    }

    public function test_the_reconciliation_exports_without_secrets_and_round_trips(): void
    {
        $reconciliation = $this->reconcile();
        $json = json_encode($reconciliation->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(ScientificSourceOnboardingReconciliation::SCHEMA_ID, $decoded['schema_id']);
        $this->assertCount(109, $decoded['seats']);
        $this->assertSame(array_column($this->decoded(self::REGISTER)['seats'], 'adr_id'), array_column($decoded['seats'], 'adr_id'));
        $this->assertSame(self::REGISTER_SHA256, $decoded['inputs']['membership_register_sha256']);
        foreach ($decoded['seats'] as $seat) {
            $this->assertSame(SeatReconciliationEntry::DIMENSIONS, array_keys($seat['dimensions']));
            $this->assertArrayNotHasKey('status', $seat);
            $this->assertNotNull(SeatFamilyState::tryFrom($seat['family_state']));
        }
        $this->assertSame($reconciliation->localIndexReconciliation(), $decoded['local_index_reconciliation']);
        $this->assertSame($reconciliation->warningDispositions(), $decoded['warning_dispositions']);
        $this->assertSame($json, json_encode($this->reconcile()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $this->assertDoesNotMatchRegularExpression('/(api[_-]?key|secret|password|bearer)\s*[:=]/i', $json);

        $target = getenv('WSA_RECONCILIATION_EXPORT');
        if (is_string($target) && $target !== '') {
            file_put_contents($target, $json."\n");
        }
    }

    private function reconcile(): ScientificSourceOnboardingReconciliation
    {
        return (new ScientificSourceOnboardingReconciler(ScientificSourceOnboardingReconciler::repositoryFamilyImplementations()))->reconcile(
            $this->decoded(self::REGISTER),
            hash('sha256', $this->raw(self::REGISTER)),
            $this->decoded(self::EVIDENCE),
            ScientificSourceCatalog::default(),
            ScientificSourceProfileCatalog::all(),
            new DateTimeImmutable(self::AT),
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function records(): array
    {
        return array_column($this->decoded(self::EVIDENCE)['records'], null, 'adr_id');
    }

    /** @return array<string, mixed> */
    private function decoded(string $file): array
    {
        return json_decode($this->raw($file), true, flags: JSON_THROW_ON_ERROR);
    }

    private function raw(string $file): string
    {
        foreach ([dirname(__DIR__, 6), dirname(__DIR__, 5)] as $root) {
            $path = $root.DIRECTORY_SEPARATOR.self::DIRECTORY.$file;
            if (is_file($path)) {
                return (string) file_get_contents($path);
            }
        }
        $this->fail('Not found: '.self::DIRECTORY.$file);
    }
}
