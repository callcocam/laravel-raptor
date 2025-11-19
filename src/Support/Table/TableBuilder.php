<?php   
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Table;

use Callcocam\LaravelRaptor\Support\Concerns;

class TableBuilder
{
    use \Concerns\Interacts\WithColumns, 
        \Concerns\Interacts\WithActions,
        \Concerns\Interacts\WithBulkActions,
        \Concerns\Interacts\WithFilters,
        \Concerns\Interacts\WithHeaderActions;
}