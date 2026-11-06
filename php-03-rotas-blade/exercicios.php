<?php
// LARAVEL 3 — ROTAS E VIEWS COM BLADE — EXERCÍCIOS
// Resolva no site (correção automática): https://site-alunos-nu.vercel.app/php-03/
// Todos usam o mini-Laravel da L3 (no computador: require "mini-laravel.php";).

// ===== Exercício 01: Rotas com parâmetro =====
// Objetivo: Receber pedaços da URL como parâmetros e restringir com whereNumber.
// 1. Crie a rota /alunos/{nome}, que devolve Perfil de Ana para /alunos/Ana.
// 2. Crie a rota /dobro/{n}, que devolve O dobro de 21 é 42.
// 3. Use ->whereNumber("n") na segunda, para /dobro/abc dar 404.
// Saída esperada:
//   Perfil de Ana
//   O dobro de 21 é 42
//   404 | Página não encontrada
// routes/web.php
// crie as duas rotas aqui

// o navegador (não precisa mexer)
visitar("/alunos/Ana");
visitar("/dobro/21");
visitar("/dobro/abc");

// ===== Exercício 02: Parâmetro opcional e rota nomeada =====
// Objetivo: Criar um parâmetro opcional e montar a URL pelo nome da rota.
// 1. Crie a rota /ola/{nome?} que devolve Olá, Bia!. Sem nome, ela deve dizer Olá, visitante!.
// 2. Dê a ela o nome ola com ->name().
// 3. Complete o echo usando route("ola", "Bia"), sem escrever a URL à mão.
// Saída esperada:
//   Olá, visitante!
//   Olá, Bia!
//   Link: /ola/Bia
// routes/web.php
// crie a rota aqui

visitar("/ola");
visitar("/ola/Bia");
echo "Link: " . "complete com route()" . "\n";

// ===== Exercício 03: {{ }} ou {!! !!}? =====
// Objetivo: Entender o escape do Blade e corrigir uma view insegura.
// 1. Antes de executar, escreva num papel o que você acha que cada linha mostra.
// 2. Execute. O nome vem do cadastro do aluno (qualquer um digita), mas a view mostra o nome sem escapar. O destaque vem do sistema (é confiável), mas aparece escapado.
// 3. Corrija a view: escape o nome e mostre o destaque como HTML.
// Saída esperada:
//   <p>Aluno: &lt;b&gt;Hacker&lt;/b&gt;</p>
//   <p>Destaque: <strong>novo</strong></p>
// resources/views/aluno.blade.php
criar_view("aluno", <<<'BLADE'
<p>Aluno: {!! $nome !!}</p>
<p>Destaque: {{ $destaque }}</p>
BLADE);

Route::get("/aluno", function () {
  return view("aluno", [
    "nome" => "<b>Hacker</b>",           // digitado pelo aluno
    "destaque" => "<strong>novo</strong>", // escrito pelo sistema
  ]);
});

visitar("/aluno");

// ===== Exercício 04: @if no boletim =====
// Objetivo: Escolher o que mostrar com @if, @elseif e @else.
// 1. Na view boletim, mostre <p>Ana: aprovado(a)</p> se a média for 7 ou mais.
// 2. Use @elseif para a média de 5 até 7 (recuperação) e @else para o resto (reprovado(a)).
// 3. A rota e as visitas já estão prontas.
// Saída esperada:
//   <p>Ana: aprovado(a)</p>
//   <p>Caio: recuperação</p>
//   <p>Bia: reprovado(a)</p>
// resources/views/boletim.blade.php
criar_view("boletim", <<<'BLADE'
{{-- use @if, @elseif, @else e @endif --}}
<p>{{ $nome }}: complete aqui</p>
BLADE);

Route::get("/boletim/{nome}/{media}", function (string $nome, string $media) {
  return view("boletim", ["nome" => $nome, "media" => $media]);
});

visitar("/boletim/Ana/9");
visitar("/boletim/Caio/6");
visitar("/boletim/Bia/3");

