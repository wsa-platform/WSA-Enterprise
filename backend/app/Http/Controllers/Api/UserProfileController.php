<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->forceFill(['name' => $data['name']])->save();

        return response()->json($user->only(['id', 'name', 'email']));
    }
}
