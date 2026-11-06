# LARAVEL 3 — ROTAS E VIEWS COM BLADE

**Módulo:** Laravel · aula 3 de 6
**Playground dos alunos:** https://site-alunos-nu.vercel.app/php-03/
**Apostila:** `apostila-php-03.pdf` (também para download no site)
**Duração:** 2h30

Continua de onde a L2 parou (rota → controller → view com `<?= ?>`): aprofunda as rotas e troca o PHP puro das views pelo Blade. Todos os exemplos e exercícios carregam antes do código do aluno o **mini-Laravel da L3** (`mini-laravel.php`), uma versão maior que a da L2:

- **Rotas:** `Route::get` com `->name()`, `->where()` e `->whereNumber()`, parâmetros `{id}` e opcionais `{nome?}`, `Route::view`, `Route::redirect`, grupos com `Route::prefix()->name()->group()`, `route()`, `abort()` e `visitar()`.
- **Blade de verdade (compilado para PHP, como o Laravel faz):** `{{ }}` com escape, `{!! !!}`, `{{-- --}}`, `@if`/`@elseif`/`@else`, `@unless`, `@isset`, `@empty`, `@foreach` com `$loop`, `@forelse`/`@empty`, `@for`, `@while`, `@php`, `@extends`/`@section`/`@yield`, `@include`, componentes `<x-nome>` com `$slot` e `@props`, `@json`, `@csrf` e `@method` (estes dois já prontos para a L5).
- `criar_view($nome, $blade)` faz o papel de um arquivo `.blade.php`; `compilar_blade($blade)` mostra o PHP gerado (exemplo 11).

A apostila mostra o código do Laravel de verdade (`use`, `namespace`, arquivos em `resources/views`); o mini-Laravel é só do playground.

## Exemplos

| # | Exemplo | Bloco |
|---|---|---|
| 01 | routes/web.php: três jeitos de responder | Rotas |
| 02 | Parâmetros: {id}, {nome?} e where | Rotas |
| 03 | Rotas nomeadas e route() | Rotas |
| 04 | Grupos, redirect e abort(404) | Rotas |
| 05 | Blade: {{ }} no lugar do <?= ?> | Blade |
| 06 | Condicionais: @if, @unless, @isset e @empty | Blade |
| 07 | Laços: @foreach, @forelse e $loop | Blade |
| 08 | Layout: @extends, @section e @yield | Blade |
| 09 | Pedaços reaproveitados: @include | Blade |
| 10 | Componentes: <x-alerta> | Blade |
| 11 | O Blade vira PHP | Por dentro |
| 12 | Projeto da aula: o site da escola | Juntando tudo |
| 13 | Área livre | Livre |

## Exercícios

| # | Exercício | Tipo | Objetivo |
|---|---|---|---|
| 01 | Rotas com parâmetro | implementação | Receber pedaços da URL e restringir com whereNumber. |
| 02 | Parâmetro opcional e rota nomeada | implementação | `{nome?}` com valor padrão e `route()`. |
| 03 | {{ }} ou {!! !!}? | previsão de saída e correção | Entender o escape e corrigir uma view insegura. |
| 04 | @if no boletim | implementação | `@if` / `@elseif` / `@else`. |
| 05 | @forelse da turma | implementação | Lista com plano B para lista vazia. |
| 06 | Ranking com $loop | implementação | `iteration`, `first`, `last` e `count`. |
| 07 | Conserte a view | identificação de erros | Três erros de Blade: sem `$`, chave faltando, `@endif` faltando. |
| 08 | Página com layout | implementação | `@extends` e `@section`. |
| 09 | Refatore com @include | refatoração | Tirar o card repetido para um partial. |
| 10 | Desafio: catálogo de cursos | integração | Rotas nomeadas → controller → views com layout, links e 404. |

Arquivos: `exemplos.php`, `exercicios.php`, `gabarito.php` (com o comentário de cada resposta), `mini-laravel.php`. O `gabarito.php` roda inteiro (`php gabarito.php`, PHP 8.1+). Os exemplos rodam um de cada vez, com `require "mini-laravel.php";` no começo.

O mini-Laravel também está copiado dentro de `site-alunos/php-03/index.html` (bloco `<script type="text/x-kit" id="kit-laravel">`). Se alterar um, altere o outro. Cuidado ao editar: um `?>` dentro de um comentário `//` fecha o PHP.
