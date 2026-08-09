<?php

namespace Database\Seeders;

use App\Models\CropType;
use App\Models\DiagnosisCategory;
use App\Models\DiagnosisDisease;
use App\Models\DiagnosisSubject;
use App\Models\DiagnosisSymptom;
use App\Models\FarmField;
use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Models\LibraryTag;
use App\Models\Organization;
use App\Models\TrainingCourse;
use App\Models\TrainingLesson;
use App\Models\TrainingObjective;
use App\Models\TrainingQuiz;
use App\Models\TrainingQuestion;
use App\Models\User;
use App\Services\Diagnosis\DiagnosisWorkflowService;
use Illuminate\Database\Seeder;

class Phase5Seeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'wsa-demo')->firstOrFail();
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $tomato = CropType::where('organization_id', $organization->id)->where('code', 'TOM')->first();
        $field = FarmField::where('organization_id', $organization->id)->where('code', 'FLD-A')->first();

        $cropCategory = DiagnosisCategory::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'CROP'],
            ['name' => 'Crop diseases', 'name_ar' => 'أمراض المحاصيل', 'description' => 'Demo crop diagnosis category.', 'is_active' => true]
        );

        $tomatoSubject = DiagnosisSubject::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'TOM-DX'],
            [
                'category_id' => $cropCategory->id,
                'crop_type_id' => $tomato?->id,
                'name' => 'Tomato diagnostics',
                'name_ar' => 'تشخيص الطماطم',
                'subject_type' => 'crop',
                'description' => 'Demo tomato subject linked to Green Valley Farm crop type.',
                'is_active' => true,
            ]
        );

        $symptom = DiagnosisSymptom::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'LEAF-SPOT'],
            [
                'subject_id' => $tomatoSubject->id,
                'name' => 'Brown leaf spots',
                'name_ar' => 'بقع بنية على الأوراق',
                'description' => 'Demo symptom for training and diagnosis workflows.',
            ]
        );

        DiagnosisDisease::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'EARLY-BLIGHT'],
            [
                'subject_id' => $tomatoSubject->id,
                'name' => 'Early blight (demo)',
                'name_ar' => 'اللفحة المبكرة (تجريبي)',
                'scientific_name' => 'Alternaria solani',
                'description' => 'Demo disease record for decision-support workflows only.',
                'default_severity' => 'medium',
            ]
        );

        if (! \App\Models\DiagnosisRequest::where('organization_id', $organization->id)->where('reference', 'DX-2026-001')->exists()) {
            app(DiagnosisWorkflowService::class)->submit($organization->id, $admin->id, [
                'reference' => 'DX-2026-001',
                'field_id' => $field?->id,
                'crop_type_id' => $tomato?->id,
                'subject_id' => $tomatoSubject->id,
                'notes' => 'Demo diagnosis request with lower-leaf spotting in Tomato Block A.',
                'symptom_ids' => [$symptom->id],
            ]);
        }

        $course = TrainingCourse::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'TOM-101'],
            [
                'title' => 'Integrated tomato protection',
                'title_ar' => 'الحماية المتكاملة للطماطم',
                'description' => 'Demo course for greenhouse and open-field tomato growers.',
                'description_ar' => 'دورة تجريبية لمزارعي الطماطم في البيوت المحمية والحقول المفتوحة.',
                'locale' => 'ar',
                'status' => 'published',
                'sort_order' => 1,
            ]
        );

        $lesson = TrainingLesson::updateOrCreate(
            ['course_id' => $course->id, 'code' => 'L1'],
            [
                'organization_id' => $organization->id,
                'title' => 'Recognizing early blight symptoms',
                'title_ar' => 'التعرف على أعراض اللفحة المبكرة',
                'content' => 'Demo lesson content for symptom recognition.',
                'content_ar' => 'محتوى تجريبي للتعرف على الأعراض.',
                'sort_order' => 1,
                'status' => 'published',
            ]
        );

        TrainingObjective::updateOrCreate(
            ['organization_id' => $organization->id, 'lesson_id' => $lesson->id, 'objective' => 'Identify lower-leaf lesions'],
            ['objective_ar' => 'تحديد آفات الأوراق السفلية', 'sort_order' => 1]
        );

        $quiz = TrainingQuiz::updateOrCreate(
            ['organization_id' => $organization->id, 'lesson_id' => $lesson->id, 'title' => 'Symptom check'],
            ['title_ar' => 'اختبار الأعراض', 'passing_score' => 70]
        );

        TrainingQuestion::updateOrCreate(
            ['organization_id' => $organization->id, 'quiz_id' => $quiz->id, 'question' => 'Where do early blight lesions usually start?'],
            [
                'question_ar' => 'أين تبدأ آفات اللفحة المبكرة عادة؟',
                'question_type' => 'multiple_choice',
                'options' => ['Lower leaves', 'Fruit', 'Roots'],
                'correct_answer' => 'Lower leaves',
                'sort_order' => 1,
            ]
        );

        $libraryCategory = LibraryCategory::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'CROP-GUIDES'],
            ['name' => 'Crop guides', 'name_ar' => 'أدلة المحاصيل']
        );

        $tag = LibraryTag::updateOrCreate(
            ['organization_id' => $organization->id, 'name' => 'tomato'],
            ['name_ar' => 'طماطم']
        );

        $item = LibraryItem::updateOrCreate(
            ['organization_id' => $organization->id, 'slug' => 'tomato-early-blight-guide'],
            [
                'category_id' => $libraryCategory->id,
                'crop_type_id' => $tomato?->id,
                'title' => 'Tomato early blight management guide',
                'title_ar' => 'دليل إدارة اللفحة المبكرة في الطماطم',
                'summary' => 'Demo article for library search and filtering.',
                'summary_ar' => 'مقال تجريبي للبحث والتصفية في المكتبة.',
                'content' => 'This demo article explains monitoring, sanitation, and decision-support follow-up.',
                'content_ar' => 'يشرح هذا المقال التجريبي المراقبة والنظافة والمتابعة لدعم القرار.',
                'item_type' => 'article',
                'author' => 'WSA Demo Team',
                'source' => 'WSA Enterprise Demo Library',
                'locale' => 'ar',
                'publication_status' => 'published',
                'published_at' => now()->subDay(),
                'metadata' => ['demo' => true],
            ]
        );

        $item->tags()->syncWithoutDetaching([$tag->id]);
    }
}
