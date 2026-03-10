# Sidebar — Documentação Completa

## Visão Geral

O sistema de sidebar do Raptor é 100% nativo — sem `reka-ui`, sem `Sheet`, sem `TooltipProvider`. Foi construído com:

- **Vue 3 Composition API** (`ref`, `computed`, `provide`/`inject`)
- **CSS Transitions nativas** para drawer mobile e animações
- **TailwindCSS v4** com variáveis `--sidebar-*`
- **Teleport** do Vue para o flyout de sub-menus no modo colapsado

---

## Arquitetura do Contexto

### O Problema de Contexto Entre Pacote e App

O `SidebarProvider` usa `provide`/`inject` com uma chave Symbol. Como o pacote (`packages/callcocam/laravel-raptor`) e o app (`resources/js`) são bundles separados, cada um criaria seu próprio Symbol — causando o erro:

```
[Vue warn]: injection "Symbol(SidebarContext)" not found
```

### A Solução: Re-exportação

`packages/callcocam/laravel-raptor/resources/js/components/ui/sidebar/utils.ts` **re-exporta** diretamente do `utils.ts` do app:

```typescript
// packages/.../ui/sidebar/utils.ts
export { useSidebar, provideSidebarContext } from '@/components/ui/sidebar/utils'
```

Isso garante que **ambos usam o mesmo Symbol** — o definido pelo app. O `SidebarProvider` do app fornece o contexto, e os componentes do pacote consomem corretamente.

---

## Componentes

### `SidebarProvider`

Fornece o contexto para todos os filhos da sidebar. Deve envolver o layout inteiro.

```vue
<!-- resources/js/components/AppShell.vue -->
<SidebarProvider>
    <AppSidebar />
    <SidebarInset>
        <slot />
    </SidebarInset>
</SidebarProvider>
```

**Context disponível via `useSidebar()`:**

| Propriedade | Tipo | Descrição |
|-------------|------|-----------|
| `state` | `Ref<'expanded' \| 'collapsed'>` | Estado atual da sidebar |
| `open` | `Ref<boolean>` | Se está aberta (desktop) |
| `isMobile` | `Ref<boolean>` | Se está em viewport mobile |
| `openMobile` | `Ref<boolean>` | Se o drawer mobile está aberto |
| `toggleSidebar()` | `() => void` | Alterna entre expandido/colapsado |
| `setOpenMobile(v)` | `(boolean) => void` | Controla o drawer mobile |

---

### `Sidebar`

Componente raiz. Gerencia três modos de renderização:

```vue
<Sidebar collapsible="icon" variant="sidebar" side="left">
    <!-- conteúdo -->
</Sidebar>
```

**Props:**

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `collapsible` | `'offcanvas' \| 'icon' \| 'none'` | `'offcanvas'` | Modo de colapso |
| `variant` | `'sidebar' \| 'floating' \| 'inset'` | `'sidebar'` | Estilo visual |
| `side` | `'left' \| 'right'` | `'left'` | Posição |

**Modos de renderização:**

```
collapsible="none"   → Sidebar sempre visível, sem toggle
isMobile === true    → Drawer nativo com Transition + backdrop
isMobile === false   → Sidebar desktop com animação de largura (CSS transition)
```

**Modo Mobile — Drawer Nativo:**

```html
<!-- Backdrop com fade -->
<Transition name="sidebar-backdrop">
    <div v-if="openMobile" class="fixed inset-0 z-40 bg-black/50" @click="setOpenMobile(false)" />
</Transition>

<!-- Painel com slide -->
<Transition name="sidebar-drawer">
    <div v-if="openMobile" class="fixed inset-y-0 z-50 flex h-full flex-col" role="dialog" aria-modal="true">
        <slot />
    </div>
</Transition>
```

As animações são definidas em `<style scoped>` com CSS puro:

```css
.sidebar-drawer-enter-active,
.sidebar-drawer-leave-active {
    transition: transform 0.25s ease;
}
.sidebar-drawer-enter-from,
.sidebar-drawer-leave-to {
    transform: translateX(-100%); /* ou +100% para side="right" */
}
```

**Modo Desktop — Collapse Icon:**

Quando `state === 'collapsed'` e `collapsible="icon"`:
- A sidebar encolhe para `--sidebar-width-icon` (3rem = 48px)
- O atributo `data-collapsible="icon"` é aplicado ao wrapper com classe `group`
- Elementos internos usam `group-data-[collapsible=icon]:hidden` para se esconder

```css
/* Variáveis CSS da sidebar */
--sidebar-width: 16rem;        /* expandida */
--sidebar-width-icon: 3rem;    /* colapsada (icon-only) */
--sidebar-width-mobile: 18rem; /* drawer mobile */
```

---

### `SidebarMenuButton`

Botão de item do menu. Provê tooltip nativo no modo colapsado.

```vue
<SidebarMenuButton
    :is-active="urlIsActive(item.href, page.url)"
    :tooltip="item.title"
    @click="router.visit(item.href)"
>
    <component :is="item.icon" class="size-4 shrink-0" />
    <span class="flex-1 truncate group-data-[collapsible=icon]:hidden">
        {{ item.title }}
    </span>
</SidebarMenuButton>
```

