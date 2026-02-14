<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Concerns;

use Callcocam\LaravelRaptor\Support\Table\Confirm;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HasActionCallback
{
    protected string|Closure|null $callback = null;

    protected array|Closure|Confirm $confirm = [];

    /**
     * Define o callback da action
     */
    public function callback(string|Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Executa o callback da action
     */
    public function executeCallback(Request $request, ?Model $model = null): mixed
    {

        if (! $this->callback) {
            return null;
        }

        if ($this->callback instanceof Closure) {
            return $this->evaluate($this->callback, ['request' => $request, 'model' => $model ?? $this->getModel()]);
        }

        // Se for string, assume que é um método do controller
        if (method_exists($this, $this->callback)) {
            return $this->{$this->callback}($request, $model);
        }

        return null;
    }

    /**
     * Retorna informações sobre o callback (para serialização)
     * NÃO executa o callback - apenas retorna metadata
     */
    protected function getEvaluatedCallback($model = null): mixed
    {
        if (! $this->callback) {
            return null;
        }

        // Se for Closure, retorna apenas um indicador de que existe
        // NÃO execute a closure aqui - isso causaria execução indevida
        if ($this->callback instanceof Closure) {
            return 'callback'; // Apenas indica que há um callback
        }

        // Se for string (nome de método), retorna o nome
        return $this->callback;
    }

    public function confirm(array|Closure|Confirm $confirm): self
    {
        $this->confirm = $confirm;

        return $this;
    }

    public function getConfirm($params): array|Closure|Confirm
    {
        if ($this->confirm instanceof Confirm) {
            return $this->confirm->toArray();
        }

        return $this->evaluate($this->confirm, $params);
    }
}
