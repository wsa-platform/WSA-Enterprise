<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'is_active',
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
            'experience.*.years' => ['nullable', 'numeric', 'min:0', 'max:80'],
            'experience.*.description' => ['nullable', 'string', 'max:2000'],
            'education' => ['nullable', 'array'],
            'education.*' => ['array'],
            'education.*.institution' => ['nullable', 'string', 'max:255'],
            'education.*.degree' => ['nullable', 'string', 'max:255'],
            'education.*.field' => ['nullable', 'string', 'max:255'],
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
            'experience' => ['title', 'company', 'years', 'description'],
            'education' => ['institution', 'degree', 'field', 'year'],
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

    /** @return array<string, mixed> */
    public function toAdminArray(bool $includePrivate = false): array
    {
        $data = $this->only([
            'id',
            'user_id',
            'full_name',
            'country',
            'region',
            'city',
            'specialization',
            'biography',
            'skills',
            'experience',
            'education',
            'certifications',
            'languages',
            'availability_date',
            'recruitment_status',
            'is_active',
            'created_at',
            'updated_at',
        ]);
        $data['completeness_percent'] = $this->completenessPercent($includePrivate);

        if ($includePrivate) {
            $data['email'] = $this->email;
            $data['phone'] = $this->phone;
            $data['cv_path'] = $this->cv_path;
            $data['desired_salary'] = $this->desired_salary;
            $data['salary_currency'] = $this->salary_currency;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function toOwnerArray(): array
    {
        return array_merge($this->toAdminArray(true), [
            'email' => $this->email,
            'phone' => $this->phone,
            'cv_path' => $this->cv_path,
            'desired_salary' => $this->desired_salary,
            'salary_currency' => $this->salary_currency,
        ]);
    }
}
