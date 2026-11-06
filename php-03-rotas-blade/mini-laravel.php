<?php
// MINI-LARAVEL DO PLAYGROUND (versão da L3: rotas + Blade)
// Uma versão de brinquedo do que o Laravel faz quando chega um pedido.
// No site ele já vem carregado (como o dd); no computador: require "mini-laravel.php";
//
// ROTAS (routes/web.php)
//   Route::get($url, $acao)                  registra uma rota; $acao = function ou [Controller::class, "metodo"]
//     ->name("cursos.show")                  dá um nome à rota
//     ->where("id", "[0-9]+") / ->whereNumber("id")   restringe um parâmetro
//   Route::view($url, $view, $dados)         rota que só mostra uma view
//   Route::redirect($de, $para)              redireciona uma URL para outra
//   Route::prefix("admin")->name("admin.")->group(function () { ... })   grupo de rotas
//   route($nome, $parametros)                monta a URL de uma rota pelo nome
//   abort(404)                               interrompe e responde com erro
//   visitar($url)                            simula o navegador pedindo uma URL
//
// VIEWS (resources/views)
//   criar_view($nome, $blade)                faz o papel de um arquivo .blade.php
//   view($nome, $dados)                      monta o HTML da view com os dados
//   compilar_blade($blade)                   mostra o PHP em que o Blade se transforma
//   e($texto), asset($caminho), url($caminho)
namespace App\Http\Controllers {
  // no Laravel: app/Http/Controllers/Controller.php
  abstract class Controller {}
}

namespace {
  class_alias(App\Http\Controllers\Controller::class, 'Controller');

  // ===================== ROTAS =====================

  class HttpErro extends Exception {}

  class RotaRegistrada {
    public ?string $nome = null;
    public array $regras = [];

    public function __construct(public string $url, public mixed $acao) {}

    public function name(string $nome): static {
      $this->nome = Route::$prefixoNome . $nome;
      return $this;
    }

    public function where(string|array $param, ?string $regex = null): static {
      $regras = is_array($param) ? $param : [$param => $regex];
      $this->regras = array_merge($this->regras, $regras);
      return $this;
    }

    public function whereNumber(string $param): static {
      return $this->where($param, '[0-9]+');
    }

    // "/cursos/{id}/{aula?}" vira #^/cursos/([0-9]+)(?:/([^/]+))?$#
    public function regex(): string {
      $r = preg_replace_callback('#/\{(\w+)(\?)?\}#', function ($m) {
        $parte = '(' . ($this->regras[$m[1]] ?? '[^/]+') . ')';
        return isset($m[2]) ? "(?:/$parte)?" : "/$parte";
      }, $this->url);
      return '#^' . $r . '$#';
    }
  }

  class GrupoDeRotas {
    private string $prefixo = '';
    private string $nome = '';

    public function prefix(string $prefixo): static { $this->prefixo = trim($prefixo, '/'); return $this; }
    public function name(string $nome): static { $this->nome = $nome; return $this; }

    public function group(Closure $rotas): void {
      [$p, $n] = [Route::$prefixo, Route::$prefixoNome];
      if ($this->prefixo !== '') Route::$prefixo .= '/' . $this->prefixo;
      Route::$prefixoNome .= $this->nome;
      $rotas();
      [Route::$prefixo, Route::$prefixoNome] = [$p, $n];
    }
  }

  class Route {
    public static array $rotas = [];
    public static string $prefixo = '';
    public static string $prefixoNome = '';

    public static function get(string $url, array|Closure $acao): RotaRegistrada {
      $url = '/' . trim(self::$prefixo . '/' . trim($url, '/'), '/');
      return self::$rotas[] = new RotaRegistrada($url, $acao);
    }

    public static function view(string $url, string $view, array $dados = []): RotaRegistrada {
      return self::get($url, fn () => view($view, $dados));
    }

    public static function redirect(string $de, string $para): RotaRegistrada {
      return self::get($de, fn () => new Redirecionamento($para));
    }

