<?php
// LARAVEL 2 — PHP: CLASSES, OBJETOS E ORGANIZAÇÃO DO CÓDIGO
// Exemplos da aula. Rode no site: https://site-alunos-nu.vercel.app/php-02/
// Cada exemplo roda sozinho: copie o bloco para o playground ou para um arquivo .php.
// (Não dá para rodar o arquivo inteiro de uma vez: vários exemplos declaram classes com o mesmo nome.)
// Os exemplos marcados com [mini-Laravel] usam Route, view, criar_view e visitar:
// no computador, coloque require "mini-laravel.php"; no começo.

// ===== 01. Classe e objeto: a forma e o bolo (Classes e objetos) =====
class Carro {
  public string $marca;
  public string $cor;
  public int $ano = 2024;   // valor padrão
}

$meuCarro = new Carro();      // new cria um objeto a partir da classe
$meuCarro->marca = "Fiat";    // -> acessa a propriedade
$meuCarro->cor = "vermelho";

$outro = new Carro();         // outro objeto, com os próprios valores
$outro->marca = "VW";
$outro->cor = "preto";

echo "Meu carro: $meuCarro->marca $meuCarro->cor\n";
echo "O outro: $outro->marca $outro->cor\n";
echo "Ano: $meuCarro->ano\n";

dump($meuCarro);

// ===== 02. Métodos e $this (Classes e objetos) =====
class Pedido {
  public array $itens = [];

  public function adicionar(string $item, float $preco): void {
    $this->itens[] = ["item" => $item, "preco" => $preco];
  }

  public function total(): float {
    $soma = 0;
    foreach ($this->itens as $i) {
      $soma += $i["preco"];
    }
    return $soma;
  }
}

$pedido = new Pedido();
$pedido->adicionar("Pizza", 45);
$pedido->adicionar("Refrigerante", 8.5);

echo count($pedido->itens) . " itens\n";
echo "Total: R$ " . number_format($pedido->total(), 2, ",", ".") . "\n";

// ===== 03. Construtor: o objeto já nasce pronto (Classes e objetos) =====
class Aluno {
  public string $nome;
  public string $curso;

  public function __construct(string $nome, string $curso) {
    $this->nome = $nome;
    $this->curso = $curso;
  }

  public function apresentar(): string {
    return "Oi, eu sou $this->nome e estudo $this->curso.";
  }
}

$ana = new Aluno("Ana", "Laravel");
echo $ana->apresentar(), "\n";

// forma curta (PHP 8): o "public" no parâmetro cria e preenche a propriedade
class Professor {
  public function __construct(
    public string $nome,
    public string $materia = "PHP",
  ) {}
}

$prof = new Professor("Rita");
echo "$prof->nome ensina $prof->materia\n";

// ===== 04. public e private: protegendo os dados (Classes e objetos) =====
class ContaBancaria {
  private float $saldo = 0;

  public function __construct(public string $titular) {}

