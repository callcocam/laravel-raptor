# Sistema de Layouts — RaptorLayout & ResourceLayout

Esta documentação descreve o sistema de layouts do pacote Raptor, construído de forma **independente** do `AppLayout.vue` da aplicação. O objetivo é que o pacote tenha controle total sobre o shell da página, sem depender de layouts externos.

---

## Visão Geral

```
ResourceLayout (wrapper de página)
└── RaptorLayout (shell completo)
    ├── SidebarProvider (pacote nativo, sem reka-ui)
    │   ├── AppSidebar
    │   └── SidebarInset
    │       ├── RaptorHeader
    │       ├── FlashMessages
    │       ├── Cabeçalho de página (título + subtítulo + ações)
    │       └── <main> → conteúdo da página
    └── Toaster (fora do SidebarProvider para z-index correto)
```

---

## RaptorLayout

**Arquivo:** `packages/callcocam/laravel-raptor/resources/js/layouts/RaptorLayout.vue`

O layout base. Não depende de nenhum arquivo da aplicação host — exceto `AppSidebar.vue`, que contém a estrutura de navegação específica do projeto.

### Props

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `title` | `string` | `'Dashboard'` | Título da página (exibido no `<head>` e no cabeçalho) |
| `message` | `string` | `''` | Subtítulo exibido abaixo do título |
| `resourceLabel` | `string` | — | Fallback para `message` |
| `resourcePluralLabel` | `string` | — | Fallback para `title` |
| `breadcrumbs` | `BreadcrumbItem[]` | `[]` | Migalhas de pão **já mapeadas** (`{ title, href }`) |
| `showPageHeader` | `boolean` | `true` | Exibe o bloco de título/subtítulo |
| `fullHeight` | `boolean` | `false` | Modo tela-cheia (ver seção abaixo) |

### Slots

| Slot | Descrição |
|------|-----------|
| `default` | Conteúdo principal da página |
| `content` | Alternativa ao slot default (para compatibilidade) |
| `header` | Cabeçalho customizado (substitui o bloco título/subtítulo) |
| `header-actions` | Botões no canto direito do cabeçalho (ex: Exportar, Importar) |

### Exemplo de uso direto

```vue
<RaptorLayout
  title="Minha Página"
  message="Descrição curta"
  :breadcrumbs="breadcrumbs"
>
  <template #header-actions>
    <Button>Exportar</Button>
  </template>

  <div>Conteúdo aqui</div>
</RaptorLayout>
```

---

## ResourceLayout

**Arquivo:** `packages/callcocam/laravel-raptor/resources/js/layouts/ResourceLayout.vue`

Wrapper de conveniência para páginas de recursos (CRUD, listagens, etc.). Faz o mapeamento de breadcrumbs do backend (`BackendBreadcrumb[]`) para o formato do frontend (`BreadcrumbItem[]`) e delega tudo para `RaptorLayout`.

### Props

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `title` | `string` | `'Dashboard'` | Título da página |
| `message` | `string` | — | Subtítulo |
| `resourceName` | `string` | — | Nome singular do recurso |
| `resourcePluralName` | `string` | — | Nome plural do recurso |
| `resourceLabel` | `string` | — | Label de exibição singular |
| `resourcePluralLabel` | `string` | — | Label de exibição plural (fallback de título) |
| `maxWidth` | `string` | `'7xl'` | Largura máxima do container (`full`, `7xl`, `6xl`…) |
| `breadcrumbs` | `BackendBreadcrumb[]` | — | Breadcrumbs vindos do backend |
| `actionName` | `string` | — | Nome da ação atual |
| `fullHeight` | `boolean` | `false` | Modo tela-cheia (ver seção abaixo) |

### Slots

Idênticos ao `RaptorLayout` + compatibilidade retroativa com `#header` (usado por `IndexPage.vue`).

### Exemplo básico

