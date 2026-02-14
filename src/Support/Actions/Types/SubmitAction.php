<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

class SubmitAction extends Action
{
    protected string $type = 'submit';

    protected string $actionType = 'submit';

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy('store');
        $this->label('Salvar');
        $this->icon('Save');
        $this->variant('default');
        $this->size('default');
        $this->component('action-submit');
        $this->url(false); // Submit não precisa de URL
    }
}
