<?php

namespace App\Contracts;

interface AiProviderInterface
{
    public function name(): string;

    public function model(): string;

    /**
     * Execute a domain request and return a vendor-neutral array.
     *
     * Expected optional keys for later usage logging: tokens_used, finish_reason, sources, model, provider.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function complete(string $requestType, array $input): array;
}
