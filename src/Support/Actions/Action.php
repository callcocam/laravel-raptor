<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions;

abstract class Action extends \Callcocam\LaravelRaptor\Support\AbstractColumn
{
    abstract public function handle(): mixed;
}
