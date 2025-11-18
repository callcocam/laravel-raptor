<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\LaravelRaptor\Support\Concerns\EvaluatesClosures;
use Closure;

abstract class ResourceController extends Controller
{
    use EvaluatesClosures;

    /**
     * Define as variáveis basicas que podem ser substituídas pelos controllers filhos
     */

    /**
     * o nome do curto do recurso, ex: user, post, product
     */
    protected Closure|string| null $resourceName = null;

    /**
     * o nome longo do recurso, ex: users, posts, products
     */
    protected Closure|string| null $resourcePluralName = null;

    /**
     * Retorna o label do nome do recurso
     */
    protected Closure|string| null $resourceLabel = null;

    /**
     * Retorna o label do nome plural do recurso
     */
    protected Closure|string| null $resourcePluralLabel = null;

    /**
     * Define o model que será usado pelo controller
     */
    abstract protected function model(): string;

    /**
     * Define o resource path para as views
     */
    abstract protected function resourcePath(): ?string;

    /**
     * Retorna o nome curto do recurso
     */
    protected function getResourceName(): ?string
    {
        if (empty($this->resourceName)) {
            $modelClass = $this->model();
            $modelInstance = new $modelClass;
            $this->resourceName = $modelInstance->getTable();
        }

        $value = $this->evaluate($this->resourceName);
        return is_null($value) ? null : (string) $value;
    }

    /**
     * Retorna o nome longo do recurso
     */
    protected function getResourcePluralName(): ?string
    {
        if (empty($this->resourcePluralName)) {
            $modelClass = $this->model();
            $modelInstance = new $modelClass;
            $this->resourcePluralName = str($modelInstance->getTable())->singular()->toString();
        }

        $value = $this->evaluate($this->resourcePluralName);
        return is_null($value) ? null : (string) $value;
    }

    /**
     * Retorna o label do nome do recurso
     */
    protected function getResourceLabel(): ?string
    {
        if (empty($this->resourceLabel)) {
            $this->resourceLabel = str($this->getResourceName())->title()->toString();
        }
        $value = $this->evaluate($this->resourceLabel);
        return is_null($value) ? null : (string) $value;
    }

    /**
     * Retorna o label do nome plural do recurso
     */
    protected function getResourcePluralLabel(): ?string
    {
        if (empty($this->resourcePluralLabel)) {
            $this->resourcePluralLabel = str($this->getResourcePluralName())->title()->toString();
        }
        $value = $this->evaluate($this->resourcePluralLabel);
        return is_null($value) ? null : (string) $value;
    }

    protected function breadcrumbs(): array
    {
        return [];
    }
}
