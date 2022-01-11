<?php
declare(strict_types=1);

namespace Tests;

use Ekvio\Integration\Contracts\Profiler;

class DummyProfiler implements Profiler
{
    public function profile(string $message): void
    {
    }
}