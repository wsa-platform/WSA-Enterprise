<?php

namespace App\Services\Agriculture\Research\Onboarding;

/**
 * Root cause, impact, corrective action, verification and limitation for every WARNING code
 * the reconciler can emit. A WARNING without a disposition is itself an export defect.
 */
final class OnboardingWarningDisposition
{
    private const DISPOSITIONS = [
        'stub_declares_unverified_query_capabilities' => [
            'root_cause' => 'ScientificSourceProfileCatalog ships inactive query-profile stubs whose identifier and constraint fields were written before any evidence review or endpoint test of the source.',
            'impact' => 'None at runtime: the profile is inactive and unregistered, so CSQ compiles no query for it and the selector cannot choose it. Activating it unchanged would let CSQ emit identifier and constraint parameters the source was never shown to accept.',
            'corrective_action' => 'Keep the stub inactive and unchanged, which preserves CSQ semantics. Before activation, an accepted onboarding decision and a Capability decision must back each declared field, or the field must be removed.',
            'verification' => 'ScientificSourceOnboardingReconciliationTest asserts that each such stub is inactive, has no registration, and that an active unregistered profile is an ERROR.',
            'limitation' => 'The declared fields are neither confirmed nor refuted: no live probe of the source is authorized.',
        ],
        'unsupported_verification_claim' => [
            'root_cause' => 'An evidence input claims identity, endpoint or integration verification that no owning authority recorded.',
            'impact' => 'The claim is ignored: the seat stays below VERIFIED and keeps its verification blockers.',
            'corrective_action' => 'Correct the evidence input, or record the verification through its owning authority.',
            'verification' => 'ScientificSourceOnboardingReconciliationTest injects such a claim and asserts the warning and a non-VERIFIED seat.',
            'limitation' => 'The reconciler cannot tell a mistaken claim from an unrecorded real verification.',
        ],
        'registration_without_query_profile' => [
            'root_cause' => 'A runtime registration exists without a query profile.',
            'impact' => 'CSQ cannot compile queries for that source.',
            'corrective_action' => 'Add the query profile under the change that registered the source, or remove the registration.',
            'verification' => 'ScientificSourceOnboardingReconciliationTest asserts no registration lacks a profile.',
            'limitation' => 'Says nothing about whether an existing profile is correct.',
        ],
        'registered_source_licence_unresolved' => [
            'root_cause' => 'A registered source family profile declares no resolved licensing.',
            'impact' => 'Licence enforcement refuses retrieval and storage for that source; unknown is not permission.',
            'corrective_action' => 'Record the owner-accepted licensing declaration for the source.',
            'verification' => 'Licensing enforcement tests of the source families.',
            'limitation' => 'Licence terms are not interpreted automatically.',
        ],
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::DISPOSITIONS);
    }

    /** @return array<string, string>|null */
    public static function for(OnboardingIntegrityFinding $finding): ?array
    {
        if ($finding->severity !== OnboardingIntegrityFinding::WARNING || ! isset(self::DISPOSITIONS[$finding->code])) {
            return null;
        }

        return [
            'code' => $finding->code,
            'subject' => $finding->subject,
            'evidence' => $finding->detail,
        ] + self::DISPOSITIONS[$finding->code];
    }
}
