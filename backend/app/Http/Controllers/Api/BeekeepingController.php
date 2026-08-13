<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ScopesOwnedServices;
use App\Http\Controllers\Controller;
use App\Models\Apiary;
use App\Models\BeeCalendarTask;
use App\Models\BeekeeperProfile;
use App\Models\BeeKnowledgeTopic;
use App\Models\Hive;
use App\Models\HiveFeeding;
use App\Models\HiveInspection;
use App\Models\HiveProductionRecord;
use App\Models\HiveTreatment;
use App\Models\PollinationPlant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeekeepingController extends Controller
{
    use AuthorizesOrganizationAccess;
    use ScopesOwnedServices;

    public function profile(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');
        $profile = BeekeeperProfile::query()
            ->where('organization_id', $this->organization($request))
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json($profile);
    }

    public function upsertProfile(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string'],
            'region' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'hive_count' => ['nullable', 'integer', 'min:0'],
            'colony_count' => ['nullable', 'integer', 'min:0'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'production_types' => ['nullable', 'array'],
            'goals' => ['nullable', 'array'],
            'seasonal_activity' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $profile = BeekeeperProfile::unguarded(fn () => BeekeeperProfile::updateOrCreate(
            [
                'organization_id' => $this->organization($request),
                'user_id' => $request->user()->id,
            ],
            [
                ...$this->ownership()->stripOwnerKeys($data),
                'owner_user_id' => $request->user()->id,
            ],
        ));

        return response()->json($profile);
    }

    public function apiaries(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');

        return response()->json(
            $this->scopedOwnedQuery($request, Apiary::query())->withCount('hives')->paginate(20)
        );
    }

    public function storeApiary(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        $data = $request->validate([
            'beekeeper_profile_id' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'country' => ['nullable', 'string'],
            'region' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $profile = BeekeeperProfile::query()
            ->where('organization_id', $this->organization($request))
            ->whereKey($data['beekeeper_profile_id'])
            ->firstOrFail();
        $this->assertOwnedRecord($request, $profile, 'beekeeping.manage');

        $apiary = Apiary::unguarded(fn () => Apiary::create([
            ...$this->assignOwnedPayload($request, $data),
            'organization_id' => $this->organization($request),
        ]));

        return response()->json($apiary, 201);
    }

    public function hives(Request $request, Apiary $apiary): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');
        abort_unless($apiary->organization_id === $this->organization($request), 404);
        $this->assertOwnedRecord($request, $apiary, 'beekeeping.view');

        return response()->json(
            $this->scopedOwnedQuery($request, $apiary->hives()->getQuery())->paginate(50)
        );
    }

    public function storeHive(Request $request, Apiary $apiary): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        abort_unless($apiary->organization_id === $this->organization($request), 404);
        $this->assertOwnedRecord($request, $apiary, 'beekeeping.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'colony_status' => ['nullable', 'string'],
            'queen_info' => ['nullable', 'array'],
            'frame_count' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $hive = Hive::unguarded(fn () => Hive::create([
            ...$this->assignOwnedPayload($request, $data),
            'organization_id' => $this->organization($request),
            'apiary_id' => $apiary->id,
        ]));

        return response()->json($hive, 201);
    }

    public function storeInspection(Request $request, Hive $hive): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        abort_unless($hive->organization_id === $this->organization($request), 404);
        $this->assertOwnedRecord($request, $hive, 'beekeeping.manage');

        $data = $request->validate([
            'inspected_at' => ['required', 'date'],
            'overall_status' => ['nullable', 'string'],
            'findings' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $inspection = HiveInspection::unguarded(fn () => HiveInspection::create([
            ...$this->assignOwnedPayload($request, $data),
            'organization_id' => $this->organization($request),
            'hive_id' => $hive->id,
            'inspector_user_id' => $request->user()->id,
        ]));

        return response()->json($inspection, 201);
    }

    public function treatments(Request $request, Hive $hive): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');
        abort_unless($hive->organization_id === $this->organization($request), 404);
        $this->assertOwnedRecord($request, $hive, 'beekeeping.view');

        return response()->json(
            $this->scopedOwnedQuery($request, $hive->treatments()->getQuery())->latest('applied_at')->paginate(30)
        );
    }

    public function storeTreatment(Request $request, Hive $hive): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        abort_unless($hive->organization_id === $this->organization($request), 404);
        $this->assertOwnedRecord($request, $hive, 'beekeeping.manage');

        $data = $request->validate([
            'treatment_type' => ['required', 'string', 'max:255'],
            'applied_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $treatment = HiveTreatment::unguarded(fn () => HiveTreatment::create([
            ...$this->assignOwnedPayload($request, $data),
            'organization_id' => $this->organization($request),
            'hive_id' => $hive->id,
        ]));

        return response()->json($treatment, 201);
    }

    public function feedings(Request $request, Hive $hive): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');
        abort_unless($hive->organization_id === $this->organization($request), 404);
        $this->assertOwnedRecord($request, $hive, 'beekeeping.view');

        return response()->json(
            $this->scopedOwnedQuery($request, $hive->feedings()->getQuery())->latest('fed_at')->paginate(30)
        );
    }

    public function storeFeeding(Request $request, Hive $hive): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        abort_unless($hive->organization_id === $this->organization($request), 404);
        $this->assertOwnedRecord($request, $hive, 'beekeeping.manage');

        $data = $request->validate([
            'feed_type' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:16'],
            'fed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $feeding = HiveFeeding::unguarded(fn () => HiveFeeding::create([
            ...$this->assignOwnedPayload($request, $data),
            'organization_id' => $this->organization($request),
            'hive_id' => $hive->id,
        ]));

        return response()->json($feeding, 201);
    }

    public function productionRecords(Request $request, Hive $hive): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');
        abort_unless($hive->organization_id === $this->organization($request), 404);
        $this->assertOwnedRecord($request, $hive, 'beekeeping.view');

        return response()->json(
            $this->scopedOwnedQuery($request, $hive->productionRecords()->getQuery())->latest('recorded_at')->paginate(30)
        );
    }

    public function storeProductionRecord(Request $request, Hive $hive): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        abort_unless($hive->organization_id === $this->organization($request), 404);
        $this->assertOwnedRecord($request, $hive, 'beekeeping.manage');

        $data = $request->validate([
            'product_type' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit' => ['sometimes', 'string', 'max:16'],
            'recorded_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $record = HiveProductionRecord::unguarded(fn () => HiveProductionRecord::create([
            ...$this->assignOwnedPayload($request, $data),
            'organization_id' => $this->organization($request),
            'hive_id' => $hive->id,
        ]));

        return response()->json($record, 201);
    }

    public function calendar(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');

        return response()->json(
            $this->scopedOwnedQuery($request, BeeCalendarTask::query())
                ->orderBy('scheduled_for')
                ->paginate(30)
        );
    }

    public function storeCalendarTask(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        $data = $request->validate([
            'apiary_id' => ['nullable', 'integer'],
            'hive_id' => ['nullable', 'integer'],
            'task_type' => ['required', 'string'],
            'severity' => ['nullable', 'string'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'scheduled_for' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'context' => ['nullable', 'array'],
        ]);

        if ($apiaryId = $data['apiary_id'] ?? null) {
            $apiary = Apiary::query()
                ->where('organization_id', $this->organization($request))
                ->findOrFail($apiaryId);
            $this->assertOwnedRecord($request, $apiary, 'beekeeping.manage');
        }

        if ($hiveId = $data['hive_id'] ?? null) {
            $hive = Hive::query()
                ->where('organization_id', $this->organization($request))
                ->findOrFail($hiveId);
            $this->assertOwnedRecord($request, $hive, 'beekeeping.manage');
        }

        $task = BeeCalendarTask::unguarded(fn () => BeeCalendarTask::create([
            ...$this->assignOwnedPayload($request, $data),
            'organization_id' => $this->organization($request),
        ]));

        return response()->json($task, 201);
    }

    public function plants(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');

        return response()->json(
            $this->scopedOwnedQuery($request, PollinationPlant::query())->paginate(30)
        );
    }

    public function storePlant(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        $data = $request->validate([
            'species_name' => ['required', 'string'],
            'common_name' => ['nullable', 'string'],
            'flowering_start' => ['nullable', 'date'],
            'flowering_end' => ['nullable', 'date'],
            'location' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'region' => ['nullable', 'string'],
            'pollination_relevance' => ['nullable', 'integer', 'min:1', 'max:10'],
            'expected_seasons' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $plant = PollinationPlant::unguarded(fn () => PollinationPlant::create([
            ...$this->assignOwnedPayload($request, $data),
            'organization_id' => $this->organization($request),
        ]));

        return response()->json($plant, 201);
    }

    public function knowledgeTopics(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');

        $query = BeeKnowledgeTopic::query()->where('is_active', true);
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return response()->json($query->orderBy('category')->orderBy('slug')->paginate(50));
    }
}
