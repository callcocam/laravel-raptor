<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\HiddenField;
use Callcocam\LaravelRaptor\Support\Form\Concerns\InteractWithForm;
use Illuminate\Support\Facades\Route;

abstract class ExecuteAction extends Action
{
    use InteractWithForm;

    protected string $method = 'POST';

    public function __construct(?string $name)
    {
        parent::__construct($name ?? 'execute');
        $this->actionType('header')
            ->column(HiddenField::make('actionType', 'actions'))
            ->column(HiddenField::make('actionName',  $name))
            ->url(function () {
                $name = str($this->getName())
                    ->replace('import', 'execute')
                    ->replace('update', 'execute')
                    ->replace('delete', 'execute')
                    ->replace('store', 'execute')
                    ->replace('export', 'execute')->toString();
                $route = sprintf('%s.%s', $this->getRequest()->getContext(), $name);
                if (Route::has($route)) {
                    return route($route);
                }
                return null;
            });
        $this->setUp();
    }

    public function toArray($model = null, $request = null): array
    {
        $array = array_merge(parent::toArray($model, $request), $this->getForm());

        return $array;
    }
}
