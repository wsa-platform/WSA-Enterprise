<?php

declare(strict_types=1);

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;

$catalog = ScientificSourceCatalog::default();
$registry = app(ScientificSourceAdapterRegistry::class);
$selector = app(ScientificSourceSelector::class);
$plan = app(ResearchPlanner::class)->planKnowledgeQuery([
    'query' => 'What are the types of agricultural land in Egypt?',
]);

$nextGate = [];
foreach (NextGateEligibleSeatCatalog::all() as $adrId => $seat) {
    $nextGate[$adrId] = [
        'source_key' => $seat->sourceKey,
        'runtime_active' => $seat->isRuntimeActive(),
        'activation' => $seat->onboardingContract?->activationState?->value ?? null,
        'intended_activation' => $seat->intendedActivation->value,
        'seat_blockers' => $seat->blockers,
        'contract_blockers' => $seat->onboardingContract?->blockers ?? [],
    ];
}
$nearReady = [];
foreach (CurrentMembershipNearReadySeatCatalog::all() as $adrId => $seat) {
    $nearReady[$adrId] = [
        'source_key' => $seat->sourceKey,
        'runtime_active' => $seat->isRuntimeActive(),
        'activation' => $seat->onboardingContract?->activationState?->value ?? null,
        'intended_activation' => $seat->intendedActivation->value,
        'seat_blockers' => $seat->blockers,
        'contract_blockers' => $seat->onboardingContract?->blockers ?? [],
    ];
}

$regs = [];
foreach ($catalog->all() as $key => $reg) {
    $regs[$key] = [
        'enabled' => $reg->isEnabled(),
        'role' => $reg->selectionRole->value,
        'adapter' => $reg->adapterClass,
        'adr_id' => $reg->adrId?->value ?? null,
        'lifecycle' => $reg->lifecycle->value,
    ];
}

echo json_encode([
    'registered_keys' => $registry->registeredSourceKeys(),
    'selected_for_egypt_land' => $selector->selectSources($plan),
    'selection_trace' => $selector->selectionTrace($plan),
    'catalog' => $regs,
    'next_gate_seats' => $nextGate,
    'near_ready_seats' => $nearReady,
    'activated_next_gate_count' => count(NextGateEligibleSeatCatalog::activatedRegistrations()),
    'activated_near_ready_count' => count(CurrentMembershipNearReadySeatCatalog::activatedRegistrations()),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
