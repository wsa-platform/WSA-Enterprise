<?php

namespace Database\Seeders;

use App\Models\JobSeekerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobSeekerSeeder extends Seeder
{
    public function run(): void
    {
        $seekers = [
            ['email' => 'seeker1@wsa.test', 'name' => 'فاطمة العتيبي', 'specialization' => 'مهندسة زراعية', 'country' => 'SA', 'city' => 'الرياض', 'status' => JobSeekerProfile::STATUS_NEW],
            ['email' => 'seeker2@wsa.test', 'name' => 'محمد الشمري', 'specialization' => 'فني ري', 'country' => 'SA', 'city' => 'جدة', 'status' => JobSeekerProfile::STATUS_UNDER_REVIEW],
            ['email' => 'seeker3@wsa.test', 'name' => 'سارة الحربي', 'specialization' => 'أخصائية تسويق زراعي', 'country' => 'SA', 'city' => 'الدمام', 'status' => JobSeekerProfile::STATUS_QUALIFIED],
        ];

        foreach ($seekers as $index => $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                // Demo/test password only (`password`). Not for production.
                ['name' => $data['name'], 'password' => bcrypt('password')]
            );

            $profile = JobSeekerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => '+96650000000'.($index + 1),
                    'specialization' => $data['specialization'],
                    'country' => $data['country'],
                    'city' => $data['city'],
                    'skills' => ['ري', 'زراعة'],
                    'education' => [['institution' => 'جامعة الملك سعود', 'degree' => 'بكالوريوس']],
                    'experience' => [['title' => 'أخصائي', 'years' => 3]],
                    'certifications' => [['name' => 'شهادة سلامة زراعية']],
                    'languages' => ['ar', 'en'],
                    'is_active' => true,
                    'biography' => 'باحث عن عمل في القطاع الزراعي.',
                ]
            );
            $profile->recruitment_status = $data['status'];
            $profile->save();
        }
    }
}
