<?php
// LARAVEL 4 — BANCO DE DADOS: MIGRATIONS E ELOQUENT — EXERCÍCIOS
// Resolva no site (correção automática): https://site-alunos-nu.vercel.app/php-04/
// Todos usam o mini-Laravel da L4 (no computador: require "mini-laravel.php";). Cada exercício roda sozinho.

// ===== Exercício 01: Sua primeira migration =====
// Objetivo: Escrever uma migration com Schema::create e rodar com artisan.
// 1. Complete o up() da migration create_alunos_table com as colunas id, nome (texto), email (texto, sem repetir) e os timestamps.
// 2. Complete o down() para apagar a tabela.
// 3. A conferência roda o migrate, lista as colunas e tenta cadastrar o mesmo e-mail duas vezes.
// Saída esperada:
//   Colunas: id, nome, email, created_at, updated_at
//   E-mail repetido: bloqueado
// database/migrations/2026_11_14_000001_create_alunos_table.php
criar_migration("2026_11_14_000001_create_alunos_table", new class extends Migration {
  public function up(): void {
    // Schema::create("alunos", function (Blueprint $table) { ... });
  }

  public function down(): void {
    // apague a tabela
  }
});

// a conferência (não precisa mexer)
artisan("migrate");
echo "Colunas: " . implode(", ", Schema::getColumnListing("alunos")) . "\n";
try {
  DB::table("alunos")->insert(["nome" => "Ana", "email" => "ana@greenn.school"]);
  DB::table("alunos")->insert(["nome" => "Ana Paula", "email" => "ana@greenn.school"]);
  echo "Opa: o banco aceitou um e-mail repetido\n";
} catch (QueryException $erro) {
  echo str_contains($erro->getMessage(), "UNIQUE") ? "E-mail repetido: bloqueado\n" : $erro->getMessage() . "\n";
}

