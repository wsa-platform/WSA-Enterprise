<?php

namespace App\Http\Controllers\Api;

use App\Models\LibraryTag;
use App\Models\Product;
use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrganizationScopeValidator
{
    /** @param  array<string, class-string<Model>>  $relations */
    public static function assert(int $organizationId, array $data, array $relations): void
    {
        AgriculturalScopeValidator::assert($organizationId, $data, $relations);

        $user = Auth::user() ?? request()->user();
        if ($user !== null) {
            app(ServiceOwnershipAuthorizer::class)->assertAccessibleRelations(
                $user,
                $organizationId,
                $data,
                $relations,
            );
        }
    }

    /** @param  list<int|null>  $productIds */
    public static function assertProductIds(int $organizationId, array $productIds): void
    {
        foreach (array_unique(array_filter($productIds)) as $productId) {
            self::assert($organizationId, ['product_id' => $productId], ['product_id' => Product::class]);
        }
    }

    /** @param  list<int|null>  $tagIds */
    public static function assertLibraryTagIds(int $organizationId, array $tagIds): void
    {
        foreach (array_unique(array_filter($tagIds)) as $tagId) {
            self::assert($organizationId, ['tag_id' => $tagId], ['tag_id' => LibraryTag::class]);
        }
    }
}
