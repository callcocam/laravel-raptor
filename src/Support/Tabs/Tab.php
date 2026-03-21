<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Tabs;

use Callcocam\LaravelRaptor\Support\Concerns\EvaluatesClosures;
use Callcocam\LaravelRaptor\Support\Concerns\FactoryPattern;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToIcon;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToLabel;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToName;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToVisible;

class Tab
{
    use BelongsToIcon;
    use BelongsToLabel;
    use BelongsToName;
    use BelongsToVisible;
    use EvaluatesClosures;
    use FactoryPattern;

    protected ?string $key = null;

    protected ?string $href = null;

    protected ?int $badge = null;

    protected bool $active = false;

    public function __construct(string $name, ?string $label = null)
    {
        $this->name($name);
        $this->key($name);
        $this->label($label ?? ucfirst($name));
    }

    public function key(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function href(string $href): static
    {
        $this->href = $href;

        return $this;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function badge(?int $count): static
    {
        $this->badge = $count;

        return $this;
    }

    public function getBadge(): ?int
    {
        return $this->badge;
    }

    public function active(bool $active = true): static
    {
        $this->active = $active;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function toArray(): array
    {
        return [
            'key'    => $this->getKey(),
            'name'   => $this->getLabel(),
            'href'   => $this->getHref(),
            'icon'   => $this->getIcon(),
            'badge'  => $this->getBadge(),
            'active' => $this->isActive(),
        ];
    }
}
