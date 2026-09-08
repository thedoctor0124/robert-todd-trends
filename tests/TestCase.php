<?php

namespace Tests;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // app_settings values are memoised in a static cache that outlives a
        // single test, so clear it to stop one test's settings leaking into the
        // next once the database has been rolled back.
        AppSetting::flushCache();
    }
}
