<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use App\Models\User;
use Callcocam\LaravelRaptor\Enums\TenantStatus;
use Callcocam\LaravelRaptor\Models\Permission;
use Callcocam\LaravelRaptor\Models\Tenant;
use Callcocam\LaravelRaptor\Support\Shinobi\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class LaravelRaptorCommand extends Command
{
    public $signature = 'laravel-raptor:install
                        {--fresh : Deleta e recria todas as tabelas}
                        {--tenants : Cria apenas tenants}
                        {--users : Cria apenas usuários}
                        {--roles : Cria apenas roles}
                        {--permissions : Cria apenas permissões}';

    public $description = 'Instala e configura o Laravel Raptor de forma interativa';

    protected array $defaultRoles = [
        'super-admin' => [
            'name' => 'Super Admin',
            'description' => 'Acesso total ao sistema',
            'special' => true,
        ],
        'admin' => [
            'name' => 'Administrador',
            'description' => 'Administrador com acesso amplo',
            'special' => false,
        ],
        'user' => [
            'name' => 'Usuário',
            'description' => 'Usuário padrão do sistema',
            'special' => false,
        ],
    ];

    protected ?string $defaultPassword = null;

    protected ?string $baseDomain = null;

    protected function landlordConnection(): string
    {
        return config('raptor.database.landlord_connection_name', 'landlord');
    }

    public function handle(): int
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║              🚀 Laravel Raptor - Setup Inicial                ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Verifica se deve rodar em modo fresh
        if ($this->option('fresh')) {
            if (! $this->confirmFreshMode()) {
                return self::SUCCESS;
            }
        }

        // Verifica se deve rodar apenas uma parte específica
        $onlyTenants = $this->option('tenants');
        $onlyUsers = $this->option('users');
        $onlyRoles = $this->option('roles');
        $onlyPermissions = $this->option('permissions');

        $runAll = ! ($onlyTenants || $onlyUsers || $onlyRoles || $onlyPermissions);

        if ($runAll) {
            if (! $this->confirm('Deseja executar a configuração completa?', true)) {
                return self::SUCCESS;
            }

            $this->runMigrations();
        }

        $tenant = null;
        $user = null;

        // Gerenciamento de Tenants
        if ($runAll || $onlyTenants) {
            $this->section('📦 Gerenciamento de Tenants');
            $result = $this->manageAllTenants();
            if (is_array($result)) {
                $tenant = $result['tenant'];
                $user = $result['user'] ?? null;
            } else {
                $tenant = $result;
            }
        }

        // Gerenciamento de Usuários
        if ($runAll || $onlyUsers) {
            $this->section('👥 Gerenciamento de Usuários');
            if (! $tenant && $runAll) {
                $tenant = $this->selectTenant();
            }
            if ($tenant && ! $user) {
                $user = $this->manageUser($tenant);
            }
        }

        // Gerenciamento de Roles
        if ($runAll || $onlyRoles) {
            $this->section('🎭 Gerenciamento de Roles');
            $this->manageAllRoles($user);
        }

        // Gerenciamento de Permissões
        if ($runAll || $onlyPermissions) {
            $this->section('🔐 Gerenciamento de Permissões');
            $this->createAllPermissions();
        }

        if ($runAll) {
            $this->clearCaches();
        }

        $this->newLine(2);
        $this->info('✅ Configuração concluída com sucesso!');
        $this->newLine();

        if ($runAll) {
            $this->displayCredentials();
        }

        return self::SUCCESS;
    }

    /**
     * Confirma modo fresh (deletar e recriar)
     */
    protected function confirmFreshMode(): bool
    {
        $this->warn('⚠️  MODO FRESH ATIVADO');
        $this->warn('Isso irá DELETAR todos os dados das seguintes tabelas:');
        $this->line('  - Tenants');
        $this->line('  - Users');
        $this->line('  - Roles');
        $this->line('  - Permissions');
        $this->newLine();

        if (! $this->confirm('Tem certeza que deseja continuar?', false)) {
            $this->info('Operação cancelada.');

            return false;
        }

        if (! $this->confirm('CONFIRMA que deseja DELETAR todos os dados?', false)) {
            $this->info('Operação cancelada.');

            return false;
        }

        $this->truncateTables();

        return true;
    }

    /**
     * Trunca as tabelas
     */
    protected function truncateTables(): void
    {
        $this->info('Limpando tabelas...');

        $conn = $this->landlordConnection();
        $driver = DB::connection($conn)->getDriverName();

        // Desabilitar checagem de chaves estrangeiras conforme o driver
        if ($driver === 'mysql') {
            DB::connection($conn)->statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'pgsql') {
            // PostgreSQL não precisa desabilitar constraints para TRUNCATE CASCADE
        }

        $tables = ['permission_role', 'role_user', 'permission_user', 'permissions', 'roles', 'users', 'tenants'];

        foreach ($tables as $table) {
            if (Schema::connection($conn)->hasTable($table)) {
                if ($driver === 'pgsql') {
                    // PostgreSQL usa TRUNCATE CASCADE
                    DB::connection($conn)->statement("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE");
                } else {
                    DB::connection($conn)->table($table)->truncate();
                }
                $this->line("  ✓ {$table}");
            }
        }

        // Reabilitar checagem de chaves estrangeiras
        if ($driver === 'mysql') {
            DB::connection($conn)->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Tabelas limpas com sucesso!');
        $this->newLine();
    }

    /**
     * Exibe seção
     */
    protected function section(string $title): void
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("  {$title}");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
    }

    /**
     * Executa migrações
     */
    protected function runMigrations(): void
    {
        $this->section('📦 Executando Migrações');

        try {
            // Publicar migrações do pacote
            $this->comment('   Publicando migrações do pacote...');
            Artisan::call('vendor:publish', [
                '--tag' => 'raptor-migrations',
                '--force' => true,
            ]);
            $this->comment('   ✓ Migrações publicadas');

            // Executar migrações
            $this->comment('   Executando migrações...');
            if ($this->option('fresh')) {
                Artisan::call('migrate:fresh', ['--force' => true]);
            } else {
                Artisan::call('migrate', ['--force' => true]);
            }
            $this->comment('   ✓ Migrações executadas');
        } catch (\Exception $e) {
            $this->error('   ✗ Erro ao executar migrações: '.$e->getMessage());
        }
    }

    /**
     * Gerencia todos os tenants
     */
    protected function manageAllTenants()
    {
        $tenantClass = config('raptor.landlord.models.tenant', Tenant::class);
        $tenants = $tenantClass::all();

        if ($tenants->count()) {
            $this->info("Tenants existentes encontrados: {$tenants->count()}");
            $this->table(
                ['ID', 'Nome', 'Slug', 'Status'],
                $tenants->map(fn ($t) => [
                    $t->id,
                    $t->name,
                    $t->slug ?? $t->domain,
                    $t->status instanceof \BackedEnum ? $t->status->value : $t->status,
                ])
            );
            $this->newLine();

            if (! $this->confirm('Deseja criar novos tenants?')) {
                return $this->selectTenant();
            }
        } else {
            $this->info('Nenhum tenant encontrado.');
        }

        // Pergunta se quer criar tenants padrão
        $createDefault = $this->confirm('Deseja criar os tenants padrão (Landlord + Tenant)?', true);

        if ($createDefault) {
            $landlordUser = $this->createDefaultTenants();

            // Retorna o usuário landlord para poder associá-lo ao super-admin
            return ['tenant' => $tenantClass::first(), 'user' => $landlordUser];
        }

        return ['tenant' => $this->createTenant(), 'user' => null];
    }

    /**
     * Cria tenants padrão
     */
    protected function createDefaultTenants()
    {
        $this->info('Criando tenants padrão...');

        $this->baseDomain = $this->ask('Qual o domínio base?', $this->getBaseHost());
        $this->defaultPassword = $this->secret('Qual a senha padrão para os usuários?') ?: 'password';

        $tenantClass = config('raptor.landlord.models.tenant', Tenant::class);
        $userClass = config('auth.providers.users.model', User::class);

        // Tenant Landlord (Administração)
        $landlord = $tenantClass::create([
            'name' => 'Landlord - Administração',
            'slug' => config('raptor.landlord.subdomain', 'landlord'),
            'subdomain' => config('raptor.landlord.subdomain', 'landlord'),
            'domain' => $this->baseDomain,
            'status' => TenantStatus::Published,
        ]);
        $this->line("  ✓ Landlord criado: {$landlord->name}");

        // Cria usuário para Landlord
        $landlordUser = $userClass::create([
            'name' => 'Administrador Landlord',
            'email' => "landlord@{$this->baseDomain}",
            'password' => Hash::make($this->defaultPassword),
            'email_verified_at' => now(),
            'tenant_id' => null,
        ]);
        $this->line("  ✓ Usuário Landlord criado: {$landlordUser->email}");

        // Tenant Cliente
        $tenant = $tenantClass::create([
            'name' => 'Tenant - Área do Cliente',
            'slug' => 'tenant',
            'subdomain' => 'tenant',
            'domain' => $this->baseDomain,
            'status' => TenantStatus::Published,
        ]);
        $this->line("  ✓ Tenant Cliente criado: {$tenant->name}");

        // Cria usuário para Tenant
        $tenantUser = $userClass::create([
            'name' => 'Administrador Tenant',
            'email' => "tenant@{$this->baseDomain}",
            'password' => Hash::make($this->defaultPassword),
            'email_verified_at' => now(),
            'tenant_id' => $tenant->id,
        ]);
        $this->line("  ✓ Usuário Tenant criado: {$tenantUser->email}");

        $this->newLine();
        $this->info('Tenants e usuários padrão criados com sucesso!');

        // Retorna o usuário landlord para ser associado ao super-admin
        return $landlordUser;
    }

    /**
     * Seleciona um tenant existente
     */
    protected function selectTenant()
    {
        $tenantClass = config('raptor.landlord.models.tenant', Tenant::class);
        $tenants = $tenantClass::all();

        if ($tenants->isEmpty()) {
            $this->error('Nenhum tenant encontrado.');

            return null;
        }

        $choices = $tenants->pluck('name', 'id')->toArray();
        $tenantId = $this->choice('Qual tenant você deseja utilizar?', $choices);

        return $tenantClass::find($tenantId);
    }

    /**
     * Cria um tenant customizado
     */
    protected function createTenant()
    {
        $this->comment('Criando tenant customizado...');

        $name = $this->ask('Qual o nome do tenant?', 'Minha Empresa');
        $slug = $this->ask('Qual o slug do tenant?', str($name)->slug());

        if (! $this->baseDomain) {
            $this->baseDomain = $this->ask('Qual o domínio base?', $this->getBaseHost());
        }

        $domain = $this->ask('Qual o domínio completo do tenant?', "{$slug}.{$this->baseDomain}");
        $status = $this->choice('Qual o status do tenant?', ['published', 'draft'], 'published');

        $tenantClass = config('raptor.landlord.models.tenant', Tenant::class);
        $tenant = $tenantClass::create([
            'name' => $name,
            'slug' => $slug,
            'domain' => $domain,
            'status' => $status === 'published' ? TenantStatus::Published : TenantStatus::Draft,
        ]);

        $this->info("Tenant `{$name}` criado com sucesso.");

        return $tenant;
    }

    /**
     * Gerencia usuário
     */
    protected function manageUser($tenant)
    {
        $userClass = config('auth.providers.users.model', User::class);
        $users = $userClass::where('tenant_id', $tenant->id)->get();

        if ($users->count()) {
            $this->info("Usuários existentes encontrados para este tenant: {$users->count()}");

            if (! $this->confirm('Deseja criar um novo usuário?')) {
                $choices = $users->pluck('name', 'id')->toArray();
                $userId = $this->choice('Qual usuário você deseja utilizar?', $choices);

                return $userClass::find($userId);
            }
        } else {
            $this->info('Nenhum usuário encontrado para este tenant.');
        }

        return $this->createUser($tenant);
    }

    /**
     * Cria um usuário
     */
    protected function createUser($tenant)
    {
        $this->comment('Criando usuário...');

        $name = $this->ask('Qual o nome do usuário?', 'Admin');
        $email = $this->ask('Qual o email do usuário?', sprintf('admin@%s', $this->baseDomain ?? $this->getBaseHost()));

        $userClass = config('auth.providers.users.model', User::class);

        if ($userClass::where('email', $email)->exists()) {
            $this->error('Usuário com este email já existe');

            return $this->manageUser($tenant);
        }

        if (! $this->defaultPassword) {
            $this->defaultPassword = $this->secret('Qual a senha do usuário?') ?: 'password';
        }

        $user = $userClass::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($this->defaultPassword),
            'email_verified_at' => now(),
        ]);

        $this->info("Usuário `{$name}` criado com sucesso.");

        return $user;
    }

    /**
     * Gerencia todas as roles
     */
    protected function manageAllRoles($user = null): void
    {
        $roleClass = config('raptor.shinobi.models.role', Role::class);
        $roles = $roleClass::all();

        if ($roles->count()) {
            $this->info("Roles existentes encontradas: {$roles->count()}");
            $this->table(
                ['ID', 'Nome', 'Slug', 'Descrição'],
                $roles->map(fn ($r) => [$r->id, $r->name, $r->slug, $r->description ?? '-'])
            );
            $this->newLine();

            if (! $this->confirm('Deseja criar novas roles?')) {
                if ($user && $this->confirm('Deseja associar o usuário a uma role existente?')) {
                    $this->associateUserToRole($user);
                }

                return;
            }
        } else {
            $this->info('Nenhuma role encontrada.');
        }

        // Pergunta se quer criar roles padrão
        if ($this->confirm('Deseja criar as roles padrão (super-admin, admin, user)?', true)) {
            $this->createDefaultRoles($user);
        } else {
            $this->createCustomRole($user);
        }
    }

    /**
     * Cria roles padrão
     */
    protected function createDefaultRoles($user = null): void
    {
        $this->info('Criando roles padrão...');

        $roleClass = config('raptor.shinobi.models.role', Role::class);
        $createdRoles = [];

        foreach ($this->defaultRoles as $slug => $roleData) {
            if ($roleClass::where('slug', $slug)->exists()) {
                $this->line("  ⊗ Role '{$roleData['name']}' já existe, pulando...");

                continue;
            }

            $role = $roleClass::create([
                'name' => $roleData['name'],
                'slug' => $slug,
                'description' => $roleData['description'],
                'special' => $roleData['special'] ?? false,
            ]);

            $createdRoles[] = $role;
            $this->line("  ✓ Role criada: {$role->name} ({$slug})");
        }

        $this->newLine();
        $this->info(count($createdRoles).' roles criadas com sucesso!');

        // Associa automaticamente o usuário landlord ao super-admin
        if ($user) {
            $superAdmin = $roleClass::where('slug', 'super-admin')->first();
            if ($superAdmin) {
                // Verifica se é o usuário landlord (sem tenant_id)
                if (is_null($user->tenant_id)) {
                    $user->roles()->sync([$superAdmin->id]);
                    $this->info("Usuário Landlord associado automaticamente à role 'Super Admin'!");
                } elseif ($this->confirm('Deseja associar o usuário à role super-admin?', true)) {
                    $user->roles()->sync([$superAdmin->id]);
                    $this->info("Usuário associado à role 'Super Admin'!");
                }
            }
        }
    }

    /**
     * Cria role customizada
     */
    protected function createCustomRole($user = null): void
    {
        $roleName = $this->ask('Qual o nome da role?', 'Gerente');
        $slug = $this->ask('Qual o slug da role?', str($roleName)->slug());
        $description = $this->ask('Descrição da role?', "Role para {$roleName}");
        $special = $this->confirm('Esta role tem acesso total (all-access)?');

        $roleClass = config('raptor.shinobi.models.role', Role::class);

        if ($roleClass::where('slug', $slug)->exists()) {
            $this->error("Role com slug '{$slug}' já existe.");

            return;
        }

        $role = $roleClass::create([
            'name' => $roleName,
            'slug' => $slug,
            'description' => $description,
            'special' => $special,
        ]);

        $this->info("Role `{$roleName}` criada com sucesso.");

        if ($user && $this->confirm('Deseja associar o usuário a esta role?')) {
            $user->roles()->sync([$role->id]);
            $this->info("Usuário associado à role '{$roleName}'!");
        }

        if ($this->confirm('Criar outra role?')) {
            $this->createCustomRole($user);
        }
    }

    /**
     * Associa usuário a uma role
     */
    protected function associateUserToRole($user): void
    {
        $roleClass = config('raptor.shinobi.models.role', Role::class);
        $choices = $roleClass::pluck('name', 'id')->toArray();

        $roleId = $this->choice('Qual role você deseja associar?', $choices);
        $role = $roleClass::find($roleId);

        if ($user && $role) {
            $user->roles()->sync([$role->id]);
            $this->info("Usuário associado à role '{$role->name}' com sucesso!");
        }
    }

    /**
     * Cria permissões baseadas na navegação
     */
    protected function createAllPermissions(): void
    {
        $this->info('Gerando permissões básicas...');

        $permissionClass = config('raptor.shinobi.models.permission', Permission::class);

        $basicPermissions = [
            ['name' => 'Visualizar Dashboard', 'slug' => 'dashboard.view', 'description' => 'Visualizar o dashboard'],
            ['name' => 'Visualizar Usuários', 'slug' => 'users.viewAny', 'description' => 'Visualizar lista de usuários'],
            ['name' => 'Criar Usuário', 'slug' => 'users.create', 'description' => 'Criar novo usuário'],
            ['name' => 'Editar Usuário', 'slug' => 'users.update', 'description' => 'Editar usuário existente'],
            ['name' => 'Deletar Usuário', 'slug' => 'users.delete', 'description' => 'Deletar usuário'],
            ['name' => 'Visualizar Roles', 'slug' => 'roles.viewAny', 'description' => 'Visualizar lista de roles'],
            ['name' => 'Criar Role', 'slug' => 'roles.create', 'description' => 'Criar nova role'],
            ['name' => 'Editar Role', 'slug' => 'roles.update', 'description' => 'Editar role existente'],
            ['name' => 'Deletar Role', 'slug' => 'roles.delete', 'description' => 'Deletar role'],
            ['name' => 'Visualizar Permissões', 'slug' => 'permissions.viewAny', 'description' => 'Visualizar lista de permissões'],
        ];

        $count = 0;
        foreach ($basicPermissions as $permData) {
            if ($permissionClass::where('slug', $permData['slug'])->exists()) {
                continue;
            }

            $permissionClass::create($permData);
            $count++;
            $this->line("  ✓ {$permData['name']}");
        }

        $this->newLine();
        $this->info("{$count} permissões criadas com sucesso!");
    }

    /**
     * Limpa caches
     */
    protected function clearCaches(): void
    {
        $this->section('🧹 Limpando Caches');

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            $this->comment('   ✓ Caches limpos');
        } catch (\Exception $e) {
            $this->error('   ✗ Erro ao limpar caches: '.$e->getMessage());
        }
    }

    /**
     * Exibe credenciais
     */
    protected function displayCredentials(): void
    {
        $domain = $this->baseDomain ?? $this->getBaseHost();

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('  📝 Credenciais de Acesso');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->line('  <fg=cyan>Landlord (Administrador Principal)</>');
        $this->line('  URL:   <fg=yellow>http://landlord.'.$domain.'</>');
        $this->line('  Email: <fg=yellow>landlord@'.$domain.'</>');
        $this->line('  Senha: <fg=yellow>'.($this->defaultPassword ?? 'password').'</>');
        $this->newLine();
        $this->line('  <fg=cyan>Tenant (Cliente)</>');
        $this->line('  URL:   <fg=yellow>http://tenant.'.$domain.'</>');
        $this->line('  Email: <fg=yellow>tenant@'.$domain.'</>');
        $this->line('  Senha: <fg=yellow>'.($this->defaultPassword ?? 'password').'</>');
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->comment('💡 Dica: Não esqueça de configurar seu /etc/hosts ou DNS');
    }

    /**
     * Retorna o host base
     */
    protected function getBaseHost(): string
    {
        if (request()->getHost() && request()->getHost() !== 'localhost') {
            return request()->getHost();
        }

        $appUrl = config('app.url', 'http://localhost');
        $parsedUrl = parse_url($appUrl);

        return $parsedUrl['host'] ?? 'localhost';
    }
}
