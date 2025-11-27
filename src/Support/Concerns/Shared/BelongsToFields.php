<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Closure;

trait BelongsToFields
{
    protected ?array $fields = [];

    protected Closure|string|null $fieldsUsing = null;

    public function fields(array $fields): static
    {
        foreach ($fields as $order => $field) {
            $this->addField($field, $order);
        }
        return $this;
    }

    public function getField(string $name): ?AbstractColumn
    {
        return collect($this->fields)->firstWhere('name', $name);
    }

    public function addField(AbstractColumn $field, $order = 0): static
    {
        $this->fields[] = $field->order($order)->relationshipName($this->getRelationshipName() ?? $this->getName());
        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    public function getFieldsForForm(): array
    {
        return array_map(fn(AbstractColumn $field) => $field->toArray(), $this->fields);
    }

    public function fieldsUsing(Closure|string|null $callback): static
    {
        $this->fieldsUsing = $callback;

        return $this;
    }

    public function getFieldsUsing(): Closure|string|null
    { 
        return $this->evaluate($this->fieldsUsing);
    }
}