```vue
<ResourceLayout
  title="Dimensões"
  message="Gerencie as dimensões dos produtos"
  :breadcrumbs="breadcrumbs"
  max-width="full"
>
  <template #content>
    <MinhaTabela />
  </template>
</ResourceLayout>
```

---

## Breadcrumbs — Fluxo de Mapeamento

Um ponto crítico: o mapeamento de breadcrumbs acontece **uma única vez**, no `ResourceLayout`. O `RaptorLayout` recebe os dados já convertidos.

```
Backend PHP
└── BackendBreadcrumb[] { label: string, url: string | null }
      ↓ useBreadcrumbs() — acontece no ResourceLayout
Frontend Vue
└── BreadcrumbItem[]   { title: string, href: string }
      ↓ passado direto ao RaptorLayout
      ↓ passado direto ao RaptorHeader
      ↓ renderizado nativamente (sem componentes externos)
```

> **Atenção:** Nunca chame `useBreadcrumbs()` novamente no `RaptorLayout` ou `RaptorHeader`. Isso causaria dupla conversão e os títulos ficariam vazios (propriedade `label` não existiria mais no objeto já convertido).

---

## Modo Tela-Cheia (`fullHeight`)

O modo `fullHeight` faz o layout ocupar **exatamente `100svh`** sem scroll externo. É ideal para:

- Kanban boards (colunas com scroll independente)
- Editores de planograma
- Painéis de visualização com áreas de scroll separadas
- Qualquer interface no estilo "app" onde a página não deve rolar

### Como funciona (cadeia CSS)

O segredo é que **toda a cadeia de ancestrais** precisa ter altura definida para que `h-full` funcione nos filhos:

```
SidebarProvider wrapper → h-svh overflow-hidden   (1)
  └─ SidebarInset       → flex-1 flex-col overflow-hidden   (2)
       ├─ RaptorHeader  → h-12 shrink-0   (fixo)
       ├─ Page header   → shrink-0        (fixo)
       └─ <main>        → flex-1 min-h-0 overflow-hidden   (3) ← chave!
            └─ Conteúdo → h-full flex-col  (agora funciona)
```

> **`min-h-0` é o truque crítico**: em `display: flex`, filhos com `flex-1` podem crescer além do pai se o pai não tiver `min-h-0`. Isso quebra toda a cadeia de alturas.

### Ativação

```vue
<!-- ResourceLayout (recomendado para páginas) -->
<ResourceLayout :full-height="true">
  <KanbanView />
</ResourceLayout>

<!-- RaptorLayout (uso direto) -->
<RaptorLayout :full-height="true">
  <div class="flex h-full gap-4">
    <!-- colunas com overflow-y-auto -->
  </div>
</RaptorLayout>
```

### Estrutura interna recomendada para o conteúdo

Quando `fullHeight=true`, o conteúdo deve seguir esta estrutura:

```vue
<!-- Componente principal da página -->
<template>
  <div class="flex h-full flex-col">
    <!-- Header fixo: filtros, título local, etc. -->
    <div class="shrink-0 border-b">
      <Filtros />
    </div>

    <!-- Área scrollável -->
    <div class="flex-1 overflow-x-auto overflow-y-hidden">
      <div class="flex h-full gap-4" style="min-width: max-content;">
        <Coluna v-for="col in colunas" :key="col.id" />
      </div>
    </div>
  </div>
</template>
```

### Estrutura de coluna Kanban (exemplo completo)

```vue
<template>
  <!-- Coluna: altura total, layout flex vertical -->
  <div class="flex w-80 h-full shrink-0 flex-col rounded-lg border bg-card">
    
    <!-- Header da coluna: fixo no topo da coluna -->
    <div class="sticky top-0 z-10 flex items-center border-b p-3 bg-card rounded-t-lg">
      <h3>{{ coluna.titulo }}</h3>
    </div>

    <!-- Body: scroll vertical independente -->
    <div class="flex-1 overflow-y-auto p-3 space-y-2">
      <Card v-for="item in coluna.itens" :key="item.id" />
    </div>
  </div>
</template>
```

### `maxWidth` é ignorado em modo fullHeight

