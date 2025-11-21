<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support;

use Callcocam\LaravelRaptor\Support\Concerns;
use Callcocam\LaravelRaptor\Support\Concerns\Shared;

abstract class AbstractColumn
{
    use Concerns\EvaluatesClosures, Concerns\FactoryPattern;
    use Shared\BelongsToColor;
    use Shared\BelongsToIcon;
    use Shared\BelongsToId;
    use Shared\BelongsToLabel;
    use Shared\BelongsToName;
    use Shared\BelongsToOptions;
    use Shared\BelongsToTooltip;
    use Shared\BelongsToType;
    use Shared\BelongsToValidation;
    use Shared\BelongsToVisible;



    protected ?string $component = null;

    /**
     * Método para ser sobrescrito por classes filhas para configuração inicial
     */
    protected function setUp(): void
    {
        //
    }

    /**
     * Define o componente a ser usado
     */
    public function component(string $component): self
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Retorna o componente configurado
     */
    public function getComponent(): ?string
    {
        return $this->component;
    }

    public function getRecord()
    {
        
        return null;
    }
}
