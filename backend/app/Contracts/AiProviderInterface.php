<?php

namespace App\Contracts;

interface AiProviderInterface
{
    public function name(): string;

    /** @param  array<string, mixed>  $input */
    public function complete(string $requestType, array $input): array;
}