Quando `fullHeight=true`, o `ResourceLayout` substitui `containerClasses` por `flex h-full flex-1 flex-col`, ignorando o `maxWidth`. Isso é intencional — em modo tela-cheia, o conteúdo deve preencher toda a área disponível.

---

## RaptorHeader

**Arquivo:** `packages/callcocam/laravel-raptor/resources/js/components/RaptorHeader.vue`

Header de navegação superior. Substituiu o `AppSidebarHeader.vue` como referência para layouts do pacote.

### Estrutura visual

```
[ ≡ Trigger ] [ | ] [ Dashboard > Planogramas > Kanban ]   [ ☀/☾ ] [ 🔔 ]
```

### Props

| Prop | Tipo | Padrão | Descrição |
|------|------|--------|-----------|
| `breadcrumbs` | `BreadcrumbItem[]` | `[]` | Breadcrumbs já mapeados |

### Funcionalidades

- **SidebarTrigger**: botão `≡` para expandir/colapsar a sidebar
- **Breadcrumbs nativos**: renderizados com `ChevronRight` do Lucide, sem componentes externos
  - Itens intermediários: links clicáveis com `<Link>` do Inertia
  - Último item: texto bold com `aria-current="page"`
- **Toggle dark/light mode**: botão sun/moon que alterna a classe `.dark` no `<html>` e persiste em `localStorage`
- **NotificationDropdown**: sino de notificações com sistema completo

### Breadcrumbs — comportamento

```vue
<!-- Painel de controle > Planogramas > Kanban -->
<!-- "Painel de controle" e "Planogramas" são links clicáveis -->
<!-- "Kanban" é o item atual (não clicável, bold) -->
```

---

## Sistema de Notificações

**Arquivo:** `packages/callcocam/laravel-raptor/resources/js/components/NotificationDropdown.vue`

Dropdown de notificações 100% nativo, sem `reka-ui`, `DropdownMenu` ou `ScrollArea` externos.

### Funcionalidades

| Funcionalidade | Descrição |
|----------------|-----------|
| Painel nativo | `position: absolute`, animação `scale + fade` com `<Transition>` |
| Click outside | `mousedown` listener no `document`, limpa ao clicar fora |
| Fechar com Esc | `keydown` listener no `document` |
| Agrupamento temporal | **Hoje**, **Ontem**, **Anteriores** (calculado no cliente) |
| Ícones por tipo | `CheckCircle2` (sucesso), `XCircle` (erro), `AlertTriangle` (aviso), `Info` (info) |
| Cores por tipo | emerald, red, amber, blue — adaptam ao tema e dark mode |
| Status de conexão | dot verde/âmbar/vermelho com estado do Reverb WebSocket |
| Marcar como lida | clique no item, ou "Ler todas" no cabeçalho |
| Remover | botão `X` que aparece no hover do item |
| Limpar tudo | botão no cabeçalho do painel |
| Links de ação | Download (exportação), Baixar erros (importação) |
| Passos de resolução | `<details>` expansível para erros de banco |
| Contador rodapé | total + não lidas com destaque primário |

### Agrupamento de notificações

```typescript
// Hoje:      data >= início do dia atual
// Ontem:     data >= início do dia anterior
// Anteriores: demais
const groupedNotifications = computed(() => {
  const todayStart = new Date(); todayStart.setHours(0, 0, 0, 0);
  const yesterdayStart = new Date(todayStart.getTime() - 86_400_000);
  // ...
})
```

### Registro global

O componente é registrado globalmente via `RaptorPlugin` (em `raptor/index.ts`):

```typescript
app.component('NotificationDropdown',
  defineAsyncComponent(() => import('~/components/NotificationDropdown.vue'))
)
```

Portanto está disponível em qualquer template da aplicação, incluindo no `AppSidebarHeader.vue` do app host (sem necessidade de import explícito).

### Integração com `useGlobalNotifications`

