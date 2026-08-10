<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\ProtectsStagingDatabase;

abstract class TestCase extends BaseTestCase
{
    use ProtectsStagingDatabase;
}
