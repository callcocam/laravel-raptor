# Navegação — NavMain, NavUser, NavFooter

## NavMain

Componente de navegação principal. Completamente nativo — **sem reka-ui**.

### Localização

```
packages/callcocam/laravel-raptor/resources/js/components/NavMain.vue
```

### Props

| Prop | Tipo | Obrigatório | Descrição |
|------|------|-------------|-----------|
| `items` | `NavItem[]` | ✅ | Lista de itens de navegação |
| `groupLabel` | `string` | ❌ | Rótulo do grupo (ex: "GERAL", "CATÁLOGO") |

### Tipo `NavItem`

```typescript
interface NavItem {
    title: string
    label?: string        // Texto exibido (fallback para title)
    href: string
    icon?: Component      // Componente de ícone (Lucide, etc.)
    badge?: string        // Badge numérico/texto
    group?: string        // Nome do grupo
    groupCollapsible?: boolean
    children?: NavItem[]  // Sub-itens
    order?: number        // Ordem de exibição
}
```

### Collapsible Nativo

Substituímos o `Collapsible` do reka-ui por estado Vue reativo simples:

```typescript
// Estado dos grupos abertos/fechados
const openGroups = ref<Record<string, boolean>>({})

const isOpen = (key: string) => !!openGroups.value[key]

const toggleGroup = (key: string) => {
    openGroups.value[key] = !openGroups.value[key]
}

// Abre automaticamente grupos com item filho ativo
onMounted(() => {
    props.items.forEach(item => {
        if (hasChildren(item) && item.children?.some(c => urlIsActive(c.href, page.url))) {
            openGroups.value[item.title] = true
        }
    })
})
```

A animação de expandir/colapsar usa `<Transition>` nativo do Vue:

```vue
<Transition name="submenu">
    <SidebarMenuSub v-show="isOpen(item.title)" class="...">
        <!-- sub-itens -->
    </SidebarMenuSub>
</Transition>
```

```css
.submenu-enter-active,
.submenu-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.submenu-enter-from,
.submenu-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
```

### Itens Pai (com filhos)

Renderiza um `SidebarMenuButton` com `@click="toggleGroup()"`:

```vue
<SidebarMenuButton
    :is-active="item.children?.some(c => urlIsActive(c.href, page.url))"
    :tooltip="item.title"
    @click="toggleGroup(item.title)"
>
    <component :is="item.icon" v-if="item.icon" class="size-4 shrink-0" />
    <span class="flex-1 truncate group-data-[collapsible=icon]:hidden">
        {{ item.label || item.title }}
    </span>
    <ChevronRight
        class="ml-auto size-3.5 shrink-0 transition-transform duration-200 group-data-[collapsible=icon]:hidden"
        :class="{ 'rotate-90 text-sidebar-primary/60': isOpen(item.title) }"
    />
</SidebarMenuButton>
```

O item pai é marcado como **ativo** quando qualquer filho está ativo, e o chevron roda 90° quando o grupo está aberto.

### Itens Folha (sem filhos)

> **Importante:** Não usamos `SidebarMenuButton as-child > Link` do Inertia.
>
> **Por quê?** O componente `Link` do Inertia v2 tem tratamento próprio de `class` que é incompatível com `cloneVNode`. Resultado: o ícone e o texto não ficavam na mesma linha flex.
>
> **Solução:** `SidebarMenuButton` com `@click="router.visit(href)"` — renderiza como `<button>` e faz navegação SPA via Inertia router.

```vue
<SidebarMenuButton
    :is-active="urlIsActive(item.href, page.url)"
    :tooltip="item.title"
    @click="router.visit(item.href)"
>
    <component :is="item.icon" v-if="item.icon" class="size-4 shrink-0" />
    <span class="flex-1 truncate group-data-[collapsible=icon]:hidden">
        {{ item.label || item.title }}
    </span>
</SidebarMenuButton>
```

### Sub-itens

