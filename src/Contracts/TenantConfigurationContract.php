<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface para configuração do tenant (role, permissões e usuário quando banco vazio).
 * Sempre envia email ao endereço do tenant (novas credenciais ou aviso de atualização).
 */
interface TenantConfigurationContract
{
    /**
     * Configura o tenant quando o banco está vazio; sempre envia email (credenciais ou atualização).
     *
     * @param  Model  $tenant  Model do tenant (ex.: Tenant) com atributos database, email, name
     * @param  bool  $databaseWasEmpty  true se o banco estava vazio (criou user/role/permissions)
     */
    public function run(Model $tenant, bool $databaseWasEmpty): void;
}