    public static function prefix(string $prefixo): GrupoDeRotas {
      return (new GrupoDeRotas())->prefix($prefixo);
    }

    public static function name(string $nome): GrupoDeRotas {
      return (new GrupoDeRotas())->name($nome);
    }

    public static function despachar(string $url): string {
      $url = '/' . trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
      try {
        foreach (self::$rotas as $rota) {
          if (preg_match($rota->regex(), $url, $partes)) {
            array_shift($partes);                                       // sobram só os {parâmetros}
            while ($partes && end($partes) === '') array_pop($partes);  // opcionais que não vieram
            $acao = $rota->acao;
            if (is_array($acao)) {
              [$classe, $metodo] = $acao;
              $acao = [new $classe(), $metodo];
            }
            $resposta = $acao(...$partes);
            if ($resposta instanceof Redirecionamento) {
              return "↪ 302: redirecionando para $resposta->para\n" . self::despachar($resposta->para);
            }
            if (is_array($resposta) || $resposta instanceof JsonSerializable) {
              return json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            return (string) $resposta;
          }
        }
        abort(404);
      } catch (HttpErro $erro) {
        return $erro->getMessage();
      }
    }
  }

  class Redirecionamento {
    public function __construct(public string $para) {}
  }

  function redirect(string $para): Redirecionamento {
    return new Redirecionamento($para);
  }

  function route(string $nome, mixed $parametros = []): string {
    foreach (Route::$rotas as $rota) {
      if ($rota->nome !== $nome) continue;
      $parametros = is_array($parametros) ? $parametros : [$parametros];
      $url = preg_replace_callback('#/\{(\w+)(\?)?\}#', function ($m) use (&$parametros, $nome) {
        $valor = array_key_exists($m[1], $parametros) ? $parametros[$m[1]] : array_shift($parametros);
        unset($parametros[$m[1]]);
        if ($valor === null && isset($m[2])) return '';
        if ($valor === null) throw new Exception("Faltou o parâmetro {{$m[1]}} para a rota \"$nome\"");
        return '/' . $valor;
      }, $rota->url);
      return $url === '' ? '/' : $url;
    }
    throw new Exception("A rota \"$nome\" não existe (confira o ->name)");
  }

  function abort(int $codigo, string $mensagem = ''): never {
    $textos = [403 => 'Acesso proibido', 404 => 'Página não encontrada', 500 => 'Erro no servidor'];
    throw new HttpErro("$codigo | " . ($mensagem ?: ($textos[$codigo] ?? 'Erro')));
  }

  function visitar(string $url): void {
    echo Route::despachar($url), "\n";
  }

  function url(string $caminho = ''): string {
    return '/' . ltrim($caminho, '/');
  }

  function asset(string $caminho): string {
    return url($caminho);
  }

  // HTML confiável (ex.: o $slot de um componente): o {{ }} não escapa
  class HtmlString {
    public function __construct(public string $html) {}
    public function __toString(): string { return $this->html; }
  }

  function e(mixed $valor): string {
    if ($valor instanceof HtmlString) return (string) $valor;
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
  }

  function csrf_token(): string {
    return 'token-do-formulario';
  }

  // ===================== BLADE =====================

  // $loop: existe dentro de todo @foreach do Blade
  class Loop {
    public int $index = -1;
    public int $iteration = 0;
    public bool $first = false;
    public bool $last = false;
    public bool $even = false;
    public bool $odd = false;
    public int $remaining;

    public function __construct(public int $count, public ?Loop $parent = null) {
      $this->remaining = $count;
    }

    public function passo(): void {
      $this->index++;
      $this->iteration++;
      $this->first = $this->iteration === 1;
      $this->last = $this->iteration === $this->count;
      $this->even = $this->iteration % 2 === 0;
      $this->odd = !$this->even;
      $this->remaining = $this->count - $this->iteration;
    }
  }

  class Blade {
    public static array $arquivos = [];
    private static array $secoes = [];
    private static array $abertas = [];

