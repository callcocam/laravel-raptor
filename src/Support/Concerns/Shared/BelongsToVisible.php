<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Callcocam\LaravelRaptor\Support\Concerns\EvaluatesClosures;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Trait BelongsToVisible - Gerencia visibilidade com suporte a Laravel Policies
 *
 * FEATURES:
 * ✅ Visibilidade por contexto (index, create, show, edit, delete)
 * ✅ Callbacks customizados de visibilidade
 * ✅ Integração com Laravel Policies (lazy evaluation)
 * ✅ API fluente e concisa
 *
 * USO:
 * ->visible(fn($item, $user) => $user->isAdmin())
 * ->visibleWhen['index'](true)
 * ->policy('update') // Usa Laravel Policy
 * ->isVisible($item)
 * ->isVisibleOn('edit', $item)
 */
trait BelongsToVisible
{
    use EvaluatesClosures;

    /**
     * Callback de visibilidade customizado
     */
    protected ?Closure $visibilityCallback = null;

    /**
     * Visibilidade geral (padrão: true)
     * Se policy estiver configurada, ela terá prioridade
     */
    protected bool|Closure|null $visible = true;

    /**
     * Visibilidade por contexto (armazenamento centralizado)
     */
    protected array $contextVisibility = [
        'index' => true,
        'create' => true,
        'show' => true,
        'edit' => true,
        'delete' => true,
    ];

    /**
     * Laravel Policy ability para verificar (lazy)
     */
    protected ?string $policyAbility = null;

    /**
     * ========================================
     * SETTERS - API Fluente
     * ========================================
     */

    /**
     * Define visibilidade geral
     */
    public function visible(bool|Closure|null $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Oculta o elemento (atalho para visible(false) ou visible com closure invertida)
     *
     * @param  bool|Closure|null  $hidden  Se true, oculta. Se Closure, inverte o resultado.
     */
    public function hidden(bool|Closure|null $hidden = true): self
    {
        if ($hidden instanceof Closure) {
            // Inverte a lógica da closure
            $this->visible = function ($record = null) use ($hidden) {
                return ! $this->evaluate($hidden, [
                    'item' => $record,
                    'model' => $record,
                    'record' => $record,
                    'user' => Auth::user(),
                    'auth' => Auth::user(),
                ]);
            };
        } else {
            $this->visible = ! $hidden;
        }

        return $this;
    }

    /**
     * Define callback customizado de visibilidade
     */
    public function visibleWhen(?Closure $callback): self
    {
        $this->visibilityCallback = $callback;

        return $this;
    }

    /**
     * Define Laravel Policy ability para verificar visibilidade
     * Exemplo: ->policy('update') verificará Gate::allows('update', $item)
     */
    public function policy(?string $ability): self
    {
        $this->policyAbility = $ability;

        return $this;
    }

    /**
     * Define visibilidade para contexto específico
     * Uso: ->setContextVisibility('index', true)
     */
    public function setContextVisibility(string $context, bool|Closure|null $visible): self
    {
        $this->contextVisibility[$context] = $visible;

        return $this;
    }

    /**
     * ========================================
     * MÉTODOS DINÂMICOS POR CONTEXTO
     * ========================================
     */

    /**
     * visibleWhenIndex(bool|Closure $visible)
     * showWhenIndex()
     * hiddenWhenIndex()
     * getVisibleWhenIndex()
     */
    public function __call($method, $args)
    {
        // visibleWhen{Context}
        if (preg_match('/^visibleWhen([A-Z]\w+)$/', $method, $matches)) {
            $context = strtolower($matches[1]);

            return $this->setContextVisibility($context, $args[0] ?? true);
        }

        // showWhen{Context}
        if (preg_match('/^showWhen([A-Z]\w+)$/', $method, $matches)) {
            $context = strtolower($matches[1]);

            return $this->setContextVisibility($context, true);
        }

        // hiddenWhen{Context}
        if (preg_match('/^hiddenWhen([A-Z]\w+)$/', $method, $matches)) {
            $context = strtolower($matches[1]);

            return $this->setContextVisibility($context, false);
        }

        // getVisibleWhen{Context}
        if (preg_match('/^getVisibleWhen([A-Z]\w+)$/', $method, $matches)) {
            $context = strtolower($matches[1]);

            return $this->getContextVisibility($context);
        }

        // throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    /**
     * ========================================
     * GETTERS - Visibilidade
     * ========================================
     */

    /**
     * Obtém visibilidade para contexto específico
     */
    public function getContextVisibility(string $context): bool
    {
        $visibility = $this->contextVisibility[$context] ?? true;

        return (bool) $this->evaluate($visibility);
    }

    /**
     * Verifica visibilidade geral (todas as camadas)
     *
     * ORDEM DE VALIDAÇÃO:
     * 1. Callback customizado (visibilityCallback)
     * 2. Laravel Policy (se definida via ->policy())
     * 3. Visibilidade geral ($visible)
     */
    public function isVisible($item = null): bool
    {
        $user = Auth::user();

        // Camada 1: Callback customizado (prioridade máxima)
        if ($this->visibilityCallback) {
            $result = $this->evaluate($this->visibilityCallback, [
                'item' => $item,
                'model' => $item,
                'record' => $item,
                'user' => $user,
                'auth' => $user,
            ]);

            if ($result === false) {
                return false;
            }
        }

        // Camada 2: Laravel Policy (lazy evaluation)
        if ($this->policyAbility && ! $this->checkPolicy($item, $user)) {
            return false;
        }

        // Camada 3: Visibilidade geral
        if (! $this->evaluate($this->visible, [
            'item' => $item,
            'model' => $item,
            'record' => $item,
            'user' => $user,
            'auth' => $user,
        ])) {
            return false;
        }

        return true;
    }

    /**
     * Verifica visibilidade em contexto específico
     * Exemplo: ->isVisibleOn('edit', $item)
     */
    public function isVisibleOn(string $context, $item = null): bool
    {
        // Verifica visibilidade geral primeiro
        if (! $this->isVisible($item)) {
            return false;
        }

        // Depois verifica contexto específico
        return $this->getContextVisibility($context);
    }

    /**
     * ========================================
     * MÉTODOS DE CONVENIÊNCIA POR CONTEXTO
     * ========================================
     */
    public function isVisibleOnIndex($item = null): bool
    {
        return $this->isVisibleOn('index', $item);
    }

    public function isVisibleOnCreate($item = null): bool
    {
        return $this->isVisibleOn('create', $item);
    }

    public function isVisibleOnShow($item = null): bool
    {
        return $this->isVisibleOn('show', $item);
    }

    public function isVisibleOnEdit($item = null): bool
    {
        return $this->isVisibleOn('edit', $item);
    }

    public function isVisibleOnDelete($item = null): bool
    {
        return $this->isVisibleOn('delete', $item);
    }

    /**
     * ========================================
     * HELPERS INTERNOS
     * ========================================
     */

    /**
     * Verifica Laravel Policy (lazy evaluation)
     */
    protected function checkPolicy($item, $user): bool
    {
        if (! $this->policyAbility) {
            return true;
        }

        // Se não tem usuário autenticado, falha
        if (! $user) {
            return false;
        }
        // Verifica policy usando Gate
        try {
            return Gate::forUser($user)->allows($this->policyAbility, $item);
        } catch (\Throwable) {
            // Se policy não existe ou erro, retorna true (não bloqueia)
            return true;
        }
    }
}
