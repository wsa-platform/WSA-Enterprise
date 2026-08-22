<?php

namespace App\Rules;

use App\Models\JobSeekerProfile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FourPartFullName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || JobSeekerProfile::fullNamePartCount($value) < 4) {
            $fail(__('jobs.full_name_four_parts'));
        }
    }
}
