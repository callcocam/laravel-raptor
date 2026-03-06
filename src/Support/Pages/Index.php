<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Pages;

class Index extends Page
{
    public string $method = 'GET';

    public string $action = 'index';

    protected ?string $indexGroupIcon = null;

    /**
     * @var class-string|null
     */
    protected ?string $resource = null;

    /**
     * @param  class-string  $resource
     */
    public function resource(string $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return class-string|null
     */
    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function hasResource(): bool
    {
        return $this->resource !== null;
    }

    public function groupIcon(?string $icon): static
    {
        $this->indexGroupIcon = $icon;

        return parent::groupIcon($icon);
    }

    public function getGroupIcon(): ?string
    {
        return $this->indexGroupIcon ?? parent::getGroupIcon();
    }
}
