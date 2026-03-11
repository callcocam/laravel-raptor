# SectionField — Agrupamento de Campos

O `SectionField` organiza campos de formulário em grupos visuais com título e suporte a accordion (colapsável). Ele **não cria um campo no banco de dados** — é puramente estrutural por padrão.

---

## Modos de operação

### Modo flat (padrão)

Os campos-filhos vivem diretamente no `formData` raiz. O nome da seção (`additional_data_section`) é ignorado no request e no banco de dados.

```php
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SectionField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SelectField;

SectionField::make('additional_data_section', 'Dados Adicionais')
    ->helpText('Informações adicionais sobre o produto')
    ->fields([
        TextField::make('type', 'Tipo')->columnSpan(4),
        TextField::make('brand', 'Marca')->columnSpan(4),
        TextField::make('color', 'Cor')->columnSpan(4),
    ])
```

**Resultado no request:** `{ type: 'X', brand: 'Y', color: 'Z' }` — sem a chave `additional_data_section`.

### Modo nested (explícito)

Os campos-filhos ficam aninhados sob o nome da seção. Ideal para JSON cast ou relacionamentos.

```php
SectionField::make('settings', 'Configurações')
    ->nested()   // ou ->flat(false)
    ->fields([
        TextField::make('key', 'Chave'),
        TextField::make('value', 'Valor'),
    ])
```

**Resultado no request:** `{ settings: { key: 'X', value: 'Y' } }`.

---

## API do SectionField

| Método | Tipo | Padrão | Descrição |
|--------|------|--------|-----------|
| `make(name, label)` | `static` | — | Cria a seção |
| `->fields([...])` | `static` | `[]` | Campos-filhos |
| `->collapsible(bool)` | `static` | `false` | Torna a seção colapsável (accordion) |
| `->defaultOpen(bool)` | `static` | `false` | Abre o accordion ao carregar |
| `->flat(bool)` | `static` | `true` | Modo flat (campos no formData raiz) |
| `->nested(bool)` | `static` | — | Atalho para `->flat(false)` |
| `->helpText(string)` | `static` | — | Texto descritivo abaixo do título |
| `->label(string)` | `static` | — | Título da seção |

---

## Com accordion (collapsible)

```php
SectionField::make('dimensions_section', 'Dimensões')
    ->helpText('Dimensões físicas do produto')
    ->collapsible()
    ->defaultOpen(true)
    ->fields([
        TextField::make('width', 'Largura (cm)')->columnSpan(3),
        TextField::make('height', 'Altura (cm)')->columnSpan(3),
        TextField::make('depth', 'Profundidade (cm)')->columnSpan(3),
        TextField::make('weight', 'Peso (g)')->columnSpan(3),
    ])
```

---

## Comportamento no backend

O `InteractWithForm` detecta automaticamente seções flat (`isFlat() === true`) e as trata de forma transparente:

| Operação | Comportamento flat | Comportamento nested |
|----------|-------------------|----------------------|
| `getValidationRules()` | Gera regras para cada campo-filho individualmente | Gera regra para o nome da seção |
| `prepareDataForValidation()` | Remove a chave da seção do `$data`; processa `valueUsing` dos filhos | Processa normalmente |
| `getFormData()` | Remove a chave da seção; aplica `valueUsing` dos filhos no nível raiz | Processa normalmente |
| `getValidationMessages()` | Coleta mensagens customizadas dos filhos | Coleta do campo-seção |
| `model->create($validated)` | Nenhuma chave de seção no `$validated` | Chave de seção presente |

---

## Comportamento no frontend

O `FormFieldSection.vue` detecta `column.flat !== false` e:

- **Leitura**: injeta o `formData` raiz (via `provide('formData')` do `FormRenderer`) e lê cada campo-filho diretamente de lá
- **Escrita**: emite `createMultiFieldUpdate({ fieldName: value })` — cada campo atualiza sua própria chave no `formData` raiz
- **Erros**: o `FormRenderer` passa o objeto completo de `formErrors` para seções flat, permitindo que `FormFieldSection` exiba `formErrors['unit']`, `formErrors['brand']`, etc.

---

## Erros de validação

Erros de campos dentro de uma seção flat aparecem automaticamente em cada campo. Não é necessária nenhuma configuração adicional.

```php
// Backend: regras geradas automaticamente para os filhos
// { 'type' => ['nullable'], 'brand' => ['required', 'string'], ... }

// Frontend: FormRenderer detecta a seção flat e passa formErrors completo
// FormFieldSection passa formErrors['brand'] para o campo 'brand'
```

---

## Exemplo completo (ProductController)

```php
protected function form($form): mixed
{
    return $form->columns([

        // Seção flat com accordion — campos salvos diretamente no model
        SectionField::make('dimensions_section', 'Dimensões')
            ->helpText('Dimensões físicas do produto')
            ->collapsible()
            ->defaultOpen(true)
            ->fields([
                NumberField::make('width', 'Largura (cm)')
                    ->columnSpan(3),
                NumberField::make('height', 'Altura (cm)')
                    ->columnSpan(3),
                NumberField::make('depth', 'Profundidade (cm)')
                    ->columnSpan(3),
                NumberField::make('weight', 'Peso (g)')
                    ->columnSpan(3),
                SelectField::make('unit', 'Unidade')
                    ->required()
                    ->options(['cm' => 'cm', 'mm' => 'mm', 'in' => 'in'])
                    ->columnSpan(3),
            ]),

        // Seção flat sem accordion — campos salvos diretamente no model
        SectionField::make('additional_data_section', 'Dados Adicionais')
            ->helpText('Informações adicionais sobre o produto')
            ->fields([
                TextField::make('type', 'Tipo')->columnSpan(4),
                TextField::make('brand', 'Marca')->columnSpan(4),
                TextField::make('color', 'Cor')->columnSpan(4),
            ]),
    ]);
}
```

---

## Diferença entre SectionField e CascadingField

| | SectionField (flat) | CascadingField |
|--|---------------------|----------------|
| Chave no request | **Removida** automaticamente | Presente (`mercadologico_cascading`) |
| Campos-filhos | Nível raiz do `formData` | Aninhados sob a chave |
| Banco de dados | Campos individuais no model | JSON cast ou relacionamento |
| Erros de validação | Nível raiz (`errors.brand`) | Aninhados (`errors.mercadologico_cascading.campo`) |
| Componente Vue | `form-field-section` | `form-field-cascading` |
