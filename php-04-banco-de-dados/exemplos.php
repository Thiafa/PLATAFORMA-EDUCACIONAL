<?php
// LARAVEL 4 — BANCO DE DADOS: MIGRATIONS E ELOQUENT
// Exemplos da aula. Rode no site: https://site-alunos-nu.vercel.app/php-04/
// Cada exemplo roda sozinho (o banco nasce vazio a cada execução): copie o bloco para o playground ou para um arquivo .php.
// Todos usam o mini-Laravel da L4 (rotas, Blade, migrations, DB, Eloquent, seeders e artisan):
// no computador, coloque require "mini-laravel.php"; no começo (precisa da extensão pdo_sqlite).

// ===== 01. O banco: tabelas, linhas e SQL (Banco de dados) =====
// 1. criar a tabela: colunas e o tipo de cada uma
DB::statement("create table cursos (
  id integer primary key autoincrement,
  nome varchar not null,
  horas integer
)");

// 2. inserir linhas: os valores vão separados, no lugar dos ?
DB::insert("insert into cursos (nome, horas) values (?, ?)", ["HTML", 8]);
DB::insert("insert into cursos (nome, horas) values (?, ?)", ["CSS", 10]);
DB::insert("insert into cursos (nome, horas) values (?, ?)", ["Laravel", 15]);

// 3. consultar: cada linha volta como um objeto
$cursos = DB::select("select * from cursos where horas >= ? order by nome", [10]);
foreach ($cursos as $curso) {
  echo "$curso->id. $curso->nome ($curso->horas h)\n";
}

// o mesmo select com o Query Builder do Laravel
$nomes = DB::table("cursos")->where("horas", ">=", 10)->orderBy("nome")->pluck("nome");
echo "Query Builder: " . $nomes->implode(", ") . "\n";

// a tabela inteira (ver_tabela é um ajudante do playground)
ver_tabela("cursos");

// ===== 02. Sua primeira migration (Migrations) =====
// database/migrations/2026_11_14_000001_create_cursos_table.php
criar_migration("2026_11_14_000001_create_cursos_table", new class extends Migration {
  // up(): o que a migration faz
  public function up(): void {
    Schema::create("cursos", function (Blueprint $table) {
      $table->id();               // id: número automático (1, 2, 3...)
      $table->string("nome");     // texto curto
      $table->integer("horas");   // número inteiro
      $table->timestamps();       // created_at e updated_at
    });
  }

  // down(): como desfazer
  public function down(): void {
    Schema::dropIfExists("cursos");
  }
});

// no terminal: php artisan migrate
artisan("migrate");

echo "A tabela cursos existe? " . (Schema::hasTable("cursos") ? "sim" : "não") . "\n";
echo "Colunas: " . implode(", ", Schema::getColumnListing("cursos")) . "\n";

// rodar de novo não faz nada: ela já está anotada
artisan("migrate");
artisan("migrate:status");

// ===== 03. Tipos de coluna e modificadores (Migrations) =====
criar_migration("2026_11_14_000001_create_alunos_table", new class extends Migration {
  public function up(): void {
    Schema::create("alunos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");                          // obrigatório
      $table->string("email")->unique();               // não pode repetir
      $table->date("nascimento")->nullable();          // pode ficar vazio (NULL)
      $table->decimal("mensalidade", 8, 2);            // dinheiro: 2 casas depois da vírgula
      $table->integer("faltas")->default(0);           // se ninguém mandar, vale 0
      $table->boolean("ativo")->default(true);         // verdadeiro ou falso (1 ou 0)
      $table->enum("turno", ["manha", "tarde", "noite"])->default("manha");
      $table->text("observacoes")->nullable();         // texto longo
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("alunos");
  }
});

// --pretend: só mostra o SQL
artisan("migrate --pretend");
artisan("migrate");

// a Ana manda só o obrigatório; o resto usa o padrão ou fica NULL
DB::table("alunos")->insert(["nome" => "Ana", "email" => "ana@greenn.school", "mensalidade" => 150]);
DB::table("alunos")->insert([
  "nome" => "Caio", "email" => "caio@greenn.school", "mensalidade" => 150,
  "nascimento" => "2008-03-15", "faltas" => 2, "turno" => "noite",
]);

