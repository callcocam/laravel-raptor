<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form;

use Callcocam\LaravelRaptor\Support\Concerns;
use Callcocam\LaravelRaptor\Support\Concerns\HasGridLayout;
use Callcocam\LaravelRaptor\Support\Form\Concerns\InteractWithForm;
use Illuminate\Database\Eloquent\Model; 

class Form
{
    use Concerns\FactoryPattern;
    use Concerns\EvaluatesClosures;
    use Concerns\Interacts\WithActions;
    use Concerns\Shared\BelongToRequest;
    use InteractWithForm;
    use HasGridLayout;

    protected ?Model $model = null;

    protected array $values = [];

    public function __construct(?Model $model)
    {
        $this->model = $model;
    }

    /**
     * Define o modelo para popular os valores do formulário
     */
    public function model(?Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Retorna o modelo atual
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }

    /**
     * Configura ações padrão do formulário (Submit e Cancel)
     */
    public function defaultActions($actions = []): self
    {
        foreach ($actions as $action) {
            $this->action($action);
        }
        return $this;
    }

    /**
     * Renderiza o formulário e retorna a resposta JSON
     */
    public function render($model = null): array
    {
        // Se recebeu modelo via parâmetro, usa ele
        if ($model) {
            $this->model($model);
        }
        
        return $this->toArray();
    }

    /**
     * Serializa o formulário para array
     */
    public function toArray(): array
    {
        $data = array_merge($this->getGridLayoutConfig(), [
            'columns' => $this->getArrayColumns( $this->model),
            // Usa getRenderedActions para filtrar por visibilidade
            'formActions' => $this->getRenderedActions($this->model, $this->getRequest()),
        ]);

        // Se houver um modelo, inclui os valores
        if ($this->model) {
            $data['model'] = array_merge($this->model->toArray(), $this->values);
        } 
        return $data;
    }
}
