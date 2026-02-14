<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

/**
 * Trait HasGridLayout
 *
 * Fornece funcionalidades de controle de layout em grid para componentes.
 * Suporta configuração de colunas, spans, ordem e responsividade.
 */
trait HasGridLayout
{
    use HasColSpan;

    /**
     * Número de colunas do grid interno
     */
    protected ?string $gridColumns = null;

    /**
     * Quantas colunas este item ocupa no grid pai
     */
    protected ?string $columnSpan = null;

    /**
     * Ordem de exibição
     */
    protected ?int $order = null;

    /**
     * Espaçamento entre items (gap)
     */
    protected ?string $gap = null;

    /**
     * Largura máxima do modal/dialog
     */
    protected ?string $maxWidth = null;

    /**
     * Grid columns por breakpoint (responsivo)
     */
    protected ?string $smCols = null;

    protected ?string $mdCols = null;

    protected ?string $lgCols = null;

    protected ?string $xlCols = null;

    /**
     * Column span por breakpoint (responsivo)
     */
    protected ?string $smSpan = null;

    protected ?string $mdSpan = null;

    protected ?string $lgSpan = null;

    protected ?string $xlSpan = null;

    /**
     * Define quantas colunas o grid interno terá
     *
     * @param  string  $columns  '1', '2', '3', '4', '5', '6', etc
     */
    public function gridColumns(string $columns): self
    {
        $this->gridColumns = $columns;

        return $this;
    }

    /**
     * Define como full
     */
    public function columnSpanFull(): self
    {
        $this->columnSpan = 'full';

        return $this;
    }

    /**
     * Define quantas colunas este item ocupa no grid pai
     *
     * @param  string  $span  '1', '2', '3', '4', 'full', etc
     */
    public function columnSpan(string $span): self
    {
        $this->columnSpan = $span;

        return $this;
    }

    /**
     * Define a ordem de exibição
     */
    public function order(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Define o espaçamento entre items do grid
     *
     * @param  string  $gap  '1', '2', '3', '4', '6', '8', etc (Tailwind spacing)
     */
    public function gap(string $gap): self
    {
        $this->gap = $gap;

        return $this;
    }

    /**
     * Define a largura máxima do modal/dialog
     *
     * @param  string  $width  'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl', 'full'
     */
    public function maxWidth(string $width): self
    {
        $this->maxWidth = $width;

        return $this;
    }

    /**
     * Define grid columns responsivo
     */
    public function responsiveGridColumns(?string $sm = null, ?string $md = null, ?string $lg = null, ?string $xl = null): self
    {
        if ($sm) {
            $this->smCols = $sm;
        }
        if ($md) {
            $this->mdCols = $md;
        }
        if ($lg) {
            $this->lgCols = $lg;
        }
        if ($xl) {
            $this->xlCols = $xl;
        }

        return $this;
    }

    /**
     * Define column span responsivo
     */
    public function responsiveColumnSpan(?string $sm = null, ?string $md = null, ?string $lg = null, ?string $xl = null): self
    {
        if ($sm) {
            $this->smSpan = $sm;
        }
        if ($md) {
            $this->mdSpan = $md;
        }
        if ($lg) {
            $this->lgSpan = $lg;
        }
        if ($xl) {
            $this->xlSpan = $xl;
        }

        return $this;
    }

    /**
     * Retorna as configurações de grid como array
     */
    public function getGridLayoutConfig(): array
    {
        return array_filter([
            'gridColumns' => $this->gridColumns,
            'columnSpan' => $this->columnSpan,
            'order' => $this->order,
            'gap' => $this->gap,
            'maxWidth' => $this->maxWidth,
            'responsive' => array_filter([
                'grid' => array_filter([
                    'sm' => $this->smCols,
                    'md' => $this->mdCols,
                    'lg' => $this->lgCols,
                    'xl' => $this->xlCols,
                ]),
                'span' => array_filter([
                    'sm' => $this->smSpan,
                    'md' => $this->mdSpan,
                    'lg' => $this->lgSpan,
                    'xl' => $this->xlSpan,
                ]),
            ]),
        ]);
    }

    /**
     * Getters para propriedades de grid
     */
    public function getGridColumns(): ?string
    {
        return $this->gridColumns;
    }

    public function getColumnSpan(): ?string
    {
        return $this->columnSpan;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function getGap(): ?string
    {
        return $this->gap;
    }

    public function getMaxWidth(): ?string
    {
        return $this->maxWidth;
    }
}
