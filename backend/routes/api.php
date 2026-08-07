<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/v1/health', fn () => response()->json(['status' => 'ok']));

Route::middleware('auth:sanctum')->get('/v1/user', fn (Request $request) => $request->user());
