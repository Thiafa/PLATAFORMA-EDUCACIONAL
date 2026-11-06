<?php
// LARAVEL 3 — ROTAS E VIEWS COM BLADE
// Exemplos da aula. Rode no site: https://site-alunos-nu.vercel.app/php-03/
// Cada exemplo roda sozinho: copie o bloco para o playground ou para um arquivo .php.
// Todos usam o mini-Laravel da L3 (Route, route, abort, criar_view, view e o Blade):
// no computador, coloque require "mini-laravel.php"; no começo.

// ===== 01. routes/web.php: três jeitos de responder (Rotas) =====
// resources/views/sobre.blade.php
criar_view("sobre", <<<'BLADE'
<h1>Sobre a {{ $escola }}</h1>
<p>Aulas aos sábados.</p>
BLADE);

// app/Http/Controllers/CursoController.php
class CursoController extends Controller {
  public function index(): string {
    return "Lista de cursos (vinda do controller)";
  }
}

// routes/web.php
// ① uma função (closure)
Route::get("/", function () {
  return "Bem-vindo à Greenn School!";
});

// ② um controller
Route::get("/cursos", [CursoController::class, "index"]);

// ③ Route::view: a página só mostra uma view
Route::view("/sobre", "sobre", ["escola" => "Greenn School"]);

// o navegador
visitar("/");
visitar("/cursos");
visitar("/sobre");
visitar("/contato");

// ===== 02. Parâmetros: {id}, {nome?} e where (Rotas) =====
// {id}: um pedaço da URL que chega como parâmetro
Route::get("/cursos/{id}", function (string $id) {
  return "Curso número $id";
})->whereNumber("id");          // só aceita números

// dois parâmetros: chegam na ordem da URL
Route::get("/cursos/{curso}/aulas/{aula}", function (string $curso, string $aula) {
  return "Curso $curso, aula $aula";
});

// {nome?}: opcional; precisa de um valor padrão
Route::get("/ola/{nome?}", function (string $nome = "visitante") {
  return "Olá, $nome!";
});

// where: só letras minúsculas e hífen
Route::get("/blog/{slug}", function (string $slug) {
  return "Post: $slug";
})->where("slug", "[a-z-]+");

visitar("/cursos/3");
visitar("/cursos/abc");         // 404: não é número
visitar("/cursos/2/aulas/5");
visitar("/ola");
visitar("/ola/Ana");
visitar("/blog/primeiro-post");
visitar("/blog/Post_1");        // 404

// ===== 03. Rotas nomeadas e route() (Rotas) =====
Route::get("/", fn () => "Início")->name("home");
Route::get("/cursos", fn () => "Todos os cursos")->name("cursos.index");
Route::get("/cursos/{id}", fn (string $id) => "Curso $id")->name("cursos.show");

// route() monta a URL pelo nome da rota
echo route("home"), "\n";
echo route("cursos.index"), "\n";
echo route("cursos.show", 3), "\n";
echo route("cursos.show", ["id" => 7]), "\n";

// o link sempre acompanha a rota
$link = route("cursos.show", 3);
echo "<a href=\"$link\">Ver curso</a>\n";

visitar($link);

// ===== 04. Grupos, redirect e abort(404) (Rotas) =====
$cursos = [1 => "HTML", 2 => "CSS", 3 => "Laravel"];

// abort(404): o curso não existe? para tudo e responde 404
Route::get("/cursos/{id}", function (string $id) use ($cursos) {
  if (!isset($cursos[$id])) {
    abort(404);
  }
  return "Curso: " . $cursos[$id];
});

// redirect: a URL antiga manda para a nova
Route::redirect("/formacoes", "/cursos/1");

// grupo: /admin na URL e admin. no nome
Route::prefix("admin")->name("admin.")->group(function () {
  Route::get("/painel", fn () => "Painel do professor")->name("painel");
  Route::get("/alunos", fn () => "Gerenciar alunos")->name("alunos");
});

visitar("/cursos/3");
visitar("/cursos/9");
visitar("/formacoes");
visitar("/admin/painel");
echo route("admin.alunos"), "\n";

