<?php

declare(strict_types=1);

/**
 * PHPUnit 10+ never overwrites existing process environment variables.
 * The force attribute in phpunit.xml is ignored. Force sqlite :memory:
 * before Composer autoload and before Laravel boots.
 */
$testingRoot = dirname(__DIR__).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'testing';
if (! is_dir($testingRoot) && ! mkdir($testingRoot, 0775, true) && ! is_dir($testingRoot)) {
    fwrite(STDERR, "Refusing to boot tests: cannot create the isolated scientific store directory.\n");
    exit(1);
}

$forced = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
    'DATABASE_URL' => '',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'FORBIDDEN_TEST_DATABASES' => 'wsa_enterprise',
    // Read by config/database.php before the first scientific_records connection.
    // An empty URL is required: Illuminate treats a non-empty url as authoritative.
    'SCIENTIFIC_RECORD_STORE_DRIVER' => 'sqlite',
    'SCIENTIFIC_RECORD_STORE_URL' => '',
    'SCIENTIFIC_RECORD_STORE_DATABASE' => $testingRoot.DIRECTORY_SEPARATOR.'scientific_records_'.getmypid().'.sqlite',
];

foreach ($forced as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require_once dirname(__DIR__).'/vendor/autoload.php';
