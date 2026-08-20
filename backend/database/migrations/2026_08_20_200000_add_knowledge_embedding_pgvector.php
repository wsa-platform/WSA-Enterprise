<?php

use App\Services\Ai\Embeddings\PgvectorSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new PgvectorSchema)->migrate();
    }

    public function down(): void
    {
        (new PgvectorSchema)->rollback();
    }
};
