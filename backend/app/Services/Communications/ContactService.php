<?php

namespace App\Services\Communications;

use App\Models\Contact;
use Illuminate\Support\Collection;

class ContactService
{
    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return strtolower(trim($email));
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === null || $digits === '') {
            return null;
        }

        return '+'.$digits;
    }

    /** @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Contact> */
    public function list(int $organizationId, ?string $search = null, int $perPage = 25)
    {
        $query = Contact::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('last_contacted_at')
            ->orderBy('name');

        $term = trim((string) $search);
        if ($term !== '') {
            $normalizedEmail = $this->normalizeEmail(str_contains($term, '@') ? $term : null);
            $normalizedPhone = ! str_contains($term, '@') ? $this->normalizePhone($term) : null;

            $query->where(function ($q) use ($term, $normalizedEmail, $normalizedPhone): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
                if ($normalizedEmail !== null) {
                    $q->orWhere('normalized_email', 'like', "{$normalizedEmail}%");
                }
                if ($normalizedPhone !== null) {
                    $q->orWhere('normalized_phone', 'like', "{$normalizedPhone}%");
                }
            });
        }

        return $query->paginate(min(max($perPage, 1), 100));
    }

    /** @param  array{name?: string, email?: string, phone?: string, user_id?: int}  $data */
    public function create(int $organizationId, array $data): Contact
    {
        $normalizedEmail = $this->normalizeEmail($data['email'] ?? null);
        $normalizedPhone = $this->normalizePhone($data['phone'] ?? null);

        if ($normalizedEmail === null && $normalizedPhone === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['البريد الإلكتروني أو رقم الهاتف مطلوب.'],
            ]);
        }

        $existing = $this->findExisting($organizationId, $normalizedEmail, $normalizedPhone);
        if ($existing !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['جهة الاتصال موجودة مسبقاً.'],
            ]);
        }

        return Contact::create([
            'organization_id' => $organizationId,
            'user_id' => $data['user_id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'normalized_email' => $normalizedEmail,
            'normalized_phone' => $normalizedPhone,
        ]);
    }

    /** @param  array{name?: string, email?: string, phone?: string, user_id?: int|null}  $data */
    public function update(Contact $contact, array $data): Contact
    {
        $email = array_key_exists('email', $data) ? $data['email'] : $contact->email;
        $phone = array_key_exists('phone', $data) ? $data['phone'] : $contact->phone;
        $normalizedEmail = $this->normalizeEmail($email);
        $normalizedPhone = $this->normalizePhone($phone);

        if ($normalizedEmail === null && $normalizedPhone === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['البريد الإلكتروني أو رقم الهاتف مطلوب.'],
            ]);
        }

        $existing = $this->findExisting($contact->organization_id, $normalizedEmail, $normalizedPhone);
        if ($existing !== null && $existing->id !== $contact->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['جهة الاتصال موجودة مسبقاً.'],
            ]);
        }

        $contact->update([
            'name' => $data['name'] ?? $contact->name,
            'email' => $email,
            'phone' => $phone,
            'user_id' => array_key_exists('user_id', $data) ? $data['user_id'] : $contact->user_id,
            'normalized_email' => $normalizedEmail,
            'normalized_phone' => $normalizedPhone,
        ]);

        return $contact->fresh();
    }

    public function delete(Contact $contact): void
    {
        $contact->delete();
    }

    /** @return Collection<int, Contact> */
    public function search(int $organizationId, string $query, int $limit = 10): Collection
    {
        $term = trim($query);
        if ($term === '') {
            return collect();
        }

        $normalizedEmail = $this->normalizeEmail(str_contains($term, '@') ? $term : null);
        $normalizedPhone = ! str_contains($term, '@') ? $this->normalizePhone($term) : null;

        return Contact::query()
            ->where('organization_id', $organizationId)
            ->where(function ($q) use ($term, $normalizedEmail, $normalizedPhone): void {
                $q->where('name', 'like', "%{$term}%");
                if ($normalizedEmail !== null) {
                    $q->orWhere('normalized_email', 'like', "{$normalizedEmail}%");
                }
                if ($normalizedPhone !== null) {
                    $q->orWhere('normalized_phone', 'like', "{$normalizedPhone}%");
                }
                $q->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->orderByDesc('last_contacted_at')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /** @param  array{name?: string, email?: string, phone?: string, user_id?: int}  $data */
    public function saveAfterSuccess(int $organizationId, array $data): ?Contact
    {
        $normalizedEmail = $this->normalizeEmail($data['email'] ?? null);
        $normalizedPhone = $this->normalizePhone($data['phone'] ?? null);

        if ($normalizedEmail === null && $normalizedPhone === null) {
            return null;
        }

        $existing = $this->findExisting($organizationId, $normalizedEmail, $normalizedPhone);
        if ($existing !== null) {
            $existing->update([
                'name' => $data['name'] ?? $existing->name,
                'email' => $data['email'] ?? $existing->email,
                'phone' => $data['phone'] ?? $existing->phone,
                'user_id' => $data['user_id'] ?? $existing->user_id,
                'last_contacted_at' => now(),
            ]);

            return $existing->fresh();
        }

        return Contact::create([
            'organization_id' => $organizationId,
            'user_id' => $data['user_id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'normalized_email' => $normalizedEmail,
            'normalized_phone' => $normalizedPhone,
            'last_contacted_at' => now(),
        ]);
    }

    private function findExisting(int $organizationId, ?string $normalizedEmail, ?string $normalizedPhone): ?Contact
    {
        return Contact::query()
            ->where('organization_id', $organizationId)
            ->where(function ($q) use ($normalizedEmail, $normalizedPhone): void {
                if ($normalizedEmail !== null) {
                    $q->orWhere('normalized_email', $normalizedEmail);
                }
                if ($normalizedPhone !== null) {
                    $q->orWhere('normalized_phone', $normalizedPhone);
                }
            })
            ->first();
    }
}