// ===== Exercício 05: @forelse da turma =====
// Objetivo: Percorrer uma lista com @forelse e tratar a lista vazia.
// 1. Na view turma, use @forelse ($alunos as $aluno) para gerar um <li> por aluno.
// 2. No @empty, mostre <li>Nenhum aluno matriculado</li>.
// 3. A turma da manhã tem dois alunos; a da noite está vazia.
// Saída esperada:
//   <h1>Turma da manha</h1>
//   <ul>
//     <li>Ana</li>
//     <li>Caio</li>
//   </ul>
//   <h1>Turma da noite</h1>
//   <ul>
//     <li>Nenhum aluno matriculado</li>
//   </ul>
// resources/views/turma.blade.php
criar_view("turma", <<<'BLADE'
<h1>Turma da {{ $turno }}</h1>
<ul>
  {{-- crie o @forelse aqui --}}
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

// ===== Exercício 06: Ranking com $loop =====
// Objetivo: Usar a variável $loop dentro do @foreach.
// 1. Numere cada aluno com $loop->iteration: <li>1º Ana</li>.
// 2. Logo depois do primeiro, mostre <p>🏆 Primeiro lugar!</p> (use $loop->first).
// 3. Depois do último, mostre <p>Fim do ranking: 3 alunos</p>, com $loop->last e $loop->count.
// Saída esperada:
//   <h1>Ranking</h1>
//     <li>1º Ana</li>
//         <p>🏆 Primeiro lugar!</p>
//         <li>2º Caio</li>
//         <li>3º Bia</li>
//           <p>Fim do ranking: 3 alunos</p>
criar_view("ranking", <<<'BLADE'
<h1>Ranking</h1>
@foreach ($ranking as $aluno)
  <li>{{ $aluno }}</li>
@endforeach
BLADE);

Route::get("/ranking", fn () => view("ranking", ["ranking" => ["Ana", "Caio", "Bia"]]));

visitar("/ranking");

// ===== Exercício 07: Conserte a view =====
// Objetivo: Encontrar e corrigir erros de Blade lendo as mensagens.
// 1. A view notas tem 3 erros. Execute, leia o erro, corrija e execute de novo.
// 2. Atenção: um dos erros não dá mensagem nenhuma, só mostra um texto estranho na saída.
// 3. Quando estiver certa, a página lista os alunos e coloca ✅ só em quem tem média 7 ou mais.
// Saída esperada:
//   <ul>
//     <li>
//       Ana: 9.5
//             ✅
//         </li>
//     <li>
//       Caio: 6
//         </li>
//   </ul>
class Aluno {
  public function __construct(public string $nome, public float $media) {}
}

criar_view("notas", <<<'BLADE'
<ul>
@foreach ($alunos as aluno)
  <li>
    {{ $aluno->nome }}: { $aluno->media }}
    @if ($aluno->media >= 7)
      ✅
  </li>
@endforeach
</ul>
BLADE);

Route::get("/notas", fn () => view("notas", [
  "alunos" => [new Aluno("Ana", 9.5), new Aluno("Caio", 6)],
]));

visitar("/notas");

// ===== Exercício 08: Página com layout =====
// Objetivo: Criar uma página que estende um layout com @extends e @section.
// 1. O layout layouts.app já está pronto. Crie a view contato começando com @extends("layouts.app").
// 2. Preencha o título com @section("titulo", "Contato").
// 3. Na seção conteudo, mostre <h1>Fale com a gente</h1> e <p>{{ $email }}</p>.
// Saída esperada:
//   <title>Contato | Greenn School</title>
//   <header>🌱 Greenn School</header>
//   <main>
//       <h1>Fale com a gente</h1>
//     <p>contato@greenn.school</p>
//   </main>
//   <footer>Aulas aos sábados</footer>
// resources/views/layouts/app.blade.php (pronto)
criar_view("layouts.app", <<<'BLADE'
<title>@yield("titulo") | Greenn School</title>
<header>🌱 Greenn School</header>
<main>
  @yield("conteudo")
</main>
<footer>Aulas aos sábados</footer>
BLADE);

// resources/views/contato.blade.php
criar_view("contato", <<<'BLADE'
{{-- transforme esta página: ela ainda não usa o layout --}}
<h1>Fale com a gente</h1>
BLADE);

