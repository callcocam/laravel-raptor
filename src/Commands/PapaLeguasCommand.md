<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\PapaLeguas\Commands;

use App\Models\Tenant;
use App\Models\User;
use Callcocam\PapaLeguas\Enums\Menu\ContextEnum;
use Callcocam\PapaLeguas\Enums\PermissionStatus;
use Callcocam\PapaLeguas\Enums\UserStatus;
use Callcocam\PapaLeguas\Services\Menu\VueRouteGeneratorService;
use Callcocam\PapaLeguas\Support\Shinobi\Models\Permission;
use Callcocam\PapaLeguas\Support\Shinobi\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class PapaLeguasCommand extends Command
{
    public $signature = 'papa-leguas:setup
                        {--fresh : Deleta e recria todas as tabelas}
                        {--tenants : Cria apenas tenants}
                        {--users : Cria apenas usuários}
                        {--roles : Cria apenas roles}
                        {--permissions : Cria apenas permissões}';

    public $description = 'Configura recursos iniciais para o PapaLeguas, como tenants, usuários, funções e permissões.';

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

    public function handle(): int
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║              🚀 Papa Leguas - Setup Inicial                   ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Verifica se deve rodar em modo fresh
        if ($this->option('fresh')) {
            if (!$this->confirmFreshMode()) {
                return self::SUCCESS;
            }
        }

        // Verifica se deve rodar apenas uma parte específica
        $onlyTenants = $this->option('tenants');
        $onlyUsers = $this->option('users');
        $onlyRoles = $this->option('roles');
        $onlyPermissions = $this->option('permissions');

        $runAll = !($onlyTenants || $onlyUsers || $onlyRoles || $onlyPermissions);

        if ($runAll) {
            if (!$this->confirm('Deseja executar a configuração completa?', true)) {
                return self::SUCCESS;
            }
        }

        $tenant = null;
        $user = null;

        // Gerenciamento de Tenants
        if ($runAll || $onlyTenants) {
            $this->section('📦 Gerenciamento de Tenants');
            $tenant = $this->manageAllTenants();
        }

        // Gerenciamento de Usuários
        if ($runAll || $onlyUsers) {
            $this->section('👥 Gerenciamento de Usuários');
            if (!$tenant && $runAll) {
                $tenant = $this->selectTenant();
            }
            if ($tenant) {
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

        $this->newLine(2);
        $this->info('✅ Configuração concluída com sucesso!');
        $this->newLine();

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

        if (!$this->confirm('Tem certeza que deseja continuar?', false)) {
            $this->info('Operação cancelada.');
            return false;
        }

        if (!$this->confirm('CONFIRMA que deseja DELETAR todos os dados?', false)) {
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

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if (Schema::hasTable('permission_role')) {
            DB::table('permission_role')->truncate();
            $this->line('  ✓ permission_role');
        }

        if (Schema::hasTable('role_user')) {
            DB::table('role_user')->truncate();
            $this->line('  ✓ role_user');
        }

        $permissionsTable = config('shinobi.tables.permissions', 'permissions');
        if (Schema::hasTable($permissionsTable)) {
            DB::table($permissionsTable)->truncate();
            $this->line("  ✓ {$permissionsTable}");
        }

        $rolesTable = config('shinobi.tables.roles', 'roles');
        if (Schema::hasTable($rolesTable)) {
            DB::table($rolesTable)->truncate();
            $this->line("  ✓ {$rolesTable}");
        }

        if (Schema::hasTable('users')) {
            DB::table('users')->truncate();
            $this->line('  ✓ users');
        }

        if (Schema::hasTable('tenants')) {
            DB::table('tenants')->truncate();
            $this->line('  ✓ tenants');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('Tabelas limpas com sucesso!');
        $this->newLine();
    }

    /**
     * Exibe seção
     */
    protected function section(string $title): void
    {
        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("  {$title}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();
    }

    /**
     * Gerencia todos os tenants (cria landlord e tenant)
     */
    protected function manageAllTenants()
    {
        $tenants = Tenant::all();

        if ($tenants->count()) {
            $this->info("Tenants existentes encontrados: {$tenants->count()}");
            $this->table(
                ['ID', 'Nome', 'Domínio', 'Email', 'Status'],
                $tenants->map(fn($t) => [$t->id, $t->name, $t->domain, $t->email, $t->status])
            );
            $this->newLine();

            if (!$this->confirm('Deseja criar novos tenants?')) {
                return $this->selectTenant();
            }
        } else {
            $this->info('Nenhum tenant encontrado.');
        }

        // Pergunta quantos tenants criar
        $createDefault = $this->confirm('Deseja criar os tenants padrão (Landlord + Tenant)?', true);

        if ($createDefault) {
            $this->createDefaultTenants();
            return Tenant::first();
        }

        return $this->createTenant();
    }

    /**
     * Cria tenants padrão
     */
    protected function createDefaultTenants(): void
    {
        $this->info('Criando tenants padrão...');

        $domain = $this->ask('Qual o domínio base?', request()->getHost());
        $defaultPassword = $this->ask('Qual a senha padrão para os usuários?', 'password');

        // Tenant Landlord (Administração)
        $landlord = Tenant::create([
            'name' => 'Landlord - Administração',
            'domain' => "landlord.{$domain}",
            'email' => "admin@{$domain}",
            'status' => 'published',
        ]);
        $this->line("  ✓ Landlord criado: {$landlord->name}");

        // Cria usuário para Landlord
        $landlordUser = User::create([
            'name' => 'Administrador Landlord',
            'email' => "landlord@{$domain}",
            'password' => bcrypt($defaultPassword),
            'tenant_id' => $landlord->id,
            'status' => UserStatus::Published->value,
        ]);
        $this->line("  ✓ Usuário Landlord criado: {$landlordUser->email}");

        // Tenant Cliente
        $tenantClient = Tenant::create([
            'name' => 'Tenant - Área do Cliente',
            'domain' => "tenant.{$domain}",
            'email' => "cliente@{$domain}",
            'status' => 'published',
        ]);
        $this->line("  ✓ Tenant Cliente criado: {$tenantClient->name}");

        // Cria usuário para Tenant
        $tenantUser = User::create([
            'name' => 'Administrador Tenant',
            'email' => "tenant@{$domain}",
            'password' => bcrypt($defaultPassword),
            'tenant_id' => $tenantClient->id,
            'status' => UserStatus::Published->value,
        ]);
        $this->line("  ✓ Usuário Tenant criado: {$tenantUser->email}");

        $this->newLine();
        $this->info('Tenants e usuários padrão criados com sucesso!');
        $this->comment("Senha padrão: {$defaultPassword}");
    }

    /**
     * Seleciona um tenant existente
     */
    protected function selectTenant()
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('Nenhum tenant encontrado.');
            return null;
        }

        $tenantId = $this->choice(
            'Qual tenant você deseja utilizar?',
            Tenant::pluck('name', 'id')->toArray()
        );

        return Tenant::find($tenantId);
    }

    /**
     * Gerencia Tenants - permite selecionar existente ou criar novo
     */
    protected function manageTenant()
    {
        $tenants = Tenant::all();

        if ($tenants->count()) {
            $this->info('Tenants existentes encontrados: ' . $tenants->count());

            if ($this->confirm('Deseja criar um novo tenant?')) {
                return $this->createTenant();
            } else {
                $tenantId = $this->choice('Qual tenant você deseja utilizar?', Tenant::pluck('name', 'id')->toArray());
                return Tenant::find($tenantId);
            }
        } else {
            $this->info('Nenhum tenant encontrado.');
            return $this->createTenant();
        }
    }

    /**
     * Gerencia Usuários - permite selecionar existente ou criar novo
     */
    protected function manageUser($tenant)
    {
        $users = User::all();

        if ($users->count()) {
            $this->info('Usuários existentes encontrados: ' . $users->count());

            if ($this->confirm('Deseja criar um novo usuário?')) {
                return $this->createUsers($tenant);
            } else {
                $userId = $this->choice('Qual usuário você deseja utilizar?', User::pluck('name', 'id')->toArray());
                return User::find($userId);
            }
        } else {
            $this->info('Nenhum usuário encontrado.');
            return $this->createUsers($tenant);
        }
    }

    /**
     * Gerencia todas as roles
     */
    protected function manageAllRoles($user = null): void
    {
        $roles = Role::all();

        if ($roles->count()) {
            $this->info("Roles existentes encontradas: {$roles->count()}");
            $this->table(
                ['ID', 'Nome', 'Slug', 'Descrição', 'Special'],
                $roles->map(fn($r) => [$r->id, $r->name, $r->slug, $r->description, $r->special ?? '-'])
            );
            $this->newLine();

            if (!$this->confirm('Deseja criar novas roles?')) {
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

        $createdRoles = [];

        foreach ($this->defaultRoles as $slug => $roleData) {
            if (Role::where('slug', $slug)->exists()) {
                $this->line("  ⊗ Role '{$roleData['name']}' já existe, pulando...");
                continue;
            }

            $role = Role::create([
                'name' => $roleData['name'],
                'slug' => $slug,
                'description' => $roleData['description'],
                'special' => $roleData['special'],
            ]);

            $createdRoles[] = $role;
            $this->line("  ✓ Role criada: {$role->name} ({$slug})");
        }

        $this->newLine();
        $this->info(count($createdRoles) . ' roles criadas com sucesso!');

        // Associa usuário ao super-admin se existir
        if ($user && count($createdRoles) > 0) {
            if ($this->confirm('Deseja associar o usuário à role super-admin?', true)) {
                $superAdmin = Role::where('slug', 'super-admin')->first();
                if ($superAdmin) {
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
        $roleName = $this->ask('Qual o nome da função (role)?', 'Gerente');
        $isAdministrator = $this->confirm('Esta função tem acesso total (all-access)?');

        $this->createRole($roleName, $user, $isAdministrator);

        if ($this->confirm('Criar outra role?')) {
            $this->createCustomRole($user);
        }
    }

    /**
     * Associa usuário a uma role
     */
    protected function associateUserToRole($user): void
    {
        $roleId = $this->choice(
            'Qual função (role) você deseja associar?',
            Role::pluck('name', 'id')->toArray()
        );
        $role = Role::find($roleId);

        if ($user && $role) {
            $user->roles()->sync([$role->id]);
            $this->info("Usuário associado à função '{$role->name}' com sucesso!");
        }
    }

    /**
     * Gerencia Roles - permite criar múltiplas roles
     */
    protected function manageRole($user)
    {
        $roles = Role::all();

        if ($roles->count()) {
            $this->info('Funções (roles) existentes encontradas: ' . $roles->count());

            if ($this->confirm('Deseja associar o usuário a uma role existente?')) {
                $roleId = $this->choice('Qual função (role) você deseja associar?', Role::pluck('name', 'id')->toArray());
                $role = Role::find($roleId);

                if ($user) {
                    $user->assignRole($role);
                    $this->info("Usuário associado à função '{$role->name}' com sucesso!");
                }
            }
        }

        if ($this->confirm('Deseja criar uma nova função (role)?')) {
            $roleName = $this->ask('Qual o nome da função (role) que deseja criar?', 'Super Admin');
            $isAdministrator = $this->confirm('Esta função é de administrador?');

            if ($isAdministrator) {
                $this->createRole($roleName, $user, true);
            } else {
                $this->createRole($roleName, $user);
            }
        }
    }

    protected function createTenant()
    {
        $this->comment('Criando tenant');

        $name = $this->ask('Qual o nome do tenant?', fake()->company);
        $domain = $this->ask('Qual o domínio do tenant?', request()->getHost());
        $email = $this->ask('Qual o email do tenant?', fake()->email);
        $status = $this->choice('Qual o status do tenant?', ['published', 'draft'], 'published');

        $tenant = Tenant::create([
            'name' => $name,
            'domain' => $domain,
            'email' => $email,
            'status' => $status,
        ]);

        $this->info("Tenant `{$name}` criado com sucesso.");

        return $tenant;
    }

    protected function createUsers($tenant = null)
    {
        $this->comment('Criando usuário');

        $name = $this->ask('Qual o nome do usuário?', 'Admin');
        $email = $this->ask('Qual o email do usuário?', sprintf('admin@%s', request()->getHost()));
        $status = $this->choice('Qual o status do usuário?', UserStatus::getOptions(), UserStatus::Published->value);

        if (User::where('email', $email)->count()) {
            $this->error('Usuário já existe');
            return $this->manageUser($tenant);
        }

        if (!$tenant) {
            $tenant = $this->manageTenant();
        }

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'status' => $status,
        ]);

        // $user->tenant()->associate($tenant);
        $user->save();

        $this->info("Usuário `{$name}` criado com sucesso.");

        return $user;
    }

    protected function createRole($role, $user = null, $permission = false)
    {
        $this->comment("Criando função (role) `{$role}`");

        if (Role::where('slug', str($role)->slug())->exists()) {
            $this->error("Função (role) `{$role}` já existe.");
            return;
        }

        $newRole = Role::create([
            'name' => $role,
            'slug' => str($role)->slug(),
            'description' => "Função para {$role}",
            'special' => $permission
        ]);
        if ($user) {
            $user->roles()->sync([$newRole->id]);
            $this->info("Usuário associado à função '{$role}' com sucesso!");
        }

        $this->info("Função (role) `{$role}` criada com sucesso.");

        if ($this->confirm('Criar outra função (role)?')) {
            $roleName = $this->ask('Qual o nome da função (role) que deseja criar?', 'Super Admin');
            $isAdministrator = $this->confirm('Esta função é de administrador?');

            if ($isAdministrator) {
                $this->createRole($roleName, $user, true);
            } else {
                $this->createRole($roleName, $user);
            }
        }
    }

    /**
     * Cria todas as permissões baseadas nos contextos
     */
    protected function createAllPermissions(): void
    {
        $this->info('Gerando permissões baseadas nas rotas Vue...');
        $this->newLine();

        $contexts = $this->selectContexts();

        $totalCreated = 0;

        foreach ($contexts as $context) {
            $this->line("Processando contexto: {$context->label()}");
            $count = $this->createPermissionsForContext($context);
            $totalCreated += $count;
            $this->line("  ✓ {$count} permissões criadas para {$context->label()}");
        }

        $this->newLine();
        $this->info("Total de {$totalCreated} permissões criadas com sucesso!");
    }

    /**
     * Seleciona os contextos para gerar permissões
     */
    protected function selectContexts(): array
    {
        $createBoth = $this->confirm('Deseja criar permissões para ambos os contextos (LANDLORD e TENANT)?', true);

        if ($createBoth) {
            return ContextEnum::all();
        }

        $contextChoice = $this->choice(
            'Qual contexto deseja gerar permissões?',
            ['LANDLORD' => 'Landlord (Administração)', 'TENANT' => 'Tenant (Cliente)']
        );

        return [$contextChoice === 'LANDLORD' ? ContextEnum::LANDLORD : ContextEnum::TENANT];
    }

    /**
     * Cria permissões para um contexto específico
     */
    protected function createPermissionsForContext(ContextEnum $context): int
    {
        try {
            // Usa o VueRouteGeneratorService para obter as rotas
            $routeGenerator = VueRouteGeneratorService::make($context)->withCache(false);
            $routes = $routeGenerator->generate();

            $count = 0;

            foreach ($routes as $routeData) {
                // Extrai informações da rota
                $label = $routeData['label'] ?? null;
                $routeInfo = $routeData['routes'] ?? null;

                if (!$routeInfo) {
                    continue;
                }

                // Processa rota principal
                $count += $this->createPermissionFromRoute($routeInfo, $context, $label);

                // Processa rotas filhas (children)
                if (isset($routeInfo['children']) && is_array($routeInfo['children'])) {
                    foreach ($routeInfo['children'] as $childRoute) {
                        $count += $this->createPermissionFromRoute($childRoute, $context, $label);
                    }
                }
            }

            return $count;
        } catch (\Exception $e) {
            $this->error("Erro ao gerar permissões para contexto {$context->label()}: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Cria permissão a partir de uma rota
     */
    protected function createPermissionFromRoute(array $route, ContextEnum $context, ?string $label): int
    {
        $routeName = $route['name'] ?? null;
        $meta = $route['meta'] ?? [];
        $action = $meta['action'] ?? 'view';

        if (!$routeName) {
            return 0;
        }

        // Ignora rotas de sistema/autenticação
        $excludedRoutes = [
            'login', 'logout', 'register',
            'password.request', 'password.email', 'password.reset',
            'verification.notice', 'verification.verify', 'verification.send',
            'sanctum.csrf-cookie'
        ];

        if (in_array($routeName, $excludedRoutes)) {
            return 0;
        }

        // Verifica se já existe
        if (Permission::where('slug', $routeName)->exists()) {
            return 0;
        }

        // Cria nome amigável
        $permissionName = $this->generatePermissionName($routeName, $label, $action);

        // Cria a permissão
        Permission::create([
            'name' => $permissionName,
            'slug' => $routeName,
            'description' => "Permissão para {$permissionName} ({$context->label()})",
            'status' => PermissionStatus::Published->value,
        ]);

        return 1;
    }

    /**
     * Gera nome amigável para a permissão
     */
    protected function generatePermissionName(string $routeName, ?string $label, string $action): string
    {
        // Remove prefixo do contexto se existir
        $cleanName = str_replace(['api.landlord.', 'api.tenant.'], '', $routeName);

        // Substitui pontos por espaços e capitaliza
        $name = str_replace('.', ' ', $cleanName);
        $name = ucwords($name);

        // Se tiver label, usa ela com a ação
        if ($label && $action !== 'index') {
            $actionLabel = match ($action) {
                'list' => 'Listar',
                'create' => 'Criar',
                'show' => 'Visualizar',
                'edit' => 'Editar',
                'destroy' => 'Deletar',
                'store' => 'Salvar',
                'update' => 'Atualizar',
                default => ucfirst($action),
            };

            return "{$actionLabel} {$label}";
        }

        return $name;
    }

    /**
     * Método legado - mantido para compatibilidade
     */
    protected function createPermission()
    {
        $this->comment("Criando permissões baseadas nas rotas do sistema...");

        $routes = Route::getRoutes();
        $count = 0;

        foreach ($routes as $route) {
            if (isset($route->action['as'])) {
                $name = str_replace('.', ' ', $route->action['as']);
                // Ignora rotas que não devem gerar permissões
                if (in_array($route->getName(), ['login', 'logout', 'register', 'password.request', 'password.email', 'password.reset', 'verification.notice', 'verification.verify', 'verification.send', 'sanctum.csrf-cookie'])) {
                    continue;
                }
                $name = ucwords($name);

                $slug = $route->action['as'];

                if (Permission::where('slug', $slug)->count()) {
                    continue;
                }

                Permission::create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => "Permissão para {$name}",
                    'status' => PermissionStatus::Published->value,
                ]);

                $count++;
            }
        }

        $this->info("Total de {$count} permissões criadas com sucesso.");
    }
}
