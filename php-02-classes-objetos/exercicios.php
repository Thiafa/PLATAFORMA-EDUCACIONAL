<?php
// LARAVEL 2 — PHP: CLASSES, OBJETOS E ORGANIZAÇÃO DO CÓDIGO — EXERCÍCIOS
// Resolva no site (correção automática): https://site-alunos-nu.vercel.app/php-02/
// Os exercícios 09 e 10 usam o mini-Laravel (no computador: require "mini-laravel.php";).

// ===== Exercício 01: Primeira classe =====
// Objetivo: Criar uma classe, um objeto e acessar as propriedades.
// 1. Crie a classe Livro com public string $titulo e public float $preco.
// 2. Crie um objeto com new Livro() e preencha: título Dom Casmurro, preço 39.9.
// 3. Mostre Dom Casmurro custa R$ 39,90 usando -> e number_format.
// Saída esperada:
//   Dom Casmurro custa R$ 39,90
// crie a classe Livro aqui

// ===== Exercício 02: Métodos do retângulo =====
// Objetivo: Criar métodos que usam $this.
// 1. Na classe Retangulo, crie o método area(): float que devolve largura × altura.
// 2. Crie perimetro(): float que devolve 2 × (largura + altura).
// 3. Crie um retângulo e mostre Área: 12 e Perímetro: 14.
// Saída esperada:
//   Área: 12
//   Perímetro: 14
class Retangulo {
  public float $largura = 4;
  public float $altura = 3;

  // crie os métodos area() e perimetro()
}

// ===== Exercício 03: Construtor =====
// Objetivo: Usar o construtor para o objeto já nascer pronto.
// 1. Crie a classe Aluno com um __construct que recebe $nome e $curso (pode usar a forma curta com public).
// 2. Crie o método apresentar(): string que devolve Ana estuda Laravel.
// 3. O resto já está pronto embaixo: é só executar.
// Saída esperada:
//   Ana estuda Laravel
//   Caio estuda JavaScript
// crie a classe Aluno aqui

$ana = new Aluno("Ana", "Laravel");
$caio = new Aluno("Caio", "JavaScript");
echo $ana->apresentar(), "\n";
echo $caio->apresentar(), "\n";

// ===== Exercício 04: Conta protegida =====
// Objetivo: Proteger um dado com private e só mexer nele por métodos.
// 1. O $saldo já é private. Crie depositar(float $valor), que soma ao saldo.
// 2. Crie sacar(float $valor): se o valor for maior que o saldo, mostre Saldo insuficiente; senão, subtraia.
// 3. Crie verSaldo(): float, que devolve o saldo.
// Saída esperada:
//   Saldo: 150
//   Saldo insuficiente
//   Saldo: 150
class ContaBancaria {
  private float $saldo = 0;

  // crie depositar, sacar e verSaldo
}

$conta = new ContaBancaria();
$conta->depositar(100);
$conta->depositar(50);
echo "Saldo: " . $conta->verSaldo() . "\n";
$conta->sacar(500);
echo "Saldo: " . $conta->verSaldo() . "\n";

// ===== Exercício 05: Herança =====
// Objetivo: Criar classes filhas com extends e trocar um método.
// 1. A classe Animal já está pronta: o falar() usa o método som().
// 2. Crie Cachorro extends Animal com um som() que devolve Au au!.
// 3. Crie Gato extends Animal com um som() que devolve Miau!.
// Saída esperada:
//   Rex diz: Au au!
//   Mimi diz: Miau!
class Animal {
  public function __construct(public string $nome) {}

  public function som(): string {
    return "...";
  }

  public function falar(): string {
    return "$this->nome diz: " . $this->som();
  }
}

// crie as classes Cachorro e Gato aqui

$rex = new Cachorro("Rex");
$mimi = new Gato("Mimi");
echo $rex->falar(), "\n";
echo $mimi->falar(), "\n";