Sub-itens usam `<Link>` do Inertia diretamente (não precisam de `as-child`):

```vue
<Link
    :href="subItem.href"
    class="flex h-7 w-full items-center gap-2 rounded-sm px-2.5 text-[0.8125rem] font-medium transition-colors"
    :class="[
        urlIsActive(subItem.href, page.url)
            ? 'text-sidebar-primary'
            : 'text-sidebar-foreground/50 hover:text-sidebar-foreground hover:bg-sidebar-accent/60'
    ]"
>
```

### Comportamento no Modo Colapsado

- O texto dos itens tem `group-data-[collapsible=icon]:hidden` — desaparece no modo ícone
- Os sub-itens têm `group-data-[collapsible=icon]:hidden` — substituídos pelo flyout
- O chevron tem `group-data-[collapsible=icon]:hidden` — não aparece no modo ícone

### Flyout no Modo Colapsado

Ver documentação completa em [sidebar.md — Flyout de Sub-menus](./sidebar.md#flyout-de-sub-menus-modo-colapsado).

**Fluxo resumido:**

```
Mouse entra no item pai
    → openFlyout(key) lê anchorRef.getBoundingClientRect()
    → Atualiza flyoutPos { top, left }
    → activeFlyout = key
    → Teleport renderiza o painel à direita da sidebar

Mouse sai do item OU do painel
    → scheduleFlyoutClose() inicia timer de 150ms
    → Se mouse voltar antes → cancelFlyoutClose() cancela o timer
    → Se timer disparar → activeFlyout = null
```

---

## NavUser

Exibe o usuário logado no footer da sidebar com dropdown de ações.

```
packages/callcocam/laravel-raptor/resources/js/components/NavUser.vue
```

### Comportamento

- Avatar: exibe foto se disponível (`user.avatar`), caso contrário exibe iniciais
- Nome e e-mail: truncados com `text-ellipsis`
- Dropdown posicionado dinamicamente:
  - Mobile → `side="bottom"`
  - Desktop colapsado → `side="left"` (abre para a esquerda)
  - Desktop expandido → `side="bottom"` (abre para cima)

```vue
<DropdownMenuContent
    class="min-w-56 rounded-lg"
    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'"
    align="end"
    :side-offset="4"
>
    <UserMenuContent :user="user" />
</DropdownMenuContent>
```

> **Nota:** A classe `w-(--reka-dropdown-menu-trigger-width)` foi removida pois usava uma variável CSS privada do reka-ui. Substituída por `min-w-56`.

---

## NavFooter

Exibe links adicionais no rodapé da sidebar (antes do NavUser).

```
packages/callcocam/laravel-raptor/resources/js/components/NavFooter.vue
```

```vue
<NavFooter :items="footerItems" />
```

Os itens são renderizados como `SidebarMenuButton` com `text-sidebar-foreground/50` para aparecerem mais sutis que os itens de navegação principal.

---

## Dados de Navegação — Fluxo

Os itens de navegação vêm do servidor via Inertia props:

```php
// No Service Provider ou Middleware do Raptor
Inertia::share('raptor', [
    'navigation' => $this->buildNavigation(),
    'navigationFooter' => $this->buildFooterNavigation(),
]);
```

No `RaptorSidebar.vue`, os ícones são resolvidos dinamicamente de `lucide-vue-next`:

```typescript
const getIconComponent = (iconName: string) => {
    return (LucideIcons as any)[iconName] || LucideIcons.Circle
}

const navigationItems = computed(() => {
    return navData.map(item => ({
        ...item,
        icon: typeof item.icon === 'string'
            ? getIconComponent(item.icon)
            : item.icon,
    }))
})
```

Ou seja, no backend você pode passar o nome do ícone como string:

```php
NavigationItem::make('Usuários')
    ->icon('Users')  // Nome exato do ícone Lucide
    ->href(route('admin.users.index'))
    ->group('Segurança')
```
