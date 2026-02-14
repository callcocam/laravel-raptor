<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers;

use Callcocam\LaravelRaptor\Support\Form\Form;

abstract class LandlordController extends AbstractController
{
    protected function form(Form $form): Form
    {
        $form->columns([
            //
        ]);

        return $form;
    }
}
