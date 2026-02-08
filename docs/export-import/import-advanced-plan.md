# Plano: Importação Avançada Excel (laravel-raptor)

Documento de referência para a implementação do sistema de importação avançada: revisão da Action, comportamento de sheets/relatedSheets, services e job.

---

## 1. Revisão da ImportAction

### Responsabilidade da Action

- **Só** receber e armazenar as **sheets** (principal + relacionadas são configuradas em cada Sheet).
- Exibir formulário de upload (arquivo CSV/XLSX), confirmação e opção de processar **com job** ou **síncrono**.
- Validar que existe ao menos uma sheet configurada.
- Salvar o arquivo temporariamente (ex.: `imports/{uuid}.{ext}` no disco `local`) e chamar:
  - **Com job:** `dispatchAdvancedImportJob($path, $file, $user)`.
  - **Síncrono:** `processAdvancedImport($path, $file, $user)`.
- Não conter lógica de geração de ID: cada **Sheet** define seu próprio `generateIdUsing(Classe::class)` (interface `GeneratesImportId`).

### O que a Action NÃO faz

- Não define geração de ID (isso fica na Sheet).
- Não processa o Excel (isso fica no Service + Import/Job).
- Não resolve quais colunas vêm de qual sheet (isso fica no Service, seguindo o comportamento abaixo).

---

## 2. Sheet principal e relatedSheets

### Conceito

- **Cada Sheet pertence a uma tabela.** Uma Sheet (principal) define um modelo/tabela e todas as colunas possíveis dessa tabela.
- **RelatedSheets pertencem à mesma tabela.** São outras abas do Excel com colunas da **mesma** tabela, ligadas à principal por uma **chave de busca** (lookup key). Ex.: sheet principal "Tabela de produtos" → tabela `products`; related "Tabela dimensões" → também colunas de `products` (`height`, `width`, …), ligadas por `ean`.
- **Sheet principal:** define o **modelo/tabela** e **todas as colunas** possíveis (da tabela). Ex.: "Tabela de produtos" para a tabela `products`.
- **RelatedSheets:** abas com **colunas da mesma tabela**, ligadas pela lookup key. Ex.: "Tabela dimensões", "Tabela dados adicionais" com colunas `height`, `width`, `ean`, etc., ligadas por `ean`.

### Comportamento obrigatório na leitura da planilha

1. **Buscar todas as colunas na sheet principal primeiro**  
   Para cada linha da sheet principal, montar o registro com os valores das colunas que existem nessa aba.

2. **Se houver relatedSheets configuradas, completar com dados delas**  
   - Para cada relatedSheet **que existir na planilha** (nome da aba igual ao configurado):
     - Localizar linhas pelo **lookupKey** (ex.: `ean`) e mesclar/completar o registro da linha principal com os valores dessa relatedSheet.
   - Se uma relatedSheet **estiver configurada mas a aba não existir** no Excel: **não falhar**. Ignorar essa sheet e seguir (registros ficam só com os dados da principal e das relatedSheets que existirem).

3. **Um mesmo config serve para os dois cenários**  
   - Planilha com **tudo em uma sheet**: só existe a sheet principal; todas as colunas vêm dela.  
   - Planilha com **dados separados**: colunas da principal na aba principal; colunas adicionais nas relatedSheets; o sistema busca na principal e depois completa com cada relatedSheet existente.

Resumo: **sempre** ler a principal; para cada relatedSheet **presente** no arquivo, completar pelo lookupKey; relatedSheets ausentes são ignoradas (não geram erro).

### Exemplo (produtos)

- **Principal:** "Tabela de produtos" — colunas `name`, `ean`, `codigo_erp`, `tenant_id`, `user_id`, etc.
- **Related:** "Tabela dimensões" — colunas `height`, `width`, `depth`, `reference` (lookup por `ean`).
- **Related:** "Tabela dados adicionais" — colunas `fragrance`, `flavor`, `color`, `brand`, ... (lookup por `ean`).

Se o usuário enviar:
- só "Tabela de produtos" com todas as colunas na mesma aba → tudo vem da principal.
- "Tabela de produtos" + "Tabela dimensões" (sem "Tabela dados adicionais") → principal + dimensões; "Tabela dados adicionais" é ignorada sem erro.

---

## 3. Próximos passos: Services e Job

### Service de importação

- Responsável por:
  - Receber a **Sheet** (config) e a **conexão** (e contexto: tenant, user, etc.).
  - Para cada **linha** da sheet principal (e das relatedSheets existentes):
    - Aplicar a resolução de colunas: principal primeiro, depois completar com relatedSheets presentes.
    - Aplicar valores padrão (colunas hidden: `tenant_id`, `user_id`, etc.).
    - Gerar ID quando a sheet tiver `generateIdUsing(Classe)` (usar a interface `GeneratesImportId`).
    - Validar, transformar (cast/format) e persistir (create/update na mesma tabela).
  - Acumular estatísticas (sucesso/falha/erros) e retornar ou repassar para o job.

### Job de importação

- Recebe: caminho do arquivo, payload serializado das sheets (principal + relatedSheets), resourceName, userId, conexão, etc.
- Reconstrói as instâncias de **Sheet** a partir do payload (incluindo `generateId`, `generateIdClass`, colunas, relatedSheets).
- Usa **WithMultipleSheets** (ou equivalente) para ler o Excel; para cada sheet principal configurada:
  - Instancia o **Service** da sheet (ou default).
  - Lê a sheet principal; para cada relatedSheet **existente** no arquivo, lê e mescla por lookupKey.
  - Delega ao service o processamento por linha (gerar ID, defaults, validar, persistir).
- Ao final: remove arquivo temporário, dispara notificação e evento (ex.: `ImportCompleted`).

### Ordem sugerida de implementação

1. **Service padrão** (ex.: `DefaultImportService`): uma Sheet, sem relatedSheets; colunas da principal; geração de ID e defaults.
2. **Leitor multi-sheet** (implementado com **maatwebsite/excel**): `AdvancedImport` (WithMultipleSheets, SkipsUnknownSheets) + `SheetRowCollectorImport` (ToCollection, WithHeadingRow). Lê principal + relatedSheets existentes, mescla por lookupKey; relatedSheets ausentes ignoradas.
3. **Job** que recebe o arquivo e o payload das sheets, reconstrói as sheets, usa o leitor e o service, e notifica/dispara evento.

---

## 4. Resumo

| Item | Responsável | Regra |
|------|-------------|--------|
| Geração de ID | Sheet | `generateIdUsing(Classe::class)` por sheet (interface `GeneratesImportId`). |
| Colunas da tabela | Sheet principal | Todas as colunas definidas na principal; podem estar na mesma aba ou em relatedSheets. |
| Leitura | Service + leitor Excel | 1) Ler principal; 2) Para cada relatedSheet **existente**, completar por lookupKey; 3) relatedSheet ausente = ignorar. |
| Valores padrão | Colunas hidden | `tenant_id`, `user_id`, etc. via colunas hidden com `defaultValue`. |
| Processamento | Service | Por linha: resolver dados (principal + related), defaults, gerar ID, validar, persistir. |
| Execução assíncrona | Job | Recebe arquivo + payload; reconstrói sheets; chama leitor + service; notificação e evento. |
