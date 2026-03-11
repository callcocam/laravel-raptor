# Componentes UI Base

Todos os componentes são nativos (sem reka-ui/shadcn-vue), localizados em:

```
packages/callcocam/laravel-raptor/resources/js/components/ui/
```

---

## Button

```
ui/button/Button.vue
ui/button/index.ts   ← CVA variants
```

### Uso

```vue
<Button variant="default" size="default" :disabled="false">
    Salvar
</Button>

<Button variant="destructive" size="sm" @click="handleDelete">
    Excluir
</Button>

<!-- Como link -->
<Button as="a" href="/dashboard" variant="ghost">
    Dashboard
</Button>
```

### Props

| Prop | Tipo | Padrão | Valores |
|------|------|--------|---------|
| `as` | `string \| object` | `'button'` | Qualquer tag HTML ou componente Vue |
| `variant` | `string` | `'default'` | `default`, `destructive`, `outline`, `secondary`, `ghost`, `link`, `success`, `warning` |
| `size` | `string` | `'default'` | `default`, `sm`, `lg`, `icon` |
| `disabled` | `boolean` | `false` | — |
| `type` | `string` | `'button'` | `button`, `submit`, `reset` |

### Variantes

| Variante | Uso |
|----------|-----|
| `default` | Ação principal — usa `bg-primary` |
| `destructive` | Ações destrutivas — vermelho |
| `outline` | Ação secundária com borda |
| `secondary` | Ação secundária sem borda |
| `ghost` | Sem fundo, hover sutil |
| `link` | Aparência de link |
| `success` | Confirmação — verde |
| `warning` | Atenção — amarelo/amber |

### Prop `as`

Permite renderizar o botão como qualquer elemento ou componente:

```vue
<!-- Renderiza como <a> -->
<Button as="a" href="/link">Link Button</Button>

<!-- Renderiza como Inertia Link -->
<Button :as="Link" :href="route('home')">Home</Button>
```

---

## Input

```
ui/input/Input.vue
```

### Uso

```vue
<Input
    v-model="value"
    type="text"
    placeholder="Digite aqui..."
    :error="errors.name"
/>
```

### Props

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `modelValue` | `string \| number` | — | Valor (v-model) |
| `type` | `string` | `'text'` | Tipo do input |
| `placeholder` | `string` | — | Placeholder |
| `disabled` | `boolean` | `false` | Desabilitado |
| `error` | `string` | — | Mensagem de erro (aplica borda vermelha) |
| `class` | `string` | — | Classes extras |

### Estados Visuais

```
Normal:  border-input bg-background focus:ring-ring
Erro:    border-destructive focus:ring-destructive
Dark:    dark:bg-input/30 dark:border-input
```

---

## Label

```
ui/label/Label.vue
```

### Uso

```vue
<Label for="email" :required="true">
    E-mail
</Label>
```

### Props

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `for` | `string` | — | ID do input associado |
| `required` | `boolean` | `false` | Exibe asterisco `*` vermelho |

---

## Badge

```
ui/badge/Badge.vue
```

### Uso

```vue
<Badge variant="default">Ativo</Badge>
<Badge variant="destructive">Inativo</Badge>
<Badge variant="success">Aprovado</Badge>
<Badge variant="warning">Pendente</Badge>
<Badge variant="info">Informação</Badge>
<Badge variant="outline">Outline</Badge>
```

### Variantes

| Variante | Cor |
|----------|-----|
| `default` | Primário (tema) |
| `secondary` | Cinza secundário |
| `destructive` | Vermelho |
| `outline` | Transparente com borda |
| `success` | Verde |
| `warning` | Amarelo/amber |
| `info` | Azul |

---

## Card

```
ui/card/
├── Card.vue
├── CardHeader.vue
├── CardTitle.vue
├── CardDescription.vue
├── CardContent.vue
├── CardFooter.vue
└── CardAction.vue
```

### Uso

```vue
<Card>
    <CardHeader>
        <CardTitle>Título do Card</CardTitle>
        <CardDescription>Descrição opcional</CardDescription>
        <CardAction>
            <Button variant="ghost" size="icon">
                <MoreHorizontal class="size-4" />
            </Button>
        </CardAction>
    </CardHeader>

    <CardContent>
        <!-- conteúdo principal -->
    </CardContent>

    <CardFooter>
        <Button>Salvar</Button>
    </CardFooter>
</Card>
```

### Estrutura HTML Gerada

```html
<div class="rounded-xl border bg-card text-card-foreground shadow-sm">
    <!-- CardHeader -->
    <div class="flex flex-col gap-1.5 p-6 pb-4">
        <h3 class="text-base font-semibold leading-none">Título</h3>
        <p class="text-sm text-muted-foreground">Descrição</p>
    </div>
    <!-- CardContent -->
    <div class="p-6 pt-0">...</div>
    <!-- CardFooter -->
    <div class="flex items-center p-6 pt-0">...</div>
</div>
```