// ===== 05. Blade: {{ }} no lugar do <?= ?> (Blade) =====
// resources/views/perfil.blade.php
criar_view("perfil", <<<'BLADE'
{{-- comentário do Blade: não vai para o HTML --}}
<h1>Perfil de {{ $nome }}</h1>
<p>Em maiúsculas: {{ strtoupper($nome) }}</p>
<p>Média: {{ number_format($media, 1, ",") }}</p>
<p>Situação: {{ $media >= 7 ? "aprovada" : "recuperação" }}</p>
<p>Bio escapada: {{ $bio }}</p>
<p>Bio sem escapar: {!! $bio !!}</p>
BLADE);

Route::get("/perfil", function () {
  return view("perfil", [
    "nome" => "Ana",
    "media" => 8.75,
    "bio" => "Gosto de <b>PHP</b>",
  ]);
});

visitar("/perfil");

// ===== 06. Condicionais: @if, @unless, @isset e @empty (Blade) =====
criar_view("boletim", <<<'BLADE'
<h1>Boletim de {{ $aluno }}</h1>

@if ($media >= 7)
  <p>✅ Aprovado(a) com média {{ $media }}</p>
@elseif ($media >= 5)
  <p>⚠️ Recuperação</p>
@else
  <p>❌ Reprovado(a)</p>
@endif

@unless ($pagou)
  <p>Mensalidade em aberto</p>
@endunless

@isset ($observacao)
  <p>Obs.: {{ $observacao }}</p>
@endisset

@empty ($faltas)
  <p>Nenhuma falta. Parabéns!</p>
@endempty
BLADE);

Route::get("/boletim/{aluno}", function (string $aluno) {
  return view("boletim", [
    "aluno" => $aluno,
    "media" => 8,
    "pagou" => false,
    "faltas" => [],
  ]);
});

visitar("/boletim/Ana");

// ===== 07. Laços: @foreach, @forelse e $loop (Blade) =====
criar_view("cursos.index", <<<'BLADE'
<h1>Cursos ({{ count($cursos) }})</h1>
<ol>
@foreach ($cursos as $curso)
  <li>{{ $loop->iteration }}. {{ $curso }}@if ($loop->first) ⭐ o primeiro!@elseif ($loop->last) 🏁 o último!@endif</li>
@endforeach
</ol>

<h2>Matriculados</h2>
<ul>
@forelse ($alunos as $aluno)
  <li>{{ $aluno }}</li>
@empty
  <li>Nenhum aluno ainda.</li>
@endforelse
</ul>
BLADE);

Route::get("/cursos", function () {
  return view("cursos.index", [
    "cursos" => ["HTML", "CSS", "JavaScript", "Laravel"],
    "alunos" => [],
  ]);
});

visitar("/cursos");

// ===== 08. Layout: @extends, @section e @yield (Blade) =====
// resources/views/layouts/app.blade.php: o esqueleto de TODAS as páginas
criar_view("layouts.app", <<<'BLADE'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <title>@yield("titulo", "Greenn School")</title>
</head>
<body>
  <header>🌱 Greenn School</header>
  <main>
    @yield("conteudo")
  </main>
  <footer>Aulas aos sábados</footer>
</body>
</html>
BLADE);

// resources/views/home.blade.php: só o que muda
criar_view("home", <<<'BLADE'
@extends("layouts.app")

@section("titulo", "Início")

@section("conteudo")
  <h1>Bem-vindo, {{ $nome }}!</h1>
@endsection
BLADE);

// resources/views/sobre.blade.php
criar_view("sobre", <<<'BLADE'
@extends("layouts.app")

@section("conteudo")
  <h1>Sobre nós</h1>
@endsection
BLADE);

Route::get("/", fn () => view("home", ["nome" => "Ana"]));
Route::view("/sobre", "sobre");

visitar("/");
visitar("/sobre");

// ===== 09. Pedaços reaproveitados: @include (Blade) =====
// resources/views/partials/menu.blade.php
criar_view("partials.menu", <<<'BLADE'
<nav>
  <a href="{{ route('home') }}">Início</a> |
  <a href="{{ route('cursos.index') }}">Cursos</a>
</nav>
BLADE);

// resources/views/partials/card-curso.blade.php
criar_view("partials.card-curso", <<<'BLADE'
<div class="card">
  <h3>{{ $curso["nome"] }}</h3>
  <p>{{ $curso["aulas"] }} aulas</p>
</div>
BLADE);

// resources/views/cursos/index.blade.php
criar_view("cursos.index", <<<'BLADE'
@include("partials.menu")
<h1>Cursos</h1>
@foreach ($cursos as $c)
  @include("partials.card-curso", ["curso" => $c])
@endforeach
BLADE);