**Props:**

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `as` | `string \| object` | `'button'` | Elemento raiz |
| `asChild` | `boolean` | `false` | Renderiza o slot como raiz (cloneVNode) |
| `variant` | `'default' \| 'outline'` | `'default'` | Variante visual |
| `size` | `'default' \| 'sm' \| 'lg'` | `'default'` | Tamanho |
| `isActive` | `boolean` | `false` | Estado ativo |
| `tooltip` | `string \| Component` | — | Texto/conteúdo do tooltip |

**Tooltip Nativo:**

O tooltip é renderizado via `position: absolute; left: 100%` e aparece **apenas** quando `state === 'collapsed' && !isMobile`:

```vue
<Transition name="tooltip-fade">
    <div
        v-if="showTooltip && tooltipVisible"
        role="tooltip"
        class="pointer-events-none absolute top-1/2 left-full z-50 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2.5 py-1 text-xs text-popover-foreground shadow-md"
    >
        {{ tooltip }}
    </div>
</Transition>
```

**`asChild` — Como Funciona:**

Quando `asChild=true`, o `SidebarMenuButtonChild` usa `cloneVNode` do Vue para clonar o primeiro filho do slot e mesclar as props do botão nele:

```typescript
// SidebarMenuButtonChild.vue
if (props.asChild && slots.default) {
    const children = slots.default()
    const firstChild = children.find(vnode => vnode.type !== Comment && vnode.type !== Text)
    if (firstChild) {
        return cloneVNode(firstChild, mergedProps)
    }
}
```

> ⚠️ **Atenção**: O `asChild` com o componente `Link` do Inertia pode causar problemas de layout (ícone e texto não ficam em linha) porque o `Link` tem tratamento próprio de `class`. Para itens de navegação folha (sem filhos), prefira `SidebarMenuButton` com `@click="router.visit(href)"`.

---

### `SidebarTrigger`

Botão que alterna a sidebar. Usa `toggleSidebar()` do contexto.

```vue
<SidebarTrigger class="-ml-1" />
```

Renderiza um `Button` (ghost, icon) com ícone `PanelLeft` do Lucide.

---

## `RaptorSidebar` — Componente de Alto Nível

Componente pré-montado que agrupa toda a estrutura da sidebar:

```vue
<!-- resources/js/components/AppSidebar.vue -->
<RaptorSidebar
    :dashboard-url="dashboard().url"
    :footer-items="footerNavItems"
>
    <template #logo>
        <Link :href="dashboard().url">
            <!-- Logo completo: esconde no modo icon-only -->
            <AppLogo class="group-data-[collapsible=icon]:hidden" />
            <!-- Monograma: aparece somente no modo icon-only -->
            <span class="hidden size-7 ... group-data-[collapsible=icon]:flex">P</span>
        </Link>
    </template>
</RaptorSidebar>
```

**Props:**

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `dashboardUrl` | `string` | `'/'` | URL do dashboard (link do logo fallback) |
| `footerItems` | `NavItem[]` | `[]` | Itens extras no footer |

**Slots:**

| Slot | Descrição |
|------|-----------|
| `#logo` | Área do logo no header. Deve conter lógica de collapsed/expanded. |

**Estrutura Interna:**

```
RaptorSidebar
├── Sidebar (collapsible="icon")
│   ├── SidebarHeader
│   │   └── slot#logo
│   ├── SidebarContent
│   │   └── NavMain (para cada grupo de navegação)
│   └── SidebarFooter
│       ├── NavFooter (se houver itens)
│       └── NavUser
└── slot (conteúdo adicional)
```

**Agrupamento de Navegação:**

Os itens de `page.props.raptor.navigation` são agrupados pela propriedade `item.group`:

```typescript
const groupedNavigation = computed(() => {
    const groups = new Map<string, NavItem[]>()
    navigationItems.value.forEach(item => {
        const groupName = item.group || 'Geral'
        if (!groups.has(groupName)) groups.set(groupName, [])
        groups.get(groupName)!.push(item)
    })
    // ...
})
```

Grupos com `item.groupCollapsible = true` são renderizados como um único item colapsável pai com os filhos como sub-itens.

---

## Logo — Modo Expandido vs Colapsado

### O Problema

No modo colapsado (48px de largura), um logo horizontal transborda o container. O `overflow-hidden` no header cortaria o logo.

### A Solução

O slot `#logo` contém **dois elementos** com visibilidade condicional via `group-data-[collapsible=icon]`:

```vue
<template #logo>
    <Link :href="dashboard().url" class="flex items-center">
        <!-- Logo completo — visível no modo expandido, oculto no colapsado -->
        <AppLogo class="group-data-[collapsible=icon]:hidden" />

        <!-- Monograma — oculto no expandido, visível no colapsado -->
        <span class="hidden size-7 items-center justify-center rounded-md
                     bg-sidebar-primary text-sm font-bold text-sidebar-primary-foreground
                     group-data-[collapsible=icon]:flex">
            P
        </span>
    </Link>
</template>
```

