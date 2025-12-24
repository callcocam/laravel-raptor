<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

class BelongsToManyColumn extends HasManyColumn
{
    protected string $type = 'belongs-to-many';

    protected ?string $component = 'info-column-belongs-to-many';

    public function __construct($name, $label = null)
    {
        parent::__construct($name, $label);

        $this->icon('Network');
    }
}
