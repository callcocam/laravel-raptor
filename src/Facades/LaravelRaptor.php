<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Callcocam\LaravelRaptor\LaravelRaptor
 */
class LaravelRaptor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Callcocam\LaravelRaptor\LaravelRaptor::class;
    }
}
