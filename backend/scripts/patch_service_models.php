<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Str;

$tables = config('service_ownership.service_tables', []);
$modelsPath = app_path('Models');
$updated = 0;
$skipped = 0;

foreach ($tables as $table) {
    $modelName = Str::studly(Str::singular($table));
    $path = $modelsPath.DIRECTORY_SEPARATOR.$modelName.'.php';

    if (! is_file($path)) {
        echo "SKIP missing model: {$modelName} ({$table})\n";
        $skipped++;

        continue;
    }

    $content = file_get_contents($path);
    $original = $content;

    if (! str_contains($content, 'use App\Models\Concerns\BelongsToOwner;')) {
        if (preg_match('/^namespace App\\\\Models;\R/m', $content)) {
            $content = preg_replace(
                '/^(namespace App\\\\Models;\R)/m',
                "$1\nuse App\\Models\\Concerns\\BelongsToOwner;\n",
                $content,
                1,
            );
        }
    }

    if (! preg_match('/\buse BelongsToOwner;/', $content)) {
        if (preg_match('/class \w+ extends Model\s*\R\s*\{/m', $content)) {
            $content = preg_replace(
                '/(class \w+ extends Model\s*\R\s*\{)/m',
                "$1\n    use BelongsToOwner;\n",
                $content,
                1,
            );
        } elseif (preg_match('/class \w+ extends Model \{/', $content)) {
            $content = preg_replace(
                '/(class \w+ extends Model \{)/',
                "$1 use BelongsToOwner;",
                $content,
                1,
            );
        }
    }

    if (! str_contains($content, "'owner_user_id'") && ! str_contains($content, '"owner_user_id"')) {
        if (preg_match("/protected \\\$fillable = \['organization_id',/", $content)) {
            $content = preg_replace(
                "/protected \\\$fillable = \['organization_id',/",
                "protected \$fillable = ['organization_id','owner_user_id',",
                $content,
                1,
            );
        } elseif (preg_match("/protected \\\$fillable=\['organization_id',/", $content)) {
            $content = preg_replace(
                "/protected \\\$fillable=\['organization_id',/",
                "protected \$fillable=['organization_id','owner_user_id',",
                $content,
                1,
            );
        } elseif (preg_match("/'organization_id',\R\s*'/", $content)) {
            $content = preg_replace(
                "/('organization_id',)(\R\s*')/",
                "$1\n        'owner_user_id',$2",
                $content,
                1,
            );
        } else {
            echo "WARN no fillable patch for: {$modelName}\n";
        }
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "UPDATED: {$modelName}\n";
        $updated++;
    } else {
        echo "OK: {$modelName}\n";
    }
}

echo "\nDone. Updated {$updated}, skipped {$skipped}.\n";
