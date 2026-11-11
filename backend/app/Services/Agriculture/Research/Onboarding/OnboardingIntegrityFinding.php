<?php

namespace App\Services\Agriculture\Research\Onboarding;

/**
 * One registry, capability, profile, licensing or evidence integrity finding.
 *
 * ERROR means the inputs contradict each other or a governance rule; WARNING means a
 * declared claim is not backed by evidence; INFO records a fact a reviewer must see.
 */
final readonly class OnboardingIntegrityFinding
{
    public const ERROR = 'ERROR';

    public const WARNING = 'WARNING';

    public const INFO = 'INFO';

    public function __construct(
        public string $code,
        public string $severity,
        public string $subject,
        public string $detail,
    ) {
        if (! in_array($severity, [self::ERROR, self::WARNING, self::INFO], true)) {
            throw new OnboardingContractViolation("Unknown finding severity '{$severity}'.");
        }
        if (preg_match('/^[a-z][a-z0-9_]*$/', $code) !== 1 || trim($subject) === '' || trim($detail) === '') {
            throw new OnboardingContractViolation('A finding needs a snake_case code, a subject and a detail.');
        }
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'subject' => $this->subject,
            'detail' => $this->detail,
        ];
    }
}
