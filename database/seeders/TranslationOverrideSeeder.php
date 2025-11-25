<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Database\Seeders;

use Callcocam\LaravelRaptor\Models\Tenant;
use Callcocam\LaravelRaptor\Models\TranslationOverride;
use Illuminate\Database\Seeder;

class TranslationOverrideSeeder extends Seeder
{
    /**
     * Seed the application's database with translation overrides.
     */
    public function run(): void
    {
        // ========================================
        // TRADUÇÕES GLOBAIS (tenant_id = NULL)
        // ========================================

        $this->seedGlobalTranslations();

        // ========================================
        // OVERRIDES PARA TENANTS ESPECÍFICOS
        // ========================================

        // Busca tenants de exemplo (se existirem)
        $tenants = Tenant::limit(3)->get();

        if ($tenants->isNotEmpty()) {
            foreach ($tenants as $index => $tenant) {
                $this->seedTenantOverrides($tenant, $index);
            }
        }
    }

    /**
     * Seed traduções globais do sistema
     */
    protected function seedGlobalTranslations(): void
    {
        $globalTranslations = [
            // Grupo: products
            ['group' => 'products', 'key' => 'product', 'locale' => 'pt_BR', 'value' => 'Produto'],
            ['group' => 'products', 'key' => 'product', 'locale' => 'en', 'value' => 'Product'],
            ['group' => 'products', 'key' => 'product', 'locale' => 'es', 'value' => 'Producto'],

            ['group' => 'products', 'key' => 'add_to_cart', 'locale' => 'pt_BR', 'value' => 'Adicionar ao Carrinho'],
            ['group' => 'products', 'key' => 'add_to_cart', 'locale' => 'en', 'value' => 'Add to Cart'],
            ['group' => 'products', 'key' => 'add_to_cart', 'locale' => 'es', 'value' => 'Añadir al Carrito'],

            ['group' => 'products', 'key' => 'price', 'locale' => 'pt_BR', 'value' => 'Preço'],
            ['group' => 'products', 'key' => 'price', 'locale' => 'en', 'value' => 'Price'],
            ['group' => 'products', 'key' => 'price', 'locale' => 'es', 'value' => 'Precio'],

            // Grupo: cart
            ['group' => 'cart', 'key' => 'cart', 'locale' => 'pt_BR', 'value' => 'Carrinho'],
            ['group' => 'cart', 'key' => 'cart', 'locale' => 'en', 'value' => 'Cart'],
            ['group' => 'cart', 'key' => 'cart', 'locale' => 'es', 'value' => 'Carrito'],

            ['group' => 'cart', 'key' => 'checkout', 'locale' => 'pt_BR', 'value' => 'Finalizar Compra'],
            ['group' => 'cart', 'key' => 'checkout', 'locale' => 'en', 'value' => 'Checkout'],
            ['group' => 'cart', 'key' => 'checkout', 'locale' => 'es', 'value' => 'Finalizar Compra'],

            ['group' => 'cart', 'key' => 'empty', 'locale' => 'pt_BR', 'value' => 'Seu carrinho está vazio'],
            ['group' => 'cart', 'key' => 'empty', 'locale' => 'en', 'value' => 'Your cart is empty'],
            ['group' => 'cart', 'key' => 'empty', 'locale' => 'es', 'value' => 'Tu carrito está vacío'],

            // Grupo: checkout
            ['group' => 'checkout', 'key' => 'title', 'locale' => 'pt_BR', 'value' => 'Finalizar Pedido'],
            ['group' => 'checkout', 'key' => 'title', 'locale' => 'en', 'value' => 'Complete Order'],
            ['group' => 'checkout', 'key' => 'title', 'locale' => 'es', 'value' => 'Completar Pedido'],

            ['group' => 'checkout', 'key' => 'payment', 'locale' => 'pt_BR', 'value' => 'Forma de Pagamento'],
            ['group' => 'checkout', 'key' => 'payment', 'locale' => 'en', 'value' => 'Payment Method'],
            ['group' => 'checkout', 'key' => 'payment', 'locale' => 'es', 'value' => 'Método de Pago'],

            // Grupo: auth
            ['group' => 'auth', 'key' => 'login', 'locale' => 'pt_BR', 'value' => 'Entrar'],
            ['group' => 'auth', 'key' => 'login', 'locale' => 'en', 'value' => 'Login'],
            ['group' => 'auth', 'key' => 'login', 'locale' => 'es', 'value' => 'Iniciar Sesión'],

            ['group' => 'auth', 'key' => 'logout', 'locale' => 'pt_BR', 'value' => 'Sair'],
            ['group' => 'auth', 'key' => 'logout', 'locale' => 'en', 'value' => 'Logout'],
            ['group' => 'auth', 'key' => 'logout', 'locale' => 'es', 'value' => 'Cerrar Sesión'],

            // Grupo: navigation
            ['group' => 'navigation', 'key' => 'home', 'locale' => 'pt_BR', 'value' => 'Início'],
            ['group' => 'navigation', 'key' => 'home', 'locale' => 'en', 'value' => 'Home'],
            ['group' => 'navigation', 'key' => 'home', 'locale' => 'es', 'value' => 'Inicio'],

            ['group' => 'navigation', 'key' => 'about', 'locale' => 'pt_BR', 'value' => 'Sobre'],
            ['group' => 'navigation', 'key' => 'about', 'locale' => 'en', 'value' => 'About'],
            ['group' => 'navigation', 'key' => 'about', 'locale' => 'es', 'value' => 'Acerca de'],
        ];

        foreach ($globalTranslations as $translation) {
            TranslationOverride::updateOrCreate(
                [
                    'tenant_id' => null,
                    'group' => $translation['group'],
                    'key' => $translation['key'],
                    'locale' => $translation['locale'],
                ],
                [
                    'value' => $translation['value'],
                ]
            );
        }

        $this->command->info('✓ Traduções globais criadas com sucesso');
    }

    /**
     * Seed overrides para um tenant específico
     */
    protected function seedTenantOverrides(Tenant $tenant, int $index): void
    {
        // Exemplo 1: Tenant que chama "Produto" de "Sacola"
        if ($index === 0) {
            $overrides = [
                ['group' => 'products', 'key' => 'product', 'locale' => 'pt_BR', 'value' => 'Sacola'],
                ['group' => 'cart', 'key' => 'cart', 'locale' => 'pt_BR', 'value' => 'Minha Sacola'],
                ['group' => 'cart', 'key' => 'empty', 'locale' => 'pt_BR', 'value' => 'Sua sacola está vazia'],
            ];
        }
        // Exemplo 2: Tenant que usa terminologia de "Item" ao invés de "Produto"
        elseif ($index === 1) {
            $overrides = [
                ['group' => 'products', 'key' => 'product', 'locale' => 'pt_BR', 'value' => 'Item'],
                ['group' => 'products', 'key' => 'add_to_cart', 'locale' => 'pt_BR', 'value' => 'Adicionar Item'],
            ];
        }
        // Exemplo 3: Tenant com branding específico
        else {
            $overrides = [
                ['group' => 'checkout', 'key' => 'title', 'locale' => 'pt_BR', 'value' => 'Concluir Compra'],
                ['group' => 'navigation', 'key' => 'home', 'locale' => 'pt_BR', 'value' => 'Dashboard'],
            ];
        }

        foreach ($overrides as $override) {
            TranslationOverride::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'group' => $override['group'],
                    'key' => $override['key'],
                    'locale' => $override['locale'],
                ],
                [
                    'value' => $override['value'],
                ]
            );
        }

        $this->command->info("✓ Overrides criados para tenant: {$tenant->name}");
    }
}
