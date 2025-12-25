<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToRelationship
{
    protected Closure|string $relationship;

    protected ?string $relationshipName = 'name';

    protected ?Closure $usingRelationshipQuery = null;

    public function relationship(Closure|string $relationship, $relationshipField = null, $relationshipKey = null, $queryUsingRelationship = null): static
    {
        $this->relationship = $relationship;

        if (is_string($relationship)) {
            $this->relationshipName = $relationship;
        }

        // Usa optionLabel e optionKey do BelongsToOptions
        if ($relationshipField && method_exists($this, 'optionLabel')) {
            $this->optionLabel($relationshipField);
        }

        if ($relationshipKey && method_exists($this, 'optionKey')) {
            $this->optionKey($relationshipKey);
        }

        if ($queryUsingRelationship) {
            $this->usingRelationshipQuery = $queryUsingRelationship;
        }

        return $this;
    }

    public function hasRelationship(): bool
    {
        return !empty($this->relationship);
    }

    public function getRelationship()
    {
        return $this->evaluate($this->relationship);
    }


    public function relationshipName(?string $relationshipName): static
    {
        $this->relationshipName = $relationshipName;

        return $this;
    }

    public function getRelationshipName(): ?string
    {
        return $this->relationshipName;
    }

    protected function processRelationshipOptions(): mixed
    {
        $relationship = $this->getRelationship();

        if ($relationship) {
            // 1. Validar que o relacionamento existe no model
            $record = $this->getRecord();

            // Validar que record não é null antes de chamar method_exists
            if (!$record || !is_object($record)) {
                return null;
            } elseif (!method_exists($record, $relationship)) {
                throw new \InvalidArgumentException("Relationship '{$relationship}' does not exist.");
            } else {
                // 2. Verificar se é realmente um relacionamento válido
                try {
                    $relationInstance = $record->$relationship();

                    if (!$relationInstance instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        throw new \InvalidArgumentException("'{$relationship}' is not a valid relationship.");
                    }

                    // 3. Pegar o model relacionado de forma segura
                    $relatedModel = $this->getUsingRelationshipQuery($relationInstance->getRelated());
                    return $relatedModel;
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }
        return null;
    }

    public function usingRelationshipQuery(Closure $callback): static
    {
        $this->usingRelationshipQuery = $callback;

        return $this;
    }

    public function getUsingRelationshipQuery($query, $request = null)
    {

        if (method_exists($this, 'getDependsOn')) {
            if ($dependsOn = $this->getDependsOn()) {
                $dependencyValue = null;
                // Prioridade 1: pega da URL query (quando o usuário seleciona um campo)
                if ($request) {
                    $dependencyValue = $request->query($dependsOn);
                } else {
                    $dependencyValue = request()->query($dependsOn);
                } 

                // Aplica o filtro de dependência no query
                if ($dependencyValue !== null) {
                    $query->where($dependsOn, $dependencyValue);
                }
            }
        }

        if (!$this->usingRelationshipQuery) {
            return $query;
        }

        // Usa optionLabel e optionKey do BelongsToOptions
        $optionLabel = method_exists($this, 'getOptionLabel') ? $this->getOptionLabel() : 'name';
        $optionKey = method_exists($this, 'getOptionKey') ? $this->getOptionKey() : 'id';

        return $this->evaluate($this->usingRelationshipQuery, [
            'query' => $query,
            'request' => $request,
            'relationshipField' => $optionLabel,
            'relationshipKey' => $optionKey,
            'relationshipName' => $this->getRelationshipName(),
        ]);
    }
}
