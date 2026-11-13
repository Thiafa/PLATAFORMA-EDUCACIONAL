<?php
// LARAVEL 4 — BANCO DE DADOS: MIGRATIONS E ELOQUENT — GABARITO (só para o professor)
// Cada exercício usa um banco novo, então eles rodam separados:
//   php gabarito.php      roda os 10, um depois do outro
//   php gabarito.php 3    roda só o exercício 3
// Precisa do mini-laravel.php na mesma pasta e da extensão pdo_sqlite (PHP 8.1+).
require __DIR__ . "/mini-laravel.php";

$so = $argv[1] ?? null;
if ($so === null) {
  foreach (range(1, 10) as $n) {
    echo "\n########## Exercício $n ##########\n";
    passthru(escapeshellarg(PHP_BINARY) . " " . escapeshellarg(__FILE__) . " $n");
  }
  exit;
}
function ex(int $n): bool { return (int) $GLOBALS["so"] === $n; }

// ===== Exercício 01: Sua primeira migration (implementação) =====
// O id() cria a chave automática, string() o texto curto e timestamps() as duas colunas de data. O ->unique() cria um índice único:
// o banco recusa o segundo e-mail igual (UNIQUE constraint failed). O down() desfaz o up(): quem cria a tabela no up apaga no down.
if (ex(1)):

