<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns\Types;

use Callcocam\LaravelRaptor\Support\Table\Columns\Column;

class ImageColumn extends Column
{
    protected ?string $component = 'table-column-image';

    protected bool $rounded = false;

    protected bool $clickable = false;

    protected ?string $defaultImage = null;

    protected ?int $width = 40;

    protected ?int $height = 40;

    public function rounded(bool $rounded = true): self
    {
        $this->rounded = $rounded;

        return $this;
    }

    public function clickable(bool $clickable = true): self
    {
        $this->clickable = $clickable;

        return $this;
    }

    public function defaultImage(string $url): self
    {
        $this->defaultImage = $url;

        return $this;
    }

    public function size(int $width, ?int $height = null): self
    {
        $this->width = $width;
        $this->height = $height ?? $width;

        return $this;
    }

    public function render(mixed $value, $row = null): mixed
    {
        return $value ?: $this->defaultImage;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'rounded' => $this->rounded,
            'clickable' => $this->clickable,
            'defaultImage' => $this->defaultImage,
            'width' => $this->width,
            'height' => $this->height,
        ]);
    }
}
