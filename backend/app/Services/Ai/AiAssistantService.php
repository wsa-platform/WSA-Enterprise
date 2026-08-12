<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\User;

class AiAssistantService
{
    public function __construct(private AiService $aiService) {}

    public function startConversation(int $organizationId, User $user, string $domain, ?string $title = null): AiConversation
    {
        return AiConversation::create([
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'domain' => $domain,
            'title' => $title,
        ]);
    }

    /** @return array<string, mixed> */
    public function sendMessage(AiConversation $conversation, string $message, int $organizationId, User $user): array
    {
        AiConversationMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
        ]);

        $record = $this->aiService->run(
            organizationId: $organizationId,
            requestType: 'assistant',
            input: [
                'message' => $message,
                'domain' => $conversation->domain,
                'context' => $conversation->context ?? [],
            ],
            userId: $user->id,
            sourceType: AiConversation::class,
            sourceId: $conversation->id,
        );

        $output = is_array($record->output) ? $record->output : [];
        $reply = (string) ($output['reply'] ?? 'Assistant response unavailable.');

        $assistantMessage = AiConversationMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
            'metadata' => [
                'confidence' => $output['confidence'] ?? null,
                'requires_more_information' => $output['requires_more_information'] ?? false,
                'ai_request_id' => $record->id,
            ],
        ]);

        return [
            'conversation_id' => $conversation->id,
            'message' => $assistantMessage,
            'confidence' => $output['confidence'] ?? null,
            'requires_more_information' => $output['requires_more_information'] ?? false,
        ];
    }
}