---

## Separator

```
ui/separator/Separator.vue
```

### Uso

```vue
<!-- Horizontal (padrão) -->
<Separator />

<!-- Vertical -->
<Separator orientation="vertical" class="h-6" />

<!-- Com decoração -->
<Separator decorative />
```

### Props

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `orientation` | `'horizontal' \| 'vertical'` | `'horizontal'` | Direção |
| `decorative` | `boolean` | `false` | Remove semântica (`role="none"`) |

---

## Skeleton

```
ui/skeleton/Skeleton.vue
```

Usado para estados de carregamento (loading placeholders).

### Uso

```vue
<!-- Linha de texto -->
<Skeleton class="h-4 w-48" />

<!-- Avatar circular -->
<Skeleton class="size-10 rounded-full" />

<!-- Card completo -->
<div class="flex items-center gap-3">
    <Skeleton class="size-10 rounded-full" />
    <div class="space-y-2">
        <Skeleton class="h-4 w-32" />
        <Skeleton class="h-3 w-24" />
    </div>
</div>
```

Renderiza um `<div>` com `animate-pulse bg-muted rounded-md`.

---

## Spinner

```
ui/spinner/Spinner.vue
```

### Uso

```vue
<Spinner />
<Spinner size="sm" />
<Spinner size="lg" class="text-primary" />
```

### Props

| Prop | Tipo | Padrão | Valores |
|------|------|--------|---------|
| `size` | `string` | `'default'` | `sm`, `default`, `lg` |

### Tamanhos

| Size | Dimensão |
|------|----------|
| `sm` | `size-4` (16px) |
| `default` | `size-5` (20px) |
| `lg` | `size-6` (24px) |

SVG animado com `animate-spin`, usa `currentColor` para herdar a cor do texto pai.

---

## Select

```
ui/select/
├── Select.vue          ← raiz (provide/inject)
├── SelectTrigger.vue   ← botão de abertura
├── SelectValue.vue     ← exibe o label selecionado ou placeholder
├── SelectContent.vue   ← dropdown (posicionado absolute)
├── SelectItem.vue      ← cada opção
├── SelectGroup.vue     ← agrupador visual
├── SelectLabel.vue     ← rótulo de grupo
├── SelectSeparator.vue ← divisor
├── SelectWithClear.vue ← variante com botão de limpar
└── index.ts
```

### Uso básico

```vue
<Select v-model="value">
    <SelectTrigger>
        <SelectValue placeholder="Selecione..." />
    </SelectTrigger>
    <SelectContent>
        <SelectItem value="opt1" label="Opção 1">Opção 1</SelectItem>
        <SelectItem value="opt2" label="Opção 2">Opção 2</SelectItem>
    </SelectContent>
</Select>
```

> **Importante**: sempre passe `:label` idêntico ao texto do slot em cada `SelectItem`.
> Isso permite que `SelectValue` exiba o label correto ao carregar com valor pré-selecionado,
> sem precisar abrir o dropdown.

### Com grupo

```vue
<SelectContent>
    <SelectGroup>
        <SelectLabel>Frutas</SelectLabel>
        <SelectItem value="apple" label="Maçã">Maçã</SelectItem>
        <SelectItem value="banana" label="Banana">Banana</SelectItem>
    </SelectGroup>
    <SelectSeparator />
    <SelectGroup>
        <SelectLabel>Legumes</SelectLabel>
        <SelectItem value="carrot" label="Cenoura">Cenoura</SelectItem>
    </SelectGroup>
</SelectContent>
```

### Props

**`Select`**

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `modelValue` (v-model) | `string \| number \| null` | — | Valor selecionado |
| `disabled` | `boolean` | `false` | Desabilita o select |
| `required` | `boolean` | `false` | Campo obrigatório |

**`SelectItem`**

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `value` | `string` | — | Valor da opção (obrigatório) |
| `label` | `string` | — | Label exibido no trigger (obrigatório para pré-seleção) |
| `disabled` | `boolean` | `false` | Desabilita a opção |

### Funcionamento interno

O `Select` usa `provide/inject` para compartilhar estado entre os subcomponentes:
- `SelectContent` usa `v-show` (não `v-if`) para manter os `SelectItem`s sempre montados
- `SelectItem` registra seu label **sincronamente no `setup()`** via `registerItem`
- `SelectValue` lê do registro para exibir o label correto desde a primeira renderização
- Fecha ao clicar fora (`pointerdown` no `Select` root com `data-select-root`) ou pressionar `Escape`

---

## Field (primitivos de formulário)

