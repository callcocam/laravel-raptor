<?php

namespace Callcocam\LaravelRaptor\Support\Import\Generators;

use Callcocam\LaravelRaptor\Support\Import\Contracts\GeneratesImportId;
use Illuminate\Support\Str;

class DefaultUlidGenerator implements GeneratesImportId
{
    public function generate(array $row): string
    {
        return (string) Str::ulid();
    }
}
