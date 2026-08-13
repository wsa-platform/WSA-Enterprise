<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ManagesUserOwnedModules
{
    abstract protected function moduleManagePermission(Request $request, string $module): string;

    abstract protected function moduleViewPermission(Request $request, string $module): string;

    protected function ownership(): ServiceOwnershipAuthorizer
    {
        return app(ServiceOwnershipAuthorizer::class);
    }

    protected function ownedIndex(Request $request, string $module, string $modelClass): JsonResponse
    {
        $this->authorizePermission($request, $this->moduleViewPermission($request, $module));

        $query = $this->ownership()->scopeAccessibleServices(
            $modelClass::query()->where('organization_id', $this->organization($request)),
            $request->user(),
            $this->organization($request),
        )->latest();

        return $this->paginateQuery($request, $query);
    }

    protected function ownedStore(Request $request, string $module, string $modelClass, array $payload): JsonResponse
    {
        $this->authorizePermission($request, $this->moduleManagePermission($request, $module));

        $record = $modelClass::unguarded(fn () => $modelClass::create([
            'organization_id' => $this->organization($request),
            ...$this->ownership()->assignOwnerFromSession($payload, $request->user()),
        ]));

        return response()->json($record, 201);
    }

    protected function ownedUpdate(Request $request, string $module, string $modelClass, int $id, array $payload): JsonResponse
    {
        $record = $this->findOwnedModuleRecord($request, $module, $modelClass, $id);
        $record->update($this->ownership()->stripOwnerKeys($payload));

        return response()->json($record);
    }

    protected function ownedDestroy(Request $request, string $module, string $modelClass, int $id): JsonResponse
    {
        $record = $this->findOwnedModuleRecord($request, $module, $modelClass, $id);
        $record->delete();

        return response()->json(status: 204);
    }

    protected function findOwnedModuleRecord(Request $request, string $module, string $modelClass, int $id): Model
    {
        $record = $this->ownership()->scopeAccessibleServices(
            $modelClass::query()->where('organization_id', $this->organization($request)),
            $request->user(),
            $this->organization($request),
        )->findOrFail($id);

        $this->ownership()->authorizeManage(
            $request->user(),
            $record,
            $this->organization($request),
            $this->moduleManagePermission($request, $module),
        );

        return $record;
    }
}