// ===== Exercício 06: Lista de produtos =====
// Objetivo: Percorrer uma lista de objetos (o formato que vem do banco).
// 1. Com um foreach, mostre cada produto no formato - Caneca: R$ 25,00.
// 2. No mesmo laço, some os preços numa variável $total.
// 3. No fim, mostre Total: R$ 129,90.
// Saída esperada:
//   - Caneca: R$ 25,00
//   - Camiseta: R$ 59,90
//   - Boné: R$ 45,00
//   Total: R$ 129,90
class Produto {
  public function __construct(public string $nome, public float $preco) {}
}

$produtos = [
  new Produto("Caneca", 25),
  new Produto("Camiseta", 59.9),
  new Produto("Boné", 45),
];

// ===== Exercício 07: Método estático =====
// Objetivo: Criar um método static e chamar com ::.
// 1. Crie a classe Formata com public static function dinheiro(float $valor): string.
// 2. Ela devolve o valor no formato R$ 1.234,50 (use number_format).
// 3. As chamadas com Formata::dinheiro(...) já estão prontas: sem new.
// Saída esperada:
//   R$ 25,00
//   R$ 1.234,50
// crie a classe Formata aqui

echo Formata::dinheiro(25), "\n";
echo Formata::dinheiro(1234.5), "\n";

// ===== Exercício 08: Onde fica cada arquivo =====
// Objetivo: Fixar a estrutura de pastas do Laravel.
// 1. Complete a função pasta($arquivo) com um match (true).
// 2. Termina em Controller.php: app/Http/Controllers; termina em .blade.php: resources/views; é web.php: routes; senão: app/Models.
// 3. Use str_ends_with($arquivo, "Controller.php") para testar o final do nome.
// Saída esperada:
//   AlunoController.php → app/Http/Controllers
//   web.php → routes
//   index.blade.php → resources/views
//   Aluno.php → app/Models
function pasta(string $arquivo): string {
  // complete com match (true) { ... }
  return "?";
}

$arquivos = ["AlunoController.php", "web.php", "index.blade.php", "Aluno.php"];
foreach ($arquivos as $arquivo) {
  echo "$arquivo → " . pasta($arquivo) . "\n";
}

// ===== Exercício 09: Rota e controller =====
// Objetivo: Ligar URLs a métodos de um controller.
// 1. Crie PaginaController extends Controller com home(), que devolve Página inicial, e sobre(), que devolve Sobre a Greenn School.
// 2. Registre as rotas: Route::get("/", [PaginaController::class, "home"]); e a de /sobre.
// 3. As visitas já estão prontas; o /contato não tem rota, então dá 404.
// Saída esperada:
//   Página inicial
//   Sobre a Greenn School
//   404 | Página não encontrada
// app/Http/Controllers/PaginaController.php
// crie a classe aqui

// routes/web.php
// registre as rotas aqui

// o navegador (não precisa mexer)
visitar("/");
visitar("/sobre");
visitar("/contato");

// ===== Exercício 10: O caminho completo =====
// Objetivo: Fazer um pedido percorrer rota → controller → view.
// 1. Na view produtos.index, complete o foreach para gerar um <li>Caneca - R$ 25,00</li> por produto.
// 2. Crie ProdutoController extends Controller com index(), que monta a lista de produtos e devolve view("produtos.index", ["produtos" => $produtos]).
// 3. Registre a rota /produtos e clique em 🌐 ver como página.
// Saída esperada:
//   <h1>Produtos</h1>
//   <ul>
//     <li>Caneca - R$ 25,00</li>
//     <li>Camiseta - R$ 59,90</li>
//   </ul>
// app/Models/Produto.php
class Produto {
  public function __construct(public string $nome, public float $preco) {}
}

// resources/views/produtos/index.php
criar_view("produtos.index", <<<'HTML'
<h1>Produtos</h1>
<ul>
<?php foreach ($produtos as $p): ?>
  <li>complete aqui</li>
<?php endforeach; ?>
</ul>
HTML);

// app/Http/Controllers/ProdutoController.php
// crie o controller aqui (Caneca 25 e Camiseta 59.9)

// routes/web.php
// registre a rota /produtos aqui

visitar("/produtos");
