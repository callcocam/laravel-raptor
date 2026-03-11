# Formulários — Laravel Raptor

Documentação do sistema de formulários do Raptor.

## Documentação por Seção

| Documento | Descrição |
|-----------|-----------|
| [section-field.md](./section-field.md) | `SectionField` — agrupamento visual de campos (flat/nested), accordion, erros de validação |
| [columns.md](./columns.md) | Colunas especiais para InfoList/Show |
| [hints.md](./hints.md) | Sistema de hints e tooltips nos campos |

---

## Fluxo de dados do formulário

```
FormRenderer.vue
  ↓ formData (Inertia useForm)
  ↓ formErrors (form.errors)
  ↓ columns[] (vindo do backend via Inertia props)
  ↓
FieldRenderer.vue  ←→  ComponentRegistry (resolve component pelo column.component)
  ↓
FormFieldXxx.vue   (ex: FormFieldSection, FormFieldSelect, FormFieldCascading...)
```

### SectionField flat vs outros campos

O `FormRenderer` diferencia seções flat dos demais campos para passar os erros corretamente:

```typescript
// FormRenderer.vue
const isFlatSection = (column) =>
    column.component === 'form-field-section' && column.flat !== false

// Para seções flat: passa formErrors COMPLETO (erros dos filhos estão no nível raiz)
// Para demais campos: passa formErrors[column.name] (erro específico do campo)
:error="isFlatSection(column) ? formErrors : formErrors[column.name]"
```

### Por que `component === 'form-field-section'` e não `column.fields`?

Outros campos como `CascadingField` (`form-field-cascading`) também têm `fields` no array,
mas NÃO são flat — armazenam dados aninhados sob seu nome. A distinção pelo `component`
garante que apenas `SectionField` recebe o tratamento flat.

---

## Criação de novos campos

Use `php artisan make:class` para criar uma nova classe de campo:

```bash
php artisan make:class "Support/Form/Columns/Types/MyField"
```

Extenda `Column` e registre o componente Vue:

```php
class MyField extends Column
{
    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-my');
    }
}
```

Registre em `raptor/index.ts`:

```typescript
FormRegistry.register('form-field-my', defineAsyncComponent(() =>
    import('~/components/form/fields/FormFieldMy.vue')
))
```
