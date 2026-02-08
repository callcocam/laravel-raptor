# Plano: Colunas hierárquicas (qualquer planilha) + Hooks (before/after persist e after process)

Este documento descreve o plano para dois recursos do sistema de importação:

1. **Colunas hierárquicas (genérico)** – qualquer planilha em que as colunas representam níveis pai/filho (ex.: categorias, organograma, taxonomia de produtos, etc.). Leitura vertical/horizontal com `dependencyOn` ou `hierarchicalColumns`; colunas de ligação que não persistem na tabela usam `->exclude()`.
2. **Hooks** – `beforePersist`, `afterPersist` e `afterProcess` (classe executada ao final com dados completos, incluindo id gerado e campos exclude), permitindo classes customizadas pelo usuário (ex.: `CategoriesAfterProcess` para o caso de categorias).

O recurso de **colunas hierárquicas não é específico de categorias**: serve para qualquer aba/tabela cuja estrutura do Excel seja “coluna A = raiz, coluna B = filha de A, coluna C = filha de B, …”, com uma chave de pai (ex.: `parent_id`, `category_id`, `department_id`).

---

## 1. Exemplo de uso: planilha de categorias

- **Ean** – coluna que liga produtos a categorias; **não** existe na tabela `categories` → `->exclude()`.
- Colunas que **entram** na tabela (todas com hierarquia via `category_id`):
  - **Segmento varejista** – raiz (sem pai).
  - **Departamento** – filho de Segmento varejista.
  - **Subdepartamento** – filho de Departamento.
  - **Categoria** – filho de Subdepartamento.
  - **Subcategoria** – filho de Categoria.
  - **Segmento** – filho de Subcategoria.
  - **Subsegmento** – filho de Segmento.

Ordem: `Segmento varejista ← Departamento ← … ← Subsegmento`. Outras planilhas (ex.: organograma, árvore de departamentos) usam o mesmo mecanismo com outras tabelas e nomes de coluna pai.

---

## 2. Parte A – Colunas hierárquicas (genérico)

### 2.1 Ideia geral

- **Qualquer sheet** pode declarar que tem “colunas hierárquicas”: uma ordem de colunas em que cada uma (exceto a primeira) depende da anterior (pai).
- **Uma linha do Excel** = um caminho completo (ex.: Ean + Segmento varejista + … + Subsegmento, ou Código + Região + Filial + Setor, etc.).
- Para cada linha, o serviço percorre as colunas na ordem da hierarquia: garante que cada nível existe (find or create) e usa o `id` do nível anterior como coluna de pai (ex.: `category_id`, `parent_id`).
- Colunas que não existem na tabela (ex.: Ean, código de ligação) usam `->exclude()` e continuam disponíveis em regras e no `afterProcess`.

**Recomendação:** Um **serviço genérico** `HierarchicalImportService` (ou nome equivalente) que:

1. Lê a configuração da Sheet: ordem das colunas hierárquicas + nome da coluna de pai no model (ex.: `category_id`, `parent_id`).
2. Por linha: monta `$data`, depois para cada coluna na ordem – resolve o pai (id do nível anterior; raiz = null), find or create, guarda o id para o próximo nível.
3. Ao final da linha, entrega o resultado ao fluxo normal (incluindo hooks e `afterProcess`).

Assim serve para categorias, organograma, taxonomia, ou qualquer planilha com estrutura “colunas em cadeia pai-filho”.

### 2.2 Configuração na Sheet (proposta)

- **Genérico**, não atrelado a “categorias”:
  - `->hierarchicalColumns(['coluna_raiz', 'coluna_filha_1', 'coluna_filha_2', ...])` – ordem da hierarquia; a primeira não tem pai, as demais têm pai = coluna anterior.
  - Opcional: `->parentColumnName('category_id')` (ou `parent_id`, `department_id`, etc.); default pode ser `category_id` ou ler do model.
- **Ou** na coluna: `->dependsOn('nome_da_coluna_pai')` para definir a dependência; o service deriva a ordem a partir disso.
- O usuário pode usar `->serviceClass(HierarchicalImportService::class)` ou uma classe própria que implemente a mesma lógica para sua tabela.

### 2.3 Serviço genérico (ex.: `HierarchicalImportService`)

- Contrato: implementa `ImportServiceInterface`.
- Responsabilidades (genéricas):
  - Obter ordem das colunas (por `dependencyOn` ou por `hierarchicalColumns`).
  - Obter o nome da coluna de pai na tabela (ex.: `category_id`, `parent_id`).
  - Por linha: para cada nível na ordem, resolver pai (id do nível anterior), find or create no model/tabela da Sheet, guardar id.
  - Funcionar com qualquer model/tabela (categories, departments, regions, etc.); não hardcodar “categoria”.
