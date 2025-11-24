# Refatoração do FormFieldRepeater - Componentes Modulares

## Visão Geral

O componente `FormFieldRepeater` foi refatorado em uma arquitetura modular, separando responsabilidades em componentes menores e mais fáceis de manter.

## Estrutura de Componentes

### 1. **RepeaterItem** (`repeater/RepeaterItem.vue`)

Componente responsável por renderizar cada item individual do repeater.

#### Recursos:
- **Header colapsável** com indicador visual (ChevronRight/ChevronDown)
- **Indicador de erro** no header quando há erros no item
- **Drag handle** (GripVertical) para reordenação visual
- **Botões de reordenação** (Move Up/Down) quando `orderable: true`
- **Botão de duplicar** (Copy icon) quando `allowDuplication: true`
- **Botão de remover** (Trash2 icon) com estilo destrutivo
- **Grid layout** (12 colunas) para os campos internos
- **Estado de arrastar** com visual feedback

#### Props:
```typescript
item: any              // Dados do item
itemId: string         // ID único do item
index: number          // Índice no array
isLast: boolean        // Se é o último item
fields: FormColumn[]   // Campos a renderizar
errors: any            // Erros de validação
collapsible: boolean   // Se permite colapsar
orderable: boolean     // Se permite reordenar
canRemove: boolean     // Se pode remover
canDuplicate: boolean  // Se pode duplicar
isDragging: boolean    // Estado de arrastar (opcional)
```

#### Eventos:
```typescript
updateField(fieldName: string, value: any)
remove(index: number)
duplicate(index: number)
moveUp(index: number)
moveDown(index: number)
dragStart(index: number)
```

---

### 2. **RepeaterActions** (`repeater/RepeaterActions.vue`)

Barra de ações com operações em lote e adicionar item.

#### Recursos:
- **Botão Add** com ícone Plus e label customizável
- **Collapse All** (ChevronsDown) - colapsa todos os itens
- **Expand All** (ChevronsUp) - expande todos os itens
- **Clear All** (Trash2) - remove todos os itens com confirmação
- Botões desabilitados quando não aplicável

#### Props:
```typescript
totalItems: number        // Total de itens
canAdd: boolean          // Se pode adicionar (maxItems)
canClearAll: boolean     // Se pode limpar tudo (minItems)
collapsible: boolean     // Se mostra collapse/expand
addButtonLabel: string   // Label do botão adicionar
```

#### Eventos:
```typescript
add()
clearAll()
collapseAll()
expandAll()
```

---

### 3. **RepeaterEmptyState** (`repeater/RepeaterEmptyState.vue`)

Estado vazio quando não há itens.

#### Recursos:
- **Ícone PackageOpen** (12x12, muted)
- **Título customizável** (padrão: "Nenhum item adicionado")
- **Descrição customizável** (padrão: "Clique no botão abaixo...")
- **Botão Add opcional** (quando permitido adicionar)

#### Props:
```typescript
emptyTitle: string          // Título do estado vazio
emptyDescription: string    // Descrição do estado vazio
addButtonLabel: string      // Label do botão
showAddButton: boolean      // Se mostra o botão
```

#### Eventos:
```typescript
add()
```

---

### 4. **FormFieldRepeater** (Principal)

Componente orquestrador que gerencia o estado e usa os componentes acima.

#### Responsabilidades:
- Gerenciar array de itens com IDs únicos
- Controlar estado de collapsible/orderable
- Validar min/max items
- Emitir mudanças para o parent
- Orquestrar os componentes menores

---

## Novas Funcionalidades

### 1. **Reordenação de Itens**

```php
RepeaterField::make('items')
    ->orderable()  // Ativa botões Move Up/Down
    ->fields([...])
```

Quando ativo, cada item terá:
- Drag handle (GripVertical) - preparado para drag-and-drop futuro
- Botão "Move Up" (desabilitado no primeiro item)
- Botão "Move Down" (desabilitado no último item)

### 2. **Duplicação de Itens**

```php
RepeaterField::make('items')
    ->allowDuplication()  // Ativa botão de duplicar
    ->fields([...])
```

Permite duplicar um item existente clonando todos os seus valores.

### 3. **Collapse/Expand All**

Quando `collapsible(true)`, a barra de ações mostra:
- **Collapse All**: Colapsa todos os itens de uma vez
- **Expand All**: Expande todos os itens de uma vez

### 4. **Clear All**

Remove todos os itens com confirmação, respeitando `minItems`.

### 5. **Estado Vazio Customizável**

```php
RepeaterField::make('items')
    ->emptyTitle('Sem produtos')
    ->emptyDescription('Adicione produtos ao pedido')
```

---

## Exemplo de Uso Completo

### Backend (PHP)

