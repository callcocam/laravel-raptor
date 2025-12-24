<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

/**
 * LinkAction - Navega para uma URL usando Inertia.js (método GET)
 *
 * Exemplo de uso:
 * LinkAction::make('edit')
 *     ->label('Editar')
 *     ->icon('Edit')
 *     ->url(fn($user) => route('users.edit', $user))
 */
class LinkAction extends Action
{
    protected string $actionType = 'link';

    protected string $method = 'GET';

    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? 'link');
        $this->component('action-link');
        $this->policy('viewAny');
    }

    /**
     * Define o tipo de link a ou Link(inertia) ou Redirect (redirecionamento completo)
     */
    public function actionAlink(): static
    {
        $this->component('action-a-link');
        return $this;
    }
}
