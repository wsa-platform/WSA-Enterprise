<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesPlatformAdmin;
use App\Http\Controllers\Controller;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Audit\AuditService;
use App\Services\Media\MediaReferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminLibraryController extends Controller
{
    use AuthorizesPlatformAdmin;

    public function __construct(
        private AuditService $audit,
        private MediaReferenceService $media,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.library.view');

        $query = LibraryItem::query()->latest();

        if ($type = $request->query('item_type')) {
            $query->where('item_type', $type);
        }
        if ($status = $request->query('publication_status')) {
            $query->where('publication_status', $status);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($builder) use ($search, $like): void {
                $builder->where('title', $like, "%{$search}%")
                    ->orWhere('slug', $like, "%{$search}%");
            });
        }

        $paginator = $query->paginate(min(max((int) $request->query('per_page', 15), 1), 100));
        $paginator->getCollection()->transform(fn (LibraryItem $item) => $this->present($item));

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.library.manage');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:128'],
            'summary' => ['nullable', 'string'],
            'item_type' => ['sometimes', 'string', 'max:32'],
            'author' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'locale' => ['sometimes', 'string', 'max:8'],
            'publication_status' => ['sometimes', 'string', 'max:32'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        $storageOrganizationId = $this->storageOrganizationId($data['organization_id'] ?? null);
        $slug = $data['slug'] ?? Str::slug($data['title']).'-'.Str::lower(Str::random(6));

        $fileDisk = null;
        $filePath = null;
        if ($request->hasFile('file')) {
            [$fileDisk, $filePath] = $this->storeOriginalFile($request->file('file'), $storageOrganizationId);
        }

        $item = LibraryItem::unguarded(fn () => LibraryItem::create([
            'organization_id' => $storageOrganizationId,
            'owner_user_id' => null,
            'slug' => $slug,
            'title' => $data['title'],
            'title_ar' => $data['title_ar'] ?? null,
            'summary' => $data['summary'] ?? null,
            'item_type' => $data['item_type'] ?? 'reference',
            'author' => $data['author'] ?? null,
            'source' => $data['source'] ?? null,
            'locale' => $data['locale'] ?? 'en',
            'publication_status' => $data['publication_status'] ?? 'published',
            'published_at' => now(),
            'file_disk' => $fileDisk,
            'file_path' => $filePath,
            'metadata' => [
                'admin_upload' => true,
                'original_filename' => $request->file('file')?->getClientOriginalName(),
            ],
        ]));

        // Platform-administered Library records are not personal service ownership.
        // BelongsToOwner would otherwise stamp the acting admin as owner.
        if ($item->owner_user_id !== null) {
            $item->forceFill(['owner_user_id' => null])->saveQuietly();
        }

        $this->audit->record(
            action: 'admin.lib.created',
            organizationId: null,
            userId: $request->user()->id,
            auditable: $item,
            newValues: [
                'library_item_id' => $item->id,
                'item_type' => $item->item_type,
                'storage_organization_id' => $storageOrganizationId,
                'has_file' => $filePath !== null,
            ],
            request: $request,
        );

        return response()->json($this->present($item), 201);
    }

    public function update(Request $request, LibraryItem $libraryItem): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.library.manage');

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'item_type' => ['sometimes', 'string', 'max:32'],
            'publication_status' => ['sometimes', 'string', 'max:32'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        $old = $libraryItem->only(['title', 'item_type', 'publication_status']);

        if ($request->hasFile('file')) {
            [$disk, $path] = $this->storeOriginalFile($request->file('file'), (int) $libraryItem->organization_id);
            $data['file_disk'] = $disk;
            $data['file_path'] = $path;
        }

        unset($data['file']);
        $libraryItem->update($data);

        $this->audit->record(
            action: 'admin.lib.updated',
            organizationId: null,
            userId: $request->user()->id,
            auditable: $libraryItem,
            oldValues: $old,
            newValues: [
                'library_item_id' => $libraryItem->id,
                ...$libraryItem->only(['title', 'item_type', 'publication_status']),
            ],
            request: $request,
        );

        return response()->json($this->present($libraryItem->fresh()));
    }

    public function destroy(Request $request, LibraryItem $libraryItem): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.library.manage');

        $id = $libraryItem->id;
        $libraryItem->delete();

        $this->audit->record(
            action: 'admin.lib.deleted',
            organizationId: null,
            userId: $request->user()->id,
            newValues: ['library_item_id' => $id],
            request: $request,
        );

        return response()->json(['message' => 'Library item deleted.']);
    }

    /** @return array<string, mixed> */
    private function present(LibraryItem $item): array
    {
        return [
            ...$item->only([
                'id', 'slug', 'title', 'title_ar', 'summary', 'item_type', 'author',
                'source', 'locale', 'publication_status', 'published_at', 'organization_id',
            ]),
            'file' => $this->media->toPublicMetadata($item->file_disk, $item->file_path),
            'owner_user_id' => $item->owner_user_id,
        ];
    }

    private function storageOrganizationId(?int $requested): int
    {
        if ($requested !== null) {
            return $requested;
        }

        $slug = (string) config('wsa.public_organization_slug', 'wsa-demo');
        $organization = Organization::query()->where('slug', $slug)->first()
            ?? Organization::query()->orderBy('id')->first();

        abort_unless($organization !== null, 503, 'No storage organization is available.');

        return (int) $organization->id;
    }

    /** @return array{0: string, 1: string} */
    private function storeOriginalFile(UploadedFile $file, int $organizationId): array
    {
        $original = $file->getClientOriginalName() ?: 'upload.bin';
        $sanitized = $this->media->validateAndSanitize([
            'file_disk' => 'local',
            'file_path' => 'library/admin/'.$organizationId.'/'.basename($original),
        ]);

        $path = $sanitized['file_path'];
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()) ?: '');

        return ['local', $path];
    }
}
