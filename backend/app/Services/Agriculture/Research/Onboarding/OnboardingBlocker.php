<?php

namespace App\Services\Agriculture\Research\Onboarding;

/**
 * Why a seat cannot be onboarded yet. Each code names the authority or gate that must act.
 */
enum OnboardingBlocker: string
{
    /** Identity authority: no owner-verified identity binding (D-10.8.2). */
    case IDENTITY_NOT_VERIFIED = 'identity_not_verified';
    /** Identity authority: competing candidates were observed; the owner must decide. */
    case IDENTITY_AMBIGUOUS = 'identity_ambiguous';
    /** Identity authority: no candidate resource was established. */
    case IDENTITY_NOT_ESTABLISHED = 'identity_not_established';
    /** Integration classification is UNCLASSIFIED (GB-IC-01). */
    case INTEGRATION_UNCLASSIFIED = 'integration_unclassified';
    /** No machine-readable access method is documented. */
    case NO_MACHINE_ACCESS_DOCUMENTED = 'no_machine_access_documented';
    /** The documented access family has no family implementation in this repository. */
    case NO_FAMILY_IMPLEMENTATION = 'no_family_implementation';
    /** No endpoint test was run; live probes are not authorized. */
    case ENDPOINT_NOT_TESTED = 'endpoint_not_tested';
    /** Usage rights are not resolved; unknown is not permission. */
    case LICENCE_UNRESOLVED = 'licence_unresolved';
    /** The source requires credentials and no credential setting is defined. */
    case CREDENTIALS_REQUIRED = 'credentials_required';
    /**
     * Searching needs the local index, whose durable record store (§8.41) has no deployed
     * schema: its migration has run only against disposable test databases.
     */
    case LOCAL_INDEX_STORE_NOT_DEPLOYED = 'local_index_store_not_deployed';
    /** Documentary review pages failed to load; evidence is incomplete. */
    case EVIDENCE_FETCH_FAILURES = 'evidence_fetch_failures';
    /** No Capability decision exists (missing Cap = UNVERIFIED). */
    case CAPABILITY_UNVERIFIED = 'capability_unverified';
    /** No owner-accepted onboarding decision exists (D-10.11). */
    case ONBOARDING_DECISION_NOT_ACCEPTED = 'onboarding_decision_not_accepted';
}