```php
use Callcocam\Raptor\Support\Form\Columns\Types\RepeaterField;
use Callcocam\Raptor\Support\Form\Columns\Types\TextField;
use Callcocam\Raptor\Support\Form\Columns\Types\NumberField;
use Callcocam\Raptor\Support\Form\Columns\Types\SelectField;

RepeaterField::make('order_items')
    ->label('Itens do Pedido')
    ->helpText('Adicione os produtos do pedido')
    ->minItems(1)           // Mínimo 1 item
    ->maxItems(20)          // Máximo 20 itens
    ->collapsible()         // Permite colapsar itens
    ->orderable()           // Permite reordenar
    ->allowDuplication()    // Permite duplicar
    ->addButtonLabel('Adicionar Produto')
    ->emptyTitle('Nenhum produto no pedido')
    ->emptyDescription('Clique em "Adicionar Produto" para começar')
    ->defaultItems([        // Item inicial
        ['quantity' => 1]
    ])
    ->fields([
        SelectField::make('product_id')
            ->label('Produto')
            ->required()
            ->columnSpan('6'),
            
        NumberField::make('quantity')
            ->label('Quantidade')
            ->required()
            ->default(1)
            ->columnSpan('3'),
            
        NumberField::make('price')
            ->label('Preço')
            ->required()
            ->columnSpan('3'),
            
        TextField::make('notes')
            ->label('Observações')
            ->columnSpan('12'),
    ])
```

### Frontend (Uso Automático)

O componente é automaticamente carregado pelo `ComponentRegistry` quando o tipo de campo é `repeater`.

---

## Benefícios da Refatoração

### 1. **Separação de Responsabilidades**
- Cada componente tem uma responsabilidade clara
- Mais fácil de testar individualmente
- Código mais legível

### 2. **Reutilização**
- `RepeaterItem` pode ser usado em outros contextos
- `RepeaterActions` pode ser reutilizada em listas similares
- `RepeaterEmptyState` é um padrão reutilizável

### 3. **Manutenibilidade**
- Mudanças em um componente não afetam os outros
- Fácil adicionar novas features
- Debugging mais simples

### 4. **Performance**
- Componentes menores são mais otimizáveis pelo Vue
- Re-renderizações mais granulares
- Tree-shaking mais eficiente

### 5. **Extensibilidade**
- Fácil adicionar drag-and-drop real (apenas no RepeaterItem)
- Fácil adicionar validações customizadas
- Suporte futuro para temas/variantes

---

## Próximos Passos (Opcional)

### 1. **Drag-and-Drop Real**
Implementar reordenação via drag-and-drop usando VueDraggable ou similar:
```bash
npm install vuedraggable@next
```

### 2. **Animações de Transição**
Adicionar transições suaves ao adicionar/remover itens:
```vue
<TransitionGroup name="repeater-item">
  <RepeaterItem ... />
</TransitionGroup>
```

### 3. **Validação em Tempo Real**
Mostrar erros de validação enquanto o usuário digita.

### 4. **Testes**
Criar testes unitários para cada componente:
- `RepeaterItem.test.ts`
- `RepeaterActions.test.ts`
- `RepeaterEmptyState.test.ts`

---

## Estrutura de Arquivos

```
resources/js/components/form/fields/
├── FormFieldRepeater.vue           # Componente principal orquestrador
└── repeater/                       # Pasta de componentes modulares
    ├── RepeaterItem.vue           # Item individual
    ├── RepeaterActions.vue        # Barra de ações
    └── RepeaterEmptyState.vue     # Estado vazio
```

---

## Notas Técnicas

### IDs Únicos
Cada item recebe um `_id` único gerado com:
```typescript
`item-${Date.now()}-${index}`
```

Isso garante que o Vue possa rastrear corretamente os itens mesmo após reordenação.

### Limpeza de Dados
Ao emitir para o parent, o `_id` é removido:
```typescript
const cleanItems = items.value.map(({ _id, ...rest }) => rest)
emit('update:modelValue', cleanItems)
```

### Erros Aninhados
O componente suporta erros no formato:
```typescript
{
  "0.product_id": "O produto é obrigatório",
  "1.quantity": "A quantidade deve ser maior que 0"
}
```

---

## Changelog

### v1.1.0 - Refatoração Modular

**Adicionado:**
- Componente `RepeaterItem` para itens individuais
- Componente `RepeaterActions` para barra de ações
- Componente `RepeaterEmptyState` para estado vazio
- Funcionalidade de reordenação (Move Up/Down)
- Funcionalidade de duplicação de itens
- Collapse All / Expand All
- Clear All com confirmação
- Estado vazio customizável

**Modificado:**
- `FormFieldRepeater` agora orquestra componentes menores
- Melhor organização do código
- Performance otimizada

**Backend:**
- Adicionado `allowDuplication()` ao RepeaterField
- Adicionado `emptyTitle()` ao RepeaterField
- Adicionado `emptyDescription()` ao RepeaterField
