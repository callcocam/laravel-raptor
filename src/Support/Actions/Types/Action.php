<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action as BaseAction;

class Action extends BaseAction
{
    protected string $actionType = 'default';

    protected string $method = 'POST';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'view');
        $this->name($name) // ✅ Sempre define o name
            ->label('Visualizar')
            ->icon('DocumentText')
            ->executeUrlCallback()
            ->color('blue')
            ->component('action-button');

        $this->actionType('field');
        $this->setUp();
    }
}
