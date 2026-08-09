<?php

namespace App\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Model;

class AgriculturalScopeValidator
{
    /** @param  array<string, class-string<Model>>  $relations */
    public static function assert(int $organizationId, array $data, array $relations): void
    {
        foreach ($relations as $field => $modelClass) {
            $id = $data[$field] ?? null;

            if ($id === null) {
                continue;
            }

            abort_unless(
                $modelClass::query()->where('organization_id', $organizationId)->whereKey($id)->exists(),
                422,
                "The selected {$field} is invalid for this organization."
            );
        }
    }
}
