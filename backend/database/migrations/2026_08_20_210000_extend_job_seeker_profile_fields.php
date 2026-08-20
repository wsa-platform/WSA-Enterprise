<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_seeker_profiles', function (Blueprint $table): void {
            $table->string('target_job_title')->nullable()->after('specialization');
            $table->string('date_of_birth')->nullable()->after('availability_date');
            $table->string('nationality')->nullable()->after('date_of_birth');
            $table->string('address')->nullable()->after('nationality');
        });
    }

    public function down(): void
    {
        Schema::table('job_seeker_profiles', function (Blueprint $table): void {
            $table->dropColumn(['address', 'nationality', 'date_of_birth', 'target_job_title']);
        });
    }
};
