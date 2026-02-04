<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions;

use Callcocam\LaravelRaptor\Support\Actions\Concerns\HasActionCallback;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongToRequest;
use Callcocam\LaravelRaptor\Support\Form\Concerns\InteractWithForm;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Action extends \Callcocam\LaravelRaptor\Support\AbstractColumn
{
    use InteractWithForm;
    use BelongToRequest;
    use HasActionCallback;

    protected $model = null;

    protected string $method = 'POST';

    protected string $target = '_self';

    protected string|Closure|bool|null $url = null;

    protected string|Closure|null $authorization = null;

    protected string $actionType = 'api';

    protected bool $preserveScroll = true;

    protected bool $preserveState = false;

    protected array $onlyProps = [];

    protected string|null $modalSize = null;

    protected string|null $variant = null;

    protected string|null $size = null;

    protected bool $emptyRecordAllowed = true;

    public function __construct(?string $name)
    {
        $this->name($name);
        $this->url(function ($target,?Request $request) {
            $name = sprintf('%s.%s', $request->getContext(), $this->name);
            if (\Illuminate\Support\Facades\Route::has($name)) {
                $parameters = $request->query();
                $parameters = array_filter($parameters, function ($key) {
                    return $key !== 'record';
                }, ARRAY_FILTER_USE_KEY);
                return $target instanceof Model
                    ? route($name, $parameters, false)
                    : route($name, $parameters, false);
            }
            return null;
        });
        $this->setUp();
    }

    public function method(string $method): self
    {
        $this->method = strtoupper($method);

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function url(Closure|string|bool|null $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl($target): mixed
    {

        return $this->evaluate($this->url, [
            'request' => $this->getRequest(),
            'target' => $target,
            'record' => $this->getRecord(),
            'model' => $this->model,
        ]);
    }

    public function variant(string|null $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function emptyRecordAllowed(bool $allowed = true): self
    {
        $this->emptyRecordAllowed = $allowed;

        return $this;
    }

    public function getVariant(): string|null
    {
        return $this->variant;
    }

    public function size(string|null $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): string|null
    {
        return $this->size;
    }

    public function isEmptyRecordAllowed(): bool
    {
        return $this->emptyRecordAllowed;
    }

    public function button(): self
    {
        $this->component('action-button');

        return $this;
    }

    public function submit(): self
    {
        $this->component('action-submit');

        return $this;
    }

    public function link(): self
    {
        $this->component('action-link');

        return $this;
    }

    public function modal(): self
    {
        $this->component('action-modal-form');

        return $this;
    }

    /**
     * Define o tipo de link a ou Link(inertia) ou Redirect (redirecionamento completo)
     */
    public function actionAlink(): static
    {
        $this->component('action-a-link');

        return $this;
    }

    /**
     * Define a url de execução automática da action baseado em rotas padrão
     * 
     * @param string $action
     */
    protected function executeUrlCallback($action = 'execute'): self
    {
        $this->url(function ($target = null, Request $request = null) use ($action) {
            $route = sprintf('%s.%s', $request->getContext(),  $action);
            $parameters = $request->query();
            $parameters = array_filter($parameters, function ($key) {
                return $key !== 'record';
            }, ARRAY_FILTER_USE_KEY);
            if (\Illuminate\Support\Facades\Route::has($route)) { 
                return $target instanceof \Illuminate\Database\Eloquent\Model
                    ? route($route, $parameters, false)
                    : route($route, $parameters, false);
            } else {
                $route = sprintf('%s.%s', $request->getContext(), $action);
                if (\Illuminate\Support\Facades\Route::has($route)) {
                    return $target instanceof \Illuminate\Database\Eloquent\Model
                        ? route($route, $parameters, false)
                        : route($route, $parameters, false);
                }
            }
            return '#';
        })->callback(function (Request $request, Model $model = null) {
            return redirect()->back()->with('error', 'Ação padrão não implementada. usando callback padrão.');
        });
        return $this;
    }
    public function toArray($model = null, $request = null): array
    {
        if ($model) :
            $this->record($model);
        endif;
        if ($request) :
            $this->request($request);
        endif;

        return  [
            'actionType' => $this->getActionType(),
            'component' => $this->getComponent(),
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'tooltip' => $this->getTooltip(),
            'target' => $this->target,
            'method' => $this->method,
            'variant' => $this->getVariant(),
            'size' => $this->getSize(),
            'url' => $this->getUrl($model, $request),
            'emptyRecordAllowed' => $this->isEmptyRecordAllowed(),
        ];
    }

    /**
     * Renderiza a action com suporte a Inertia.js
     */
    public function render($model, $request = null): array
    {

        if ($model instanceof Model) :
            $this->record($model);
        endif;
        if ($request) :
            $this->request($request);
        endif;

        $result = [
            'type' => 'action',
            'actionType' => $this->getActionType(),
            'url' => $this->getUrl($model),
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'method' => $this->getMethod(),
            'component' => $this->getComponent(),
            'variant' => $this->getVariant(),
            'size' => $this->getSize(),
            'tooltip' => $this->getTooltip(),
            'visible' => $this->isVisible($model),
            'emptyRecordAllowed' => $this->isEmptyRecordAllowed(),
        ];

        if (! empty($this->confirm)) {
            // Se confirm for Closure, não avaliar - apenas indicar que existe
            $result['confirm'] = $this->getConfirm([
                'model' => $model,
                'request' => $this->getRequest(),
            ]);
        }

        if (! empty($this->getColumns())) {
            $result['columns'] = $this->getArrayColumns();
        }

        $result['inertia'] = [
            'preserveScroll' => $this->preserveScroll,
            'preserveState' => $this->preserveState,
            'only' => $this->onlyProps,
        ];


        if ($this->modalSize) {
            $result['modalSize'] = $this->modalSize;
        }

        if (method_exists($this, 'getGridLayoutConfig')) {
            $result = array_merge($result, $this->getGridLayoutConfig());
        }

        return $result;
    }

    public function actionType(string $type): self
    {
        $this->actionType = $type;

        return $this;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function preserveScroll(bool $preserve = true): self
    {
        $this->preserveScroll = $preserve;

        return $this;
    }

    public function preserveState(bool $preserve = true): self
    {
        $this->preserveState = $preserve;

        return $this;
    }

    public function only(array $props): self
    {
        $this->onlyProps = $props;

        return $this;
    }

    public function modalSize(string $size): self
    {
        $this->modalSize = $size;

        return $this;
    }

    public function target(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function targetBlank(): self
    {
        $this->target = '_blank';

        return $this;
    }

    public function targetSelf(): self
    {
        $this->target = '_self';

        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