Route::get("/", fn () => "Início")->name("home");
Route::get("/cursos", function () {
  return view("cursos.index", ["cursos" => [
    ["nome" => "HTML", "aulas" => 4],
    ["nome" => "CSS", "aulas" => 4],
    ["nome" => "Laravel", "aulas" => 6],
  ]]);
})->name("cursos.index");

visitar("/cursos");

// ===== 10. Componentes: <x-alerta> (Blade) =====
// resources/views/components/alerta.blade.php
criar_view("components.alerta", <<<'BLADE'
@props(["tipo" => "info"])
<div class="alerta {{ $tipo }}">{{ $slot }}</div>
BLADE);

criar_view("painel", <<<'BLADE'
<style>
  .alerta { padding: 10px; margin: 6px 0; border-radius: 6px; background: lightblue }
  .sucesso { background: lightgreen }
  .erro { background: pink }
</style>
<h1>Painel</h1>
<x-alerta tipo="sucesso">Matrícula feita!</x-alerta>
<x-alerta tipo="erro">Falta pagar a mensalidade.</x-alerta>
<x-alerta>Aula sábado às 9h.</x-alerta>
<x-alerta :tipo="$nivel">O tipo veio de uma <b>variável</b>.</x-alerta>
BLADE);

Route::get("/painel", fn () => view("painel", ["nivel" => "erro"]));

visitar("/painel");

// ===== 11. O Blade vira PHP (Por dentro) =====
$blade = <<<'BLADE'
<h1>{{ $titulo }}</h1>
@if (count($cursos) > 0)
  <ul>
  @foreach ($cursos as $curso)
    <li>{{ $curso }}</li>
  @endforeach
  </ul>
@else
  <p>{!! $vazio !!}</p>
@endif
BLADE;

echo compilar_blade($blade);

// ===== 12. Projeto da aula: o site da escola (Juntando tudo) =====
// app/Models/Curso.php  (na L4 os dados vêm do banco)
class Curso {
  public function __construct(public int $id, public string $nome, public int $aulas) {}

  public static function all(): array {
    return [
      1 => new Curso(1, "HTML", 4),
      2 => new Curso(2, "CSS", 4),
      3 => new Curso(3, "Laravel", 6),
    ];
  }

  public static function find(int $id): ?Curso {
    return self::all()[$id] ?? null;
  }
}

// app/Http/Controllers/CursoController.php
class CursoController extends Controller {
  public function index(): string {
    return view("cursos.index", ["cursos" => Curso::all()]);
  }

  public function show(string $id): string {
    $curso = Curso::find($id);
    if (!$curso) {
      abort(404);
    }
    return view("cursos.show", ["curso" => $curso]);
  }
}

// resources/views/layouts/app.blade.php
criar_view("layouts.app", <<<'BLADE'
<style>body { font-family: sans-serif; margin: 24px }</style>
<nav><a href="{{ route('cursos.index') }}">🌱 Cursos</a></nav>
@yield("conteudo")
BLADE);

// resources/views/cursos/index.blade.php
criar_view("cursos.index", <<<'BLADE'
@extends("layouts.app")
@section("conteudo")
  <h1>Nossos cursos</h1>
  <ul>
  @foreach ($cursos as $curso)
    <li><a href="{{ route('cursos.show', $curso->id) }}">{{ $curso->nome }}</a></li>
  @endforeach
  </ul>
@endsection
BLADE);

// resources/views/cursos/show.blade.php
criar_view("cursos.show", <<<'BLADE'
@extends("layouts.app")
@section("conteudo")
  <h1>{{ $curso->nome }}</h1>
  <p>{{ $curso->aulas }} aulas</p>
@endsection
BLADE);

// routes/web.php
Route::get("/cursos", [CursoController::class, "index"])->name("cursos.index");
Route::get("/cursos/{id}", [CursoController::class, "show"])->whereNumber("id")->name("cursos.show");

visitar("/cursos");
visitar("/cursos/3");
visitar("/cursos/9");

// ===== 13. Área livre (Livre) =====
criar_view("teste", <<<'BLADE'
<h1>Olá, {{ $nome }}!</h1>
BLADE);

Route::get("/teste/{nome}", fn (string $nome) => view("teste", ["nome" => $nome]));

visitar("/teste/mundo");
