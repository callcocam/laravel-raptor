<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Actions\Types;

use Callcocam\LaravelRaptor\Support\Actions\Action;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * ModalAction - Abre um modal com formulário e envia via Inertia.js
 *
 * Exemplo de uso:
 * ModalAction::make('assign')
 *     ->label('Atribuir Departamento')
 *     ->icon('UserPlus')
 *     ->columns([
 *         TextInput::make('department_id')
 *             ->label('Departamento')
 *             ->required(),
 *         Textarea::make('notes')
 *             ->label('Observações'),
 *     ])
 *     ->modalSize('lg')
 *     ->confirm([
 *         'title' => 'Atribuir Usuário',
 *         'confirmText' => 'Atribuir',
 *     ])
 */
class ModalAction extends ExecuteAction
{
    protected string $actionType = 'modal';

    protected string|Closure|null $modalTitle = null;

    protected string|Closure|null $modalDescription = null;

    protected string|Closure|null $modalContent = null;

    protected string|Closure|null $modalType = 'normal';

    protected string|Closure|null $slideoverPosition = 'right';

    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? 'modal');
        $this
            ->actionType('actions')
            ->component('action-modal-form')
            ->method('POST')
            ->callback(function ($request = null) {
                return redirect()->back()->with('warning', 'Action executed successfully.');
            })
            ->modalSize('md');
    }


    public function modalTitle(string|Closure|null $modalTitle): self
    {
        $this->modalTitle = $modalTitle;

        return $this;
    }

    public function getModalTitle(array $context = []): ?string
    {
        return $this->evaluate($this->modalTitle, $context);
    }

    public function modalDescription(string|Closure|null $modalDescription): static
    {
        $this->modalDescription = $modalDescription;

        return $this;
    }

    public function getModalDescription(array $context = []): ?string
    {
        return $this->evaluate($this->modalDescription, $context);
    }

    public function modalContent(string|Closure|null $modalContent): self
    {
        $this->modalContent = $modalContent;

        return $this;
    }

    public function getModalContent(array $context = []): ?string
    {
        return $this->evaluate($this->modalContent, $context);
    }

    public function modalType(string|Closure|null $modalType): self
    {
        $this->modalType = $modalType;

        if ($modalType === 'slideover') {
            $this->component = 'action-modal-slideover';
        }

        return $this;
    }

    public function getModalType(array $context = []): ?string
    {
        return $this->evaluate($this->modalType, $context);
    }

    public function slideover(): self
    {
        return $this->modalType('slideover');
    }

    public function slideoverPosition(string|Closure|null $position): self
    {
        $this->slideoverPosition = $position;

        return $this;
    }

    public function getSlideoverPosition(array $context = []): ?string
    {
        return $this->evaluate($this->slideoverPosition, $context);
    }

    public function slideoverLeft(): self
    {
        return $this->slideover()->slideoverPosition('left');
    }

    public function slideoverRight(): self
    {
        return $this->slideover()->slideoverPosition('right');
    }

    /**
     * Renderiza a action com suporte a Inertia.js
     */
    public function render($model, $request = null): array
    {
        $this->request($request);

        $result = [
            'type' => 'action',
            'actionType' => $this->getActionType(),
            'url' => $this->getUrl($model),
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'method' => $this->getMethod(),
            'component' => $this->getComponent(),
            'tooltip' => $this->getTooltip(),
            'visible' => $this->isVisible($model),
            'modalTitle' => $this->getModalTitle(['record' => $model]),
            'modalDescription' => $this->getModalDescription(['record' => $model]),
            'modalContent' => $this->getModalContent(['record' => $model]),
            'modalType' => $this->getModalType(['record' => $model]),
            'slideoverPosition' => $this->getSlideoverPosition(['record' => $model]),
        ];

        if (! empty($this->confirm)) {
            $result['confirm'] = $this->evaluate($this->confirm, [
                'model' => $model,
                'record' => $model,
                'item' => $model,
            ]);
        }

        if (! empty($this->getColumns())) {
            $result['columns'] = $this->getArrayColumns();
        }

        $result['inertia'] = [
            'preserveScroll' => $this->preserveScroll,
            'preserveState' => $this->preserveState,
            'only' => $this->onlyProps,
        ];

        if ($this->callback) {
            $result['callback'] = $this->getEvaluatedCallback($model);
        }

        if ($this->modalSize) {
            $result['modalSize'] = $this->modalSize;
        }

        return $result;
    }
}
