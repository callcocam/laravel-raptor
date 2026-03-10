# Sistema de Temas

## Visão Geral

O sistema de temas usa variáveis CSS (CSS Custom Properties) com TailwindCSS v4. A paleta de cores é definida em `resources/css/app.css` e sobrescrita por temas específicos em `resources/css/themes.css`.

---

## Arquivos

| Arquivo | Função |
|---------|--------|
| `resources/css/app.css` | Variáveis padrão (`:root` e `.dark`), importação do Tailwind |
| `resources/css/themes.css` | Temas específicos (plannerate, blue, green, etc.) |

---

## Variáveis CSS Principais

### Cores Base (`:root` — light mode)

```css
:root {
    --background: hsl(0 0% 100%);
    --foreground: hsl(0 0% 3.9%);
    --card: hsl(0 0% 100%);
    --card-foreground: hsl(0 0% 3.9%);
    --popover: hsl(0 0% 100%);
    --popover-foreground: hsl(0 0% 3.9%);
    --primary: hsl(0 0% 9%);
    --primary-foreground: hsl(0 0% 98%);
    --secondary: hsl(0 0% 96.1%);
    --secondary-foreground: hsl(0 0% 9%);
    --muted: hsl(0 0% 96.1%);
    --muted-foreground: hsl(0 0% 45.1%);
    --accent: hsl(0 0% 96.1%);
    --accent-foreground: hsl(0 0% 9%);
    --destructive: hsl(0 84.2% 60.2%);
    --border: hsl(0 0% 89.8%);
    --input: hsl(0 0% 89.8%);
    --ring: hsl(0 0% 3.9%);
    --radius: 0.625rem;
}
```

### Cores da Sidebar

```css
:root {
    --sidebar-background: hsl(0 0% 98%);
    --sidebar-foreground: hsl(240 5.3% 26.1%);
    --sidebar-primary: hsl(240 5.9% 10%);
    --sidebar-primary-foreground: hsl(0 0% 98%);
    --sidebar-accent: hsl(240 4.8% 95.9%);
    --sidebar-accent-foreground: hsl(240 5.9% 10%);
    --sidebar-border: hsl(220 13% 91%);
    --sidebar-ring: hsl(217.2 91.2% 59.8%);
}
```

### Dark Mode (`.dark`)

```css
.dark {
    --background: hsl(0 0% 3.9%);
    --foreground: hsl(0 0% 98%);
    --sidebar-background: hsl(0 0% 7%);
    --sidebar-foreground: hsl(240 4.8% 95.9%);
    --sidebar-primary: hsl(0 0% 98%);
    --sidebar-primary-foreground: hsl(0 0% 9%);
    --sidebar-accent: hsl(240 3.7% 15.9%);
    --sidebar-border: hsl(240 3.7% 15.9%);
    /* ... */
}
```

---

## Temas Disponíveis

Os temas são aplicados via classe no elemento `<html>` ou em um container pai. Cada tema sobrescreve as variáveis de cor.

### Tema `plannerate` (padrão da aplicação)

```css
.theme-plannerate .theme-container,
.theme-plannerate [data-slot="popover-content"],
.theme-plannerate [data-slot="dropdown-content"],
/* ... */ {
    --primary: hsl(25 95% 53%);           /* laranja/amber */
    --primary-foreground: hsl(0 0% 100%);
    --sidebar-primary: hsl(25 95% 53%);
    --sidebar-primary-foreground: hsl(0 0% 100%);
    /* ... */
}
```

### Outros Temas

| Tema | Cor primária |
|------|-------------|
| `theme-plannerate` | Laranja/Amber |
| `theme-blue` | Azul |
| `theme-green` | Verde |
| `theme-violet` | Violeta |
| `theme-rose` | Rosa |

---

## TailwindCSS v4 — Integração

### `@theme inline`

O TailwindCSS v4 mapeia as variáveis CSS para classes utilitárias via `@theme inline` em `app.css`:

```css
@import "tailwindcss";

@theme inline {
    --color-background: var(--background);
    --color-foreground: var(--foreground);
    --color-primary: var(--primary);
    --color-primary-foreground: var(--primary-foreground);
    --color-sidebar-background: var(--sidebar-background);
    --color-sidebar-primary: var(--sidebar-primary);
    /* ... */
}
```

Isso gera classes como `bg-sidebar-primary`, `text-sidebar-foreground`, `border-sidebar-border`, etc.

### Uso nos Componentes

```vue
<!-- Classes geradas pelo @theme -->
<div class="bg-sidebar-primary text-sidebar-primary-foreground">
    Logo
</div>

<button class="hover:bg-sidebar-accent text-sidebar-foreground/60">
    Item
</button>
```

---

## Como os Temas Funcionam com a Sidebar

A sidebar usa exclusivamente variáveis `--sidebar-*`. Isso significa:

1. **Light mode**: Sidebar clara (quase branco), texto escuro
2. **Dark mode**: Sidebar escura (quase preto), texto claro
3. **Tema ativo**: Cor primária muda (laranja para plannerate, azul para blue, etc.)

O **visual/design** (bordas, espaçamentos, tipografia, animações) é **independente do tema** — apenas as cores mudam.

---

## `data-slot` — Hooks CSS para Popovers

Para garantir que popovers e dropdowns respeitem o tema ativo, `themes.css` usa `data-slot` em vez de `[data-reka-popper-content-wrapper]` (que era específico do reka-ui):

```css
.theme-plannerate [data-slot="popover-content"],
.theme-plannerate [data-slot="dropdown-content"],
.theme-plannerate [data-slot="tooltip-content"],
.theme-plannerate [data-slot="select-content"],
.theme-plannerate [data-slot="combobox-content"] {
    --primary: hsl(25 95% 53%);
    /* ... */
}
```

Os componentes nativos devem adicionar `data-slot="popover-content"` (ou equivalente) em seus containers para respeitar o tema.

---

## Aplicando um Tema

O tema é aplicado via classe no container raiz. No `AppLayout` ou similar:

```vue
<div :class="`theme-${settings.theme}`">
    <div class="theme-container">
        <!-- conteúdo -->
    </div>
</div>
```

O `settings.theme` vem de `page.props.settings.theme` (configuração do tenant/usuário).

---

## Adicionando um Novo Tema

1. Adicione as variáveis em `resources/css/themes.css`:

```css
.theme-mycolor .theme-container,
.theme-mycolor [data-slot="popover-content"],
.theme-mycolor [data-slot="dropdown-content"] {
    --primary: hsl(200 80% 50%);
    --primary-foreground: hsl(0 0% 100%);
    --ring: hsl(200 80% 50%);
    --sidebar-primary: hsl(200 80% 50%);
    --sidebar-primary-foreground: hsl(0 0% 100%);
    --sidebar-ring: hsl(200 80% 50%);
}

.dark .theme-mycolor .theme-container,
.dark .theme-mycolor [data-slot="popover-content"] {
    /* versão dark do tema */
}
```

2. Registre o tema nas opções de configuração do backend.