    // transforma Blade em PHP (o Laravel faz isso e guarda em storage/framework/views)
    public static function compilar(string $t): string {
      $t = preg_replace('/\{\{--.*?--\}\}/s', '', $t);                                   // {{-- comentário --}}
      $t = self::componentes($t);
      // (o PHP engole a quebra de linha logo depois do fechamento da tag: por isso ela vai em dobro)
      $t = preg_replace('/\{!!\s*(.+?)\s*!!\}(\r?\n)?/s', '<?php echo $1; ?>$2$2', $t);           // {!! sem escapar !!}
      $t = preg_replace('/(?<!@)\{\{\s*(.+?)\s*\}\}(\r?\n)?/s', '<?php echo e($1); ?>$2$2', $t);  // {{ escapado }}
      $t = str_replace('@{{', '{{', $t);
      $parenteses = '(\s*\((?:[^()\'"]++|\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"|(?2))*\))?';
      return preg_replace_callback('/\B@(\w+)' . $parenteses . '([ \t]*\r?\n)?/', function ($m) {
        $args = isset($m[2]) && $m[2] !== '' ? trim(substr(trim($m[2]), 1, -1)) : null;
        $php = self::diretiva($m[1], $args);
        if ($php === null) return $m[0];   // diretiva desconhecida: fica como está (ex.: e-mails)
        $nl = $m[3] ?? '';
        // as que mostram algo (@include, @yield…) mantêm a quebra de linha depois delas
        return in_array($m[1], ['include', 'yield', 'json', 'csrf', 'method']) ? $php . $nl . $nl : $php . $nl;
      }, $t);
    }

    private static function diretiva(string $nome, ?string $a): ?string {
      return match ($nome) {
        'if' => "<?php if ($a): ?>",
        'elseif' => "<?php elseif ($a): ?>",
        'else' => '<?php else: ?>',
        'endif', 'endunless', 'endisset', 'endempty', 'endforelse' => '<?php endif; ?>',
        'unless' => "<?php if (!($a)): ?>",
        'isset' => "<?php if (isset($a)): ?>",
        'empty' => $a === null
          ? '<?php endforeach; $loop = array_pop($__loops); if (array_pop($__vazios)): ?>'   // o @empty do @forelse
          : "<?php if (empty($a)): ?>",
        'foreach', 'forelse' => self::laco($nome, $a),
        'endforeach' => '<?php endforeach; $loop = array_pop($__loops); ?>',
        'for' => "<?php for ($a): ?>",
        'endfor' => '<?php endfor; ?>',
        'while' => "<?php while ($a): ?>",
        'endwhile' => '<?php endwhile; ?>',
        'php' => $a === null ? '<?php ' : "<?php $a; ?>",
        'endphp' => ' ?>',
        'extends' => "<?php \$__pai = $a; ?>",
        'section' => "<?php Blade::section($a); ?>",
        'endsection', 'stop' => '<?php Blade::endsection(); ?>',
        'yield' => "<?php echo Blade::mostrar($a); ?>",
        'include' => "<?php echo Blade::incluir(get_defined_vars(), $a); ?>",
        'props' => "<?php foreach ($a as \$__k => \$__v) { if (is_int(\$__k)) continue; \$\$__k ??= \$__v; } ?>",
        'json' => "<?php echo json_encode($a, JSON_UNESCAPED_UNICODE); ?>",
        'csrf' => '<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">',
        'method' => "<input type=\"hidden\" name=\"_method\" value=\"<?php echo $a; ?>\">",
        default => null,
      };
    }

    // @foreach($cursos as $curso) guarda o $loop de fora e cria um novo
    private static function laco(string $tipo, string $a): string {
      [$lista, $vars] = preg_split('/\s+as\s+/', $a, 2);
      $vazio = $tipo === 'forelse' ? ' $__vazios[] = empty($__lista);' : '';
      return "<?php \$__loops[] = \$loop ?? null; \$__lista = $lista;$vazio "
        . "\$loop = new Loop(count(\$__lista), end(\$__loops) ?: null); "
        . "foreach (\$__lista as $vars): \$loop->passo(); ?>";
    }

