# Laravel Raptor - Documentação

Bem-vindo à documentação do Laravel Raptor, um pacote multi-tenant para Laravel com suporte a broadcasting, notificações, exportação/importação e muito mais.

## Índice

### 🎨 UI — Componentes e Estilos
- [Visão Geral do Sistema UI](./ui/README.md) - Estrutura, filosofia e localização dos componentes
- [Layouts](./ui/layouts.md) - RaptorLayout, ResourceLayout, RaptorHeader, modo tela-cheia, notificações, scrollbar
- [SelectWithClear](./ui/select.md) - Select nativo com limpar, pesquisa, teclado e integração Raptor backend
- [Sidebar](./ui/sidebar.md) - Sistema completo de sidebar: provider, collapse, mobile drawer, flyout
- [Navegação](./ui/navigation.md) - NavMain, NavUser, NavFooter — navegação nativa sem reka-ui
- [Componentes Base](./ui/components.md) - Button, Input, Badge, Card, Separator, Skeleton, Spinner
- [Temas](./ui/theming.md) - Sistema de temas, variáveis CSS, dark mode, TailwindCSS v4

### 🏗️ Arquitetura
- [Multi-Tenancy](./architecture/multi-tenancy.md) - Sistema de tenants, resolvers e contexto
- [Tenant em Jobs e Commands](./architecture/tenant-context.md) - Como manter contexto do tenant em filas e comandos

### 📡 Broadcasting & Notificações
- [Sistema de Notificações](./broadcasting/notifications.md) - Notificações em tempo real com WebSocket
- [Autenticação de Canais](./broadcasting/channel-auth.md) - Configuração de canais privados
- [Echo Vue Helpers](./broadcasting/echo-vue.md) - Composables para Vue.js

### 📦 Exportação & Importação
- [Sistema de Export](./export-import/export.md) - Exportação de dados para Excel
- [Jobs Customizados](./export-import/custom-jobs.md) - Criar jobs de exportação personalizados

### 🎯 Actions
- [Guia de Actions](./actions/guide.md) - Como criar e usar ações
- [useActionUI Composable](./actions/use-action-ui.md) - Composable para UI de ações

### 🗃️ Banco de Dados
- [Migrations Multi-Database](./database/multi-database.md) - Executar migrations em múltiplos bancos

### 📝 Formulários
- [Hints com Actions](./forms/hints.md) - Dicas e ações em campos de formulário
- [Novas Colunas](./forms/columns.md) - LinkColumn, HasManyColumn, BelongsToManyColumn

---

## Instalação Rápida

```bash
composer require callcocam/laravel-raptor
php artisan vendor:publish --tag=laravel-raptor-config
php artisan migrate
```

## Requisitos

- PHP 8.2+
- Laravel 11+
- Node.js 18+ (para frontend)