**Por que funciona no mobile:**
No mobile, o drawer não possui o wrapper com `class="group"` e `data-collapsible="icon"`. Portanto, `group-data-[collapsible=icon]:hidden` **não se aplica** no mobile — o logo completo sempre aparece no drawer.

**Header com padding adaptável:**

```vue
<!-- RaptorSidebar.vue -->
<SidebarHeader class="border-b border-sidebar-border px-3 py-2.5 group-data-[collapsible=icon]:px-1.5">
```

No modo colapsado: `px-1.5` (6px) → espaço disponível = `48 - 12 = 36px` → suficiente para `size-7 = 28px`.

---

## AppLogo

```vue
<!-- resources/js/components/AppLogo.vue -->
<template>
    <div class="flex items-center">
        <AppLogoIcon class="h-8 w-auto max-w-[200px] object-contain object-left" />
    </div>
</template>
```

`AppLogoIcon` detecta dark mode via `MutationObserver` e exibe `/img/marcadark.png` ou `/img/marca.png` conforme o tema ativo.

---

## Flyout de Sub-menus (Modo Colapsado)

No modo colapsado desktop, passar o mouse sobre um item pai abre um painel lateral com os sub-itens.

### Por que Teleport?

`SidebarContent` tem `overflow-hidden` no modo colapsado. Um elemento `position: absolute` dentro seria clipado. A solução é renderizar o flyout em `<body>` via `Teleport`.

### Posicionamento

A âncora de posição é um `<div>` nativo (não um componente Vue) para garantir leitura correta de `getBoundingClientRect()`:

```vue
<!-- NavMain.vue -->
<div
    :ref="(el) => setAnchorRef(item.title, el as HTMLElement)"
    @mouseenter="openFlyout(item.title)"
    @mouseleave="scheduleFlyoutClose()"
>
    <SidebarMenuButton ...>
```

```typescript
const openFlyout = (key: string) => {
    if (state.value !== 'collapsed' || isMobile.value) return
    const el = anchorRefs.value[key]
    if (el) {
        const rect = el.getBoundingClientRect()
        flyoutPos.value = {
            top: Math.min(Math.max(8, rect.top), window.innerHeight - 250),
            left: rect.right + 6,
        }
    }
    activeFlyout.value = key
}
```

> **Por que não usar `$event.currentTarget`?** O `Collapsible` do reka-ui com `as-child` pode interceptar o evento e retornar um `currentTarget` incorreto. Usar refs diretos é mais confiável.

### Timer de Fechamento

O flyout permanece aberto ao mover o mouse do item para o painel (gap de 6px):

```typescript
// Fecha após 150ms de inatividade
const scheduleFlyoutClose = () => {
    closeTimer = setTimeout(() => { activeFlyout.value = null }, 150)
}

// Cancela o fechamento ao entrar no flyout
const cancelFlyoutClose = () => {
    if (closeTimer) clearTimeout(closeTimer)
}
```

### Template do Flyout

```vue
<Teleport to="body">
    <Transition name="flyout">
        <div
            v-if="activeFlyout && state === 'collapsed' && !isMobile"
            class="fixed z-[60] min-w-44 overflow-hidden rounded-lg border border-border bg-sidebar py-1.5 shadow-lg"
            :style="{ top: `${flyoutPos.top}px`, left: `${flyoutPos.left}px` }"
            @mouseenter="cancelFlyoutClose()"
            @mouseleave="scheduleFlyoutClose()"
        >
            <!-- Header do grupo -->
            <p class="px-3 pb-1.5 pt-0.5 text-[10px] font-semibold uppercase tracking-wider text-sidebar-foreground/40">
                {{ item.label || item.title }}
            </p>
            <!-- Links dos sub-itens -->
            <Link v-for="child in item.children" :href="child.href" ...>
                ...
            </Link>
        </div>
    </Transition>
</Teleport>
```

---

## Variáveis CSS da Sidebar

Definidas em `resources/css/app.css`:

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

.dark {
    --sidebar-background: hsl(0 0% 7%);
    --sidebar-foreground: hsl(240 4.8% 95.9%);
    /* ... */
}
```

Os temas em `resources/css/themes.css` sobrescrevem essas variáveis. Por exemplo, o tema `plannerate` define `--sidebar-primary` com a cor laranja/amber da marca.

---

## Uso dos `data-*` Atributos

Os componentes usam atributos `data-*` como hooks de CSS:

| Atributo | Elemento | Descrição |
|----------|---------|-----------|
| `data-slot="sidebar"` | Sidebar root | Identificação do slot |
| `data-sidebar="sidebar"` | Panel div | Área principal |
| `data-state="expanded\|collapsed"` | Sidebar wrapper | Estado atual |
| `data-collapsible="icon"` | Sidebar wrapper | Ativo quando colapsado |
| `data-mobile="true"` | Drawer mobile | Identifica renderização mobile |
| `data-active="true"` | SidebarMenuButton | Item ativo |
| `data-slot="sidebar-menu-button"` | Button/link | Seletor CSS |