ver_tabela("alunos", ["id", "nome", "nascimento", "faltas", "ativo", "turno"]);

// ===== 04. Mudou de ideia? Nova migration e rollback (Migrations) =====
criar_migration("2026_11_14_000001_create_alunos_table", new class extends Migration {
  public function up(): void {
    Schema::create("alunos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("alunos");
  }
});

artisan("migrate");
DB::table("alunos")->insert(["nome" => "Ana"]);

// uma semana depois: a escola quer o telefone. Migration NOVA:
criar_migration("2026_11_21_000001_add_telefone_to_alunos_table", new class extends Migration {
  public function up(): void {
    Schema::table("alunos", function (Blueprint $table) {
      $table->string("telefone")->nullable();   // a Ana já existe e não tem telefone
    });
  }

  public function down(): void {
    Schema::table("alunos", function (Blueprint $table) {
      $table->dropColumn("telefone");
    });
  }
});

artisan("migrate:status");   // a nova está Pending
artisan("migrate");
echo "Colunas: " . implode(", ", Schema::getColumnListing("alunos")) . "\n";

// desfaz o último lote (roda o down)
artisan("migrate:rollback");
echo "Colunas: " . implode(", ", Schema::getColumnListing("alunos")) . "\n";
artisan("migrate:status");

// ===== 05. O Model: uma classe para cada tabela (Eloquent) =====
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

artisan("migrate");

// app/Models/Curso.php
class Curso extends Model {
  protected $fillable = ["nome", "horas"];   // colunas que o create() pode preencher
}

// create: o INSERT, sem SQL. Devolve o objeto já com o id
$html = Curso::create(["nome" => "HTML", "horas" => 8]);
echo "Criado: #$html->id $html->nome\n";
Curso::create(["nome" => "CSS", "horas" => 10]);
Curso::create(["nome" => "Laravel", "horas" => 15]);

// ou: monta o objeto, preenche e salva
$js = new Curso();
$js->nome = "JavaScript";
$js->horas = 12;
$js->save();
echo "Salvo: #$js->id $js->nome\n";

// all: todas as linhas, cada uma é um objeto Curso
foreach (Curso::all() as $curso) {
  echo "- $curso->nome ($curso->horas h)\n";
}

// find: busca pelo id (ou devolve null)
$curso = Curso::find(3);
echo "O curso 3 é $curso->nome\n";
echo "O curso 99 existe? " . (Curso::find(99) ? "sim" : "não") . "\n";

// de onde veio o nome da tabela
echo "Tabela do model: " . (new Curso())->getTable() . "\n";

// ===== 06. Buscando: where, orderBy, first e count (Eloquent) =====
criar_migration("2026_11_14_000001_create_alunos_table", new class extends Migration {
  public function up(): void {
    Schema::create("alunos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->string("turno");
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
  protected $fillable = ["nome", "turno", "media"];
}

Aluno::create(["nome" => "Ana", "turno" => "manha", "media" => 8]);
Aluno::create(["nome" => "Bia", "turno" => "noite", "media" => 9.5]);
Aluno::create(["nome" => "Caio", "turno" => "manha", "media" => 6]);
Aluno::create(["nome" => "Davi", "turno" => "noite", "media" => 5.5]);
Aluno::create(["nome" => "Eva", "turno" => "manha", "media" => 3.5]);

// where + orderBy + get: a lista filtrada e ordenada
$aprovados = Aluno::where("media", ">=", 7)->orderBy("media", "desc")->get();
foreach ($aprovados as $aluno) {
  echo "Aprovado(a): $aluno->nome ($aluno->media)\n";
}

// dois where = "e"   ·   orWhere = "ou"
echo "Manhã e aprovados: " . Aluno::where("turno", "manha")->where("media", ">=", 7)->count() . "\n";
echo "Ana ou Eva: " . Aluno::where("nome", "Ana")->orWhere("nome", "Eva")->count() . "\n";

// first: só o primeiro
$melhor = Aluno::orderByDesc("media")->first();
echo "Maior média: $melhor->nome\n";

// pluck, count e avg: o banco faz a conta
echo "Noite: " . Aluno::where("turno", "noite")->pluck("nome")->implode(", ") . "\n";
echo "Total: " . Aluno::count() . " alunos\n";
echo "Média da turma: " . round(Aluno::avg("media"), 1) . "\n";
echo "Top 3: " . Aluno::orderByDesc("media")->take(3)->pluck("nome")->implode(", ") . "\n";

// por dentro: o SQL montado (os valores vão nos ?)
echo Aluno::where("media", ">=", 7)->orderBy("nome")->toSql() . "\n";

// ===== 07. Atualizar e apagar (Eloquent) =====
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
  protected $casts = ["feita" => "boolean"];   // 1/0 do banco vira true/false
}

Tarefa::create(["titulo" => "Estudar migrations"]);
Tarefa::create(["titulo" => "Fazer os exercícios"]);
Tarefa::create(["titulo" => "Maratonar série"]);
Tarefa::create(["titulo" => "Revisar Blade"]);

// jeito 1: busca, muda e save()
$tarefa = Tarefa::find(1);
$tarefa->feita = true;
$tarefa->save();

// jeito 2: update() muda e salva de uma vez
Tarefa::find(4)->update(["titulo" => "Revisar Blade e rotas", "feita" => true]);

// apagar: pelo objeto ou direto pelo id
Tarefa::where("titulo", "Maratonar série")->first()->delete();
// Tarefa::destroy(3);   // faria o mesmo

foreach (Tarefa::all() as $t) {
  echo ($t->feita ? "[x] " : "[ ] ") . $t->titulo . "\n";
}
echo "Faltam: " . Tarefa::where("feita", false)->count() . "\n";

// várias de uma vez: apaga todas as feitas
$apagadas = Tarefa::where("feita", true)->delete();
echo "Limpei $apagadas tarefas feitas\n";
ver_tabela("tarefas", ["id", "titulo", "feita"]);

// ===== 08. $fillable: a proteção do create() (Eloquent) =====
criar_migration("2026_11_14_000001_create_usuarios_table", new class extends Migration {
  public function up(): void {
    Schema::create("usuarios", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->string("email");
      $table->boolean("admin")->default(false);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("usuarios");
  }
});
artisan("migrate");

// 1. um model SEM $fillable: o create() é bloqueado
class Visitante extends Model {
  protected $table = "usuarios";
}
try {
  Visitante::create(["nome" => "Ana", "email" => "ana@greenn.school"]);
} catch (MassAssignmentException $erro) {
  echo "Bloqueado: " . $erro->getMessage() . "\n";
}

// 2. o model certo: só nome e email são liberados
class Usuario extends Model {
  protected $fillable = ["nome", "email"];
  protected $casts = ["admin" => "boolean"];
}

// o que chegou do formulário de cadastro (alguém colocou admin no HTML)
$dadosDoFormulario = ["nome" => "Hacker", "email" => "h@x.com", "admin" => true];
$hacker = Usuario::create($dadosDoFormulario);
echo "$hacker->nome é admin? " . ($hacker->fresh()->admin ? "sim" : "não") . "\n";

// 3. virar admin é uma decisão do sistema: atribuição uma por uma
$ana = Usuario::create(["nome" => "Ana", "email" => "ana@greenn.school"]);
$ana->admin = true;
$ana->save();
echo "$ana->nome é admin? " . ($ana->fresh()->admin ? "sim" : "não") . "\n";

ver_tabela("usuarios", ["id", "nome", "admin"]);

// ===== 09. Seeders: o banco já nasce com dados (Eloquent) =====
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
class CursoSeeder extends Seeder {
  public function run(): void {
    $cursos = ["HTML" => 8, "CSS" => 10, "JavaScript" => 12, "Laravel" => 15];
    foreach ($cursos as $nome => $horas) {
      Curso::create(["nome" => $nome, "horas" => $horas]);
    }
  }
}

// database/seeders/DatabaseSeeder.php: o principal chama os outros
class DatabaseSeeder extends Seeder {
  public function run(): void {
    $this->call(CursoSeeder::class);
  }
}

artisan("migrate");
artisan("db:seed");
echo Curso::count() . " cursos: " . Curso::pluck("nome")->implode(", ") . "\n";

// bagunçou o banco testando?
Curso::create(["nome" => "teste123"]);
Curso::create(["nome" => "asdf"]);
echo "Antes: " . Curso::count() . " cursos\n";

// apaga tudo, recria e semeia (só no seu computador!)
artisan("migrate:fresh --seed");
echo "Depois: " . Curso::count() . " cursos\n";

// ===== 10. Relacionamentos: hasMany e belongsTo (Eloquent) =====
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
      $table->foreignId("curso_id")->constrained()->cascadeOnDelete();   // aponta para cursos.id
      $table->string("titulo");
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists("aulas");
  }
});
artisan("migrate");

// app/Models/Curso.php
class Curso extends Model {
  protected $fillable = ["nome"];

  // um curso TEM MUITAS aulas (aulas.curso_id = cursos.id)
  public function aulas() {
    return $this->hasMany(Aula::class);
  }
}

// app/Models/Aula.php
class Aula extends Model {
  protected $fillable = ["titulo"];

  // uma aula PERTENCE A um curso
  public function curso() {
    return $this->belongsTo(Curso::class);
  }
}

$laravel = Curso::create(["nome" => "Laravel"]);
$html = Curso::create(["nome" => "HTML"]);

// criar pelo relacionamento: o curso_id é preenchido sozinho
$laravel->aulas()->create(["titulo" => "PHP: o básico"]);
$laravel->aulas()->create(["titulo" => "Classes e objetos"]);
$laravel->aulas()->create(["titulo" => "Rotas e Blade"]);
$laravel->aulas()->create(["titulo" => "Banco de dados"]);
$html->aulas()->create(["titulo" => "Estrutura da página"]);

// $curso->aulas: a lista
echo "$laravel->nome tem " . $laravel->aulas->count() . " aulas:\n";
foreach ($laravel->aulas as $aula) {
  echo "  - $aula->titulo\n";
}

// $curso->aulas(): uma consulta para continuar
echo "Aulas com \"Blade\": " . $laravel->aulas()->where("titulo", "like", "%Blade%")->count() . "\n";

// o outro lado: da aula para o curso
$aula = Aula::find(5);
echo "A aula \"$aula->titulo\" é do curso {$aula->curso->nome}\n";

// a chave estrangeira protege: curso 99 não existe
try {
  DB::table("aulas")->insert(["curso_id" => 99, "titulo" => "Aula perdida"]);
} catch (QueryException $erro) {
  echo "Recusado: FOREIGN KEY constraint failed\n";
}

// cascadeOnDelete: apagar o curso apaga as aulas dele
$html->delete();
echo "Aulas no banco: " . Aula::count() . "\n";

// ===== 11. O SQL por trás do Eloquent (e o N+1) (Por dentro) =====
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
      $table->foreignId("curso_id")->constrained()->cascadeOnDelete();   // aponta para cursos.id
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
  public function aulas() { return $this->hasMany(Aula::class); }
}

