# 🚀 Guia Rápido - Comandos Raptor

## Gerando um CRUD Completo em 3 Passos

### 1️⃣ Crie a Migration

```bash
php artisan make:migration create_categories_table
```

Edite a migration:

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->boolean('status')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

### 2️⃣ Execute a Migration

```bash
php artisan migrate
```

### 3️⃣ Gere os Recursos Raptor

```bash
php artisan raptor:generate Category --all
```

## ✨ O que foi gerado?

### Model (`app/Models/Category.php`)
```php
class Category extends AbstractModel
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }
}
```

### Controller (`app/Http/Controllers/CategoryController.php`)
- ✅ Páginas (index, create, edit, execute)
- ✅ Form com campos apropriados
- ✅ Table com colunas e filtros
- ✅ InfoList para visualização
- ✅ Actions (CRUD, Modal, Export, Import)

### Policy (`app/Policies/CategoryPolicy.php`)
- ✅ Permissões baseadas em contexto (landlord/tenant)
- ✅ Métodos: viewAny, view, create, update, delete, restore, forceDelete

## 🎯 Comandos Disponíveis

| Comando | Descrição |
|---------|-----------|
| `raptor:generate` | Gera Model + Controller + Policy |
| `raptor:make-model` | Gera apenas Model |
| `raptor:make-controller` | Gera apenas Controller |
| `raptor:make-policy` | Gera apenas Policy |

## 💡 Exemplos Práticos

### Gerar tudo de uma vez
```bash
php artisan raptor:generate Product --all
```

### Gerar apenas Model e Controller
```bash
php artisan raptor:generate Order --model --controller
```

### Usar tabela customizada
```bash
php artisan raptor:generate Order --all --table=customer_orders
```

### Sobrescrever arquivos existentes
```bash
php artisan raptor:generate Category --all --force
```

## 📚 Documentação Completa

Para mais detalhes, veja: [docs/COMMANDS.md](docs/COMMANDS.md)

## 🎓 Próximos Passos

Após gerar os recursos:

1. **Registre a Policy** em `AuthServiceProvider`:
```php
protected $policies = [
    Category::class => CategoryPolicy::class,
];
```

2. **Adicione as rotas** (se usando rotas manuais)
3. **Customize** os campos, validações e ações conforme necessário
4. **Crie Factory e Seeder** para testes:
```bash
php artisan make:factory CategoryFactory
php artisan make:seeder CategorySeeder
```

5. **Execute os testes** (se houver)

## ⚡ Vantagens

- ✅ **Economia de tempo**: Gera 90% do código boilerplate
- ✅ **Consistência**: Todo CRUD segue o mesmo padrão
- ✅ **Tipo-seguro**: Baseado no schema real do banco
- ✅ **Inteligente**: Mapeia tipos de coluna para campos corretos
- ✅ **Completo**: Inclui validações, permissões e ações

## 🛠️ Troubleshooting

### Comandos não aparecem?
```bash
php artisan config:clear
php artisan cache:clear
php artisan list raptor
```

### Tabela não encontrada?
```bash
# Verifique se existe
php artisan db:show

# Execute migrations
php artisan migrate
```

---

**Criado por Claudio Campos**  
📧 callcocam@gmail.com | contato@sigasmart.com.br  
🌐 https://www.sigasmart.com.br
