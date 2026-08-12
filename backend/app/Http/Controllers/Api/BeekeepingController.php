<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\Apiary;
use App\Models\BeeCalendarTask;
use App\Models\BeekeeperProfile;
use App\Models\Hive;
use App\Models\HiveInspection;
use App\Models\BeeKnowledgeTopic;
use App\Models\PollinationPlant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeekeepingController extends Controller
{
    use AuthorizesOrganizationAccess;

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

        $profile = BeekeeperProfile::updateOrCreate(
            [
                'organization_id' => $this->organization($request),
                'user_id' => $request->user()->id,
            ],
            $data,
        );

        return response()->json($profile);
    }

    public function apiaries(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');

        return response()->json(
            Apiary::query()->where('organization_id', $this->organization($request))->withCount('hives')->paginate(20)
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

        $apiary = Apiary::create([
            ...$data,
            'organization_id' => $this->organization($request),
        ]);

        return response()->json($apiary, 201);
    }

    public function hives(Request $request, Apiary $apiary): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');
        abort_unless($apiary->organization_id === $this->organization($request), 404);

        return response()->json($apiary->hives()->paginate(50));
    }

    public function storeHive(Request $request, Apiary $apiary): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        abort_unless($apiary->organization_id === $this->organization($request), 404);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'colony_status' => ['nullable', 'string'],
            'queen_info' => ['nullable', 'array'],
            'frame_count' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $hive = Hive::create([
            ...$data,
            'organization_id' => $this->organization($request),
            'apiary_id' => $apiary->id,
        ]);

        return response()->json($hive, 201);
    }

    public function storeInspection(Request $request, Hive $hive): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.manage');
        abort_unless($hive->organization_id === $this->organization($request), 404);

        $data = $request->validate([
            'inspected_at' => ['required', 'date'],
            'overall_status' => ['nullable', 'string'],
            'findings' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $inspection = HiveInspection::create([
            ...$data,
            'organization_id' => $this->organization($request),
            'hive_id' => $hive->id,
            'inspector_user_id' => $request->user()->id,
        ]);

        return response()->json($inspection, 201);
    }

    public function calendar(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');

        return response()->json(
            BeeCalendarTask::query()
                ->where('organization_id', $this->organization($request))
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

        $task = BeeCalendarTask::create([
            ...$data,
            'organization_id' => $this->organization($request),
        ]);

        return response()->json($task, 201);
    }

    public function plants(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'beekeeping.view');

        return response()->json(
            PollinationPlant::query()->where('organization_id', $this->organization($request))->paginate(30)
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

        $plant = PollinationPlant::create([
            ...$data,
            'organization_id' => $this->organization($request),
        ]);

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
