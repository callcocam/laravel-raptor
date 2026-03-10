# Sistema de UI — Laravel Raptor

Esta pasta documenta o sistema de componentes de UI nativos criados para o Laravel Raptor. Todos os componentes foram construídos **do zero** usando HTML puro, Vue 3 e TailwindCSS v4, **sem dependências de `reka-ui` ou `shadcn-vue`**.

## Filosofia

- **100% nativo**: HTML semântico + Vue 3 Composition API
- **Zero `reka-ui`**: primitivos como `Primitive`, `createContext`, `TooltipProvider`, `Sheet`, `Collapsible` foram todos substituídos por implementações nativas
- **Tema adaptável**: cores seguem variáveis CSS do sistema de temas (`--sidebar-primary`, `--color-primary`, etc.)
- **Dark mode**: suporte completo via `dark:` do TailwindCSS
- **TailwindCSS v4**: usa `@theme`, `@variant`, variáveis CSS first

## Localização dos Componentes

```
packages/callcocam/laravel-raptor/resources/js/components/ui/
├── badge/
│   └── Badge.vue
├── button/
│   ├── Button.vue
│   └── index.ts          ← CVA variants
├── card/
│   ├── Card.vue
│   ├── CardHeader.vue
│   ├── CardTitle.vue
│   ├── CardDescription.vue
│   ├── CardContent.vue
│   ├── CardFooter.vue
│   └── CardAction.vue
├── input/
│   └── Input.vue
├── label/
│   └── Label.vue
├── separator/
│   └── Separator.vue
├── sidebar/
│   ├── index.ts          ← exports + CVA variants
│   ├── utils.ts          ← contexto (re-exporta do app)
│   ├── Sidebar.vue
│   ├── SidebarContent.vue
│   ├── SidebarFooter.vue
│   ├── SidebarGroup.vue
│   ├── SidebarHeader.vue
│   ├── SidebarMenu.vue
│   ├── SidebarMenuButton.vue
│   ├── SidebarMenuButtonChild.vue
│   ├── SidebarMenuItem.vue
│   ├── SidebarMenuSub.vue
│   ├── SidebarMenuSubButton.vue
│   ├── SidebarMenuSubItem.vue
│   ├── SidebarProvider.vue
│   ├── SidebarSeparator.vue
│   ├── SidebarTrigger.vue
│   └── ... (outros)
├── skeleton/
│   └── Skeleton.vue
└── spinner/
    └── Spinner.vue
```

## Documentação por Seção

| Documento | Descrição |
|-----------|-----------|
| [layouts.md](./layouts.md) | RaptorLayout, ResourceLayout, RaptorHeader, modo tela-cheia (`fullHeight`), NotificationDropdown, scrollbar global |
| [sidebar.md](./sidebar.md) | Sistema completo de sidebar: provider, collapse, mobile drawer, flyout |
| [navigation.md](./navigation.md) | NavMain, NavUser, NavFooter — navegação nativa sem reka-ui |
| [select.md](./select.md) | SelectWithClear — select nativo com limpar, pesquisa, teclado e backend Raptor |
| [components.md](./components.md) | Componentes base: Button, Input, Badge, Card, etc. |
| [theming.md](./theming.md) | Sistema de temas, variáveis CSS, dark mode |
