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
    use Shared\BelongsToVisible;
}
