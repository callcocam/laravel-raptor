<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

class CreateAction extends Action
{
    protected string $actionType = 'link';

    protected string $method = 'GET';

    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? 'create');
        $this->name($name)
            ->policy('create')
            ->label('Criar Novo')
            ->icon('PlusCircle')
            ->color('green')
            ->component('action-button-link')
            ->tooltip('Criar novo registro');
        $this->setUp();
    }
}
