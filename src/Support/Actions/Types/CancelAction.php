<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;

class CancelAction extends Action
{
    protected string $type = 'button';

    protected string $actionType = 'cancel';

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Cancelar');
        $this->icon('X');
        $this->variant('secondary');
        $this->size('default');
        $this->component('action-button-link');
        $this->url(function ($target, $request) {
            // Volta para a página anterior (index)
            $name = sprintf('%s.%s', $request->getContext(), $this->getName());
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return route($name, [], false);
            }

            return 'javascript:window.history.back()';
        });
    }
}
