<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers;

use Callcocam\LaravelRaptor\Support\Form\Form;
use Illuminate\Database\Eloquent\Builder;

abstract class TenantController extends AbstractController
{
    //
    
    protected function form(Form $form): Form
    {
        $form->columns([
            //
        ]);

        return $form;
    }
    
    protected function queryBuilder(): Builder
    { 
        return app($this->model())->newQuery()->where(function (Builder $query) {
            $query->where('tenant_id', tenant_id())->orWhereNull('tenant_id');
        });
    }

    /**
     * Adiciona tenant_id automaticamente antes de criar
     */
    protected function beforeExtraStore(array $data, \Illuminate\Http\Request $request)
    {
        $data['tenant_id'] = tenant_id();
        return $data;
    }

    /**
     * Garante que tenant_id não seja alterado no update
     */
    protected function beforeExtraUpdate(array $data, \Illuminate\Http\Request $request, \Illuminate\Database\Eloquent\Model $model)
    {
        // Remove tenant_id dos dados caso alguém tente alterar
        unset($data['tenant_id']);
        return $data;
    }

}
