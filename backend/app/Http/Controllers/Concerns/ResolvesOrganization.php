<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesOrganization
{
    protected function organization(Request $request): int
    {
        if ($request->attributes->has('organization_id')) {
            return (int) $request->attributes->get('organization_id');
        }

        $user = $request->user();
        $header = $request->header('X-Organization-Id');

        if ($header !== null && $header !== '') {
            abort_unless(
                $user->organizations()->where('organizations.id', (int) $header)->exists(),
                403,
                'You do not have access to this organization.'
            );

            return (int) $header;
        }

        return $user->organizations()->firstOrFail()->id;
    }

    protected function organizationModel(Request $request)
    {
        return $request->user()->organizations()->findOrFail($this->organization($request));
    }
}
