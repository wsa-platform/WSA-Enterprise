<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ScopesOwnedServices;
use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Services\Ai\AiActionRegistry;
use App\Services\Ai\AiAssistantService;
use App\Services\Ai\AiDomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    use AuthorizesOrganizationAccess;
    use ScopesOwnedServices;

    public function __construct(
        private AiAssistantService $assistantService,
        private AiActionRegistry $actionRegistry,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.assistant']);

        $query = $this->scopedOwnedQuery($request, AiConversation::query())->latest();

        if ($request->boolean('archived')) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        return response()->json($query->paginate(20));
    }

    public function show(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.assistant']);
        $this->assertConversationAccess($request, $conversation);

        return response()->json($conversation->load('messages'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.assistant']);
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:8000'],
            'provider' => ['prohibited'],
            'model' => ['prohibited'],
            'api_key' => ['prohibited'],
        ]);
        $data['domain'] = AiDomain::assert($data['domain']);

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
            $request,
        );

        return response()->json($response, 201);
    }

    public function message(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.assistant']);
        $this->assertConversationAccess($request, $conversation);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:8000'],
            'provider' => ['prohibited'],
            'model' => ['prohibited'],
            'api_key' => ['prohibited'],
        ]);

        return response()->json(
            $this->assistantService->sendMessage(
                $conversation,
                $data['message'],
                $this->organization($request),
                $request->user(),
                $request,
            )
        );
    }

    public function archive(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.assistant']);
        $this->assertConversationAccess($request, $conversation);

        return response()->json(
            $this->assistantService->archive($conversation, $request)
        );
    }

    public function destroy(Request $request, AiConversation $conversation): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.assistant']);
        $this->assertConversationAccess($request, $conversation);

        $this->assistantService->deleteConversation($conversation, $request);

        return response()->json(status: 204);
    }

    public function executeAction(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'ai.actions.execute');
        $data = $request->validate([
            'action_type' => ['required', 'string'],
            'payload' => ['nullable', 'array'],
            'confirmed' => ['nullable', 'boolean'],
        ]);

        return response()->json(
            $this->actionRegistry->execute(
                $data['action_type'],
                $this->organization($request),
                $request->user(),
                array_merge($data['payload'] ?? [], ['confirmed' => $data['confirmed'] ?? false]),
            )
        );
    }

    private function assertConversationAccess(Request $request, AiConversation $conversation): void
    {
        abort_unless($conversation->organization_id === $this->organization($request), 404);
        $this->ownership()->assertOwnedByUser($request->user(), $conversation, $this->organization($request));
    }
}
