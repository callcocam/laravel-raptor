<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

class ViewAction extends Action
{
    protected string $actionType = 'link';

    protected string $method = 'GET';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'view');
        $this->name($name) // ✅ Sempre define o name
            ->label('Visualizar')
            ->icon('Eye')
            ->color('blue')
            ->policy('view')
            ->component('action-button-link')
            ->tooltip('Visualizar detalhes');
        $this->setUp();
    }
}
