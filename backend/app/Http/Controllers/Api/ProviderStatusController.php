<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Providers\ProviderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderStatusController extends Controller
{
    public function __construct(private ProviderStatusService $status) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 401);

        return response()->json([
            'providers' => $this->status->all(),
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function test(Request $request, string $provider): JsonResponse
    {
        abort_unless($request->user() !== null, 401);

        $result = $this->status->test($provider);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
