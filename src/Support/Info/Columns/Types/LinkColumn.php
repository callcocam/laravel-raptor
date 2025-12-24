<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class LinkColumn extends Column
{
    protected string $type = 'link';

    protected ?string $component = 'info-column-link';

    protected ?string $url = null;

    protected bool $openInNewTab = false;

    protected bool $isExternal = false;

    public function __construct($name, $label = null)
    {
        parent::__construct($name, $label);

        $this->icon('ExternalLink');
    }

    /**
     * Define a URL do link
     */
    public function url(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Define se o link abre em nova aba
     */
    public function openInNewTab(bool $open = true): self
    {
        $this->openInNewTab = $open;

        return $this;
    }

    /**
     * Define se é um link externo (usa <a> ao invés de <Link>)
     */
    public function external(bool $external = true): self
    {
        $this->isExternal = $external;

        return $this;
    }

    public function render(mixed $value, $row = null): array
    {
        // Se tem callback customizado
        if ($this->castCallback) {
            $formatted = $this->evaluate($this->castCallback, ['value' => $value, 'column' => $this, 'row' => $row]);
        } else {
            $formatted = $value;
        }

        // Resolve a URL
        $url = $this->url;
        if (is_callable($url)) {
            $url = $this->evaluate($url, ['value' => $value, 'row' => $row]);
        }

        return [
            'text' => (string) $formatted,
            'url' => $url ?? '#',
            'icon' => $this->getIcon(),
            'tooltip' => $this->getTooltip(),
            'type' => $this->getType(),
            'component' => $this->getComponent(),
            'openInNewTab' => $this->openInNewTab,
            'isExternal' => $this->isExternal,
        ];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'url' => $this->url,
            'openInNewTab' => $this->openInNewTab,
            'isExternal' => $this->isExternal,
        ]);
    }
}
