<?php

namespace Tests\Feature;

use App\Models\JobSeekerProfile;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobSeekerProfileValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @return array<string, string> */
    private function seekerHeaders(string $email = 'validation-seeker@wsa.test'): array
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'Validation Seeker',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);

        return [
            'Authorization' => 'Bearer '.$user->createToken('seeker-validation')->plainTextToken,
            'X-Organization-Id' => (string) $org->id,
        ];
    }

    public function test_four_part_name_is_required_and_shorter_names_are_rejected(): void
    {
        $headers = $this->seekerHeaders();

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Ahmed Mohamed Ali Hassan',
            'email' => 'validation-seeker@wsa.test',
        ]), $headers)->assertCreated()
            ->assertJsonPath('full_name', 'Ahmed Mohamed Ali Hassan');

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Ahmed Mohamed',
            'email' => 'validation-seeker@wsa.test',
        ]), $headers)->assertUnprocessable()
            ->assertJsonValidationErrors(['full_name']);

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Ahmed',
            'email' => 'validation-seeker@wsa.test',
        ]), $headers)->assertUnprocessable();

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Ahmed Mohamed Ali',
            'email' => 'validation-seeker@wsa.test',
        ]), $headers)->assertUnprocessable();
    }

    public function test_date_of_birth_and_personal_fields_are_required(): void
    {
        $headers = $this->seekerHeaders('required-fields@wsa.test');

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Ahmed Mohamed Ali Hassan',
        ], $headers)->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone', 'country', 'city', 'date_of_birth', 'nationality', 'address']);

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'email' => 'required-fields@wsa.test',
            'date_of_birth' => null,
        ]), $headers)->assertUnprocessable()
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    public function test_personal_information_maps_onto_the_matching_backend_properties(): void
    {
        $headers = $this->seekerHeaders('mapped-seeker@wsa.test');

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Yasser Wahba Ali Hassan',
            'email' => 'mapped-seeker@wsa.test',
            'phone' => '+905550000001',
            'country' => 'TR',
            'city' => 'Istanbul',
            'date_of_birth' => '1992-04-18',
            'nationality' => 'Syrian',
            'address' => 'Kadikoy',
        ]), $headers)->assertCreated()
            ->assertJsonPath('full_name', 'Yasser Wahba Ali Hassan')
            ->assertJsonPath('email', 'mapped-seeker@wsa.test')
            ->assertJsonPath('phone', '+905550000001')
            ->assertJsonPath('country', 'TR')
            ->assertJsonPath('city', 'Istanbul')
            ->assertJsonPath('date_of_birth', '1992-04-18')
            ->assertJsonPath('nationality', 'Syrian')
            ->assertJsonPath('address', 'Kadikoy');

        $this->getJson('/api/v1/job-seekers/me', $headers)
            ->assertOk()
            ->assertJsonPath('full_name', 'Yasser Wahba Ali Hassan')
            ->assertJsonPath('email', 'mapped-seeker@wsa.test')
            ->assertJsonPath('phone', '+905550000001')
            ->assertJsonPath('country', 'TR')
            ->assertJsonPath('city', 'Istanbul')
            ->assertJsonPath('date_of_birth', '1992-04-18')
            ->assertJsonPath('nationality', 'Syrian')
            ->assertJsonPath('address', 'Kadikoy');
    }

    public function test_primary_qualification_requires_degree_and_document_while_additional_is_optional(): void
    {
        $headers = $this->seekerHeaders('qualification-seeker@wsa.test');
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'email' => 'qualification-seeker@wsa.test',
        ]), $headers)->assertCreated();

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'email' => 'qualification-seeker@wsa.test',
            'education' => [['institution' => 'KSU']],
        ]), $headers)->assertUnprocessable()
            ->assertJsonValidationErrors(['education.0.degree']);

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'email' => 'qualification-seeker@wsa.test',
            'education' => [['degree' => 'BSc Agricultural Engineering']],
        ]), $headers)->assertUnprocessable()
            ->assertJsonValidationErrors(['primary_qualification_document']);

        Storage::fake('local');
        $document = UploadedFile::fake()->create('degree.pdf', 180, 'application/pdf');
        $uploaded = $this->post('/api/v1/job-seekers/me/primary-qualification', ['document' => $document], $headers)
            ->assertOk()
            ->assertJsonPath('has_primary_qualification_document', true);
        $this->assertNotEmpty($uploaded->json('primary_qualification_filename'));
        $this->assertStringEndsWith('.pdf', (string) $uploaded->json('primary_qualification_filename'));
        $this->assertArrayNotHasKey(
            'primary_qualification_path',
            $this->getJson('/api/v1/job-seekers/me', $headers)->json()
        );

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'email' => 'qualification-seeker@wsa.test',
            'education' => [
                ['degree' => 'BSc Agricultural Engineering', 'institution' => 'KSU'],
                ['degree' => 'Diploma Irrigation', 'institution' => 'FAO'],
            ],
        ]), $headers)->assertOk()
            ->assertJsonPath('education.0.degree', 'BSc Agricultural Engineering')
            ->assertJsonPath('education.1.degree', 'Diploma Irrigation')
            ->assertJsonPath('has_primary_qualification_document', true);

        $this->get('/api/v1/job-seekers/me/primary-qualification', $headers)->assertOk();
    }

    public function test_qualification_document_is_owner_only_and_rejects_unsafe_files(): void
    {
        $ownerHeaders = $this->seekerHeaders('qualification-owner@wsa.test');
        $intruderHeaders = $this->seekerHeaders('qualification-intruder@wsa.test');
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'email' => 'qualification-owner@wsa.test',
        ]), $ownerHeaders)->assertCreated();

        $this->post('/api/v1/job-seekers/me/primary-qualification', [], $ownerHeaders)->assertUnprocessable();
        Storage::fake('local');
        $exe = UploadedFile::fake()->create('malware.exe', 20, 'application/x-msdownload');
        $this->post('/api/v1/job-seekers/me/primary-qualification', ['document' => $exe], $ownerHeaders)
            ->assertUnprocessable();

        $document = UploadedFile::fake()->create('degree.jpg', 80, 'image/jpeg');
        $this->post('/api/v1/job-seekers/me/primary-qualification', ['document' => $document], $ownerHeaders)
            ->assertOk();

        $this->get('/api/v1/job-seekers/me/primary-qualification', $intruderHeaders)->assertNotFound();
        $this->getJson('/api/v1/job-seekers/me/primary-qualification')->assertUnauthorized();
        $this->post('/api/v1/job-seekers/me/primary-qualification', ['document' => $document])->assertUnauthorized();

        $profile = JobSeekerProfile::where('email', 'qualification-owner@wsa.test')->firstOrFail();
        $profile->update(['primary_qualification_path' => '../secrets/passwd']);
        $this->get('/api/v1/job-seekers/me/primary-qualification', $ownerHeaders)->assertNotFound();
    }

    public function test_intermediate_personal_save_keeps_the_profile_and_does_not_require_education(): void
    {
        $headers = $this->seekerHeaders('section-save@wsa.test');
        $first = $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'email' => 'section-save@wsa.test',
            'city' => 'Riyadh',
        ]), $headers)->assertCreated();

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'email' => 'section-save@wsa.test',
            'city' => 'Jeddah',
            'target_job_title' => 'Agronomist',
            'biography' => 'Summary',
            'specialization' => 'Irrigation',
        ]), $headers)->assertOk()
            ->assertJsonPath('city', 'Jeddah')
            ->assertJsonPath('target_job_title', 'Agronomist');

        $saved = $this->getJson('/api/v1/job-seekers/me', $headers)
            ->assertOk()
            ->assertJsonPath('full_name', 'Ahmed Mohamed Ali Hassan')
            ->assertJsonPath('city', 'Jeddah');
        $this->assertSame([], $saved->json('education') ?? []);

        $this->assertSame($first->json('id'), JobSeekerProfile::where('email', 'section-save@wsa.test')->value('id'));
    }
}
