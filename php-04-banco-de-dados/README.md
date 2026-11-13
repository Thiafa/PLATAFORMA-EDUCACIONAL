# LARAVEL 4 — BANCO DE DADOS: MIGRATIONS E ELOQUENT

**Módulo:** Laravel · aula 4 de 6
**Playground dos alunos:** https://site-alunos-nu.vercel.app/php-04/
**Apostila:** `apostila-php-04.pdf` (também para download no site)
**Duração:** 2h30

Continua o site da escola da L3: os cursos agora vêm de um banco de dados. A aula sai do SQL "na mão" e chega ao Eloquent, passando por migrations, seeders e relacionamentos. Todos os exemplos e exercícios carregam antes do código do aluno o **mini-Laravel da L4** (`mini-laravel.php`), que é o da L3 (rotas + Blade) com um **SQLite de verdade**, criado na memória a cada execução:

- **Banco:** `DB::select/insert/update/delete/statement`, `DB::table()` (Query Builder), `DB::listen()` (mostra cada SQL, com `toRawSql()`), `ver_tabela()` (só do playground).
- **Migrations:** `criar_migration($nome, new class extends Migration {...})` faz o papel do arquivo; `Schema::create/table/dropIfExists/hasTable/getColumnListing`; `Blueprint` com `id`, `string`, `text`, `integer`, `decimal`, `boolean`, `date`, `enum`, `foreignId`, `timestamps`, `dropColumn`… e os modificadores `nullable`, `default`, `unique`, `constrained`, `cascadeOnDelete`. O SQL gerado é o mesmo do Laravel com SQLite.
- **Artisan:** `artisan("migrate")`, `migrate --pretend`, `migrate:status`, `migrate:rollback`, `migrate:reset`, `migrate:refresh`, `migrate:fresh --seed`, `db:seed --class=X`, e os `make:*` só imprimem o arquivo que seria criado. A saída imita a do Laravel (sem os milissegundos).
- **Eloquent:** `Model` com `$table`, `$fillable`, `$guarded`, `$casts`, `$hidden` e `$timestamps`; o nome da tabela é chutado em inglês, como no Laravel (`Professor` → `professors`); `all`, `find`, `findOrFail` (vira 404 no `visitar`), `where`/`orWhere`/`whereIn`/`whereNull`, `orderBy`, `take`, `first`, `count`/`sum`/`avg`/`max`/`min`, `pluck`, `create`, `save` (só o que mudou), `update`, `delete`, `destroy`, `toSql`; `Collection`; `hasMany`, `hasOne`, `belongsTo` e `with()`, que carrega junto de verdade (2 consultas, o exemplo 11 mostra o N+1). Fora do `$fillable` → ignorado; sem `$fillable` → `MassAssignmentException`; coluna inexistente → erro (como o modo estrito do Laravel).
- **Seeders:** `Seeder` com `run()` e `$this->call()`.
- Os nomes completos também funcionam (`use Illuminate\Database\Eloquent\Model;` etc.).

O SQLite do navegador vem da extensão `php-wasm-sqlite` 0.2.0 (Apache-2.0), em `site-alunos/php/sqlite/` (~3 MB). Ela só é carregada pela página da L4 (`runner.js?libs=sqlite`); as aulas L1–L3 continuam sem ela. O PHP do navegador não tem `mbstring`: não use `mb_*` no mini-Laravel.

A apostila mostra o código do Laravel de verdade (`return new class extends Migration`, `namespace App\Models`, `.env`); o mini-Laravel é só do playground.

## Exemplos

| # | Exemplo | Bloco |
|---|---|---|
| 01 | O banco: tabelas, linhas e SQL | Banco de dados |
| 02 | Sua primeira migration | Migrations |
| 03 | Tipos de coluna e modificadores | Migrations |
| 04 | Mudou de ideia? Nova migration e rollback | Migrations |
| 05 | O Model: uma classe para cada tabela | Eloquent |
| 06 | Buscando: where, orderBy, first e count | Eloquent |
| 07 | Atualizar e apagar | Eloquent |
| 08 | $fillable: a proteção do create() | Eloquent |
| 09 | Seeders: o banco já nasce com dados | Eloquent |
| 10 | Relacionamentos: hasMany e belongsTo | Eloquent |
| 11 | O SQL por trás do Eloquent (e o N+1) | Por dentro |
| 12 | Projeto da aula: o site da escola com banco | Juntando tudo |
| 13 | Área livre | Livre |

## Exercícios

| # | Exercício | Tipo | Objetivo |
|---|---|---|---|
| 01 | Sua primeira migration | implementação | `Schema::create` com e-mail `unique` e o `down()`. |
| 02 | Tipos e valores padrão | implementação | `decimal`, `default`, `nullable` e `boolean`. |
| 03 | Leia o erro do banco | identificação de erros | `no such table: professors`, `NOT NULL constraint failed` e `no such column`. |
| 04 | Seu primeiro Model | implementação | Model com `$fillable`, `create()`, `count()` e `all()`. |
| 05 | Consultas com where e orderBy | implementação | Filtrar, ordenar, contar e `avg`. |
| 06 | Mass assignment: previsão e correção | previsão de saída e correção | Trocar `$guarded = []` por um `$fillable` seguro. |
| 07 | Atualizar e apagar | implementação | `save()`, `update()` e `delete()`. |
| 08 | O seeder da escola | implementação | `CursoSeeder` chamado pelo `DatabaseSeeder`. |
| 09 | Curso tem muitas aulas | implementação | `hasMany` / `belongsTo` e `aulas()->create()`. |
| 10 | Desafio: catálogo do banco | integração | Models → controller com `findOrFail` → views, com 404. |

Arquivos: `exemplos.php`, `exercicios.php`, `gabarito.php` (com o comentário de cada resposta), `mini-laravel.php`. Cada exercício precisa de um banco novo, então o gabarito roda um de cada vez: `php gabarito.php` roda os 10 em sequência e `php gabarito.php 3` roda só o 3 (PHP 8.1+ com `pdo_sqlite`). Os exemplos também rodam um de cada vez, com `require "mini-laravel.php";` no começo.

O mini-Laravel também está copiado dentro de `site-alunos/php-04/index.html` (bloco `<script type="text/x-kit" id="kit-laravel">`). Se alterar um, altere o outro. Cuidado ao editar: um `?>` dentro de um comentário `//` fecha o PHP.
