<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class JobSeekerProfile extends Model
{
    use BelongsToUser;
    use SoftDeletes;

    public const STATUS_NEW = 'new';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_INTERVIEW = 'interview';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_HIRED = 'hired';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_QUALIFIED,
        self::STATUS_INTERVIEW,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_HIRED,
    ];

    /**
     * Allowed recruitment-status transitions. Hired is terminal; rejected may reopen.
     *
     * @var array<string, list<string>>
     */
    public const STATUS_TRANSITIONS = [
        self::STATUS_NEW => [self::STATUS_UNDER_REVIEW, self::STATUS_REJECTED],
        self::STATUS_UNDER_REVIEW => [self::STATUS_QUALIFIED, self::STATUS_INTERVIEW, self::STATUS_REJECTED],
        self::STATUS_QUALIFIED => [self::STATUS_INTERVIEW, self::STATUS_ACCEPTED, self::STATUS_REJECTED],
        self::STATUS_INTERVIEW => [self::STATUS_QUALIFIED, self::STATUS_ACCEPTED, self::STATUS_REJECTED],
        self::STATUS_ACCEPTED => [self::STATUS_HIRED, self::STATUS_REJECTED, self::STATUS_INTERVIEW],
        self::STATUS_REJECTED => [self::STATUS_UNDER_REVIEW],
        self::STATUS_HIRED => [],
    ];

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'country',
        'region',
        'city',
        'specialization',
        'target_job_title',
        'biography',
        'skills',
        'experience',
        'education',
        'certifications',
        'languages',
        'cv_path',
        'desired_salary',
        'salary_currency',
        'availability_date',
        'date_of_birth',
        'nationality',
        'address',
        'years_of_experience',
        'photo_path',
        'is_active',
    ];

    protected $hidden = [
        'email',
        'phone',
        'address',
        'cv_path',
        'photo_path',
        'desired_salary',
        'salary_currency',
    ];

    /**
     * Candidate self-service allowlist. Anything else is ignored on /job-seekers/me.
     *
     * @var list<string>
     */
    public const CANDIDATE_ALLOWED_KEYS = [
        'full_name',
        'email',
        'phone',
        'country',
        'region',
        'city',
        'specialization',
        'target_job_title',
        'biography',
        'skills',
        'experience',
        'education',
        'certifications',
        'languages',
        'desired_salary',
        'salary_currency',
        'availability_date',
        'date_of_birth',
        'nationality',
        'address',
        'years_of_experience',
    ];

    /** @var list<string> */
    public const CANDIDATE_FORBIDDEN_KEYS = [
        'id',
        'user_id',
        'owner_user_id',
        'owner_id',
        'organization_id',
        'tenant_id',
        'recruitment_status',
        'employment_status',
        'payment_status',
        'contact_exchange_status',
        'hiring_decision',
        'hiring_record_id',
        'contact_request_id',
        'submitted_at',
        'reviewed_at',
        'internal_rating',
        'rating',
        'is_active',
        'completeness_percent',
        'created_at',
        'updated_at',
        'deleted_at',
        'recruiter_notes',
        'notes',
        'evaluation',
        'interview_result',
        'status_history',
        'cv_path',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'experience' => 'array',
            'education' => 'array',
            'certifications' => 'array',
            'languages' => 'array',
            'desired_salary' => 'decimal:2',
            'availability_date' => 'date',
            'years_of_experience' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true);
    }

    /** @return array<string, mixed> */
    public static function nestedPayloadRules(): array
    {
        return [
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:32'],
            'experience' => ['nullable', 'array'],
            'experience.*' => ['array'],
            'experience.*.title' => ['nullable', 'string', 'max:255'],
            'experience.*.company' => ['nullable', 'string', 'max:255'],
            'experience.*.start_date' => ['nullable', 'date'],
            'experience.*.end_date' => ['nullable', 'date'],
            'experience.*.current' => ['nullable', 'boolean'],
            'experience.*.years' => ['nullable', 'numeric', 'min:0', 'max:80'],
            'experience.*.description' => ['nullable', 'string', 'max:2000'],
            'education' => ['nullable', 'array'],
            'education.*' => ['array'],
            'education.*.institution' => ['nullable', 'string', 'max:255'],
            'education.*.degree' => ['nullable', 'string', 'max:255'],
            'education.*.field' => ['nullable', 'string', 'max:255'],
            'education.*.country' => ['nullable', 'string', 'max:100'],
            'education.*.year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'certifications' => ['nullable', 'array'],
            'certifications.*' => ['array'],
            'certifications.*.name' => ['nullable', 'string', 'max:255'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:255'],
            'certifications.*.year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitizeNested(array $data): array
    {
        $maps = [
            'experience' => ['title', 'company', 'start_date', 'end_date', 'current', 'years', 'description'],
            'education' => ['institution', 'degree', 'field', 'country', 'year'],
            'certifications' => ['name', 'issuer', 'year'],
        ];
        foreach ($maps as $field => $allowed) {
            if (! isset($data[$field]) || ! is_array($data[$field])) {
                continue;
            }
            $data[$field] = array_values(array_map(function ($row) use ($allowed) {
                if (! is_array($row)) {
                    return $row;
                }

                return array_intersect_key($row, array_flip($allowed));
            }, $data[$field]));
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function candidateWritable(array $data): array
    {
        foreach (self::CANDIDATE_FORBIDDEN_KEYS as $key) {
            unset($data[$key]);
        }

        return array_intersect_key(self::sanitizeNested($data), array_flip(self::CANDIDATE_ALLOWED_KEYS));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(EmploymentStatusHistory::class);
    }

    public function recruiterNotes(): HasMany
    {
        return $this->hasMany(RecruiterNote::class);
    }

    /**
     * Public completeness uses only recruiter-visible profile fields.
     * Email, phone, cv_path, and salary must not affect the public score.
     */
    public function completenessPercent(bool $includePrivateSignals = false): int
    {
        $fields = [
            'full_name',
            'country',
            'city',
            'specialization',
            'target_job_title',
            'biography',
            'skills',
            'experience',
            'education',
            'certifications',
            'languages',
            'availability_date',
        ];
        if ($includePrivateSignals) {
            $fields = array_merge($fields, ['email', 'phone', 'cv_path']);
        }
        $filled = 0;
        foreach ($fields as $field) {
            $value = $this->{$field};
            if ($value !== null && $value !== '' && $value !== []) {
                $filled++;
            }
        }

        return (int) round(($filled / count($fields)) * 100);
    }

    /**
     * Candidate self-service score uses only supported profile sections.
     * Recruiter-facing completenessPercent() remains field-based.
     */
    public function ownerCompletenessPercent(): int
    {
        $sections = [
            filled($this->full_name) && filled($this->email) && filled($this->phone) && filled($this->country) && filled($this->city),
            filled($this->target_job_title) && filled($this->biography) && filled($this->specialization),
            is_array($this->education) && $this->education !== [],
            is_array($this->experience) && $this->experience !== [],
            is_array($this->skills) && $this->skills !== [],
            is_array($this->languages) && $this->languages !== [],
            filled($this->cv_path),
        ];
        $filled = count(array_filter($sections));

        return (int) round(($filled / count($sections)) * 100);
    }

    /** @return array<string, mixed> */
    public function toAdminArray(bool $includeHrPrivate = false, bool $includeContact = false): array
    {
        $data = $this->only([
            'id',
            'user_id',
            'full_name',
            'country',
            'region',
            'city',
            'specialization',
            'target_job_title',
            'biography',
            'skills',
            'experience',
            'education',
            'certifications',
            'languages',
            'availability_date',
            'date_of_birth',
            'nationality',
            'years_of_experience',
            'recruitment_status',
            'is_active',
            'created_at',
            'updated_at',
        ]);
        $data['completeness_percent'] = $this->completenessPercent($includeContact);

        if ($includeHrPrivate) {
            $data['desired_salary'] = $this->desired_salary;
            $data['salary_currency'] = $this->salary_currency;
        }

        if ($includeContact) {
            $data['email'] = $this->email;
            $data['phone'] = $this->phone;
            $data['address'] = $this->address;
        }

        return $data;
    }

    public function storedCvDiskPath(): ?string
    {
        if (! is_string($this->cv_path) || $this->cv_path === '') {
            return null;
        }

        $prefix = 'job-cvs/'.$this->id.'/';
        if (str_contains($this->cv_path, '..') || ! str_starts_with($this->cv_path, $prefix)) {
            return null;
        }

        if (! Storage::disk('local')->exists($this->cv_path)) {
            return null;
        }

        return $this->cv_path;
    }

    public function storedPhotoDiskPath(): ?string
    {
        if (! is_string($this->photo_path) || $this->photo_path === '') {
            return null;
        }

        $prefix = 'job-photos/'.$this->id.'/';
        if (str_contains($this->photo_path, '..') || ! str_starts_with($this->photo_path, $prefix)) {
            return null;
        }

        if (! Storage::disk('local')->exists($this->photo_path)) {
            return null;
        }

        return $this->photo_path;
    }

    /** @return array<string, mixed> */
    public function toOwnerArray(): array
    {
        $data = $this->toAdminArray(true, true);

        return array_merge($data, [
            'completeness_percent' => $this->ownerCompletenessPercent(),
            'has_cv' => filled($this->cv_path),
            'cv_filename' => $this->cv_path ? basename($this->cv_path) : null,
            'has_photo' => filled($this->photo_path),
        ]);
    }
}
