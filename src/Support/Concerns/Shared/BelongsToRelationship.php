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

    protected ?string $relationshipKey = 'id';

    protected ?Closure $usingRelationshipQuery = null;

    public function relationship(Closure|string $relationship)
    {

        $this->relationship = $relationship;

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

    public function relationshipKey(?string $relationshipKey): static
    {
        $this->relationshipKey = $relationshipKey;

        return $this;
    }

    public function getRelationshipKey(): ?string
    {
        return $this->relationshipKey;
    }
    public function usingRelationshipQuery(Closure $callback): static
    {
        $this->usingRelationshipQuery = $callback;

        return $this;
    }

    public function getUsingRelationshipQuery($query, $request = null): ?Closure
    {
        return $this->evaluate($this->usingRelationshipQuery, [
            'query' => $query,
            'request' => $request,
        ]);
    }
}
