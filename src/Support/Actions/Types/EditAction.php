<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

class EditAction extends Action
{
    protected string $actionType = 'link';

    protected string $method = 'GET';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'edit');
        $this->name($name) // ✅ Sempre define o name
            ->label('Editar')
            ->icon('Edit')
            ->color('blue')
            ->component('action-button-link') 
            ->policy("update")
            ->tooltip('Editar registro');
        $this->setUp();
    }
}
