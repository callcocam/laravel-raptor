<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Concerns;

use Closure;
use Illuminate\Http\Request;

trait HasActionCallback
{
    protected string|Closure|null $callback = null;

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
    public function execute(Request $request): mixed
    {
        if (!$this->callback) {
            return null;
        }

        if ($this->callback instanceof Closure) {
            return $this->evaluate($this->callback, ['request' => $request]);
        }

        // Se for string, assume que é um método do controller
        if (method_exists($this, $this->callback)) {
            return $this->{$this->callback}($request);
        }

        return null;
    }

    /**
     * Retorna o callback avaliado (para serialização)
     */
    protected function getEvaluatedCallback($model = null): mixed
    {
        if (!$this->callback) {
            return null;
        }

        return $this->evaluate($this->callback, [
            'model' => $model,
            'record' => $model,
            'item' => $model,
        ]);
    }
}