- Colunas com `->exclude()` continuam no `$data` para regras e `afterProcess`, e são removidas apenas na hora de persistir (já implementado).

### 2.4 Resumo Parte A

| Item | Proposta |
|------|----------|
| Escopo | **Qualquer planilha** com colunas em hierarquia (categorias, organograma, taxonomia, etc.) |
| Config | `->hierarchicalColumns([...])` e/ou `->dependsOn('parent_name')` na coluna; opcional `->parentColumnName('category_id')` |
| Serviço | `HierarchicalImportService` genérico (ou classe do usuário via `->serviceClass()`) |
| Leitura | Vertical (linhas) + horizontal (colunas na ordem da hierarquia) |
| Persistência | N registros por linha (um por nível), find or create por (tenant_id, coluna_pai, name ou chave definida) |

---

## 3. Parte B – Hooks (beforePersist, afterPersist, afterProcess)

### 3.1 Requisitos

- **beforePersist** – executado **antes** de persistir uma linha; pode alterar `$data` ou impedir o persist.
- **afterPersist** – executado **depois** de persistir uma linha; recebe o model persistido + dados da linha (ex.: para logs ou sincronização).
- **afterProcess** – executado **uma vez** ao final do processamento da sheet (ou de todo o import); deve receber **dados completos**: todas as linhas processadas com sucesso, com **id gerado** e **campos exclude** (ex.: Ean), para o usuário fazer pós-processamento (ex.: job que associa Ean → category_id em produtos).

Por padrão **nenhuma** ação; tudo opcional. As implementações devem ser **classes** (não apenas callables) para serem serializáveis quando o import roda em Job (queue).

### 3.2 Assinaturas propostas (classes do usuário)

- **BeforePersist** (opcional):  
  `beforePersist(array $data, int $rowNumber, ?Model $existing): ?array`  
  - Retorna `$data` (possivelmente modificado) para persistir, ou `null` para **não** persistir essa linha (skip).
- **AfterPersist** (opcional):  
  `afterPersist(Model $model, array $data, int $rowNumber): void`  
  - `$data` = dados da linha (inclui campos exclude).
- **AfterProcess** (opcional):  
  `afterProcess(string $sheetName, array $completedRows): void`  
  - `$completedRows` = array de itens, cada um com: `row`, `data` (com **id** e campos **exclude**), e opcionalmente `model` ou referência. Assim o usuário pode implementar `CategoriesAfterProcess` que recebe Ean + ids de categorias e, por exemplo, atualiza `products.category_id` ou dispara um job.

### 3.3 Onde encaixar no fluxo

- **DefaultImportService (ou qualquer ImportService):**
  - Antes de `persist($data, $existing)`:
    - Se a Sheet tiver `beforePersistClass` configurado: instanciar, chamar `beforePersist($data, $rowNumber, $existing)`. Se retornar `null`, não persistir (e não contar como sucesso?). Se retornar array, usar como `$data` no persist.
  - Depois de `persist($data, $existing)`:
    - Se a Sheet tiver `afterPersistClass`: instanciar e chamar `afterPersist($model, $data, $rowNumber)` (o model é o que foi criado/atualizado).
- **Após processar todas as linhas de uma sheet** (no AdvancedImport ou no Service):
  - Coletar “linhas completas” (sucesso) com `id` e dados completos (incluindo exclude).
  - Se a Sheet tiver `afterProcessClass`: instanciar e chamar `afterProcess($sheetName, $completedRows)`.

Para **Job (queue)** as classes devem ser apenas **nome de classe** (string) para serem serializadas no payload da Sheet; no worker as sheets são reconstruídas e as classes instanciadas no momento da execução.

### 3.4 Eventos (opcional)

- Em paralelo (ou em vez de) classes, podemos disparar eventos:
  - `ImportRowBeforePersist($data, $rowNumber, $existing)`
  - `ImportRowAfterPersist($model, $data, $rowNumber)`
  - `ImportSheetAfterProcess($sheetName, $completedRows)`
- Por padrão nenhum listener; o usuário pode optar por usar eventos ou classes (ou ambos).

### 3.5 Configuração na Sheet (proposta)

Os hooks são **genéricos**: qualquer sheet pode definir classes antes/depois de persistir e ao final do processamento. Exemplo com uma sheet de categorias:

```php
Sheet::make('Tabela mercadológico')
    ->table('categories')
    ->modelClass(Category::class)
    ->beforePersistClass(CategoriesBeforePersist::class)   // opcional
    ->afterPersistClass(CategoriesAfterPersist::class)    // opcional
    ->afterProcessClass(CategoriesAfterProcess::class)    // opcional
    ->columns([...])
```

O mesmo padrão vale para outras planilhas (ex.: `DepartmentsAfterProcess`, `RegionsBeforePersist`).

- **beforePersistClass / afterPersistClass / afterProcessClass**: strings (nome da classe), serializáveis no `toArray()` da Sheet para o Job.
- Na reconstrução da Sheet no `ProcessAdvancedImport`, restaurar essas propriedades e repassar ao Service (via Sheet ou via construtor do Service).

### 3.6 Contratos (interfaces) sugeridos

```php
interface BeforePersistHookInterface
{
    public function beforePersist(array $data, int $rowNumber, ?Model $existing): ?array;
}

interface AfterPersistHookInterface
{
    public function afterPersist(Model $model, array $data, int $rowNumber): void;
}

interface AfterProcessHookInterface
{
    /**
     * @param  array<int, array{row: int, data: array<string, mixed>}>  $completedRows  Dados com id e campos exclude
     */
    public function afterProcess(string $sheetName, array $completedRows): void;
}
```

O usuário cria uma classe (ex.: `CategoriesAfterProcess`, `DepartmentsAfterProcess`) implementando `AfterProcessHookInterface` e registra na Sheet com `->afterProcessClass(SuaClasse::class)`.

### 3.7 Dados em `afterProcess`

- Cada item em `$completedRows`: pelo menos `row` (número da linha) e `data` (array com todos os campos da linha **após** persistir, incluindo **id** gerado e campos **exclude** como Ean).
- Assim o usuário pode:
  - Percorrer as linhas e, para cada uma, usar `ean` + `id` (da categoria criada) para atualizar `products.category_id` ou uma tabela de ligação.

---

## 4. Ordem de implementação sugerida

1. **Hooks (Parte B)** – mais independentes e úteis em qualquer import:
   - Interfaces (BeforePersist, AfterPersist, AfterProcess).
   - Propriedades e métodos na Sheet: `beforePersistClass`, `afterPersistClass`, `afterProcessClass` + getters + `toArray`/reconstrução no Job.
   - No DefaultImportService: chamar beforePersist antes de persist, afterPersist depois de persist; coletar “completed rows” (com id e dados completos) e, ao final da sheet, chamar afterProcess.
   - (Opcional) Eventos Laravel para os três momentos.
2. **Colunas hierárquicas (Parte A)** – genérico para qualquer planilha:
   - Definir `dependencyOn` na Column e/ou `hierarchicalColumns` na Sheet (e opcionalmente `parentColumnName`).
   - Implementar `HierarchicalImportService` genérico que processa uma linha em cadeia (find or create por nível) para qualquer model/tabela.
   - Documentar no IMPORT_GUIDE; exemplos: categorias, organograma, taxonomia, etc.

---

## 5. Resumo

| Recurso | Descrição |
|---------|-----------|
| **Colunas exclude** | Já implementado: `->exclude()` / `excludeFromSave()` para colunas que não existem na tabela. |
| **Colunas hierárquicas** | Genérico: qualquer planilha com colunas pai-filho (categorias, organograma, taxonomia, etc.). Serviço que, por linha, resolve pais e persiste N registros em cadeia; configuração por `dependencyOn` ou `hierarchicalColumns`. |
| **beforePersist** | Classe opcional: recebe `$data`, `$rowNumber`, `$existing`; retorna `$data` modificado ou `null` para não persistir. |
| **afterPersist** | Classe opcional: recebe `$model`, `$data`, `$rowNumber` após cada persist. |
| **afterProcess** | Classe opcional: chamada ao final da sheet com `$sheetName` e `$completedRows` (dados completos com id e exclude) para pós-processamento (ex.: vincular Ean a category_id em produtos, ou qualquer outra lógica). |
| **Eventos** | Opcional: ImportRowBeforePersist, ImportRowAfterPersist, ImportSheetAfterProcess. |

Com isso cobre-se: (1) **qualquer** planilha com colunas hierárquicas (não só categorias); (2) hooks antes/depois do persist por linha; (3) afterProcess com dados completos (id + exclude) em classes customizadas pelo usuário.
