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
    | Custom Domains
    |--------------------------------------------------------------------------
    |
    | Habilita suporte para domínios customizados dos tenants
    | Se true, tenants podem ter seus próprios domínios (ex: cliente.com.br)
    |
    */
    'enable_custom_domains' => env('RAPTOR_ENABLE_CUSTOM_DOMAINS', false),

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
            'translate' => \Callcocam\LaravelRaptor\Models\TranslationOverride::class,
        ]
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
    | Site Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para o site principal da aplicação
    |
    */
    'site' => [
        // Middleware aplicado às rotas do site
        'middleware' => ['web'],
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

        // Prefixo para bancos de dados separados (apenas se strategy = 'separate')
        'prefix' => env('RAPTOR_DB_PREFIX', 'tenant_'),

        // Nome da coluna que identifica o tenant nas tabelas compartilhadas
        'tenant_column' => 'tenant_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações de cache por tenant
    |
    */
    'cache' => [
        // Prefixo para chaves de cache dos tenants
        'prefix' => 'tenant',

        // TTL padrão do cache (em segundos)
        'ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações de armazenamento de arquivos por tenant
    |
    */
    'storage' => [
        // Disco de armazenamento padrão para arquivos dos tenants
        'disk' => env('RAPTOR_STORAGE_DISK', 'public'),

        // Prefixo do path para arquivos dos tenants
        'path_prefix' => 'tenants',

        // Organizar por tenant ID automaticamente
        'organize_by_tenant' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações de segurança
    |
    */
    'security' => [
        // Habilita isolamento estrito entre tenants
        'strict_isolation' => true,

        // Previne acesso cross-tenant (tenant A acessar dados do tenant B)
        'prevent_cross_tenant_access' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Injector Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para o TenantRouteInjector
    | Define quais diretórios de controllers serão escaneados para
    | registrar rotas automaticamente
    |
    */
    'route_injector' => [
        // Diretórios de controllers para scan automático
        // Formato: 'Namespace' => 'path/to/controllers'
        'directories' => [
            // Controllers da aplicação
            'App\\Http\\Controllers\\Tenant' => app_path('Http/Controllers/Tenant'),
            'App\\Http\\Controllers\\Landlord' => app_path('Http/Controllers/Landlord'),

            // Controllers do package Raptor
            'Callcocam\\LaravelRaptor\\Http\\Controllers\\Admin' => base_path('vendor/callcocam/laravel-raptor/src/Http/Controllers/Admin'),
            'Callcocam\\LaravelRaptor\\Http\\Controllers\\Tenant' => base_path('vendor/callcocam/laravel-raptor/src/Http/Controllers/Tenant'),
            'Callcocam\\LaravelRaptor\\Http\\Controllers\\Landlord' => base_path('vendor/callcocam/laravel-raptor/src/Http/Controllers/Landlord'),

            // Adicione mais diretórios conforme necessário:
            // 'Seu\\Namespace\\Controllers' => base_path('caminho/para/controllers'),
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

];
