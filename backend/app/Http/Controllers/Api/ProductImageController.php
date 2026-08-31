<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ScopesOwnedServices;
use App\Http\Controllers\Controller;
use App\Models\MediaUpload;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Audit\AuditService;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    use AuthorizesOrganizationAccess;
    use ScopesOwnedServices;

    public function __construct(
        private MediaUploadService $uploadService,
        private AuditService $auditService,
    ) {}

    public function index(Request $request, Product $product): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');
        abort_unless($product->organization_id === $this->organization($request), 404);

        $images = ProductImage::where('product_id', $product->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ProductImage $image) => $this->transformImage($image, $request));

        return response()->json($images);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        $organizationId = $this->organization($request);
        abort_unless($product->organization_id === $organizationId, 404);

        $data = $request->validate([
            'file' => ['required', 'file'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $upload = $this->uploadService->store($organizationId, $request->user(), $data['file'], 'product');
        $sortOrder = $data['sort_order'] ?? ProductImage::where('product_id', $product->id)->max('sort_order') + 1;

        $image = ProductImage::create([
            'organization_id' => $organizationId,
            'product_id' => $product->id,
            'media_upload_id' => $upload->id,
            'storage_disk' => $upload->storage_disk,
            'storage_path' => $upload->storage_path,
            'sort_order' => $sortOrder,
        ]);

        return response()->json($this->transformImage($image, $request), 201);
    }

    public function destroy(Request $request, Product $product, ProductImage $productImage): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        $organizationId = $this->organization($request);
        abort_unless($product->organization_id === $organizationId && $productImage->product_id === $product->id, 404);

        if (Storage::disk($productImage->storage_disk)->exists($productImage->storage_path)) {
            Storage::disk($productImage->storage_disk)->delete($productImage->storage_path);
        }

        if ($productImage->media_upload_id) {
            MediaUpload::query()->whereKey($productImage->media_upload_id)->delete();
        }

        $productImage->delete();

        return response()->json(['message' => 'Product image deleted.']);
    }

    /** @return array<string, mixed> */
    private function transformImage(ProductImage $image, Request $request): array
    {
        return [
            'id' => $image->id,
            'product_id' => $image->product_id,
            'sort_order' => $image->sort_order,
            'url' => rtrim($request->getSchemeAndHttpHost(), '/').'/storage/'.ltrim($image->storage_path, '/'),
            'created_at' => $image->created_at,
        ];
    }
}
