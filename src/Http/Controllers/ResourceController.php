<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\LaravelRaptor\Support\Concerns\EvaluatesClosures;
use Callcocam\LaravelRaptor\Support\Concerns\HasBreadcrumbs;
use Closure;

abstract class ResourceController extends Controller
{
    use EvaluatesClosures;
    use HasBreadcrumbs;

    /**
     * Define as variáveis basicas que podem ser substituídas pelos controllers filhos
     */

    /**
     * o nome do curto do recurso, ex: user, post, product
     */
    protected Closure|string|null $resourceName = null;

    /**
     * o nome longo do recurso, ex: users, posts, products
     */
    protected Closure|string|null $resourcePluralName = null;

    /**
     * Retorna o label do nome do recurso
     */
    protected Closure|string|null $resourceLabel = null;

    /**
     * Retorna o label do nome plural do recurso
     */
    protected Closure|string|null $resourcePluralLabel = null;

    /**
     * Define o slug do recurso nas rotas
     */
    protected Closure|string|null $resourceSlug = null;

    /**
     * Define a largura máxima do container (full, 7xl, 6xl, 5xl, 4xl, 3xl, 2xl, xl, lg, md, sm)
     */
    protected Closure|string|null $maxWidth = '7xl';

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

            if ($modelClass) {
                $modelInstance = new $modelClass;
                $this->resourceName = $modelInstance->getTable();
            } else {
                $controllerBasename = class_basename(get_class($this));
                $this->resourceName = str_replace('Controller', '', $controllerBasename);
                $this->resourceName = str($this->resourceName)->snake()->toString();
            }
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

            if ($modelClass) {
                $modelInstance = new $modelClass;
                $this->resourcePluralName = str($modelInstance->getTable())->singular()->toString();
            } else {
                $this->resourcePluralName = str($this->getResourceName())->plural()->toString();
            }
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

    /**
     * Retorna o slug do recurso
     */
    protected function getResourceSlug(): ?string
    {
        if (empty($this->resourceSlug)) {
            $this->resourceSlug = str($this->getResourcePluralName())->kebab()->toString();
        }
        $value = $this->evaluate($this->resourceSlug);

        return is_null($value) ? null : (string) $value;
    }

    /**
     * Retorna a largura máxima do container
     */
    protected function getMaxWidth(): string
    {
        $value = $this->evaluate($this->maxWidth);

        return is_null($value) ? '7xl' : (string) $value;
    }

    /**
     * Retorna o caminho do recurso para as views
     * @return string|null
     */
    protected function getResourcePath(): ?string
    {
        $value = $this->evaluate($this->resourcePath());

        return is_null($value) ? null : (string) $value;
    }

    /**
     * Retorna o nome da rota do recurso
     */
    protected function getResourceRouteName(): ?string
    {
        return $this->getResourceSlug();
    }

    /**
     * Retorna o namespace completo do model
     */
    protected function getModelClass(): ?string
    {
        return $this->model();
    }

    /**
     * Retorna o model class baseado no nome do controller
     * Pode ser sobrescrito pelos controllers filhos
     * Retorna null se não houver model (ex: DashboardController)
     */
    public function model(): ?string
    {
        $controllerClass = get_class($this);
        $controllerBasename = class_basename($controllerClass);

        $modelName = str_replace('Controller', '', $controllerBasename);

        $modelClass = 'App\\Models\\' . $modelName;

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        return null;
    }
}
