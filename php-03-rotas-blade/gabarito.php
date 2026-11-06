<?php
// LARAVEL 3 — ROTAS E VIEWS COM BLADE — GABARITO (só para o professor)
// Roda inteiro: php gabarito.php (precisa do mini-laravel.php na mesma pasta)
require __DIR__ . "/mini-laravel.php";

// ===== Exercício 01: Rotas com parâmetro (implementação) =====
// O pedaço entre chaves chega como parâmetro da função, sempre como texto; o PHP converte "21" em número na
// multiplicação. Com whereNumber, /dobro/abc não combina com a rota, e sem rota o resultado é 404.
Route::get("/alunos/{nome}", function (string $nome) {
  return "Perfil de $nome";
});

Route::get("/dobro/{n}", function (string $n) {
  return "O dobro de $n é " . $n * 2;
})->whereNumber("n");

visitar("/alunos/Ana");
visitar("/dobro/21");
visitar("/dobro/abc");

// ===== Exercício 02: Parâmetro opcional e rota nomeada (implementação) =====
// O ? deixa o parâmetro opcional; quando ele não vem na URL, a função usa o valor padrão "visitante".
// route("ola", "Bia") procura a rota pelo nome e encaixa Bia no lugar de {nome?}.
Route::get("/ola/{nome?}", function (string $nome = "visitante") {
  return "Olá, $nome!";
})->name("ola");

visitar("/ola");
visitar("/ola/Bia");
echo "Link: " . route("ola", "Bia") . "\n";

// ===== Exercício 03: {{ }} ou {!! !!}? (previsão de saída e correção) =====
// Previsão: com o código original, a 1ª linha sai <p>Aluno: <b>Hacker</b></p> (o HTML do aluno entra na página)
// e a 2ª sai com &lt;strong&gt;. Regra: tudo o que vem do usuário vai em {{ }}; {!! !!} só para HTML que você
// mesmo escreveu.
criar_view("aluno", <<<'BLADE'
<p>Aluno: {{ $nome }}</p>
<p>Destaque: {!! $destaque !!}</p>
BLADE);

Route::get("/aluno", function () {
  return view("aluno", [
    "nome" => "<b>Hacker</b>",
    "destaque" => "<strong>novo</strong>",
  ]);
});

visitar("/aluno");

// ===== Exercício 04: @if no boletim (implementação) =====
// O Blade testa as condições em ordem e para na primeira verdadeira: 6 não é >= 7, mas é >= 5. Não precisa
// escrever $media < 7 no @elseif.
criar_view("boletim", <<<'BLADE'
@if ($media >= 7)
  <p>{{ $nome }}: aprovado(a)</p>
@elseif ($media >= 5)
  <p>{{ $nome }}: recuperação</p>
@else
  <p>{{ $nome }}: reprovado(a)</p>
@endif
BLADE);

Route::get("/boletim/{nome}/{media}", function (string $nome, string $media) {
  return view("boletim", ["nome" => $nome, "media" => $media]);
});

visitar("/boletim/Ana/9");
visitar("/boletim/Caio/6");
visitar("/boletim/Bia/3");

// ===== Exercício 05: @forelse da turma (implementação) =====
// @forelse é um @foreach que já pergunta "e se a lista estiver vazia?". Sem ele, você precisaria de um @if
// (count($alunos) > 0) em volta do @foreach e um @else.
criar_view("turma", <<<'BLADE'
<h1>Turma da {{ $turno }}</h1>
<ul>
@forelse ($alunos as $aluno)
  <li>{{ $aluno }}</li>
@empty
  <li>Nenhum aluno matriculado</li>
@endforelse
</ul>
BLADE);

Route::get("/turmas/{turno}", function (string $turno) {
  $turmas = [
    "manha" => ["Ana", "Caio"],
    "noite" => [],
  ];
  return view("turma", ["turno" => $turno, "alunos" => $turmas[$turno]]);
});

visitar("/turmas/manha");
visitar("/turmas/noite");

// ===== Exercício 06: Ranking com $loop (implementação) =====
// O $loop só existe dentro do @foreach. iteration começa em 1 (bom para mostrar); index começa em 0 (como o
// índice do array).
criar_view("ranking", <<<'BLADE'
<h1>Ranking</h1>
@foreach ($ranking as $aluno)
  <li>{{ $loop->iteration }}º {{ $aluno }}</li>
  @if ($loop->first)
    <p>🏆 Primeiro lugar!</p>
  @endif
  @if ($loop->last)
    <p>Fim do ranking: {{ $loop->count }} alunos</p>
  @endif
@endforeach
BLADE);

Route::get("/ranking", fn () => view("ranking", ["ranking" => ["Ana", "Caio", "Bia"]]));

visitar("/ranking");

