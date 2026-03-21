<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Tabs;

use Callcocam\LaravelRaptor\Support\Concerns\EvaluatesClosures;
use Callcocam\LaravelRaptor\Support\Concerns\FactoryPattern;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithTabs;

/**
 * TabBuilder — builder standalone de tabs para uso em qualquer controller.
 *
 * Uso em controllers que não usam TableBuilder (ex: MapController, KanbanController):
 *
 * ```php
 * use Callcocam\LaravelRaptor\Support\Tabs\Tab;
 * use Callcocam\LaravelRaptor\Support\Tabs\TabBuilder;
 *
 * 'tabs' => TabBuilder::make()->tabs([
 *     Tab::make('lista',  'Lista') ->href('/planograms')         ->icon('LayoutListIcon'),
 *     Tab::make('kanban', 'Kanban')->href('/kanbans/planogramas')->icon('KanbanIcon')->active(),
 *     Tab::make('maps',   'Maps')  ->href('/maps')               ->icon('MapIcon'),
 * ])->toArray(),
 * ```
 */
class TabBuilder
{
    use EvaluatesClosures;
    use FactoryPattern;
    use WithTabs;

    public static function make(): static
    {
        return new static;
    }

    public function toArray(): ?array
    {
        return $this->getArrayTabs();
    }
}
