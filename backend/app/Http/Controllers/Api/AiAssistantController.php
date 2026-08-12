<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Services\Ai\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(private AiAssistantService $assistantService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.assistant']);

        $rows = AiConversation::query()
            ->where('organization_id', $this->organization($request))
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.assistant']);
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $conversation = $this->assistantService->startConversation(
            $this->organization($request),
            $request->user(),
            $data['domain'],
            $data['title'] ?? null,
        );

        $response = $this->assistantService->sendMessage(
            $conversation,
            $data['message'],
            $this->organization($request),
            $request->user(),
        );

        return response()->json($response, 201);
    }

    public function message(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.assistant']);
        abort_unless($conversation->organization_id === $this->organization($request), 404);
        abort_unless($conversation->user_id === $request->user()->id, 403);

        $data = $request->validate(['message' => ['required', 'string']]);

        return response()->json(
            $this->assistantService->sendMessage(
                $conversation,
                $data['message'],
                $this->organization($request),
                $request->user(),
            )
        );
    }
}
