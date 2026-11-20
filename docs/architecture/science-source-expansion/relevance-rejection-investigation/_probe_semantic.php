<?php

declare(strict_types=1);

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\QueryUnderstandingService;

$q = 'what is the taxonomic classification of mentha piperita';
echo "SEMANTIC=\n";
echo json_encode(AgriculturalEntityCatalog::extractSemanticTarget($q), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";

$svc = app(QueryUnderstandingService::class);
$ref = new ReflectionClass($svc);
$m = $ref->getMethod('resolveScientificName');
$m->setAccessible(true);
echo "SCI_NAME=" . json_encode($m->invoke($svc, null, '', $q)) . "\n";

$m2 = $ref->getMethod('detectQuestionType');
$m2->setAccessible(true);
echo "QTYPE=" . json_encode($m2->invoke(
    $svc,
    $q,
    'What is the taxonomic classification of Mentha piperita?',
    'general_knowledge',
    'general',
    'varieties',
)) . "\n";

$m3 = $ref->getMethod('detectQuestionType');
echo "QTYPE_with_gk_intent=" . json_encode($m3->invoke(
    $svc,
    $q,
    'What is the taxonomic classification of Mentha piperita?',
    'general_knowledge',
    'general',
    'general_knowledge',
)) . "\n";