// ===== Exercício 07: Conserte a view (identificação de erros) =====
// ① as aluno sem $: erro de sintaxe (no Blade também, variável começa com $). ② { $aluno->media }} com uma chave
// só na abertura: o Blade não reconhece e o texto aparece cru na saída, sem erro nenhum. ③ @if sem @endif:
// "unexpected end of file", porque o PHP chega ao fim esperando o fechamento.
class Aluno {
  public function __construct(public string $nome, public float $media) {}
}

criar_view("notas", <<<'BLADE'
<ul>
@foreach ($alunos as $aluno)
  <li>
    {{ $aluno->nome }}: {{ $aluno->media }}
    @if ($aluno->media >= 7)
      ✅
    @endif
  </li>
@endforeach
</ul>
BLADE);

Route::get("/notas", fn () => view("notas", [
  "alunos" => [new Aluno("Ana", 9.5), new Aluno("Caio", 6)],
]));

visitar("/notas");

// ===== Exercício 08: Página com layout (implementação) =====
// A página não repete o cabeçalho nem o rodapé: ela diz qual layout usa e preenche os buracos. O Laravel monta
// primeiro as seções da página e depois o layout, que coloca cada seção no seu @yield.
criar_view("layouts.app", <<<'BLADE'
<title>@yield("titulo") | Greenn School</title>
<header>🌱 Greenn School</header>
<main>
  @yield("conteudo")
</main>
<footer>Aulas aos sábados</footer>
BLADE);

criar_view("contato", <<<'BLADE'
@extends("layouts.app")

@section("titulo", "Contato")

@section("conteudo")
  <h1>Fale com a gente</h1>
  <p>{{ $email }}</p>
@endsection
BLADE);

Route::get("/contato", fn () => view("contato", ["email" => "contato@greenn.school"]));

visitar("/contato");

// ===== Exercício 09: Refatore com @include (refatoração) =====
// Refatorar é mudar o código sem mudar o resultado: a loja continua igual, mas agora o card mora num lugar só. O
// partial recebe o produto pelo nome $produto, então serve para qualquer view que mande esse dado.
criar_view("partials.produto", <<<'BLADE'
<div class="produto">
  <h3>{{ $produto["nome"] }}</h3>
  <p>R$ {{ number_format($produto["preco"], 2, ",", ".") }}</p>
</div>
BLADE);

criar_view("loja", <<<'BLADE'
<h1>Loja</h1>
@foreach ($produtos as $p)
  @include("partials.produto", ["produto" => $p])
@endforeach
BLADE);

criar_view("promocoes", <<<'BLADE'
<h1>Promoções</h1>
@foreach ($produtos as $p)
  @include("partials.produto", ["produto" => $p])
@endforeach
BLADE);

Route::get("/loja", fn () => view("loja", ["produtos" => [
  ["nome" => "Caneca", "preco" => 25],
  ["nome" => "Camiseta", "preco" => 59.9],
]]));
Route::get("/promocoes", fn () => view("promocoes", ["produtos" => [
  ["nome" => "Camiseta", "preco" => 39.9],
]]));

visitar("/loja");
visitar("/promocoes");

// ===== Exercício 10: Desafio: catálogo de cursos (integração) =====
// É o mesmo caminho da L2 (rota → controller → view), agora com as peças da L3: rotas nomeadas, abort(404)
// quando o model não encontra, layout com @extends e links montados por route(). A ordem dos arquivos no
// playground não importa: a rota só chama o controller na hora do visitar.
class Curso {
  public function __construct(public int $id, public string $nome, public int $aulas) {}

  public static function all(): array {
    return [1 => new Curso(1, "HTML", 4), 2 => new Curso(2, "Laravel", 6)];
  }

  public static function find(int $id): ?Curso {
    return self::all()[$id] ?? null;
  }
}

criar_view("layouts.app", <<<'BLADE'
<nav>Greenn School</nav>
@yield("conteudo")
BLADE);

criar_view("cursos.index", <<<'BLADE'
@extends("layouts.app")
@section("conteudo")
  <h1>Cursos</h1>
  <ul>
  @foreach ($cursos as $curso)
    <li><a href="{{ route('cursos.show', $curso->id) }}">{{ $curso->nome }}</a></li>
  @endforeach
  </ul>
@endsection
BLADE);

criar_view("cursos.show", <<<'BLADE'
@extends("layouts.app")
@section("conteudo")
  <h1>{{ $curso->nome }}</h1>
  <p>{{ $curso->aulas }} aulas</p>
@endsection
BLADE);

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

Route::get("/cursos", [CursoController::class, "index"])->name("cursos.index");
Route::get("/cursos/{id}", [CursoController::class, "show"])->whereNumber("id")->name("cursos.show");

visitar("/cursos");
visitar("/cursos/2");
visitar("/cursos/7");
