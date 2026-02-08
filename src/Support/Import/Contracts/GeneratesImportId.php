<?php

namespace Callcocam\LaravelRaptor\Support\Import\Contracts;

interface GeneratesImportId
{
    public function generate(array $row): string;
}
