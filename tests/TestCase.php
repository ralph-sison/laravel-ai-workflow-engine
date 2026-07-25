<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function withoutCsrf(): static
    {
        return $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
