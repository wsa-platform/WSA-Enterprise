<?php

namespace App\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;

class OrganizationScopeValidator
{
    /** @param  array<string, class-string<Model>>  $relations */
    public static function assert(int $organizationId, array $data, array $relations): void
    {
        AgriculturalScopeValidator::assert($organizationId, $data, $relations);
    }
}