class Aula extends Model {
  protected $fillable = ["titulo"];
}

foreach (["HTML", "CSS", "Laravel"] as $nome) {
  $curso = Curso::create(["nome" => $nome]);
  $curso->aulas()->create(["titulo" => "Aula 1 de $nome"]);
  $curso->aulas()->create(["titulo" => "Aula 2 de $nome"]);
}

// daqui para baixo, cada SQL aparece na tela
$consultas = 0;
DB::listen(function ($query) use (&$consultas) {
  $consultas++;
  echo "   SQL: " . $query->toRawSql() . "\n";
});

echo "SEM with():\n";
foreach (Curso::all() as $curso) {
  echo "$curso->nome: " . $curso->aulas->count() . " aulas\n";
}
echo "Total: $consultas consultas\n";

$consultas = 0;
echo "COM with(\"aulas\"):\n";
foreach (Curso::with("aulas")->get() as $curso) {
  echo "$curso->nome: " . $curso->aulas->count() . " aulas\n";
}
echo "Total: $consultas consultas\n";

// ===== 12. Projeto da aula: o site da escola com banco (Juntando tudo) =====
// ---------- database/migrations ----------
criar_migration("2026_11_14_000001_create_cursos_table", new class extends Migration {
  public function up(): void {
    Schema::create("cursos", function (Blueprint $table) {
      $table->id();
      $table->string("nome");
      $table->text("descricao")->nullable();
      $table->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists("cursos"); }
});

criar_migration("2026_11_14_000002_create_aulas_table", new class extends Migration {
  public function up(): void {
    Schema::create("aulas", function (Blueprint $table) {
      $table->id();
      $table->foreignId("curso_id")->constrained()->cascadeOnDelete();
      $table->string("titulo");
      $table->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists("aulas"); }
});

// ---------- app/Models ----------
class Curso extends Model {
  protected $fillable = ["nome", "descricao"];
  public function aulas() { return $this->hasMany(Aula::class); }
}

class Aula extends Model {
  protected $fillable = ["titulo"];
  public function curso() { return $this->belongsTo(Curso::class); }
}

// ---------- database/seeders/DatabaseSeeder.php ----------
class DatabaseSeeder extends Seeder {
  public function run(): void {
    $html = Curso::create(["nome" => "HTML", "descricao" => "A estrutura de toda página."]);
    $html->aulas()->create(["titulo" => "Estrutura"]);
    $html->aulas()->create(["titulo" => "Formulários"]);

    $laravel = Curso::create(["nome" => "Laravel"]);
    foreach (["PHP: o básico", "Classes e objetos", "Rotas e Blade", "Banco de dados"] as $titulo) {
      $laravel->aulas()->create(["titulo" => $titulo]);
    }
  }
}

// no terminal: php artisan migrate --seed
artisan("migrate --seed");

// ---------- resources/views ----------
criar_view("layouts.app", <<<'BLADE'
<nav>🌱 Greenn School · <a href="{{ route('cursos.index') }}">Cursos</a></nav>
<main>
@yield('conteudo')
</main>
BLADE);

criar_view("cursos.index", <<<'BLADE'
@extends('layouts.app')
@section('conteudo')
  <h1>Nossos cursos</h1>
  <ul>
  @foreach ($cursos as $curso)
    <li><a href="{{ route('cursos.show', $curso->id) }}">{{ $curso->nome }}</a> · {{ $curso->aulas->count() }} aulas</li>
  @endforeach
  </ul>
@endsection
BLADE);

criar_view("cursos.show", <<<'BLADE'
@extends('layouts.app')
@section('conteudo')
  <h1>{{ $curso->nome }}</h1>
  <p>{{ $curso->descricao ?? 'Sem descrição.' }}</p>
  <ol>
  @forelse ($curso->aulas as $aula)
    <li>{{ $aula->titulo }}</li>
  @empty
    <li>Aulas em breve</li>
  @endforelse
  </ol>
@endsection
BLADE);

// ---------- app/Http/Controllers/CursoController.php ----------
class CursoController extends Controller {
  public function index() {
    $cursos = Curso::with("aulas")->orderBy("nome")->get();
    return view("cursos.index", ["cursos" => $cursos]);
  }

  public function show(string $id) {
    $curso = Curso::findOrFail($id);   // não achou? 404
    return view("cursos.show", ["curso" => $curso]);
  }
}

// ---------- routes/web.php ----------
Route::get("/cursos", [CursoController::class, "index"])->name("cursos.index");
Route::get("/cursos/{id}", [CursoController::class, "show"])->whereNumber("id")->name("cursos.show");

visitar("/cursos");
visitar("/cursos/2");
visitar("/cursos/9");

// ===== 13. Área livre (Livre) =====
criar_migration("2026_11_14_000001_create_livros_table", new class extends Migration {
  public function up(): void {
    Schema::create("livros", function (Blueprint $table) {
      $table->id();
      $table->string("titulo");
      $table->timestamps();
    });
  }
});
artisan("migrate");

class Livro extends Model {
  protected $fillable = ["titulo"];
}

Livro::create(["titulo" => "Dom Casmurro"]);
ver_tabela("livros");
