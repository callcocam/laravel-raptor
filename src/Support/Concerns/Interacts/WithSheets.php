<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\Import\Columns\Sheet;

trait WithSheets
{
    protected array $sheets = [];

    /**
     * Define as sheets para importação
     */
    public function sheets(array $sheets): static
    {
        $this->sheets = $sheets;

        return $this;
    }

    /**
     * Adiciona uma sheet individual
     */
    public function addSheet(Sheet $sheet): static
    {
        $this->sheets[] = $sheet;

        return $this;
    }

    /**
     * Retorna todas as sheets
     */
    public function getSheets(): array
    {
        return $this->sheets;
    }

    /**
     * Verifica se tem sheets configuradas
     */
    public function hasSheets(): bool
    {
        return ! empty($this->sheets);
    }

    /**
     * Retorna apenas as sheets principais (não relacionadas)
     */
    public function getMainSheets(): array
    {
        return array_filter($this->sheets, function ($sheet) {
            return $sheet instanceof Sheet && ! $sheet->isRelatedSheet();
        });
    }

    /**
     * Retorna todas as sheets serializadas para array
     */
    public function getSheetsAsArray(): array
    {
        return array_map(function ($sheet) {
            return $sheet instanceof Sheet ? $sheet->toArray() : $sheet;
        }, $this->sheets);
    }

    /**
     * Limpa todas as sheets
     */
    public function clearSheets(): static
    {
        $this->sheets = [];

        return $this;
    }
}
