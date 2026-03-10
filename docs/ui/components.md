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
