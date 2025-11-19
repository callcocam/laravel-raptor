<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Pages;

abstract class Page
{
    public string $path;
    public string $label = '';
    public string $name = '';
    public array $middlewares = [];
    public string $method = 'GET';
    public string $action = '';
    public string $icon = 'Circle';
    public string $group = 'Geral';
    public bool $groupCollapsible = false;
    public int $order = 50;
    public ?string $badge = null;
    public bool $visible = true;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public static function route($path): static
    {
        return new static($path);
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function middlewares(array $middlewares): static
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function method(string $method): static
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function group(string $group): static
    {
        $this->group = $group;
        return $this;
    }

    public function groupCollapsible(bool $collapsible = true): static
    {
        $this->groupCollapsible = $collapsible;
        return $this;
    }

    public function order(int $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function badge(?string $badge): static
    {
        $this->badge = $badge;
        return $this;
    }

    public function visible(bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function isGroupCollapsible(): bool
    {
        return $this->groupCollapsible;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }
}
