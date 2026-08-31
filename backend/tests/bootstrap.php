<?php

declare(strict_types=1);

/**
 * PHPUnit 10+ never overwrites existing process environment variables.
 * The force attribute in phpunit.xml is ignored. Force sqlite :memory:
 * before Composer autoload and before Laravel boots.
 */
$forced = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
    'DATABASE_URL' => '',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'FORBIDDEN_TEST_DATABASES' => 'wsa_enterprise',
];

foreach ($forced as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require_once dirname(__DIR__).'/vendor/autoload.php';
