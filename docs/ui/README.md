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
├── spinner/
│   └── Spinner.vue
├── select/
│   ├── Select.vue
│   ├── SelectTrigger.vue
│   ├── SelectValue.vue
│   ├── SelectContent.vue
│   ├── SelectItem.vue
│   ├── SelectGroup.vue
│   ├── SelectLabel.vue
│   ├── SelectSeparator.vue
│   ├── SelectWithClear.vue
│   └── index.ts
├── field/
│   ├── Field.vue
│   ├── FieldLabel.vue
│   ├── FieldDescription.vue
│   ├── FieldError.vue
│   ├── FieldSet.vue
│   ├── FieldLegend.vue
│   ├── FieldGroup.vue
│   └── index.ts
├── checkbox/
│   ├── Checkbox.vue
│   └── index.ts
├── textarea/
│   ├── Textarea.vue
│   └── index.ts
├── collapsible/
│   ├── Collapsible.vue
│   ├── CollapsibleTrigger.vue
│   ├── CollapsibleContent.vue
│   └── index.ts
├── popover/
│   ├── Popover.vue
│   ├── PopoverTrigger.vue
│   ├── PopoverContent.vue
│   └── index.ts
└── command/
    ├── Command.vue
    ├── CommandInput.vue
    ├── CommandList.vue
    ├── CommandGroup.vue
    ├── CommandItem.vue
    ├── CommandEmpty.vue
    └── index.ts
```

## Utilitários do pacote

```
packages/callcocam/laravel-raptor/resources/js/lib/utils.ts
```

O pacote possui sua própria cópia de `lib/utils.ts` para ser **independente do app**:

```typescript
import { cn } from '~/lib/utils'          // merge de classes Tailwind
import { toUrl } from '~/lib/utils'        // normaliza href para string
import { urlIsActive } from '~/lib/utils'  // compara URL com URL atual
```

> Sempre importe de `~/lib/utils` dentro do pacote. **Nunca** de `@/lib/utils` (app),
> pois isso quebraria instalações onde o app não tem essa função.

## Documentação por Seção

| Documento | Descrição |
|-----------|-----------|
| [layouts.md](./layouts.md) | RaptorLayout, ResourceLayout, RaptorHeader, modo tela-cheia (`fullHeight`), NotificationDropdown, scrollbar global |
| [sidebar.md](./sidebar.md) | Sistema completo de sidebar: provider, collapse, mobile drawer, flyout |
| [navigation.md](./navigation.md) | NavMain, NavUser, NavFooter — navegação nativa sem reka-ui |
| [select.md](./select.md) | SelectWithClear — select nativo com limpar, pesquisa, teclado e backend Raptor |
| [components.md](./components.md) | Componentes base: Button, Input, Badge, Card, Select, Field, Checkbox, Textarea, Collapsible, Popover, Command |
| [theming.md](./theming.md) | Sistema de temas, variáveis CSS, dark mode |
