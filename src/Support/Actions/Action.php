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

    public function __construct(?string $name)
    {
        $this->name($name);
        $this->url(function ($target, $request) {
            $name = sprintf('%s.%s', $request->getContext(), $this->name);
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return $target instanceof Model
                    ? route($name, ['record' => data_get($target, 'id')], false)
                    : route($name, [], false);
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
        ]);
    }

    public function variant(string|null $variant): self
    {
        $this->variant = $variant;

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

    public function toArray($model = null, $request = null): array
    {
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
            'url' => $this->getUrl(null)
        ];
    }

    /**
     * Renderiza a action com suporte a Inertia.js
     */
    public function render($model, $request = null): array
    {
        $this->request($request);

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
            'tooltip' => $this->getTooltip(),
            'visible' => $this->isVisible($model),
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
}