  public function depositar(float $valor): void {
    if ($valor <= 0) {
      echo "Valor inválido\n";
      return;
    }
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

$conta = new ContaBancaria("Ana");
$conta->depositar(200);
$conta->sacar(50);
$conta->sacar(1000);
echo "$conta->titular tem R$ " . $conta->verSaldo() . "\n";

// $conta->saldo = 1000000;

// ===== 05. Herança: extends e parent (Classes e objetos) =====
class Pessoa {
  public function __construct(public string $nome) {}

  public function apresentar(): string {
    return "Olá, eu sou $this->nome.";
  }
}

class Professor extends Pessoa {
  public function __construct(string $nome, public string $materia) {
    parent::__construct($nome);   // deixa a Pessoa guardar o nome
  }

  public function apresentar(): string {
    return parent::apresentar() . " Dou aula de $this->materia.";
  }
}

$caio = new Pessoa("Caio");
$rita = new Professor("Rita", "Laravel");

echo $caio->apresentar(), "\n";
echo $rita->apresentar(), "\n";
var_dump($rita instanceof Pessoa);   // todo professor é uma pessoa

// ===== 06. static, constantes e o :: (Classes e objetos) =====
class Escola {
  const NOME = "Greenn School";
  public static int $matriculas = 0;

  public static function matricular(string $aluno): string {
    self::$matriculas++;
    return "$aluno matriculado(a) na " . self::NOME;
  }
}

echo Escola::NOME, "\n";
echo Escola::matricular("Ana"), "\n";
echo Escola::matricular("Caio"), "\n";
echo "Total: " . Escola::$matriculas . " matrículas\n";
echo Escola::class, "\n";   // o nome da classe, como texto

// ===== 07. Arrays de objetos: o que vem do banco (Organização do código) =====
class Produto {
  public function __construct(
    public string $nome,
    public float $preco,
    public bool $emEstoque = true,
  ) {}
}

$produtos = [
  new Produto("Caneca", 25),
  new Produto("Camiseta", 59.9),
  new Produto("Boné", 45, false),
];

foreach ($produtos as $p) {
  $aviso = $p->emEstoque ? "" : " (esgotado)";
  echo "$p->nome: R$ " . number_format($p->preco, 2, ",", ".") . "$aviso\n";
}

$disponiveis = array_filter($produtos, fn($p) => $p->emEstoque);
$nomes = array_map(fn($p) => $p->nome, $disponiveis);
echo "Disponíveis: " . implode(", ", $nomes) . "\n";

// ===== 08. Namespaces e use: o sobrenome das classes (Organização do código) =====
namespace App\Models {
  // arquivo app/Models/Aluno.php
  class Aluno {
    public function __construct(public string $nome) {}
  }
}

namespace App\Http\Controllers {
  // arquivo app/Http/Controllers/AlunoController.php
  use App\Models\Aluno;

  class AlunoController {
    public function index(): string {
      $ana = new Aluno("Ana");
      return "Aluno carregado: $ana->nome";
    }
  }
}

namespace {
  // o resto do programa
  use App\Http\Controllers\AlunoController;

  $controller = new AlunoController();
  echo $controller->index(), "\n";
  echo AlunoController::class, "\n";
  echo App\Models\Aluno::class, "\n";
}

// ===== 09. A estrutura de pastas do Laravel (Laravel por dentro) =====
// As pastas de um projeto Laravel (laravel new escola).
// ★ = as que você vai abrir quase todo dia
$pastas = [
  "routes/web.php"       => "★ rotas: qual URL chama qual controller",
  "app/Http/Controllers" => "★ controllers: recebem o pedido e decidem a resposta",
  "app/Models"           => "★ models: uma classe por tabela do banco",
  "resources/views"      => "★ views: o HTML (Blade) das páginas",
  "database/migrations"  => "a estrutura das tabelas (aula L5)",
  "public"               => "a única pasta aberta ao navegador (index.php, imagens)",
  "config"               => "configurações (nome do app, banco, e-mail…)",
  ".env"                 => "senhas e dados do ambiente (nunca vai para o Git)",
  "vendor"               => "o código do Laravel e dos pacotes (não mexa)",
  "artisan"              => "o comando do terminal: php artisan …",
];

foreach ($pastas as $pasta => $oQueTem) {
  echo str_pad($pasta, 21) . " → $oQueTem\n";
}

// o namespace da classe diz onde está o arquivo
$classe = "App\\Http\\Controllers\\AlunoController";
$arquivo = lcfirst(str_replace("\\", "/", $classe)) . ".php";
echo "\n$classe\n  mora em: $arquivo\n";

// ===== 10. Rotas: cada URL chama um código (Laravel por dentro) =====
// um roteador de brinquedo: um array de URL => função
class Rota {
  private static array $rotas = [];

  public static function get(string $url, Closure $acao): void {
    self::$rotas[$url] = $acao;
  }

  public static function visitar(string $url): void {
    echo "GET $url\n";
    if (isset(self::$rotas[$url])) {
      $acao = self::$rotas[$url];
      echo "  " . $acao() . "\n";
    } else {
      echo "  404 | Página não encontrada\n";
    }
  }
}

// routes/web.php: cada URL ligada a uma função
Rota::get("/", fn() => "Bem-vindo à Greenn School!");
Rota::get("/sobre", fn() => "Aulas de programação aos sábados.");
Rota::get("/contato", function () {
  return "Fale com a gente na secretaria.";
});

// o navegador pedindo páginas
Rota::visitar("/");
Rota::visitar("/sobre");
Rota::visitar("/cursos");   // não tem rota: 404

// ===== 11. Controller: a rota chama um método (Laravel por dentro) [mini-Laravel] =====
// app/Http/Controllers/CursoController.php
class CursoController extends Controller {
  private array $cursos = ["HTML", "CSS", "JavaScript", "Laravel"];

  public function index(): string {
    return "Cursos: " . implode(", ", $this->cursos);
  }

  public function show(string $id): string {
    $curso = $this->cursos[$id - 1] ?? null;   // ?? : se não existir, null
    if (!$curso) {
      return "Curso $id não existe";
    }
    return "Curso $id: $curso";
  }
}

// routes/web.php
Route::get("/cursos", [CursoController::class, "index"]);
Route::get("/cursos/{id}", [CursoController::class, "show"]);

// o navegador
visitar("/cursos");
visitar("/cursos/4");
visitar("/cursos/9");
visitar("/alunos");

// ===== 12. View: o controller devolve HTML (Laravel por dentro) [mini-Laravel] =====
// resources/views/cursos/index.php  (na L3 ele vira index.blade.php)
criar_view("cursos.index", <<<'HTML'
<h1><?= $titulo ?></h1>
<p>Temos <?= count($cursos) ?> cursos:</p>
<ul>
<?php foreach ($cursos as $curso): ?>
  <li><?= $curso ?></li>
<?php endforeach; ?>
</ul>
HTML);

// app/Http/Controllers/CursoController.php
class CursoController extends Controller {
  public function index(): string {
    $cursos = ["HTML", "CSS", "JavaScript", "Laravel"];
    return view("cursos.index", [
      "titulo" => "Cursos da Greenn School",
      "cursos" => $cursos,
    ]);
  }
}

// routes/web.php
Route::get("/cursos", [CursoController::class, "index"]);

visitar("/cursos");

// ===== 13. O caminho de um pedido: rota → controller → view (Laravel por dentro) [mini-Laravel] =====
namespace App\Models {
  // app/Models/Aluno.php
  class Aluno {
    public function __construct(public string $nome, public float $media) {}

    public function situacao(): string {
      return $this->media >= 7 ? "aprovado(a)" : "recuperação";
    }

    // no Laravel, Aluno::all() busca no banco; aqui é uma lista fixa
    public static function all(): array {
      return [
        new Aluno("Ana", 9.5),
        new Aluno("Caio", 6),
        new Aluno("Bia", 8),
      ];
    }
  }
}

namespace App\Http\Controllers {
  // app/Http/Controllers/AlunoController.php
  use App\Models\Aluno;

  class AlunoController extends Controller {
    public function index(): string {
      $alunos = Aluno::all();           // ③ pede os dados ao model
      return view("alunos.index", [     // ④ entrega os dados à view
        "alunos" => $alunos,
      ]);
    }
  }
}

namespace {
  use App\Http\Controllers\AlunoController;

  // resources/views/alunos/index.php  ⑤ a view monta o HTML
  criar_view("alunos.index", <<<'HTML'
<h1>Alunos da turma</h1>
<ul>
<?php foreach ($alunos as $aluno): ?>
  <li><?= $aluno->nome ?>: <?= $aluno->situacao() ?></li>
<?php endforeach; ?>
</ul>
HTML);

  // routes/web.php  ② a rota escolhe o controller
  Route::get("/alunos", [AlunoController::class, "index"]);

  visitar("/alunos");   // ① o navegador pede /alunos
}

// ===== 14. Área livre (Livre) [mini-Laravel] =====
class Teste {
  public string $msg = "funcionou";
}

$t = new Teste();
dump($t);
