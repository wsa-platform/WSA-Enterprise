<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosis_requests', function (Blueprint $table) {
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'reference']);
        });

        Schema::table('library_items', function (Blueprint $table) {
            $table->index(['organization_id', 'publication_status']);
            $table->index(['organization_id', 'category_id']);
            $table->index(['organization_id', 'crop_type_id']);
        });

        Schema::table('training_enrollments', function (Blueprint $table) {
            $table->index(['organization_id', 'status']);
        });

        Schema::table('ai_requests', function (Blueprint $table) {
            $table->index(['organization_id', 'request_type']);
            $table->index(['organization_id', 'status']);
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->index(['organization_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_requests', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'status']);
            $table->dropIndex(['organization_id', 'reference']);
        });

        Schema::table('library_items', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'publication_status']);
            $table->dropIndex(['organization_id', 'category_id']);
            $table->dropIndex(['organization_id', 'crop_type_id']);
        });

        Schema::table('training_enrollments', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'status']);
        });

        Schema::table('ai_requests', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'request_type']);
            $table->dropIndex(['organization_id', 'status']);
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'code']);
        });
    }
};
