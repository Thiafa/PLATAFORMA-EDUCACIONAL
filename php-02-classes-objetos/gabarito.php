<?php
// LARAVEL 2 — PHP: CLASSES, OBJETOS E ORGANIZAÇÃO DO CÓDIGO — GABARITO
// Entregar só depois da correção.
// Os exercícios 09 e 10 usam o mini-Laravel do playground:
require __DIR__ . "/mini-laravel.php";

// ===== Exercício 01: Primeira classe =====
class Livro {
  public string $titulo;
  public float $preco;
}

$livro = new Livro();
$livro->titulo = "Dom Casmurro";
$livro->preco = 39.9;
echo "$livro->titulo custa R$ " . number_format($livro->preco, 2, ",", ".") . "\n";

// ===== Exercício 02: Métodos do retângulo =====
class Retangulo {
  public float $largura = 4;
  public float $altura = 3;

  public function area(): float {
    return $this->largura * $this->altura;
  }

  public function perimetro(): float {
    return 2 * ($this->largura + $this->altura);
  }
}

$r = new Retangulo();
echo "Área: " . $r->area() . "\n";
echo "Perímetro: " . $r->perimetro() . "\n";

// ===== Exercício 03: Construtor =====
class Aluno {
  public function __construct(
    public string $nome,
    public string $curso,
  ) {}

  public function apresentar(): string {
    return "$this->nome estuda $this->curso";
  }
}

$ana = new Aluno("Ana", "Laravel");
$caio = new Aluno("Caio", "JavaScript");
echo $ana->apresentar(), "\n";
echo $caio->apresentar(), "\n";

// ===== Exercício 04: Conta protegida =====
class ContaBancaria {
  private float $saldo = 0;

  public function depositar(float $valor): void {
    $this->saldo += $valor;
  }

  public function sacar(float $valor): void {
    if ($valor > $this->saldo) {
      echo "Saldo insuficiente\n";
      return;
    }
    $this->saldo -= $valor;
  }

  public function verSaldo(): float {
    return $this->saldo;
  }
}

$conta = new ContaBancaria();
$conta->depositar(100);
$conta->depositar(50);
echo "Saldo: " . $conta->verSaldo() . "\n";
$conta->sacar(500);
echo "Saldo: " . $conta->verSaldo() . "\n";

// ===== Exercício 05: Herança =====
class Animal {
  public function __construct(public string $nome) {}

  public function som(): string {
    return "...";
  }

  public function falar(): string {
    return "$this->nome diz: " . $this->som();
  }
}

class Cachorro extends Animal {
  public function som(): string {
    return "Au au!";
  }
}

class Gato extends Animal {
  public function som(): string {
    return "Miau!";
  }
}

$rex = new Cachorro("Rex");
$mimi = new Gato("Mimi");
echo $rex->falar(), "\n";
echo $mimi->falar(), "\n";

// ===== Exercício 06: Lista de produtos =====
class Produto {
  public function __construct(public string $nome, public float $preco) {}
}

$produtos = [
  new Produto("Caneca", 25),
  new Produto("Camiseta", 59.9),
  new Produto("Boné", 45),
];

$total = 0;
foreach ($produtos as $item) {
  echo "- $item->nome: R$ " . number_format($item->preco, 2, ",", ".") . "\n";
  $total += $item->preco;
}
echo "Total: R$ " . number_format($total, 2, ",", ".") . "\n";

// ===== Exercício 07: Método estático =====
class Formata {
  public static function dinheiro(float $valor): string {
    return "R$ " . number_format($valor, 2, ",", ".");
  }
}

echo Formata::dinheiro(25), "\n";
echo Formata::dinheiro(1234.5), "\n";

// ===== Exercício 08: Onde fica cada arquivo =====
function pasta(string $arquivo): string {
  return match (true) {
    str_ends_with($arquivo, "Controller.php") => "app/Http/Controllers",
    str_ends_with($arquivo, ".blade.php") => "resources/views",
    $arquivo == "web.php" => "routes",
    default => "app/Models",
  };
}

$arquivos = ["AlunoController.php", "web.php", "index.blade.php", "Aluno.php"];
foreach ($arquivos as $arquivo) {
  echo "$arquivo → " . pasta($arquivo) . "\n";
}

// ===== Exercício 09: Rota e controller =====
class PaginaController extends Controller {
  public function home(): string {
    return "Página inicial";
  }

  public function sobre(): string {
    return "Sobre a Greenn School";
  }
}

Route::get("/", [PaginaController::class, "home"]);
Route::get("/sobre", [PaginaController::class, "sobre"]);

visitar("/");
visitar("/sobre");
visitar("/contato");

// ===== Exercício 10: O caminho completo =====
class ProdutoController extends Controller {
  public function index(): string {
    $produtos = [
      new Produto("Caneca", 25),
      new Produto("Camiseta", 59.9),
    ];
    return view("produtos.index", ["produtos" => $produtos]);
  }
}

criar_view("produtos.index", <<<'HTML'
<h1>Produtos</h1>
<ul>
<?php foreach ($produtos as $p): ?>
  <li><?= $p->nome ?> - R$ <?= number_format($p->preco, 2, ",", ".") ?></li>
<?php endforeach; ?>
</ul>
HTML);

Route::get("/produtos", [ProdutoController::class, "index"]);

visitar("/produtos");
