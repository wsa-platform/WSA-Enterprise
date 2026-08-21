<?php

namespace App\Services\Recruitment;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class JobSeekerRegistrationService
{
    public function __construct(private JobSeekerService $jobSeekers) {}

    /**
     * Create a dedicated job-seeker account with no organization workspace.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->jobSeekers->upsertForUser($user, [
            'full_name' => $data['name'],
            'email' => $data['email'],
        ]);

        return $user;
    }
}