Route::get("/contato", fn () => view("contato", ["email" => "contato@greenn.school"]));

visitar("/contato");

// ===== Exercício 09: Refatore com @include =====
// Objetivo: Tirar um pedaço repetido para um partial e reaproveitar.
// 1. A view loja tem o HTML do card de produto escrito dentro do @foreach. A view promocoes precisa do mesmo card.
// 2. Crie o partial partials.produto com esse HTML.
// 3. Use @include("partials.produto", ["produto" => $p]) nas duas views.
// Saída esperada:
//   <h1>Loja</h1>
//     <div class="produto">
//     <h3>Caneca</h3>
//     <p>R$ 25,00</p>
//   </div>
//     <div class="produto">
//     <h3>Camiseta</h3>
//     <p>R$ 59,90</p>
//   </div>
//   <h1>Promoções</h1>
//     <div class="produto">
//     <h3>Camiseta</h3>
//     <p>R$ 39,90</p>
//   </div>
// resources/views/loja.blade.php
criar_view("loja", <<<'BLADE'
<h1>Loja</h1>
@foreach ($produtos as $p)
  <div class="produto">
    <h3>{{ $p["nome"] }}</h3>
    <p>R$ {{ number_format($p["preco"], 2, ",", ".") }}</p>
  </div>
@endforeach
BLADE);

// resources/views/promocoes.blade.php
criar_view("promocoes", <<<'BLADE'
<h1>Promoções</h1>
{{-- mostre os produtos com o mesmo card --}}
BLADE);

// crie o partial aqui

Route::get("/loja", fn () => view("loja", ["produtos" => [
  ["nome" => "Caneca", "preco" => 25],
  ["nome" => "Camiseta", "preco" => 59.9],
]]));
Route::get("/promocoes", fn () => view("promocoes", ["produtos" => [
  ["nome" => "Camiseta", "preco" => 39.9],
]]));

visitar("/loja");
visitar("/promocoes");

// ===== Exercício 10: Desafio: catálogo de cursos =====
// Objetivo: Montar rotas nomeadas, controller, layout e views com links e 404.
// 1. Crie CursoController com index() (todos os cursos na view cursos.index) e show($id) (um curso na view cursos.show, ou abort(404)).
// 2. Registre as rotas /cursos (nome cursos.index) e /cursos/{id} (nome cursos.show).
// 3. Na lista, cada curso é um link feito com route('cursos.show', $curso->id). As views estendem o layout pronto.
// Saída esperada:
//   <nav>Greenn School</nav>
//     <h1>Cursos</h1>
//     <ul>
//         <li><a href="/cursos/1">HTML</a></li>
//         <li><a href="/cursos/2">Laravel</a></li>
//       </ul>
//   <nav>Greenn School</nav>
//     <h1>Laravel</h1>
//     <p>6 aulas</p>
//   404 | Página não encontrada
// app/Models/Curso.php (pronto)
class Curso {
  public function __construct(public int $id, public string $nome, public int $aulas) {}

  public static function all(): array {
    return [1 => new Curso(1, "HTML", 4), 2 => new Curso(2, "Laravel", 6)];
  }

  public static function find(int $id): ?Curso {
    return self::all()[$id] ?? null;
  }
}

// resources/views/layouts/app.blade.php (pronto)
criar_view("layouts.app", <<<'BLADE'
<nav>Greenn School</nav>
@yield("conteudo")
BLADE);

// resources/views/cursos/index.blade.php
criar_view("cursos.index", <<<'BLADE'
{{-- @extends, @section e um <li><a href="...">nome</a></li> por curso --}}
BLADE);

// resources/views/cursos/show.blade.php
criar_view("cursos.show", <<<'BLADE'
{{-- @extends, @section, <h1>nome</h1> e <p>6 aulas</p> --}}
BLADE);

// app/Http/Controllers/CursoController.php
// crie o controller aqui

// routes/web.php
// registre as rotas aqui

visitar("/cursos");
visitar("/cursos/2");
visitar("/cursos/7");
