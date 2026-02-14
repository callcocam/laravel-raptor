<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table;

use Callcocam\LaravelRaptor\Support\Concerns\EvaluatesClosures;
use Callcocam\LaravelRaptor\Support\Concerns\FactoryPattern;

class Confirm
{
    use EvaluatesClosures;
    use FactoryPattern;

    /**
     * 'title' => 'Importar Registros',
     * 'text' => 'Tem certeza que deseja importar os registros?',
     *  'confirmButtonText' => 'Sim, Importar',
     * 'cancelButtonText' => 'Cancelar',
     * 'successMessage' => 'Importação iniciada com sucesso, assim que terminarmos avisaremos você!',
     * 'closeModalOnSuccess' => false, // Não fecha o modal automaticamente
     */
    public function __construct(
        public string $title = 'Confirmar ação',
        public string $text = 'Tem certeza que deseja continuar?',
        public string $confirmButtonText = 'Confirmar',
        public string $cancelButtonText = 'Cancelar',
        public ?string $successMessage = null,
        public bool $closeModalOnSuccess = true,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'text' => $this->text,
            'confirmButtonText' => $this->confirmButtonText,
            'cancelButtonText' => $this->cancelButtonText,
            'successMessage' => $this->successMessage,
            'closeModalOnSuccess' => $this->closeModalOnSuccess,
        ];
    }
}
