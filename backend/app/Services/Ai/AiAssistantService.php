<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class AiAssistantService
{
    public function __construct(
        private AiService $aiService,
        private AiContextBuilder $contextBuilder,
        private AiActionRegistry $actionRegistry,
        private AuditService $auditService,
    ) {}

    public function startConversation(int $organizationId, User $user, string $domain, ?string $title = null): AiConversation
    {
        $context = $this->contextBuilder->build($organizationId, $user, $domain);

        return AiConversation::create([
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'domain' => $domain,
            'title' => $title,
            'context' => $context,
        ]);
    }

    /** @return array<string, mixed> */
    public function sendMessage(AiConversation $conversation, string $message, int $organizationId, User $user, ?Request $httpRequest = null): array
    {
        AiConversationMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
        ]);

        $context = $this->contextBuilder->build($organizationId, $user, $conversation->domain);
        $conversation->update(['context' => $context]);

        $record = $this->aiService->run(
            organizationId: $organizationId,
            requestType: 'assistant',
            input: [
                'message' => $message,
                'domain' => $conversation->domain,
                'context' => $context,
            ],
            userId: $user->id,
            sourceType: AiConversation::class,
            sourceId: $conversation->id,
        );

        $output = is_array($record->output) ? $record->output : [];
        $reply = (string) ($output['reply'] ?? 'Assistant response unavailable.');
        $suggestedActions = $this->actionRegistry->suggestForDomain($conversation->domain, $context);

        $assistantMessage = AiConversationMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
            'metadata' => [
                'confidence' => $output['confidence'] ?? null,
                'requires_more_information' => $output['requires_more_information'] ?? false,
                'ai_request_id' => $record->id,
                'suggested_actions' => $suggestedActions,
            ],
        ]);

        return [
            'conversation_id' => $conversation->id,
            'message' => $assistantMessage,
            'confidence' => $output['confidence'] ?? null,
            'requires_more_information' => $output['requires_more_information'] ?? false,
            'suggested_actions' => $suggestedActions,
        ];
    }

    public function archive(AiConversation $conversation, ?Request $request = null): AiConversation
    {
        $conversation->update(['archived_at' => now()]);
        $this->auditService->record(
            action: 'ai.conversation_archived',
            organizationId: $conversation->organization_id,
            userId: $conversation->user_id,
            auditable: $conversation,
            request: $request,
        );

        return $conversation->fresh();
    }

    public function deleteConversation(AiConversation $conversation, ?Request $request = null): void
    {
        $conversation->delete();
        $this->auditService->record(
            action: 'ai.conversation_deleted',
            organizationId: $conversation->organization_id,
            userId: $conversation->user_id,
            auditable: $conversation,
            request: $request,
        );
    }
}
