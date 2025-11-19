<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form;

use Callcocam\LaravelRaptor\Support\Concerns;
use Callcocam\LaravelRaptor\Support\Form\Concerns\InteractWithForm;

class Form
{
    use Concerns\FactoryPattern;
    use Concerns\Interacts\WithActions;
    use InteractWithForm;

    /**
     * Renderiza o formulário e retorna a resposta JSON
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->toArray());
    }

    /**
     * Serializa o formulário para array
     */
    public function toArray(): array
    {
        return array_merge($this->getGridLayoutConfig(), [
            'columns' => $this->getArrayColumns(),
            'formActions' => $this->getArrayActions(),
        ]);
    }
}
