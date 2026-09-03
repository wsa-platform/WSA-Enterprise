<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Agriculture\FieldCropCategoryCatalog;
use Illuminate\Http\JsonResponse;

class PublicFieldCropTaxonomyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(FieldCropCategoryCatalog::toArray());
    }
}
