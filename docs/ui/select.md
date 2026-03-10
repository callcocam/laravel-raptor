# SelectWithClear — Select Nativo com Limpeza

**Arquivo:** `packages/callcocam/laravel-raptor/resources/js/components/ui/select/SelectWithClear.vue`

Componente de seleção customizado, 100% nativo, sem dependências de `reka-ui` ou `shadcn-vue`. Projetado para trabalhar com dados do backend Raptor (Filters/Columns) ou com dados hardcoded.

---

## Funcionalidades

| Recurso | Detalhe |
|---------|---------|
| Nativo | Sem reka-ui, shadcn ou plugins externos |
| Formatos de opção | `{ id, name }`, `{ value, label }`, `string[]` ou qualquer objeto |
| Chaves configuráveis | `optionValue` / `optionLabel` para mapear qualquer estrutura |
| Botão limpar | `✕` integrado ao trigger — sem sobreposição com o chevron |
| Pesquisa | prop `:searchable` ativa campo de busca interno |
| Item selecionado | `✓` visível ao lado da opção ativa na lista |
| Navegação por teclado | `↑ ↓ Enter Esc Tab Home End` completamente suportados |
| Click outside | fecha o dropdown sem bibliotecas externas |
| Animação | `scale + fade` (100ms) ao abrir/fechar |
| Dark mode | segue variáveis CSS do tema automaticamente |
| Acessibilidade | `role="combobox"`, `aria-expanded`, `role="listbox"`, `aria-selected` |

---

## Props

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `modelValue` | `string \| null` | `null` | Valor selecionado (v-model) |
| `options` | `RawOption[]` | `[]` | Lista de opções (ver formatos abaixo) |
| `placeholder` | `string` | `'Selecionar…'` | Texto exibido quando não há seleção |
| `label` | `string` | — | Label exibida acima do trigger |
| `disabled` | `boolean` | `false` | Desabilita o componente |
| `searchable` | `boolean` | `false` | Ativa campo de pesquisa no dropdown |
| `optionValue` | `string` | `'id'` | Chave do objeto usada como **valor** |
| `optionLabel` | `string` | `'name'` | Chave do objeto usada como **label** |
| `class` | `string` | — | Classe extra para o wrapper (útil para `w-*`) |

## Eventos

| Evento | Payload | Descrição |
|--------|---------|-----------|
| `update:modelValue` | `string \| null` | Compatível com `v-model` |
| `change` | `string \| null` | Emitido a cada mudança (igual ao update) |

---

## Formatos de Opção

O componente normaliza automaticamente qualquer formato:

```typescript
// 1. Padrão Raptor (backend Filters/Columns)
{ id: '1', name: 'LOJA 01' }

// 2. Formato frontend comum
{ value: 'loja-01', label: 'LOJA 01' }

// 3. Array simples de strings
'pendente'

// 4. Objeto com chaves customizadas
{ slug: 'loja-01', titulo: 'LOJA 01' }
// → use :option-value="'slug'" :option-label="'titulo'"
```

A prioridade de resolução das chaves é:
- **Valor**: `optionValue` → `id` → `value` → string do próprio item
- **Label**: `optionLabel` → `name` → `label` → valor resolvido

---

## Exemplos de Uso

### Básico (dados do backend Raptor)

```vue
<SelectWithClear
    v-model="filtros.loja_id"
    label="Loja"
    placeholder="Todas"
    :options="lojas"
/>
```

### Com pesquisa (listas longas)

```vue
<SelectWithClear
    v-model="filtros.planogram_id"
    label="Planograma"
    placeholder="Todos"
    :options="planograms"
    :searchable="planograms.length > 6"
    class="w-48"
/>
```

### Opções hardcoded

```vue
<SelectWithClear
    v-model="filtros.status"
    label="Status"
    placeholder="Todos"
    :options="[
        { id: 'pending',     name: 'Pendente' },
        { id: 'in_progress', name: 'Em Andamento' },
        { id: 'blocked',     name: 'Bloqueada' },
    ]"
    class="w-36"
/>
```

### Chaves customizadas (dados do backend com estrutura diferente)

```vue
<!-- Backend retorna: { slug: 'cat-01', titulo: 'Categoria A' } -->
<SelectWithClear
    v-model="filtros.categoria"
    label="Categoria"
    :options="categorias"
    option-value="slug"
    option-label="titulo"
/>
```

### Controlando largura

```vue
<!-- w-* via prop class -->
<SelectWithClear v-model="val" :options="opts" class="w-44" />
<SelectWithClear v-model="val" :options="opts" class="w-full" />
<SelectWithClear v-model="val" :options="opts" class="min-w-[200px]" />
```

---

## Integração com Backend Raptor

### Via `Filter` (Support/Table/Filters)

O `SelectWithClear` aceita diretamente os dados retornados pelos filters do Raptor. O formato padrão `{ id, name }` é o default do componente:

```php
// No Controller
public function index(): Response
{
    return Inertia::render('MinhaPage', [
        'lojas'      => Loja::query()->select('id', 'name')->get(),
        'planograms' => Planogram::query()->select('id', 'name')->get(),
    ]);
}
```

```vue
<!-- No componente Vue -->
<SelectWithClear
    v-model="filtros.loja_id"
    label="Loja"
    :options="lojas"
/>
```

### Via `SelectColumn` (Support/Table/Columns)

```php
// Na definição da tabela do Raptor
SelectColumn::make('status')
    ->label('Status')
    ->options([
        'active'   => 'Ativo',
        'inactive' => 'Inativo',
    ])
```

Para este formato (chave → valor), use `string[]` ou adapte com `optionValue`/`optionLabel`:

```vue
<!-- Opções pré-mapeadas para { id, name } -->
<SelectWithClear
    v-model="filtros.status"
    :options="statusOptions"
/>
```

```typescript
// Mapeamento no script
const statusOptions = Object.entries(props.statusMap).map(([id, name]) => ({ id, name }))
```

---

## Design do Trigger

O trigger foi projetado para evitar a sobreposição de ícones — problema comum em implementações com shadcn/reka:

```
┌─────────────────────────────────┐
│  Texto selecionado        [✕][▼] │  ← quando há valor
└─────────────────────────────────┘

┌─────────────────────────────────┐
│  Placeholder…                [▼] │  ← sem valor
└─────────────────────────────────┘
```

- `✕` está em um `<span role="button">` com `@mousedown.stop` — impede que o clique abra o dropdown junto com o clear
- `▼` (ChevronDown) sempre visível, rotaciona 180° quando o dropdown está aberto
- Ambos ficam dentro de um `<span class="flex items-center">` no lado direito do trigger

---

## Navegação por Teclado

| Tecla | Ação |
|-------|------|
| `↓` / `↑` | Move o highlight entre as opções |
| `Enter` / `Space` | Abre o dropdown / confirma a opção destacada |
| `Esc` / `Tab` | Fecha o dropdown |
| `Home` | Vai para a primeira opção |
| `End` | Vai para a última opção |

Quando `searchable=true`, o foco vai automaticamente para o campo de pesquisa ao abrir o dropdown. A navegação por seta também funciona a partir do campo de pesquisa.

---

## Onde é Usado

| Arquivo | Contexto |
|---------|---------|
| `resources/js/components/kanban/KanbanHeader.vue` | Filtros do board Kanban (Loja, Planograma, Função, Usuário, Status) |

Para usar o componente em outros arquivos da aplicação:

```typescript
import SelectWithClear from '~/components/ui/select/SelectWithClear.vue'
// onde ~ = packages/callcocam/laravel-raptor/resources/js
```