```
ui/field/
├── Field.vue            ← wrapper com orientação
├── FieldLabel.vue       ← rótulo acessível
├── FieldDescription.vue ← texto de ajuda
├── FieldError.vue       ← exibe erros de validação
├── FieldSet.vue         ← fieldset semântico
├── FieldLegend.vue      ← legenda do fieldset
├── FieldGroup.vue       ← grupo de campos inline
└── index.ts
```

### Uso

```vue
<Field orientation="vertical" :data-invalid="hasError">
    <FieldLabel for="name">
        Nome <span class="text-destructive">*</span>
    </FieldLabel>

    <Input id="name" v-model="name" />

    <FieldDescription>Digite seu nome completo.</FieldDescription>
    <FieldError :errors="[{ message: 'Nome é obrigatório.' }]" />
</Field>
```

### Props `FieldError`

| Prop | Tipo | Descrição |
|------|------|-----------|
| `errors` | `Array<{ message: string }>` | Lista de erros a exibir |

---

## Checkbox (nativo)

```
ui/checkbox/Checkbox.vue
```

### Uso

```vue
<Checkbox v-model:checked="ativo" :indeterminate="parcial" />
```

### Props

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `checked` (v-model) | `boolean` | `false` | Estado marcado |
| `indeterminate` | `boolean` | `false` | Estado intermediário |
| `disabled` | `boolean` | `false` | Desabilitado |

---

## Textarea (nativo)

```
ui/textarea/Textarea.vue
```

### Uso

```vue
<Textarea v-model="descricao" placeholder="Digite aqui..." :rows="4" />
```

---

## Collapsible

```
ui/collapsible/
├── Collapsible.vue         ← raiz com provide
├── CollapsibleTrigger.vue  ← botão toggle
├── CollapsibleContent.vue  ← conteúdo animado
└── index.ts
```

### Uso

```vue
<Collapsible :default-open="true">
    <CollapsibleTrigger as-child>
        <Button variant="ghost">
            Configurações avançadas <ChevronsUpDown class="ml-2 h-4 w-4" />
        </Button>
    </CollapsibleTrigger>
    <CollapsibleContent>
        <!-- conteúdo que expande/colapsa com transição -->
    </CollapsibleContent>
</Collapsible>
```

### Props `Collapsible`

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `defaultOpen` | `boolean` | `false` | Abre ao montar |

---

## Popover

```
ui/popover/
├── Popover.vue         ← raiz com provide
├── PopoverTrigger.vue  ← elemento que abre
├── PopoverContent.vue  ← conteúdo flutuante (absolute)
└── index.ts
```

### Uso

```vue
<Popover>
    <PopoverTrigger as-child>
        <Button variant="outline">Abrir popover</Button>
    </PopoverTrigger>
    <PopoverContent class="w-80">
        <p>Conteúdo do popover</p>
    </PopoverContent>
</Popover>
```

Fecha ao pressionar `Escape` ou clicar fora (via `useEventListener`).

---

## Command (paleta de comandos / combobox)

```
ui/command/
├── Command.vue       ← raiz com busca via provide
├── CommandInput.vue  ← campo de busca
├── CommandList.vue   ← lista filtrada
├── CommandGroup.vue  ← grupo de itens
├── CommandItem.vue   ← cada item
├── CommandEmpty.vue  ← estado vazio
└── index.ts
```

### Uso (combobox)

```vue
<Command>
    <CommandInput placeholder="Buscar..." />
    <CommandList>
        <CommandEmpty>Nenhum resultado.</CommandEmpty>
        <CommandGroup heading="Sugestões">
            <CommandItem value="laravel" @select="select('laravel')">
                Laravel
            </CommandItem>
            <CommandItem value="vue" @select="select('vue')">
                Vue
            </CommandItem>
        </CommandGroup>
    </CommandList>
</Command>
```

---

## Como Importar os Componentes

### No pacote (componentes internos)

```typescript
// Importação relativa dentro do pacote
import { Button } from '../button'
import { Input } from '../input'
import { Spinner } from '../spinner'
```

### Na aplicação

```typescript
// Usando o alias do pacote (~)
import { Button } from '~/components/ui/button'
import { Input } from '~/components/ui/input'
import Badge from '~/components/ui/badge/Badge.vue'
```

Ou via auto-import se configurado no `vite.config.ts`.

---

## Dark Mode

Todos os componentes suportam dark mode via `dark:` do TailwindCSS. As variáveis CSS são definidas em `resources/css/app.css`:

```css
:root {
    --background: hsl(0 0% 100%);
    --foreground: hsl(0 0% 3.9%);
    --primary: hsl(0 0% 9%);
    /* ... */
}

.dark {
    --background: hsl(0 0% 3.9%);
    --foreground: hsl(0 0% 98%);
    --primary: hsl(0 0% 98%);
    /* ... */
}
```