    // <x-alerta tipo="erro" :titulo="$t">texto</x-alerta>  vira  a view components.alerta com $slot
    private static function componentes(string $t): string {
      $attrs = '((?:\s+:?[\w-]+(?:="[^"]*")?)*)';
      $converte = function (string $nome, string $atributos, ?string $slot, string $nl = '') {
        preg_match_all('/(:?)([\w-]+)(?:="([^"]*)")?/', $atributos, $lista, PREG_SET_ORDER);
        $dados = [];
        foreach ($lista as [, $dinamico, $chave, $valor]) {
          $valor ??= 'true';
          $dados[] = var_export(lcfirst(str_replace('-', '', ucwords($chave, '-'))), true) . ' => '
            . ($dinamico ? $valor : var_export($valor, true));
        }
        $dados = '[' . implode(', ', $dados) . ']';
        if ($slot === null) return "<?php echo Blade::componente('$nome', $dados, ''); ?>$nl$nl";
        return "<?php ob_start(); ?>$slot<?php echo Blade::componente('$nome', $dados, ob_get_clean()); ?>$nl$nl";
      };
      $t = preg_replace_callback('#<x-([\w.-]+)' . $attrs . '\s*/>(\r?\n)?#',
        fn ($m) => $converte($m[1], $m[2], null, $m[3] ?? ''), $t);
      do {
        $antes = $t;
        $t = preg_replace_callback('#<x-([\w.-]+)' . $attrs . '\s*>((?:(?!<x-).)*?)</x-\1>(\r?\n)?#s',
          fn ($m) => $converte($m[1], $m[2], $m[3], $m[4] ?? ''), $t);
      } while ($t !== $antes);
      return $t;
    }

    public static function render(string $__view, array $__dados = []): string {
      if (!isset(self::$arquivos[$__view])) {
        throw new Exception("A view \"$__view\" não existe (crie com criar_view)");
      }
      $__pai = null;
      extract($__dados);   // cada chave do array vira uma variável dentro da view
      $__nivel = ob_get_level();
      $__secoesAbertas = count(self::$abertas);
      ob_start();
      try {
        eval('?>' . self::compilar(self::$arquivos[$__view]));
        if (count(self::$abertas) > $__secoesAbertas) {
          throw new Exception("Faltou o @endsection na view \"$__view\"");
        }
      } finally {
        $__html = '';
        while (ob_get_level() > $__nivel) $__html = ob_get_clean() . $__html;
      }
      // a view tem @extends: monta o layout, que mostra as @section com @yield
      return $__pai ? self::render($__pai, $__dados) : $__html;
    }

    public static function section(string $nome, mixed $valor = null): void {
      if (func_num_args() === 2) {
        self::$secoes[$nome] ??= e($valor);
        return;
      }
      self::$abertas[] = $nome;
      ob_start();
    }

    public static function endsection(): void {
      $nome = array_pop(self::$abertas);
      $conteudo = ob_get_clean();
      self::$secoes[$nome] ??= $conteudo;   // a seção da página filha vale mais que a do layout
    }

    public static function mostrar(string $nome, string $padrao = ''): string {
      return self::$secoes[$nome] ?? e($padrao);
    }

    public static function incluir(array $vars, string $view, array $dados = []): string {
      $vars = array_filter($vars, fn ($k) => !str_starts_with($k, '__') && $k !== 'loop', ARRAY_FILTER_USE_KEY);
      return self::render($view, array_merge($vars, $dados));
    }

    public static function componente(string $nome, array $dados, string $slot): string {
      return self::render("components.$nome", $dados + ['slot' => new HtmlString($slot)]);
    }

    public static function limpar(): void {
      self::$secoes = [];
      self::$abertas = [];
    }
  }

  function criar_view(string $nome, string $blade): void {
    Blade::$arquivos[$nome] = $blade;
  }

  function view(string $nome, array $dados = []): string {
    Blade::limpar();
    return Blade::render($nome, $dados);
  }

  function compilar_blade(string $blade): string {
    return Blade::compilar($blade);
  }
}
