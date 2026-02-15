<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

// config for Callcocam/LaravelRaptor
return [

    /*
    |--------------------------------------------------------------------------
    | Main Domain
    |--------------------------------------------------------------------------
    |
    | O domínio principal da aplicação sem o protocolo (http/https)
    | Exemplo: 'example.com'
    |
    */
    'main_domain' => env('RAPTOR_MAIN_DOMAIN', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | Landlord Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para o subdomínio de gerenciamento da aplicação
    | (Administrador principal que gerencia todos os tenants)
    |
    */
    'landlord' => [
        // Colunas padrão para identificar tenant
        'default_tenant_columns' => ['tenant_id'],

        // Subdomínio usado para acessar o painel de gerenciamento
        // Exemplo: 'landlord' resulta em landlord.example.com
        'subdomain' => env('RAPTOR_LANDLORD_SUBDOMAIN', 'landlord'),

        // Middleware aplicado às rotas do landlord
        'middleware' => ['web', 'auth', 'landlord'],

        // Habilita prefixo nas rotas (true/false)
        'enable_prefix' => env('RAPTOR_LANDLORD_ENABLE_PREFIX', false),

        // Prefixo das rotas (ex: 'admin' resulta em /admin/users)
        'prefix' => env('RAPTOR_LANDLORD_PREFIX', null),

        // Models do Landlord
        'models' => [
            'tenant' => \Callcocam\LaravelRaptor\Models\Tenant::class,
            'user' => \App\Models\User::class,
            'translation_group' => \Callcocam\LaravelRaptor\Models\TranslationGroup::class,
            'translate' => \Callcocam\LaravelRaptor\Models\TranslationOverride::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para os subdomínios de tenants (clientes)
    |
    */
    'tenant' => [
        // Middleware aplicado às rotas dos tenants
        'middleware' => ['web', 'tenant'],

        // Habilita prefixo nas rotas administrativas (true/false)
        // Se false, as rotas não terão prefixo (ex: /users, /roles)
        // Se true, as rotas terão o prefixo definido abaixo
        'enable_prefix' => env('RAPTOR_TENANT_ENABLE_PREFIX', false),

        // Prefixo das rotas administrativas do tenant (ex: 'admin' resulta em /admin/users)
        // Será aplicado apenas se enable_prefix for true
        // Se null ou vazio, mesmo com enable_prefix true, não haverá prefixo
        'prefix' => env('RAPTOR_TENANT_PREFIX', null),

        // Coluna na tabela de tenants que armazena o identificador do subdomínio
        'subdomain_column' => 'domain',

        // Coluna na tabela de tenants que armazena domínios customizados
        'custom_domain_column' => 'custom_domain',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shinobi - Roles & Permissions
    |--------------------------------------------------------------------------
    |
    | Sistema de permissões e roles do Laravel Raptor
    |
    */
    'shinobi' => [
        // Models do sistema de permissões
        'models' => [
            'user' => \App\Models\User::class,
            'role' => \Callcocam\LaravelRaptor\Models\Role::class,
            'permission' => \Callcocam\LaravelRaptor\Models\Permission::class,
        ],

        // Tabelas do sistema de permissões
        'tables' => [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'role_user' => 'role_user',
            'permission_user' => 'permission_user',
            'permission_role' => 'permission_role',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Nomes das tabelas do banco de dados usadas pelo Raptor
    |
    */
    'tables' => [
        'tenants' => 'tenants',
        'tenant_domains' => 'tenant_domains',
        'translation_groups' => 'translation_groups',
        'translation_overrides' => 'translation_overrides',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de navegação automática
    |
    */
    'navigation' => [
        'contexts' => [
            'tenant' => [
                'controllers_path' => app_path('Http/Controllers/Tenant'),
                'controllers_namespace' => 'App\\Http\\Controllers\\Tenant',
                'default_group' => 'Aplicação',
            ],
            'landlord' => [
                'controllers_path' => base_path('packages/callcocam/laravel-raptor/src/Http/Controllers/Landlord'),
                'controllers_namespace' => 'Callcocam\\LaravelRaptor\\Http\\Controllers\\Landlord',
                'default_group' => 'Administração',
            ],
        ],
        'default_permission' => true,
        'cache_ttl' => 3600,
        'cache_key_prefix' => 'navigation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações de banco de dados para multi-tenancy
    |
    */
    'database' => [
        // Estratégia de multi-tenancy: 'shared' (único DB) ou 'separate' (DB por tenant)
        'strategy' => env('RAPTOR_DB_STRATEGY', 'shared'),
        // Em jobs (TenantAwareJob): configurar conexão default + tenant para o banco do tenant
        'configure_in_jobs' => env('RAPTOR_DB_CONFIGURE_IN_JOBS', true),
        // Em commands (TenantAwareCommand): configurar conexão ao usar setupTenantContext/forEachTenant
        'configure_in_commands' => env('RAPTOR_DB_CONFIGURE_IN_COMMANDS', true),
        // Nome da conexão landlord (banco principal). Defina em config/database.php.
        // A conexão default da app é a que muda para o banco do tenant no contexto; não se cria outra conexão.
        'landlord_connection_name' => env('RAPTOR_LANDLORD_CONNECTION', 'landlord'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export / Import
    |--------------------------------------------------------------------------
    |
    | Disco para arquivos de export e import (Excel, etc.)
    |
    */
    'export' => [
        'disk' => env('RAPTOR_EXPORT_DISK', 'public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare (DNS)
    |--------------------------------------------------------------------------
    |
    | Integração com a API Cloudflare para criar/apagar registros DNS
    | (domínios e subdomínios). Usado pelo campo CloudflareDnsField.
    |
    */
    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN', ''),
        'base_uri' => env('CLOUDFLARE_API_BASE_URI', 'https://api.cloudflare.com/client/v4'),
        'timeout' => (int) env('CLOUDFLARE_API_TIMEOUT', 30),
    ],

    // Disco padrão para upload de arquivos (formulários, FileUpload, etc.)
    'filesystem_disk' => env('RAPTOR_FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Route Injector Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para o TenantRouteInjector
    | Define quais diretórios de controllers serão escaneados para
    | registrar rotas automaticamente, SEPARADOS POR CONTEXTO.
    |
    */
    'route_injector' => [
        /*
        |----------------------------------------------------------------------
        | Habilitar cache de rotas descobertas
        |----------------------------------------------------------------------
        | Se true, as rotas descobertas são cacheadas para melhor performance.
        | Em desenvolvimento, pode ser false para refletir mudanças imediatamente.
        */
        'cache_enabled' => env('RAPTOR_ROUTE_CACHE', false),
        'cache_ttl' => 3600, // segundos

        /*
        |----------------------------------------------------------------------
        | Diretórios por Contexto
        |----------------------------------------------------------------------
        | Cada contexto (tenant, landlord) tem seus próprios diretórios.
        | Controllers da aplicação são escaneados dinamicamente.
        | Controllers do pacote são fixos.
        */
        'contexts' => [
            'tenant' => [
                // Controllers da aplicação (dinâmico - escaneia automaticamente)
                'App\\Http\\Controllers\\Tenant' => app_path('Http/Controllers/Tenant'),
                // Adicione mais diretórios de tenant conforme necessário:
                // 'Seu\\Namespace\\Tenant\\Controllers' => base_path('caminho/para/controllers'),
            ],
            'landlord' => [
                // Controllers da aplicação (dinâmico - escaneia automaticamente)
                'App\\Http\\Controllers\\Landlord' => app_path('Http/Controllers/Landlord'),
                // Adicione mais diretórios de landlord conforme necessário:
                // 'Seu\\Namespace\\Landlord\\Controllers' => base_path('caminho/para/controllers'),
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Diretórios do Pacote (internos - não modificar)
        |----------------------------------------------------------------------
        */
        'package_directories' => [
            'tenant' => [
                'Callcocam\\LaravelRaptor\\Http\\Controllers\\Tenant' => base_path('vendor/callcocam/laravel-raptor/src/Http/Controllers/Tenant'),
            ],
            'landlord' => [
                'Callcocam\\LaravelRaptor\\Http\\Controllers\\Landlord' => base_path('vendor/callcocam/laravel-raptor/src/Http/Controllers/Landlord'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de traduções customizadas por tenant
    | Sistema de prioridade: Tenant Override > Global Override > Laravel Lang Files
    |
    */
    'translation' => [
        // Habilita o sistema de traduções customizadas
        'enabled' => env('RAPTOR_TRANSLATION_ENABLED', true),

        // Intercepta automaticamente chamadas ao __() e trans()
        'intercept_default_get' => env('RAPTOR_TRANSLATION_INTERCEPT', true),

        // Grupos de tradução que devem ser ignorados pelo sistema de override
        // (mantém comportamento padrão do Laravel para não quebrar funcionalidades core)
        'ignored_groups' => [
            'validation',
            'passwords',
            'pagination',
            'auth',
        ],

        // Configurações de cache
        'cache_enabled' => env('RAPTOR_TRANSLATION_CACHE_ENABLED', true),
        'cache_ttl' => env('RAPTOR_TRANSLATION_CACHE_TTL', 3600), // 1 hora
        'cache_prefix' => 'translation',

        // Locales disponíveis no sistema
        'available_locales' => [
            'pt_BR',
            'en',
            'es',
            'fr',
        ],

        // Locale padrão
        'default_locale' => env('RAPTOR_TRANSLATION_DEFAULT_LOCALE', 'pt_BR'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Database Migrations Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para execução de migrations em múltiplos bancos de dados
    | (tenants, clients e stores com banco dedicado)
    |
    | As migrations podem ser especificadas por:
    | - Nome do arquivo de migration (ex: '2024_01_01_000000_create_users_table.php')
    | - Padrão de busca (ex: '*_create_*_table.php')
    | - Diretório completo (ex: 'database/migrations/tenant')
    |
    */
    'migrations' => [
        // Migrations padrões executadas em TODOS os bancos (tenant, client, store)
        'default' => 'database/migrations/',

        // Migrations específicas para bancos de TENANTS
        'tenant' => 'database/migrations/tenant/',

        // Migrations específicas para bancos de CLIENTS
        'client' => 'database/migrations/client/',

        // Migrations específicas para bancos de STORES
        'store' => 'database/migrations/store/',

        // Models customizados (opcional, usa padrão se não especificado)
        'models' => [
            'tenant' => env('RAPTOR_MIGRATIONS_TENANT_MODEL', 'Callcocam\\LaravelRaptor\\Models\\Tenant'),
            'client' => env('RAPTOR_MIGRATIONS_CLIENT_MODEL', null),
            'store' => env('RAPTOR_MIGRATIONS_STORE_MODEL', null),
        ],

        /*
        |----------------------------------------------------------------------
        | Models com campo "database" para tenant:migrate
        |----------------------------------------------------------------------
        | Para cada entry: busca registros do model com campo database preenchido e
        | roda as migrations das pastas em "paths". Nunca cria nem apaga banco — só migrate.
        | Cada item: model, type, paths (array), name_key, id_key (opcionais).
        */
        'database_models' => [
            [
                'model' => 'raptor.migrations.models.tenant',
                'type' => 'Tenant',
                'name_key' => 'name',
                'paths' => [
                    'database/migrations/',
                    'database/migrations/tenant/',
                ],
            ],
            // [
            //     'model' => 'raptor.migrations.models.client',
            //     'type' => 'Client',
            //     'id_key' => 'client_id',
            //     'name_key' => 'name',
            //     'paths' => ['database/migrations/', 'database/migrations/client/'],
            // ],
            // [
            //     'model' => 'raptor.migrations.models.store',
            //     'type' => 'Store',
            //     'id_key' => 'store_id',
            //     'name_key' => 'name',
            //     'paths' => ['database/migrations/', 'database/migrations/store/'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Services Configuration
    |--------------------------------------------------------------------------
    |
    | Classes de serviços que podem ser customizadas pela aplicação.
    | Configure sua própria implementação para sobrescrever o comportamento padrão.
    |
    | Exemplo de uso:
    | Para criar um TenantResolver customizado que resolve por Client/Store:
    |
    | 1. Crie sua classe implementando TenantResolverInterface:
    |    ```php
    |    namespace App\Services;
    |
    |    use Callcocam\LaravelRaptor\Contracts\TenantResolverInterface;
    |    use Callcocam\LaravelRaptor\Services\TenantResolver;
    |
    |    class MyTenantResolver extends TenantResolver
    |    {
    |        protected function detectAndConfigureTenant(Request $request): mixed
    |        {
    |            // Sua lógica customizada aqui
    |            // Pode resolver por Client, Store, ou outra entidade
    |        }
    |    }
    |    ```
    |
    | 2. Configure neste arquivo:
    |    'services' => [
    |        'tenant_resolver' => \App\Services\MyTenantResolver::class,
    |    ]
    |
    */
    'services' => [
        // Classe responsável por resolver o tenant baseado no domínio
        // Implemente TenantResolverInterface para customizar
        'tenant_resolver' => \Callcocam\LaravelRaptor\Services\TenantResolver::class,
        // 'tenant_resolver' => \App\Services\AdvancedTenantResolver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Configuration (role, permissions, user)
    |--------------------------------------------------------------------------
    |
    | Executada na criação ou edição do tenant, apenas quando o banco do tenant
    | está vazio (sem users, roles ou permissions). Cria uma role Super
    | Administrador (special), gera permissões via PermissionGenerator na
    | conexão do tenant, um usuário e envia email ao endereço do tenant.
    | Role/usuário/permissões existem só no banco do tenant (não compartilhados).
    |
    */
    'tenant_configuration' => [
        'class' => \Callcocam\LaravelRaptor\Services\DefaultTenantConfiguration::class,
        'mail' => \Callcocam\LaravelRaptor\Mail\TenantConfiguredMail::class, // null para não enviar
    ],

];