```typescript
const {
  notifications,       // Ref<GlobalNotification[]>
  unreadCount,         // ComputedRef<number>
  connectionStatus,    // 'connected' | 'connecting' | 'reconnecting' | 'failed'
  isConnected,         // ComputedRef<boolean>
  markAsRead,          // (id: string) => void
  markAllAsRead,       // () => void
  removeNotification,  // (id: string) => void
  clearAll,            // () => void
} = useGlobalNotifications()
```

---

## Scrollbar Global

**Arquivo:** `resources/css/app.css`

A scrollbar foi estilizada globalmente para um visual limpo e quase invisível, adaptando-se automaticamente ao tema (claro/escuro).

### Comportamento

| Estado | Opacidade da thumb |
|--------|--------------------|
| Repouso | Invisível (transparente) |
| Hover no container | ~18% do `--foreground` |
| Hover direto na thumb | ~32% do `--foreground` |
| Clique/arrasto | ~45% do `--foreground` |

### Implementação

```css
/* Firefox */
* {
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
}
*:hover {
  scrollbar-color: color-mix(in oklch, var(--foreground) 18%, transparent) transparent;
}

/* Webkit (Chrome, Edge, Safari) */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: transparent; border-radius: 99px; }
*:hover::-webkit-scrollbar-thumb {
  background: color-mix(in oklch, var(--foreground) 18%, transparent);
}
```

O uso de `color-mix(in oklch, var(--foreground) ...)` garante que a cor seja derivada automaticamente das variáveis CSS do tema ativo — funciona em todos os temas (plannerate, azul, verde, etc.) e em ambos os modos (claro e escuro).

---

## Guia de Uso por Caso

### Página CRUD padrão (lista, tabela)

```vue
<ResourceLayout
  :breadcrumbs="breadcrumbs"
  title="Produtos"
  message="Gerencie o catálogo de produtos"
  max-width="7xl"
>
  <template #content>
    <TabelaDeProdutos />
  </template>
</ResourceLayout>
```

### Página com largura total

```vue
<ResourceLayout :breadcrumbs="breadcrumbs" title="Mercadológico" max-width="full">
  <MercadologicoKanban />
</ResourceLayout>
```

### Página com modo tela-cheia (Kanban, editor)

```vue
<ResourceLayout :breadcrumbs="breadcrumbs" title="Kanban" :full-height="true">
  <template #content>
    <!-- KanbanView já tem flex h-full flex-col internamente -->
    <KanbanView v-bind="props" />
  </template>
</ResourceLayout>
```

### Página com ações no cabeçalho

```vue
<ResourceLayout :breadcrumbs="breadcrumbs" title="Usuários">
  <template #header-actions>
    <Button @click="exportar">Exportar</Button>
    <Button variant="outline" @click="importar">Importar</Button>
  </template>
  <template #content>
    <TabelaUsuarios />
  </template>
</ResourceLayout>
```

### Cabeçalho totalmente customizado (retrocompatibilidade)

```vue
<ResourceLayout :breadcrumbs="breadcrumbs">
  <template #header>
    <!-- showPageHeader é automaticamente desativado quando #header está presente -->
    <div class="custom-header">...</div>
  </template>
  <template #content>
    ...
  </template>
</ResourceLayout>
```

---

## Independência do App Host

O `RaptorLayout` foi projetado para ser **autocontido**. Suas dependências do app host são mínimas e justificadas:

| Dependência | Arquivo | Justificativa |
|-------------|---------|---------------|
| `AppSidebar.vue` | `@/components/AppSidebar.vue` | Contém a árvore de navegação específica do projeto |
| `Toaster` | `@/components/ui/sonner` | Componente de toast da aplicação |
| `dashboard()` | `@/routes` | Rota Wayfinder — fallback de breadcrumb |
| `BreadcrumbItem` | `@/types` | Tipo TypeScript compartilhado |

Todos os outros elementos — `SidebarProvider`, `SidebarInset`, `RaptorHeader`, `NotificationDropdown`, `FlashMessages` — são do próprio pacote.