criar_migration("2026_11_14_000001_create_alunos_table", new class extends Migration {
  public function up(): void {
    Schema::create("alunos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->string("email")->unique();
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("alunos");
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

endif;

// ===== Exercício 02: Tipos e valores padrão (implementação) =====
// Sem modificador a coluna é obrigatória (not null). default() preenche quando o insert não manda a coluna; nullable() deixa ficar NULL.
// O boolean vira tinyint(1) no SQLite: true é guardado como 1. Dinheiro vai em decimal (nunca em float, que arredonda).
if (ex(2)):

criar_migration("2026_11_14_000001_create_produtos_table", new class extends Migration {
  public function up(): void {
    Schema::create("produtos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->decimal("preco", 8, 2);
      $table->integer("estoque")->default(0);
      $table->text("descricao")->nullable();
      $table->boolean("ativo")->default(true);
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

endif;

// ===== Exercício 03: Leia o erro do banco (identificação de erros) =====
// 1) no such table: professors: o Laravel põe a classe no plural em inglês (Professor vira professors); protected $table = "professores" resolve.
// 2) NOT NULL constraint failed: professores.bio: a Bia não tem bio, então a coluna precisa de ->nullable().
// 3) no such column: nmoe: erro de digitação no orderBy. A mensagem do banco sempre diz o nome que ele não achou.
if (ex(3)):

criar_migration("2026_11_14_000001_create_professores_table", new class extends Migration {
  public function up(): void {
    Schema::create("professores", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->string("materia");
      $table->text("bio")->nullable();
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("professores");
  }
});
artisan("migrate");

class Professor extends Model {
  protected $table = "professores";
  protected $fillable = ["nome", "materia", "bio"];
}

Professor::create(["nome" => "Walter", "materia" => "Laravel", "bio" => "Professor da Greenn School"]);
Professor::create(["nome" => "Bia", "materia" => "JavaScript"]);

foreach (Professor::orderBy("nome")->get() as $professor) {
  echo "$professor->nome: $professor->materia\n";
}

endif;

// ===== Exercício 04: Seu primeiro Model (implementação) =====
// O Model Livro cuida da tabela livros (plural do nome da classe). Sem o $fillable o create() é bloqueado (MassAssignmentException).
// Cada item do all() é um objeto Livro: as colunas viram propriedades, inclusive o id que o banco gerou.
if (ex(4)):

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

class Livro extends Model {
  protected $fillable = ["titulo", "autor"];
}

Livro::create(["titulo" => "Dom Casmurro", "autor" => "Machado de Assis"]);
Livro::create(["titulo" => "Capitães da Areia", "autor" => "Jorge Amado"]);
Livro::create(["titulo" => "O Cortiço", "autor" => "Aluísio Azevedo"]);

echo Livro::count() . " livros cadastrados\n";
foreach (Livro::all() as $livro) {
  echo "$livro->id. $livro->titulo ($livro->autor)\n";
}

endif;

// ===== Exercício 05: Consultas com where e orderBy (implementação) =====
// Dois where seguidos viram "and" no SQL: media >= 5 and media < 7 (Caio e Davi). O get() executa e devolve a lista; count() e avg()
// pedem a conta para o banco, sem trazer as linhas. (8 + 9.5 + 6 + 5.5 + 3.5) / 5 = 6.5.
if (ex(5)):

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

$aprovados = Aluno::where("media", ">=", 7)->orderBy("media", "desc")->get();
foreach ($aprovados as $aluno) {
  echo "$aluno->nome: $aluno->media\n";
}

$recuperacao = Aluno::where("media", ">=", 5)->where("media", "<", 7)->count();
echo "Recuperação: $recuperacao alunos\n";

echo "Média da turma: " . round(Aluno::avg("media"), 1) . "\n";

endif;

// ===== Exercício 06: Mass assignment: previsão e correção (previsão de saída e correção) =====
// Previsão: com $guarded = [] o create() aceita tudo e o Hacker vira admin. Com $fillable = ["nome"], o admin do formulário é ignorado
// sem aviso. Já $bia->admin = true é uma atribuição direta: o $fillable só vale para create(), fill() e update([...]).
if (ex(6)):

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
  protected $fillable = ["nome"];
  protected $casts = ["admin" => "boolean"];
}

$formulario = ["nome" => "Hacker", "admin" => true];
$hacker = Usuario::create($formulario);

$bia = Usuario::create(["nome" => "Bia"]);
$bia->admin = true;
$bia->save();

foreach (Usuario::all() as $u) {
  echo "$u->nome é admin? " . ($u->admin ? "sim" : "não") . "\n";
}

endif;

// ===== Exercício 07: Atualizar e apagar (implementação) =====
// save() grava só o que mudou (e o updated_at). update([...]) é fill + save numa linha, e respeita o $fillable.
// Também valeria Tarefa::where("titulo", "Maratonar série")->delete() (apaga direto no banco) ou Tarefa::destroy(3).
if (ex(7)):

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

$estudar = Tarefa::where("titulo", "Estudar migrations")->first();
$estudar->feita = true;
$estudar->save();

Tarefa::find(2)->update(["titulo" => "Fazer os 10 exercícios"]);

Tarefa::where("titulo", "Maratonar série")->first()->delete();

foreach (Tarefa::all() as $t) {
  echo ($t->feita ? "[x] " : "[ ] ") . $t->titulo . "\n";
}
echo "Total: " . Tarefa::count() . " tarefas\n";

endif;

// ===== Exercício 08: O seeder da escola (implementação) =====
// O db:seed (e o --seed) roda o DatabaseSeeder, que é a porta de entrada: ele chama os outros com $this->call. Separar um seeder por
// tabela deixa fácil rodar só um: php artisan db:seed --class=CursoSeeder. Rodar db:seed duas vezes duplica os dados; migrate:fresh --seed não.
if (ex(8)):

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

class CursoSeeder extends Seeder {
  public function run(): void {
    Curso::create(["nome" => "HTML", "horas" => 8]);
    Curso::create(["nome" => "CSS", "horas" => 10]);
    Curso::create(["nome" => "Laravel", "horas" => 15]);
  }
}

class DatabaseSeeder extends Seeder {
  public function run(): void {
    $this->call(CursoSeeder::class);
  }
}

artisan("migrate --seed");
echo Curso::count() . " cursos no banco: " . Curso::pluck("nome")->implode(", ") . "\n";
echo "Total de horas: " . Curso::sum("horas") . "\n";

endif;

// ===== Exercício 09: Curso tem muitas aulas (implementação) =====
// hasMany procura na tabela aulas a coluna curso_id (o nome do model em snake_case + _id); belongsTo lê o curso_id da aula e busca
// o curso. O curso_id não está no $fillable, mas aulas()->create() preenche sozinho. Sem parênteses é o resultado; com parênteses, a consulta.
if (ex(9)):

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

  public function aulas() {
    return $this->hasMany(Aula::class);
  }
}

class Aula extends Model {
  protected $fillable = ["titulo"];

  public function curso() {
    return $this->belongsTo(Curso::class);
  }
}

$laravel = Curso::create(["nome" => "Laravel"]);
$laravel->aulas()->create(["titulo" => "Rotas"]);
$laravel->aulas()->create(["titulo" => "Blade"]);
$laravel->aulas()->create(["titulo" => "Banco de dados"]);

echo "$laravel->nome tem " . $laravel->aulas->count() . " aulas:\n";
foreach ($laravel->aulas as $aula) {
  echo "- $aula->titulo\n";
}

$aula = Aula::find(2);
echo "A aula $aula->titulo é do curso {$aula->curso->nome}\n";

endif;

// ===== Exercício 10: Desafio: catálogo do banco (integração) =====
// O seeder usa Curso e Aula, então os models precisam existir antes do migrate --seed rodar (no Laravel isso é automático: cada
// classe tem seu arquivo). O CSS tem id 2 mas aparece primeiro por causa do orderBy("nome"). findOrFail troca o if + abort(404) da L3.
if (ex(10)):

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

class Curso extends Model {
  protected $fillable = ["nome"];

  public function aulas() {
    return $this->hasMany(Aula::class);
  }
}

class Aula extends Model {
  protected $fillable = ["titulo"];
}

class CursoController extends Controller {
  public function index() {
    $cursos = Curso::with("aulas")->orderBy("nome")->get();
    return view("cursos.index", ["cursos" => $cursos]);
  }

  public function show(string $id) {
    $curso = Curso::findOrFail($id);
    return view("cursos.show", ["curso" => $curso]);
  }
}

Route::get("/cursos", [CursoController::class, "index"])->name("cursos.index");
Route::get("/cursos/{id}", [CursoController::class, "show"])->whereNumber("id")->name("cursos.show");

artisan("migrate --seed");
visitar("/cursos");
visitar("/cursos/1");
visitar("/cursos/7");

endif;