// ===== Exercício 02: Tipos e valores padrão =====
// Objetivo: Escolher o tipo de cada coluna e usar nullable e default.
// 1. Crie a migration da tabela produtos: nome (texto), preco (dinheiro, 8 dígitos e 2 casas), estoque (inteiro, padrão 0), descricao (texto longo, pode ficar vazio), ativo (verdadeiro/falso, padrão true) e os timestamps.
// 2. A conferência cadastra a Caneca mandando só nome e preco: o resto precisa sair sozinho.
// 3. Compare a tabela com a saída esperada.
// Saída esperada:
//   produtos (2 linhas)
//   +----+----------+-------+---------+-----------+-------+
//   | id | nome     | preco | estoque | descricao | ativo |
//   +----+----------+-------+---------+-----------+-------+
//   | 1  | Caneca   | 25.9  | 0       | NULL      | 1     |
//   | 2  | Camiseta | 59.9  | 12      | Algodão   | 1     |
//   +----+----------+-------+---------+-----------+-------+
criar_migration("2026_11_14_000001_create_produtos_table", new class extends Migration {
  public function up(): void {
    Schema::create("produtos", function (Blueprint $table) {
      $table->id();
      // nome, preco, estoque, descricao e ativo
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("produtos");
  }
});

// a conferência (não precisa mexer)
artisan("migrate");
DB::table("produtos")->insert(["nome" => "Caneca", "preco" => 25.9]);
DB::table("produtos")->insert(["nome" => "Camiseta", "preco" => 59.9, "estoque" => 12, "descricao" => "Algodão"]);
ver_tabela("produtos", ["id", "nome", "preco", "estoque", "descricao", "ativo"]);

// ===== Exercício 03: Leia o erro do banco =====
// Objetivo: Encontrar a causa de três erros do banco lendo as mensagens.
// 1. O código tem 3 erros. Execute, leia a mensagem (a parte depois de General error ou constraint failed), corrija e execute de novo.
// 2. Pistas: o Laravel chuta o nome da tabela em inglês; nem todo professor tem bio; e confira os nomes das colunas.
// 3. Não mude os dados nem a ordem da listagem: corrija só a migration e o model.
// Saída esperada:
//   Bia: JavaScript
//   Walter: Laravel
criar_migration("2026_11_14_000001_create_professores_table", new class extends Migration {
  public function up(): void {
    Schema::create("professores", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->string("materia");
      $table->text("bio");
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("professores");
  }
});
artisan("migrate");

class Professor extends Model {
  protected $fillable = ["nome", "materia", "bio"];
}

Professor::create(["nome" => "Walter", "materia" => "Laravel", "bio" => "Professor da Greenn School"]);
Professor::create(["nome" => "Bia", "materia" => "JavaScript"]);

foreach (Professor::orderBy("nmoe")->get() as $professor) {
  echo "$professor->nome: $professor->materia\n";
}

// ===== Exercício 04: Seu primeiro Model =====
// Objetivo: Criar um Model com $fillable, cadastrar com create() e listar com all().
// 1. A migration de livros está pronta. Crie a classe Livro que estende Model, com titulo e autor no $fillable.
// 2. Cadastre com Livro::create: Dom Casmurro (Machado de Assis), Capitães da Areia (Jorge Amado) e O Cortiço (Aluísio Azevedo).
// 3. Mostre o total com Livro::count() e cada livro com um foreach no Livro::all(), no formato da saída esperada.
// Saída esperada:
//   3 livros cadastrados
//   1. Dom Casmurro (Machado de Assis)
//   2. Capitães da Areia (Jorge Amado)
//   3. O Cortiço (Aluísio Azevedo)
// database/migrations/2026_11_14_000001_create_livros_table.php
criar_migration("2026_11_14_000001_create_livros_table", new class extends Migration {
  public function up(): void {
    Schema::create("livros", function (Blueprint $table) {
      $table->id();
      $table->string("titulo");
      $table->string("autor");
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("livros");
  }
});
artisan("migrate");

// app/Models/Livro.php
// crie a classe aqui

// cadastre os 3 livros

// mostre o total e a lista

// ===== Exercício 05: Consultas com where e orderBy =====
// Objetivo: Filtrar, ordenar e contar com o Eloquent.
// 1. Mostre os aprovados (média 7 ou mais) da maior para a menor média, no formato Bia: 9.5.
// 2. Mostre quantos estão em recuperação (média de 5 até menos de 7), com dois where e count().
// 3. Mostre a média da turma com Aluno::avg("media"), arredondada com round(..., 1).
// Saída esperada:
//   Bia: 9.5
//   Ana: 8
//   Recuperação: 2 alunos
//   Média da turma: 6.5
// database/migrations/2026_11_14_000001_create_alunos_table.php
criar_migration("2026_11_14_000001_create_alunos_table", new class extends Migration {
  public function up(): void {
    Schema::create("alunos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->decimal("media", 4, 1);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("alunos");
  }
});
artisan("migrate");

class Aluno extends Model {
  protected $fillable = ["nome", "media"];
}

Aluno::create(["nome" => "Ana", "media" => 8]);
Aluno::create(["nome" => "Bia", "media" => 9.5]);
Aluno::create(["nome" => "Caio", "media" => 6]);
Aluno::create(["nome" => "Davi", "media" => 5.5]);
Aluno::create(["nome" => "Eva", "media" => 3.5]);

// 1. aprovados, da maior para a menor média

// 2. quantos em recuperação

// 3. média da turma

// ===== Exercício 06: Mass assignment: previsão e correção =====
// Objetivo: Entender o risco do create() com dados do formulário e corrigir o model.
// 1. Antes de executar, escreva num papel: o Hacker vai virar admin?
// 2. Execute. O model usa $guarded = [], que libera todas as colunas para o create(), inclusive admin.
// 3. Corrija o model com um $fillable que libere só o nome. A Bia continua admin, porque o sistema preenche essa coluna de propósito.
// Saída esperada:
//   Hacker é admin? não
//   Bia é admin? sim
// database/migrations/2026_11_14_000001_create_usuarios_table.php
criar_migration("2026_11_14_000001_create_usuarios_table", new class extends Migration {
  public function up(): void {
    Schema::create("usuarios", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->boolean("admin")->default(false);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("usuarios");
  }
});
artisan("migrate");

class Usuario extends Model {
  protected $guarded = [];   // libera tudo
  protected $casts = ["admin" => "boolean"];
}

// veio do formulário de cadastro (alguém colocou admin no HTML)
$formulario = ["nome" => "Hacker", "admin" => true];
$hacker = Usuario::create($formulario);

// o sistema decide quem é admin
$bia = Usuario::create(["nome" => "Bia"]);
$bia->admin = true;
$bia->save();

foreach (Usuario::all() as $u) {
  echo "$u->nome é admin? " . ($u->admin ? "sim" : "não") . "\n";
}

// ===== Exercício 07: Atualizar e apagar =====
// Objetivo: Alterar e apagar linhas com save(), update() e delete().
// 1. Marque a tarefa Estudar migrations como feita: busque com where(...)->first(), mude feita e salve.
// 2. Mude o título da tarefa 2 para Fazer os 10 exercícios com update([...]).
// 3. Apague a tarefa Maratonar série. A listagem no fim já está pronta.
// Saída esperada:
//   [x] Estudar migrations
//   [ ] Fazer os 10 exercícios
//   [ ] Revisar Blade
//   Total: 3 tarefas
// database/migrations/2026_11_14_000001_create_tarefas_table.php
criar_migration("2026_11_14_000001_create_tarefas_table", new class extends Migration {
  public function up(): void {
    Schema::create("tarefas", function (Blueprint $table) {
      $table->id();
      $table->string("titulo");
      $table->boolean("feita")->default(false);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("tarefas");
  }
});
artisan("migrate");

class Tarefa extends Model {
  protected $fillable = ["titulo", "feita"];
  protected $casts = ["feita" => "boolean"];
}

Tarefa::create(["titulo" => "Estudar migrations"]);
Tarefa::create(["titulo" => "Fazer os exercícios"]);
Tarefa::create(["titulo" => "Maratonar série"]);
Tarefa::create(["titulo" => "Revisar Blade"]);

// 1. marque "Estudar migrations" como feita

// 2. mude o título da tarefa 2

// 3. apague "Maratonar série"

// a listagem (não precisa mexer)
foreach (Tarefa::all() as $t) {
  echo ($t->feita ? "[x] " : "[ ] ") . $t->titulo . "\n";
}
echo "Total: " . Tarefa::count() . " tarefas\n";

// ===== Exercício 08: O seeder da escola =====
// Objetivo: Criar seeders e rodar com db:seed.
// 1. Crie o CursoSeeder (estende Seeder), cujo run() cadastra HTML (8 horas), CSS (10) e Laravel (15).
// 2. Crie o DatabaseSeeder, cujo run() chama o CursoSeeder com $this->call(...).
// 3. A conferência roda migrate --seed e lista os cursos.
// Saída esperada:
//   3 cursos no banco: HTML, CSS, Laravel
//   Total de horas: 33
// database/migrations/2026_11_14_000001_create_cursos_table.php
criar_migration("2026_11_14_000001_create_cursos_table", new class extends Migration {
  public function up(): void {
    Schema::create("cursos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->integer("horas")->default(0);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("cursos");
  }
});

class Curso extends Model {
  protected $fillable = ["nome", "horas"];
}

// database/seeders/CursoSeeder.php

// database/seeders/DatabaseSeeder.php

// a conferência (não precisa mexer)
artisan("migrate --seed");
echo Curso::count() . " cursos no banco: " . Curso::pluck("nome")->implode(", ") . "\n";
echo "Total de horas: " . Curso::sum("horas") . "\n";

// ===== Exercício 09: Curso tem muitas aulas =====
// Objetivo: Criar o relacionamento hasMany / belongsTo e usar dos dois lados.
// 1. No Curso, crie o método aulas() que devolve $this->hasMany(Aula::class). Na Aula, o método curso() com belongsTo.
// 2. Cadastre as aulas Rotas, Blade e Banco de dados pelo relacionamento: $laravel->aulas()->create([...]).
// 3. Mostre o total e a lista com $laravel->aulas e, a partir da aula 2, o nome do curso dela.
// Saída esperada:
//   Laravel tem 3 aulas:
//   - Rotas
//   - Blade
//   - Banco de dados
//   A aula Blade é do curso Laravel
// database/migrations/2026_11_14_000001_create_cursos_table.php
criar_migration("2026_11_14_000001_create_cursos_table", new class extends Migration {
  public function up(): void {
    Schema::create("cursos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("cursos");
  }
});

// database/migrations/2026_11_14_000002_create_aulas_table.php
criar_migration("2026_11_14_000002_create_aulas_table", new class extends Migration {
  public function up(): void {
    Schema::create("aulas", function (Blueprint $table) {
      $table->id();
      $table->foreignId("curso_id")->constrained()->cascadeOnDelete();
      $table->string("titulo");
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("aulas");
  }
});
artisan("migrate");

class Curso extends Model {
  protected $fillable = ["nome"];
  // aulas(): hasMany
}

class Aula extends Model {
  protected $fillable = ["titulo"];
  // curso(): belongsTo
}

$laravel = Curso::create(["nome" => "Laravel"]);
// cadastre as 3 aulas pelo relacionamento

// mostre "Laravel tem 3 aulas:" e a lista

// a aula 2 e o curso dela

// ===== Exercício 10: Desafio: catálogo do banco =====
// Objetivo: Ligar Model, relacionamento, controller, rotas e views com o banco.
// 1. Migrations, seeder e views estão prontos. Crie os models Curso (com aulas()) e Aula.
// 2. Crie o CursoController: index() manda os cursos em ordem alfabética (com with("aulas")) para cursos.index; show($id) usa findOrFail e a view cursos.show.
// 3. Registre /cursos (nome cursos.index) e /cursos/{id} (nome cursos.show). Rode migrate --seed antes das visitas.
// Saída esperada:
//   <li><a href="/cursos/2">CSS</a> (1 aulas)</li>
//   <li><a href="/cursos/1">Laravel</a> (2 aulas)</li>
//   <h1>Laravel</h1>
//   <p>1. Rotas</p>
//   <p>2. Banco de dados</p>
//   404 | Página não encontrada
// database/migrations/2026_11_14_000001_create_cursos_table.php
criar_migration("2026_11_14_000001_create_cursos_table", new class extends Migration {
  public function up(): void {
    Schema::create("cursos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("cursos");
  }
});

// database/migrations/2026_11_14_000002_create_aulas_table.php
criar_migration("2026_11_14_000002_create_aulas_table", new class extends Migration {
  public function up(): void {
    Schema::create("aulas", function (Blueprint $table) {
      $table->id();
      $table->foreignId("curso_id")->constrained()->cascadeOnDelete();
      $table->string("titulo");
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("aulas");
  }
});

// database/seeders/DatabaseSeeder.php (pronto)
class DatabaseSeeder extends Seeder {
  public function run(): void {
    $laravel = Curso::create(["nome" => "Laravel"]);
    $laravel->aulas()->create(["titulo" => "Rotas"]);
    $laravel->aulas()->create(["titulo" => "Banco de dados"]);
    $css = Curso::create(["nome" => "CSS"]);
    $css->aulas()->create(["titulo" => "Flexbox"]);
  }
}

// ---------- resources/views (prontas) ----------
criar_view("layouts.app", <<<'BLADE'
<nav>Greenn School</nav>
@yield('conteudo')
BLADE);

criar_view("cursos.index", <<<'BLADE'
@extends('layouts.app')
@section('conteudo')
  <h1>Cursos</h1>
  <ul>
    @foreach ($cursos as $curso)
      <li><a href="{{ route('cursos.show', $curso->id) }}">{{ $curso->nome }}</a> ({{ $curso->aulas->count() }} aulas)</li>
    @endforeach
  </ul>
@endsection
BLADE);

criar_view("cursos.show", <<<'BLADE'
@extends('layouts.app')
@section('conteudo')
  <h1>{{ $curso->nome }}</h1>
  @foreach ($curso->aulas as $aula)
    <p>{{ $loop->iteration }}. {{ $aula->titulo }}</p>
  @endforeach
@endsection
BLADE);

// ---------- app/Models ----------
// crie Curso (com aulas) e Aula

// ---------- app/Http/Controllers/CursoController.php ----------
// crie o controller

// ---------- routes/web.php ----------
// registre as rotas

artisan("migrate --seed");
visitar("/cursos");
visitar("/cursos/1");
visitar("/cursos/7");
