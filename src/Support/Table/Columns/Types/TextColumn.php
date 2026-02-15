<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToBadge;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToLimit;
use Callcocam\LaravelRaptor\Support\Table\Columns\Column;

class TextColumn extends Column
{
    use BelongsToBadge;
    use BelongsToLimit;

    public function render(mixed $value, $row = null): mixed
    {
        if (is_string($value) && $this->hasLimit()) {
            $value = $this->applyLimit($value);
        }

        return $this->getFormattedValue($value, $row);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'isBadge' => $this->isBadge(),
            'limit' => $this->getLimit(),
        ]);
    }
}
