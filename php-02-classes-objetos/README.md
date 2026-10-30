# LARAVEL 2 — PHP: CLASSES, OBJETOS E ORGANIZAÇÃO DO CÓDIGO

**Módulo:** Laravel · aula 2 de 6
**Playground dos alunos:** https://site-alunos-nu.vercel.app/php-02/
**Apostila:** `apostila-php-02.pdf` (também para download no site)
**Duração:** 2h30

Mesmo playground da L1 (PHP 8.4 de verdade no navegador). Nos exemplos 11 a 14 e nos exercícios 09 e 10, o playground carrega antes do código do aluno um **mini-Laravel** (`mini-laravel.php`): `Route::get`, `view()`, `criar_view()` (faz o papel de um arquivo em `resources/views`), `visitar()` (simula o navegador pedindo uma URL) e a classe `Controller`. É uma versão de brinquedo, pensada para o aluno ver o caminho rota → controller → view funcionando sem instalar o Laravel. O exemplo 10 monta um roteador do zero, só com classe e array, para mostrar que não tem mágica.

## Exemplos

| # | Exemplo | Bloco |
|---|---|---|
| 01 | Classe e objeto: a forma e o bolo | Classes e objetos |
| 02 | Métodos e $this | Classes e objetos |
| 03 | Construtor: o objeto já nasce pronto | Classes e objetos |
| 04 | public e private: protegendo os dados | Classes e objetos |
| 05 | Herança: extends e parent | Classes e objetos |
| 06 | static, constantes e o :: | Classes e objetos |
| 07 | Arrays de objetos: o que vem do banco | Organização do código |
| 08 | Namespaces e use: o sobrenome das classes | Organização do código |
| 09 | A estrutura de pastas do Laravel | Laravel por dentro |
| 10 | Rotas: cada URL chama um código | Laravel por dentro |
| 11 | Controller: a rota chama um método | Laravel por dentro |
| 12 | View: o controller devolve HTML | Laravel por dentro |
| 13 | O caminho de um pedido: rota → controller → view | Laravel por dentro |
| 14 | Área livre | Livre |

## Exercícios

| # | Exercício | Objetivo |
|---|---|---|
| 01 | Primeira classe | Criar uma classe, um objeto e acessar as propriedades. |
| 02 | Métodos do retângulo | Criar métodos que usam $this. |
| 03 | Construtor | Usar o construtor para o objeto já nascer pronto. |
| 04 | Conta protegida | Proteger um dado com private e só mexer nele por métodos. |
| 05 | Herança | Criar classes filhas com extends e trocar um método. |
| 06 | Lista de produtos | Percorrer uma lista de objetos (o formato que vem do banco). |
| 07 | Método estático | Criar um método static e chamar com ::. |
| 08 | Onde fica cada arquivo | Fixar a estrutura de pastas do Laravel. |
| 09 | Rota e controller | Ligar URLs a métodos de um controller. |
| 10 | O caminho completo | Fazer um pedido percorrer rota → controller → view. |

Arquivos: `exemplos.php`, `exercicios.php`, `gabarito.php`, `mini-laravel.php`. O `gabarito.php` roda inteiro em qualquer PHP 8.1+ (`php gabarito.php`). Os exemplos rodam um de cada vez (vários declaram classes com o mesmo nome); os que usam o mini-Laravel precisam de `require "mini-laravel.php";` no começo.

O mini-Laravel também está copiado dentro de `site-alunos/php-02/index.html` (bloco `<script type="text/x-kit" id="kit-laravel">`). Se alterar um, altere o outro.
