<?php
// MINI-LARAVEL DO PLAYGROUND (versão da L4: rotas + Blade + banco de dados)
// Uma versão de brinquedo do que o Laravel faz. O banco é um SQLite de verdade, na memória:
// ele nasce vazio a cada execução. No site já vem carregado; no computador: require "mini-laravel.php";
// (precisa da extensão pdo_sqlite, que já vem no PHP do Laravel Herd e na maioria das instalações)
//
// ROTAS E VIEWS: tudo da L3 (Route::get, ->name, route(), abort, visitar, criar_view, view, Blade)
//
// MIGRATIONS (database/migrations)
//   criar_migration($nome, new class extends Migration { up() / down() })   faz o papel do arquivo
//   Schema::create("cursos", function (Blueprint $table) { ... })          cria uma tabela
//   Schema::table / Schema::dropIfExists / Schema::hasTable / Schema::getColumnListing
//   $table->id(), string, text, integer, decimal, boolean, date, foreignId, enum, timestamps ...
//     ->nullable() ->default(x) ->unique() ->constrained() ->cascadeOnDelete()
//
// ARTISAN (o terminal)
//   artisan("migrate")  migrate --pretend  migrate:rollback  migrate:status  migrate:fresh --seed
//   artisan("db:seed")  db:seed --class=CursoSeeder  make:model Curso -m  make:migration  make:seeder
//
// BANCO
//   DB::select($sql, $valores)  DB::insert  DB::update  DB::delete  DB::statement  DB::table("cursos")
//   DB::listen(function ($query) { ... })     mostra cada SQL que vai para o banco
//   ver_tabela("cursos")                       mostra as linhas da tabela (só no playground)
//
// ELOQUENT (app/Models)
//   class Curso extends Model { protected $fillable = [...]; }
//   Curso::all() / find / findOrFail / where / orderBy / first / count / pluck / create / destroy
//   $curso->save() / update([...]) / delete()
//   hasMany / hasOne / belongsTo e with() para carregar junto
//
// SEEDERS (database/seeders)
//   class DatabaseSeeder extends Seeder { public function run(): void { ... } }
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
      } catch (ModelNotFoundException $erro) {
        return '404 | Página não encontrada';   // findOrFail não achou: o Laravel responde 404
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
      $vazio = $tipo === 'forelse' ? ' $__vazios[] = is_countable($__lista) ? count($__lista) === 0 : empty($__lista);' : '';
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

  // ===================== BANCO DE DADOS (novo na L4) =====================
  // Um banco SQLite de verdade, criado na memória a cada execução (some quando o script termina).

  class QueryException extends Exception {}
  class ModelNotFoundException extends Exception {}
  class MassAssignmentException extends Exception {}
  class MissingAttributeException extends Exception {}

  // o que o DB::listen recebe a cada consulta
  class ConsultaExecutada {
    public float $time = 0.0;
    public function __construct(public string $sql, public array $bindings = []) {}

    // o SQL com os valores no lugar dos ?
    public function toRawSql(): string {
      $b = $this->bindings;
      return preg_replace_callback('/\?/', function () use (&$b) {
        $v = array_shift($b);
        return match (true) {
          $v === null => 'null',
          is_bool($v) => $v ? '1' : '0',
          is_int($v), is_float($v) => (string) $v,
          default => "'" . str_replace("'", "''", (string) $v) . "'",
        };
      }, $this->sql);
    }
  }

  class Str {
    public static function snake(string $t): string {
      return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $t));
    }

    // o Laravel chuta o plural em inglês: curso → cursos, professor → professors (!)
    public static function plural(string $t): string {
      if (preg_match('/[^aeiou]y$/', $t)) return substr($t, 0, -1) . 'ies';
      if (preg_match('/(s|x|z|ch|sh)$/', $t)) return $t . 'es';
      return $t . 's';
    }
  }

  class DB {
    private static ?PDO $pdo = null;
    private static array $ouvintes = [];
    public static ?array $fingindo = null;   // migrate --pretend: guarda o SQL em vez de rodar

    public static function pdo(): PDO {
      if (!self::$pdo) {
        self::$pdo = new PDO('sqlite::memory:');
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo->exec('PRAGMA foreign_keys = ON');
      }
      return self::$pdo;
    }

    // roda um SQL com os valores separados (os ?): é assim que o Laravel evita SQL injection
    public static function executar(string $sql, array $bindings = []): ?PDOStatement {
      $bindings = array_values($bindings);
      $consulta = new ConsultaExecutada($sql, $bindings);
      if (self::$fingindo !== null) {
        self::$fingindo[] = $consulta->toRawSql();
        return null;
      }
      try {
        $st = self::pdo()->prepare($sql);
        foreach ($bindings as $i => $v) {
          $tipo = match (true) {
            $v === null => PDO::PARAM_NULL,
            is_bool($v), is_int($v) => PDO::PARAM_INT,
            default => PDO::PARAM_STR,
          };
          $st->bindValue($i + 1, is_bool($v) ? (int) $v : $v, $tipo);
        }
        $st->execute();
      } catch (PDOException $erro) {
        throw new QueryException($erro->getMessage() . ' (Connection: sqlite, SQL: ' . $consulta->toRawSql() . ')');
      }
      foreach (self::$ouvintes as $ouvinte) $ouvinte($consulta);
      return $st;
    }

    // DB::select devolve uma lista de objetos simples (stdClass)
    public static function select(string $sql, array $bindings = []): array {
      $st = self::executar($sql, $bindings);
      return $st ? array_map(fn ($l) => (object) $l, $st->fetchAll()) : [];
    }

    public static function insert(string $sql, array $bindings = []): bool {
      self::executar($sql, $bindings);
      return true;
    }

    public static function update(string $sql, array $bindings = []): int {
      return self::executar($sql, $bindings)?->rowCount() ?? 0;
    }

    public static function delete(string $sql, array $bindings = []): int {
      return self::update($sql, $bindings);
    }

    public static function statement(string $sql, array $bindings = []): bool {
      self::executar($sql, $bindings);
      return true;
    }

    public static function table(string $tabela): Builder {
      return new Builder($tabela);
    }

    public static function listen(Closure $ouvinte): void {
      self::$ouvintes[] = $ouvinte;
    }

    public static function lastInsertId(): int {
      return (int) self::pdo()->lastInsertId();
    }
  }

  function aspas(string $nome): string {
    if ($nome === '*') return '*';
    if (preg_match('/^(.+)\s+as\s+(.+)$/i', $nome, $m)) return aspas($m[1]) . ' as ' . aspas($m[2]);
    return implode('.', array_map(fn ($p) => $p === '*' ? '*' : '"' . $p . '"', explode('.', $nome)));
  }

  // ---------- Query Builder: monta o SQL peça por peça ----------
  class Builder {
    private array $wheres = [];      // [ligação, sql, valores]
    private array $orders = [];
    private ?int $limite = null;
    private ?int $pular = null;
    private array $colunas = ['*'];
    private array $com = [];         // with(): relacionamentos carregados de uma vez

    public function __construct(public string $tabela, public ?string $model = null) {}

    public function where(string|array|Closure $coluna, mixed $operador = null, mixed $valor = null, string $ligacao = 'and'): static {
      if (is_array($coluna)) {
        foreach ($coluna as $c => $v) $this->where($c, '=', $v, $ligacao);
        return $this;
      }
      if ($coluna instanceof Closure) {
        $grupo = new Builder($this->tabela);
        $coluna($grupo);
        [$sql, $valores] = $grupo->sqlDoWhere();
        if ($sql !== '') $this->wheres[] = [$ligacao, '(' . $sql . ')', $valores];
        return $this;
      }
      if (func_num_args() === 2 || ($valor === null && !in_array(strtolower((string) $operador), ['=', '!=', '<>', '>', '<', '>=', '<=', 'like', 'not like'], true))) {
        [$valor, $operador] = [$operador, '='];
      }
      if ($valor === null) return $operador === '=' ? $this->whereNull($coluna, $ligacao) : $this->whereNotNull($coluna, $ligacao);
      $this->wheres[] = [$ligacao, aspas($coluna) . ' ' . $operador . ' ?', [$valor]];
      return $this;
    }

    public function orWhere(string|array|Closure $coluna, mixed $operador = null, mixed $valor = null): static {
      if (func_num_args() === 2) return $this->where($coluna, '=', $operador, 'or');
      return $this->where($coluna, $operador, $valor, 'or');
    }

    public function whereIn(string $coluna, array|Collection $valores, string $ligacao = 'and', bool $not = false): static {
      $valores = $valores instanceof Collection ? $valores->all() : array_values($valores);
      if (!$valores) {
        $this->wheres[] = [$ligacao, $not ? '1 = 1' : '0 = 1', []];
        return $this;
      }
      $this->wheres[] = [$ligacao, aspas($coluna) . ($not ? ' not in (' : ' in (') . implode(', ', array_fill(0, count($valores), '?')) . ')', $valores];
      return $this;
    }

    public function whereNotIn(string $coluna, array|Collection $valores): static {
      return $this->whereIn($coluna, $valores, 'and', true);
    }

    public function whereNull(string $coluna, string $ligacao = 'and'): static {
      $this->wheres[] = [$ligacao, aspas($coluna) . ' is null', []];
      return $this;
    }

    public function whereNotNull(string $coluna, string $ligacao = 'and'): static {
      $this->wheres[] = [$ligacao, aspas($coluna) . ' is not null', []];
      return $this;
    }

    public function whereBetween(string $coluna, array $entre): static {
      $this->wheres[] = ['and', aspas($coluna) . ' between ? and ?', array_values($entre)];
      return $this;
    }

    public function orderBy(string $coluna, string $direcao = 'asc'): static {
      $this->orders[] = aspas($coluna) . ' ' . (strtolower($direcao) === 'desc' ? 'desc' : 'asc');
      return $this;
    }

    public function orderByDesc(string $coluna): static { return $this->orderBy($coluna, 'desc'); }
    public function latest(string $coluna = 'created_at'): static { return $this->orderBy($coluna, 'desc'); }
    public function oldest(string $coluna = 'created_at'): static { return $this->orderBy($coluna, 'asc'); }
    public function limit(int $n): static { $this->limite = $n; return $this; }
    public function take(int $n): static { return $this->limit($n); }
    public function offset(int $n): static { $this->pular = $n; return $this; }
    public function skip(int $n): static { return $this->offset($n); }

    public function select(string|array ...$colunas): static {
      $this->colunas = is_array($colunas[0] ?? null) ? $colunas[0] : $colunas;
      return $this;
    }

    public function with(string|array ...$relacoes): static {
      $this->com = array_merge($this->com, is_array($relacoes[0] ?? null) ? $relacoes[0] : $relacoes);
      return $this;
    }

    private function sqlDoWhere(): array {
      $sql = '';
      $valores = [];
      foreach ($this->wheres as $i => [$ligacao, $pedaco, $vals]) {
        $sql .= ($i ? " $ligacao " : '') . $pedaco;
        array_push($valores, ...$vals);
      }
      return [$sql, $valores];
    }

    private function montar(?array $colunas = null): array {
      [$where, $valores] = $this->sqlDoWhere();
      $sql = 'select ' . implode(', ', array_map('aspas', $colunas ?? $this->colunas)) . ' from ' . aspas($this->tabela);
      if ($where !== '') $sql .= ' where ' . $where;
      if ($this->orders) $sql .= ' order by ' . implode(', ', $this->orders);
      if ($this->limite !== null) $sql .= ' limit ' . $this->limite;
      if ($this->pular !== null) $sql .= ($this->limite === null ? ' limit -1' : '') . ' offset ' . $this->pular;
      return [$sql, $valores];
    }

    public function toSql(): string {
      return $this->montar()[0];
    }

    public function toRawSql(): string {
      return (new ConsultaExecutada(...$this->montar()))->toRawSql();
    }

    public function get(array|string $colunas = ['*']): Collection {
      if ($colunas !== ['*']) $this->select(...(array) $colunas);
      $linhas = DB::executar(...$this->montar())?->fetchAll() ?? [];
      if (!$this->model) return new Collection(array_map(fn ($l) => (object) $l, $linhas));
      $modelos = array_map(fn ($l) => (new $this->model())->hidratar($l), $linhas);
      foreach ($this->com as $relacao) Model::carregarJuntos($modelos, $relacao);
      return new Collection($modelos);
    }

    public function first(): mixed {
      return (clone $this)->limit(1)->get()->first();
    }

    public function firstOrFail(): mixed {
      return $this->first() ?? throw new ModelNotFoundException('No query results for model [' . $this->model . '].');
    }

    public function find(mixed $id): mixed {
      return (clone $this)->where('id', '=', $id)->first();
    }

    public function findOrFail(mixed $id): mixed {
      return $this->find($id) ?? throw new ModelNotFoundException('No query results for model [' . $this->model . '] ' . $id);
    }

    public function firstWhere(string $coluna, mixed $operador = null, mixed $valor = null): mixed {
      return (clone $this)->where(...func_get_args())->first();
    }

    public function value(string $coluna): mixed {
      $linha = DB::executar(...(clone $this)->limit(1)->montar([$coluna]))?->fetch();
      return $linha ? $linha[$coluna] : null;
    }

    public function pluck(string $coluna, ?string $chave = null): Collection {
      $linhas = DB::executar(...$this->montar($chave ? [$coluna, $chave] : [$coluna]))?->fetchAll() ?? [];
      $itens = [];
      foreach ($linhas as $l) {
        if ($chave) $itens[$l[$chave]] = $l[$coluna];
        else $itens[] = $l[$coluna];
      }
      return new Collection($itens);
    }

    private function agregado(string $funcao, string $coluna): mixed {
      [$where, $valores] = $this->sqlDoWhere();
      $sql = "select $funcao(" . aspas($coluna) . ') as aggregate from ' . aspas($this->tabela) . ($where !== '' ? ' where ' . $where : '');
      return DB::executar($sql, $valores)?->fetch()['aggregate'] ?? null;
    }

    public function count(string $coluna = '*'): int { return (int) $this->agregado('count', $coluna); }
    public function sum(string $coluna): int|float { return $this->agregado('sum', $coluna) ?? 0; }
    public function avg(string $coluna): mixed { return $this->agregado('avg', $coluna); }
    public function max(string $coluna): mixed { return $this->agregado('max', $coluna); }
    public function min(string $coluna): mixed { return $this->agregado('min', $coluna); }
    public function exists(): bool { return $this->count() > 0; }
    public function doesntExist(): bool { return !$this->exists(); }

    public function insert(array $linhas): bool {
      if (!array_is_list($linhas)) $linhas = [$linhas];
      $colunas = array_keys($linhas[0]);
      $grupo = '(' . implode(', ', array_fill(0, count($colunas), '?')) . ')';
      $sql = 'insert into ' . aspas($this->tabela) . ' (' . implode(', ', array_map('aspas', $colunas)) . ') values '
        . implode(', ', array_fill(0, count($linhas), $grupo));
      return DB::insert($sql, array_merge(...array_map('array_values', $linhas)));
    }

    public function insertGetId(array $valores): int {
      $this->insert($valores);
      return DB::lastInsertId();
    }

    public function update(array $valores): int {
      if ($this->model && (new $this->model())->timestamps) $valores['updated_at'] ??= agora();
      [$where, $vals] = $this->sqlDoWhere();
      $sets = implode(', ', array_map(fn ($c) => aspas($c) . ' = ?', array_keys($valores)));
      $sql = 'update ' . aspas($this->tabela) . ' set ' . $sets . ($where !== '' ? ' where ' . $where : '');
      return DB::update($sql, array_merge(array_values($valores), $vals));
    }

    public function increment(string $coluna, int|float $n = 1): int {
      [$where, $vals] = $this->sqlDoWhere();
      $sql = 'update ' . aspas($this->tabela) . ' set ' . aspas($coluna) . ' = ' . aspas($coluna) . ' + ?' . ($where !== '' ? ' where ' . $where : '');
      return DB::update($sql, array_merge([$n], $vals));
    }

    public function decrement(string $coluna, int|float $n = 1): int {
      return $this->increment($coluna, -$n);
    }

    public function delete(): int {
      [$where, $vals] = $this->sqlDoWhere();
      return DB::delete('delete from ' . aspas($this->tabela) . ($where !== '' ? ' where ' . $where : ''), $vals);
    }

    // Curso::where(...)->create([...]) e $curso->aulas()->create([...]) usam o model
    public function create(array $atributos): Model {
      $modelo = new $this->model($atributos);
      foreach ($this->wheres as [$ligacao, $pedaco, $vals]) {
        if (preg_match('/^"(\w+)" = \?$/', $pedaco, $m)) $modelo->{$m[1]} = $vals[0];
      }
      $modelo->save();
      return $modelo;
    }
  }

  // quantas letras (o PHP do navegador não tem mb_strlen)
  function letras(string $texto): int {
    return preg_match_all('/./us', $texto);
  }

  function agora(): string {
    return date('Y-m-d H:i:s');
  }

  // ---------- Collection: a lista que o get() e o all() devolvem ----------
  class Collection implements ArrayAccess, IteratorAggregate, Countable, JsonSerializable {
    public function __construct(protected array $items = []) {}

    public function all(): array { return $this->items; }
    public function count(): int { return count($this->items); }
    public function getIterator(): ArrayIterator { return new ArrayIterator($this->items); }
    public function offsetExists(mixed $k): bool { return isset($this->items[$k]); }
    public function offsetGet(mixed $k): mixed { return $this->items[$k]; }
    public function offsetSet(mixed $k, mixed $v): void { if ($k === null) $this->items[] = $v; else $this->items[$k] = $v; }
    public function offsetUnset(mixed $k): void { unset($this->items[$k]); }
    public function isEmpty(): bool { return !$this->items; }
    public function isNotEmpty(): bool { return (bool) $this->items; }
    public function keys(): static { return new static(array_keys($this->items)); }
    public function values(): static { return new static(array_values($this->items)); }

    public function first(?callable $teste = null): mixed {
      foreach ($this->items as $i) if (!$teste || $teste($i)) return $i;
      return null;
    }

    public function last(): mixed {
      return $this->items ? end($this->items) : null;
    }

    private static function pegar(mixed $item, ?string $campo): mixed {
      if ($campo === null) return $item;
      if (is_array($item)) return $item[$campo] ?? null;
      return $item->$campo ?? null;
    }

    public function pluck(string $campo, ?string $chave = null): static {
      $r = [];
      foreach ($this->items as $i) {
        if ($chave) $r[self::pegar($i, $chave)] = self::pegar($i, $campo);
        else $r[] = self::pegar($i, $campo);
      }
      return new static($r);
    }

    public function map(callable $f): static { return new static(array_map($f, $this->items)); }
    public function filter(?callable $f = null): static { return new static(array_values(array_filter($this->items, $f))); }
    public function each(callable $f): static { foreach ($this->items as $k => $i) if ($f($i, $k) === false) break; return $this; }
    public function take(int $n): static { return new static(array_slice($this->items, 0, $n)); }

    public function where(string $campo, mixed $operador, mixed $valor = null): static {
      if (func_num_args() === 2) [$valor, $operador] = [$operador, '='];
      return $this->filter(function ($i) use ($campo, $operador, $valor) {
        $v = self::pegar($i, $campo);
        return match ($operador) { '>' => $v > $valor, '<' => $v < $valor, '>=' => $v >= $valor, '<=' => $v <= $valor, '!=', '<>' => $v != $valor, default => $v == $valor };
      });
    }

    public function sum(?string $campo = null): int|float { return array_sum(array_map(fn ($i) => self::pegar($i, $campo), $this->items)); }
    public function avg(?string $campo = null): int|float|null { return $this->items ? $this->sum($campo) / count($this->items) : null; }
    public function max(?string $campo = null): mixed { return $this->items ? max(array_map(fn ($i) => self::pegar($i, $campo), $this->items)) : null; }
    public function min(?string $campo = null): mixed { return $this->items ? min(array_map(fn ($i) => self::pegar($i, $campo), $this->items)) : null; }

    public function contains(mixed $campo, mixed $valor = null): bool {
      if (func_num_args() === 1) return in_array($campo, $this->items);
      return $this->where($campo, $valor)->isNotEmpty();
    }

    public function sortBy(string|callable $campo, bool $desc = false): static {
      $itens = $this->items;
      usort($itens, function ($a, $b) use ($campo, $desc) {
        [$x, $y] = is_callable($campo) ? [$campo($a), $campo($b)] : [self::pegar($a, $campo), self::pegar($b, $campo)];
        return $desc ? $y <=> $x : $x <=> $y;
      });
      return new static($itens);
    }

    public function sortByDesc(string|callable $campo): static { return $this->sortBy($campo, true); }

    public function groupBy(string $campo): static {
      $g = [];
      foreach ($this->items as $i) $g[self::pegar($i, $campo)][] = $i;
      return new static(array_map(fn ($l) => new static($l), $g));
    }

    public function implode(string $campo, ?string $cola = null): string {
      if ($cola === null) return implode($campo, $this->items);
      return implode($cola, $this->pluck($campo)->items);
    }

    public function toArray(): array {
      return array_map(fn ($i) => $i instanceof Model || $i instanceof Collection ? $i->toArray() : (is_object($i) ? (array) $i : $i), $this->items);
    }

    public function jsonSerialize(): array { return $this->toArray(); }
    public function toJson(): string { return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
    public function __toString(): string { return $this->toJson(); }
    public function __pgDump(): array { return ['Collection', $this->items, true]; }
  }

  function collect(array $itens = []): Collection {
    return new Collection($itens);
  }

  // ---------- Relacionamentos: hasMany, hasOne e belongsTo ----------
  class Relacao {
    public function __construct(
      public string $tipo,        // hasMany, hasOne ou belongsTo
      public Builder $query,
      public string $relacionado,
      public string $chaveEstrangeira,
      public string $chaveLocal,
    ) {}

    public function resultados(): mixed {
      return $this->tipo === 'hasMany' ? $this->query->get() : $this->query->first();
    }

    // $curso->aulas()->orderBy(...)->get(), $curso->aulas()->count(), $curso->aulas()->create([...])
    public function __call(string $metodo, array $args): mixed {
      return $this->query->$metodo(...$args);
    }
  }

  // ---------- Eloquent: cada Model é uma tabela, cada objeto é uma linha ----------
  abstract class Model implements JsonSerializable {
    protected $table;                // padrão: o nome da classe no plural, em inglês
    protected $primaryKey = 'id';
    public $timestamps = true;       // preenche created_at e updated_at sozinho
    protected $fillable = [];        // colunas liberadas para create() e update()
    protected $guarded = ['*'];
    protected $casts = [];           // ex.: ['ativo' => 'boolean']
    protected $hidden = [];          // colunas que não aparecem no toArray/JSON
    protected $attributes = [];      // os valores da linha
    protected $original = [];        // como a linha estava no banco (para saber o que mudou)
    protected $relations = [];
    public $exists = false;          // true quando o objeto já está salvo no banco
    public $wasRecentlyCreated = false;

    public function __construct(array $atributos = []) {
      $this->fill($atributos);
    }

    public function getTable(): string {
      return $this->table ?? Str::plural(Str::snake((new ReflectionClass($this))->getShortName()));
    }

    public function getKey(): mixed {
      return $this->attributes[$this->primaryKey] ?? null;
    }

    public function newQuery(): Builder {
      return new Builder($this->getTable(), static::class);
    }

    public static function query(): Builder {
      return (new static())->newQuery();
    }

    public static function all(array|string $colunas = ['*']): Collection {
      return static::query()->get($colunas);
    }

    public static function create(array $atributos): static {
      $modelo = new static($atributos);
      $modelo->save();
      return $modelo;
    }

    public static function destroy(mixed ...$ids): int {
      $ids = is_array($ids[0] ?? null) ? $ids[0] : $ids;
      $apagados = 0;
      foreach (static::query()->whereIn('id', $ids)->get() as $m) $apagados += (int) $m->delete();
      return $apagados;
    }

    // Curso::where(...), Curso::find(1), Curso::orderBy(...) etc. viram uma consulta
    public static function __callStatic(string $metodo, array $args): mixed {
      return static::query()->$metodo(...$args);
    }

    public function __call(string $metodo, array $args): mixed {
      return $this->newQuery()->$metodo(...$args);
    }

    // ----- mass assignment -----
    public function isFillable(string $chave): bool {
      if (in_array($chave, $this->fillable, true)) return true;
      if ($this->fillable) return false;            // tem $fillable e a coluna não está lá: é ignorada
      if ($this->guarded === ['*']) return false;
      return !in_array($chave, $this->guarded, true);
    }

    public function fill(array $atributos): static {
      $tudoBloqueado = !$this->fillable && $this->guarded === ['*'];
      foreach ($atributos as $chave => $valor) {
        if ($this->isFillable($chave)) {
          $this->attributes[$chave] = $valor;
        } elseif ($tudoBloqueado) {
          throw new MassAssignmentException("Add [$chave] to fillable property to allow mass assignment on [" . static::class . '].');
        }
      }
      return $this;
    }

    public function hidratar(array $linha): static {
      $this->attributes = $this->original = $linha;
      $this->exists = true;
      return $this;
    }

    // ----- salvar, atualizar e apagar -----
    public function save(): bool {
      $query = DB::table($this->getTable());
      if ($this->exists) {
        $mudou = $this->getDirty();
        if (!$mudou) return true;                    // nada mudou: nem vai ao banco
        if ($this->timestamps) $this->attributes['updated_at'] = $mudou['updated_at'] = agora();
        $query->where($this->primaryKey, $this->getKey())->update($mudou);
        $this->original = $this->attributes;
        return true;
      }
      if ($this->timestamps) {
        $this->attributes['created_at'] ??= agora();
        $this->attributes['updated_at'] ??= agora();
      }
      $dados = array_map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v, $this->attributes);
      if ($dados) $query->insert($dados);
      else DB::insert('insert into ' . aspas($this->getTable()) . ' default values');
      $this->attributes = [$this->primaryKey => DB::lastInsertId()] + $this->attributes;
      $this->original = $this->attributes;
      $this->exists = true;
      $this->wasRecentlyCreated = true;
      return true;
    }

    // as colunas que mudaram desde que a linha veio do banco
    public function getDirty(): array {
      $mudou = [];
      foreach ($this->attributes as $k => $v) {
        if (!array_key_exists($k, $this->original) || $this->original[$k] !== $v) $mudou[$k] = $v;
      }
      return $mudou;
    }

    public function isDirty(?string $coluna = null): bool {
      return $coluna === null ? (bool) $this->getDirty() : array_key_exists($coluna, $this->getDirty());
    }

    public function update(array $atributos = []): bool {
      if (!$this->exists) return false;
      return $this->fill($atributos)->save();
    }

    public function delete(): bool {
      if (!$this->exists) return false;
      DB::table($this->getTable())->where($this->primaryKey, $this->getKey())->delete();
      $this->exists = false;
      return true;
    }

    public function fresh(): ?static {
      return $this->exists ? static::query()->find($this->getKey()) : null;
    }

    public function refresh(): static {
      $this->attributes = $this->original = $this->fresh()->attributes;
      $this->relations = [];
      return $this;
    }

    // ----- relacionamentos -----
    protected function hasMany(string $relacionado, ?string $fk = null, ?string $local = null): Relacao {
      $fk ??= Str::snake((new ReflectionClass($this))->getShortName()) . '_id';
      $local ??= $this->primaryKey;
      $query = (new $relacionado())->newQuery()->where($fk, '=', $this->attributes[$local] ?? null);
      return new Relacao('hasMany', $query, $relacionado, $fk, $local);
    }

    protected function hasOne(string $relacionado, ?string $fk = null, ?string $local = null): Relacao {
      $r = $this->hasMany($relacionado, $fk, $local);
      $r->tipo = 'hasOne';
      return $r;
    }

    protected function belongsTo(string $relacionado, ?string $fk = null, string $dono = 'id'): Relacao {
      $fk ??= Str::snake((new ReflectionClass($relacionado))->getShortName()) . '_id';
      $query = (new $relacionado())->newQuery()->where($dono, '=', $this->attributes[$fk] ?? null);
      return new Relacao('belongsTo', $query, $relacionado, $fk, $dono);
    }

    // with('aulas'): uma consulta só para os relacionamentos de todos os models (evita o N+1)
    public static function carregarJuntos(array $modelos, string $nome): void {
      if (!$modelos) return;
      $r = $modelos[0]->$nome();
      $chaveDeCada = $r->tipo === 'belongsTo' ? $r->chaveEstrangeira : $r->chaveLocal;
      $colunaNoOutro = $r->tipo === 'belongsTo' ? $r->chaveLocal : $r->chaveEstrangeira;
      $ids = array_values(array_unique(array_filter(array_map(fn ($m) => $m->attributes[$chaveDeCada] ?? null, $modelos), fn ($v) => $v !== null)));
      $achados = (new $r->relacionado())->newQuery()->whereIn($colunaNoOutro, $ids)->get();
      foreach ($modelos as $m) {
        $meus = $achados->filter(fn ($a) => $a->attributes[$colunaNoOutro] == ($m->attributes[$chaveDeCada] ?? null));
        $m->relations[$nome] = $r->tipo === 'hasMany' ? $meus : $meus->first();
      }
    }

    public function load(string ...$relacoes): static {
      foreach ($relacoes as $nome) self::carregarJuntos([$this], $nome);
      return $this;
    }

    // ----- ler e escrever colunas como propriedades: $curso->nome -----
    private function converter(string $chave, mixed $valor): mixed {
      if ($valor === null) return null;
      $tipo = $this->casts[$chave] ?? null;
      return match (true) {
        $tipo === null => $valor,
        in_array($tipo, ['int', 'integer']) => (int) $valor,
        in_array($tipo, ['float', 'double', 'real']) => (float) $valor,
        in_array($tipo, ['bool', 'boolean']) => (bool) $valor,
        $tipo === 'string' => (string) $valor,
        in_array($tipo, ['array', 'json']) => is_array($valor) ? $valor : json_decode($valor, true),
        str_starts_with($tipo, 'decimal:') => number_format((float) $valor, (int) substr($tipo, 8), '.', ''),
        default => $valor,
      };
    }

    public function __get(string $chave): mixed {
      if (array_key_exists($chave, $this->attributes)) return $this->converter($chave, $this->attributes[$chave]);
      if (array_key_exists($chave, $this->relations)) return $this->relations[$chave];
      if (method_exists($this, $chave)) {
        $r = $this->$chave();
        if ($r instanceof Relacao) return $this->relations[$chave] = $r->resultados();
      }
      if ($this->exists && !$this->wasRecentlyCreated) {
        throw new MissingAttributeException("The attribute [$chave] either does not exist or was not retrieved for model [" . static::class . '].');
      }
      return null;
    }

    public function __set(string $chave, mixed $valor): void {
      $this->attributes[$chave] = $valor;
    }

    public function __isset(string $chave): bool {
      return $this->__get($chave) !== null;
    }

    public function __unset(string $chave): void {
      unset($this->attributes[$chave]);
    }

    public function toArray(): array {
      $dados = [];
      foreach ($this->attributes as $k => $v) {
        if (!in_array($k, $this->hidden, true)) $dados[$k] = $this->converter($k, $v);
      }
      foreach ($this->relations as $k => $r) $dados[$k] = $r?->toArray();
      return $dados;
    }

    public function jsonSerialize(): array { return $this->toArray(); }
    public function toJson(): string { return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
    public function __toString(): string { return $this->toJson(); }
    public function __pgDump(): array { return [static::class, $this->toArray(), false]; }
  }

  // ===================== MIGRATIONS (database/migrations) =====================

  class ColunaDefinida {
    public bool $nula = false;
    public bool $temPadrao = false;
    public mixed $padrao = null;
    public bool $unica = false;
    public ?array $estrangeira = null;   // [tabela, coluna, ao apagar]

    public function __construct(public string $nome, public string $tipo, public string $sqlTipo, private ?Blueprint $tabela = null) {}

    public function nullable(bool $sim = true): static { $this->nula = $sim; return $this; }
    public function default(mixed $valor): static { $this->temPadrao = true; $this->padrao = $valor; return $this; }
    public function unique(): static { $this->unica = true; return $this; }
    public function unsigned(): static { return $this; }
    public function after(string $coluna): static { return $this; }       // no SQLite a coluna nova vai para o fim
    public function comment(string $texto): static { return $this; }
    public function index(): static { return $this; }

    // foreignId('curso_id')->constrained(): aponta para cursos.id
    public function constrained(?string $tabela = null, string $coluna = 'id'): static {
      $this->estrangeira = [$tabela ?? Str::plural(preg_replace('/_id$/', '', $this->nome)), $coluna, null];
      return $this;
    }

    public function references(string $coluna): static { $this->estrangeira[1] = $coluna; return $this; }
    public function on(string $tabela): static { $this->estrangeira[0] = $tabela; return $this; }
    public function cascadeOnDelete(): static { $this->estrangeira[2] = 'cascade'; return $this; }
    public function nullOnDelete(): static { $this->estrangeira[2] = 'set null'; return $this; }
    public function restrictOnDelete(): static { $this->estrangeira[2] = 'restrict'; return $this; }
    public function onDelete(string $acao): static { $this->estrangeira[2] = $acao; return $this; }

    public function sql(): string {
      if ($this->tipo === 'id') return aspas($this->nome) . ' integer primary key autoincrement not null';
      $sql = aspas($this->nome) . ' ' . $this->sqlTipo . ($this->nula ? '' : ' not null');
      if ($this->temPadrao) {
        $p = $this->padrao;
        $sql .= ' default ' . ($p === null ? 'null' : "'" . str_replace("'", "''", (string) (is_bool($p) ? (int) $p : $p)) . "'");
      }
      return $sql;
    }
  }

  class Blueprint {
    public array $colunas = [];
    public array $depois = [];      // comandos que vêm depois (índices únicos, drop/rename de coluna)

    public function __construct(public string $tabela, public bool $criando) {}

    private function coluna(string $nome, string $tipo, string $sqlTipo): ColunaDefinida {
      return $this->colunas[] = new ColunaDefinida($nome, $tipo, $sqlTipo, $this);
    }

    public function id(string $nome = 'id'): ColunaDefinida { return $this->coluna($nome, 'id', 'integer'); }
    public function increments(string $nome): ColunaDefinida { return $this->id($nome); }
    public function string(string $nome, int $tamanho = 255): ColunaDefinida { return $this->coluna($nome, 'string', 'varchar'); }
    public function char(string $nome, int $tamanho = 255): ColunaDefinida { return $this->coluna($nome, 'char', 'varchar'); }
    public function text(string $nome): ColunaDefinida { return $this->coluna($nome, 'text', 'text'); }
    public function longText(string $nome): ColunaDefinida { return $this->coluna($nome, 'text', 'text'); }
    public function integer(string $nome): ColunaDefinida { return $this->coluna($nome, 'integer', 'integer'); }
    public function tinyInteger(string $nome): ColunaDefinida { return $this->coluna($nome, 'integer', 'integer'); }
    public function smallInteger(string $nome): ColunaDefinida { return $this->coluna($nome, 'integer', 'integer'); }
    public function bigInteger(string $nome): ColunaDefinida { return $this->coluna($nome, 'integer', 'integer'); }
    public function unsignedInteger(string $nome): ColunaDefinida { return $this->coluna($nome, 'integer', 'integer'); }
    public function unsignedBigInteger(string $nome): ColunaDefinida { return $this->coluna($nome, 'integer', 'integer'); }
    public function decimal(string $nome, int $total = 8, int $casas = 2): ColunaDefinida { return $this->coluna($nome, 'decimal', 'numeric'); }
    public function float(string $nome): ColunaDefinida { return $this->coluna($nome, 'float', 'float'); }
    public function double(string $nome): ColunaDefinida { return $this->coluna($nome, 'double', 'double'); }
    public function boolean(string $nome): ColunaDefinida { return $this->coluna($nome, 'boolean', 'tinyint(1)'); }
    public function date(string $nome): ColunaDefinida { return $this->coluna($nome, 'date', 'date'); }
    public function dateTime(string $nome): ColunaDefinida { return $this->coluna($nome, 'datetime', 'datetime'); }
    public function time(string $nome): ColunaDefinida { return $this->coluna($nome, 'time', 'time'); }
    public function timestamp(string $nome): ColunaDefinida { return $this->coluna($nome, 'datetime', 'datetime'); }
    public function json(string $nome): ColunaDefinida { return $this->coluna($nome, 'json', 'text'); }

    public function enum(string $nome, array $opcoes): ColunaDefinida {
      $lista = implode(', ', array_map(fn ($o) => "'" . $o . "'", $opcoes));
      return $this->coluna($nome, 'enum', 'varchar check (' . aspas($nome) . " in ($lista))");
    }

    public function foreignId(string $nome): ColunaDefinida { return $this->coluna($nome, 'foreignId', 'integer'); }

    public function timestamps(): void {
      $this->timestamp('created_at')->nullable();
      $this->timestamp('updated_at')->nullable();
    }

    public function unique(string|array $colunas): void {
      $colunas = (array) $colunas;
      $nome = $this->tabela . '_' . implode('_', $colunas) . '_unique';
      $this->depois[] = 'create unique index ' . aspas($nome) . ' on ' . aspas($this->tabela) . ' (' . implode(', ', array_map('aspas', $colunas)) . ')';
    }

    public function dropColumn(string|array ...$colunas): void {
      foreach (is_array($colunas[0]) ? $colunas[0] : $colunas as $c) {
        $this->depois[] = 'alter table ' . aspas($this->tabela) . ' drop column ' . aspas($c);
      }
    }

    public function renameColumn(string $de, string $para): void {
      $this->depois[] = 'alter table ' . aspas($this->tabela) . ' rename column ' . aspas($de) . ' to ' . aspas($para);
    }

    public function dropTimestamps(): void { $this->dropColumn('created_at', 'updated_at'); }

    // o SQL que o Laravel manda para o banco
    public function comandos(): array {
      $sqls = [];
      $estrangeiras = [];
      foreach ($this->colunas as $c) {
        if ($c->estrangeira) {
          [$t, $col, $acao] = $c->estrangeira;
          $estrangeiras[] = 'foreign key(' . aspas($c->nome) . ') references ' . aspas($t) . '(' . aspas($col) . ')' . ($acao ? " on delete $acao" : '');
        }
      }
      if ($this->criando) {
        $partes = array_merge(array_map(fn ($c) => $c->sql(), $this->colunas), $estrangeiras);
        $sqls[] = 'create table ' . aspas($this->tabela) . ' (' . implode(', ', $partes) . ')';
      } else {
        foreach ($this->colunas as $c) {
          $sql = 'alter table ' . aspas($this->tabela) . ' add column ' . $c->sql();
          if ($c->estrangeira) {
            [$t, $col, $acao] = $c->estrangeira;
            $sql .= ' references ' . aspas($t) . '(' . aspas($col) . ')' . ($acao ? " on delete $acao" : '');
          }
          $sqls[] = $sql;
        }
      }
      foreach ($this->colunas as $c) {
        if ($c->unica) {
          $sqls[] = 'create unique index ' . aspas($this->tabela . '_' . $c->nome . '_unique') . ' on ' . aspas($this->tabela) . ' (' . aspas($c->nome) . ')';
        }
      }
      return array_merge($sqls, $this->depois);
    }
  }

  class Schema {
    public static function create(string $tabela, Closure $definicao): void {
      $b = new Blueprint($tabela, true);
      $definicao($b);
      foreach ($b->comandos() as $sql) DB::statement($sql);
    }

    public static function table(string $tabela, Closure $definicao): void {
      $b = new Blueprint($tabela, false);
      $definicao($b);
      foreach ($b->comandos() as $sql) DB::statement($sql);
    }

    public static function drop(string $tabela): void {
      DB::statement('drop table ' . aspas($tabela));
    }

    public static function dropIfExists(string $tabela): void {
      DB::statement('drop table if exists ' . aspas($tabela));
    }

    public static function rename(string $de, string $para): void {
      DB::statement('alter table ' . aspas($de) . ' rename to ' . aspas($para));
    }

    public static function hasTable(string $tabela): bool {
      return (bool) DB::pdo()->query("select count(*) from sqlite_master where type = 'table' and name = " . DB::pdo()->quote($tabela))->fetchColumn();
    }

    public static function getColumnListing(string $tabela): array {
      return array_column(DB::pdo()->query('pragma table_info(' . aspas($tabela) . ')')->fetchAll(), 'name');
    }

    public static function hasColumn(string $tabela, string $coluna): bool {
      return in_array($coluna, self::getColumnListing($tabela), true);
    }

    public static function getTables(): array {
      return DB::pdo()->query("select name from sqlite_master where type = 'table' and name not like 'sqlite_%' order by name")->fetchAll(PDO::FETCH_COLUMN);
    }
  }

  abstract class Migration {
    abstract public function up(): void;
    public function down(): void {}
  }

  class Migrator {
    public static array $arquivos = [];   // nome do arquivo => objeto da migration

    public static function linha(string $esquerda, string $direita): string {
      $pontos = max(3, 76 - letras($esquerda) - letras($direita));
      return '  ' . $esquerda . ' ' . str_repeat('.', $pontos) . ' ' . $direita;
    }

    private static function tabelaDeControle(): void {
      if (Schema::hasTable('migrations')) return;
      echo "   INFO  Preparing database.\n";
      DB::statement('create table "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null)');
      echo self::linha('Creating migration table', 'DONE'), "\n";
    }

    // a tabela migrations guarda quais arquivos já rodaram (e em qual "lote")
    private static function jaRodaram(): array {
      if (!Schema::hasTable('migrations')) return [];
      return DB::pdo()->query('select migration, batch from migrations order by id')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function migrar(bool $fingir = false): void {
      if (!$fingir) self::tabelaDeControle();
      $feitas = self::jaRodaram();
      $pendentes = array_diff(array_keys(self::$arquivos), array_keys($feitas));
      sort($pendentes);
      if (!$pendentes) {
        echo "   INFO  Nothing to migrate.\n";
        return;
      }
      echo "   INFO  Running migrations.\n";
      $lote = ($feitas ? max($feitas) : 0) + 1;
      foreach ($pendentes as $nome) {
        if ($fingir) {
          DB::$fingindo = [];
          try { self::$arquivos[$nome]->up(); } finally { $sqls = DB::$fingindo; DB::$fingindo = null; }
          echo self::linha($nome, ''), "\n";
          foreach ($sqls as $sql) echo "  ⇂ $sql\n";
          continue;
        }
        self::$arquivos[$nome]->up();
        DB::insert('insert into "migrations" ("migration", "batch") values (?, ?)', [$nome, $lote]);
        echo self::linha($nome, 'DONE'), "\n";
      }
    }

    public static function voltar(bool $tudo = false): void {
      $feitas = self::jaRodaram();
      if (!$feitas) {
        echo "   INFO  Nothing to rollback.\n";
        return;
      }
      $ultimo = max($feitas);
      $voltar = array_keys(array_filter($feitas, fn ($b) => $tudo || $b === $ultimo));
      rsort($voltar);
      echo "   INFO  Rolling back migrations.\n";
      foreach ($voltar as $nome) {
        if (isset(self::$arquivos[$nome])) self::$arquivos[$nome]->down();
        DB::delete('delete from "migrations" where "migration" = ?', [$nome]);
        echo self::linha($nome, 'DONE'), "\n";
      }
    }

    public static function status(): void {
      $feitas = self::jaRodaram();
      $todas = array_unique(array_merge(array_keys(self::$arquivos), array_keys($feitas)));
      sort($todas);
      if (!$todas) {
        echo "   ERROR  No migrations found.\n";
        return;
      }
      echo self::linha('Migration name', 'Batch / Status'), "\n";
      foreach ($todas as $nome) {
        echo self::linha($nome, isset($feitas[$nome]) ? '[' . $feitas[$nome] . '] Ran' : 'Pending'), "\n";
      }
    }

    public static function apagarTudo(): void {
      DB::statement('PRAGMA foreign_keys = OFF');
      foreach (Schema::getTables() as $t) DB::statement('drop table ' . aspas($t));
      DB::statement('PRAGMA foreign_keys = ON');
      echo self::linha('Dropping all tables', 'DONE'), "\n";
    }
  }

  function criar_migration(string $nome, Migration $migration): void {
    Migrator::$arquivos[$nome] = $migration;
  }

  // ===================== SEEDERS (database/seeders) =====================

  abstract class Seeder {
    abstract public function run(): void;

    public function call(string|array $classes): void {
      foreach ((array) $classes as $classe) {
        echo Migrator::linha($classe, 'RUNNING'), "\n";
        (new $classe())->run();
        echo Migrator::linha($classe, 'DONE'), "\n";
      }
    }
  }

  function semear(string $classe = 'DatabaseSeeder'): void {
    if (!class_exists($classe)) throw new Exception("A classe $classe não existe (crie o seeder antes de rodar o db:seed)");
    echo "   INFO  Seeding database.\n";
    (new $classe())->run();
  }

  // ===================== ARTISAN (o terminal) =====================
  // artisan("migrate") faz o papel de digitar php artisan migrate no terminal

  function artisan(string $comando): void {
    $partes = preg_split('/\s+/', trim($comando));
    $nome = array_shift($partes);
    $opcoes = [];
    $args = [];
    foreach ($partes as $p) {
      if (preg_match('/^--([\w-]+)(?:=(.*))?$/', $p, $m)) $opcoes[$m[1]] = $m[2] ?? true;
      elseif (preg_match('/^-(\w+)$/', $p, $m)) foreach (str_split($m[1]) as $l) $opcoes[$l] = true;
      else $args[] = $p;
    }
    $seed = fn () => isset($opcoes['seed']) ? semear() : null;
    $arquivo = fn (string $tipo, string $caminho) => print("   INFO  $tipo [$caminho] created successfully.\n");
    match ($nome) {
      'migrate' => [Migrator::migrar(isset($opcoes['pretend'])), $seed()],
      'migrate:rollback' => Migrator::voltar(),
      'migrate:reset' => Migrator::voltar(true),
      'migrate:refresh' => [Migrator::voltar(true), Migrator::migrar(), $seed()],
      'migrate:fresh' => [Migrator::apagarTudo(), Migrator::migrar(), $seed()],
      'migrate:status' => Migrator::status(),
      'db:seed' => semear($opcoes['class'] ?? 'DatabaseSeeder'),
      'make:migration' => $arquivo('Migration', 'database/migrations/' . date('Y_m_d_His') . '_' . ($args[0] ?? 'nova') . '.php'),
      'make:model' => [
        $arquivo('Model', 'app/Models/' . ($args[0] ?? 'Model') . '.php'),
        isset($opcoes['m']) || isset($opcoes['migration'])
          ? $arquivo('Migration', 'database/migrations/' . date('Y_m_d_His') . '_create_' . Str::plural(Str::snake($args[0] ?? 'model')) . '_table.php') : null,
      ],
      'make:seeder' => $arquivo('Seeder', 'database/seeders/' . ($args[0] ?? 'Seeder') . '.php'),
      'make:controller' => $arquivo('Controller', 'app/Http/Controllers/' . ($args[0] ?? 'Controller') . '.php'),
      default => print("   ERROR  Command \"$nome\" is not defined.\n"),
    };
  }

  // ver_tabela("cursos"): mostra as linhas de uma tabela (só do playground; no computador use um programa de banco)
  // por padrão esconde created_at e updated_at; passe as colunas para escolher: ver_tabela("cursos", ["id", "nome"])
  function ver_tabela(string $tabela, ?array $colunas = null): void {
    $linhas = DB::pdo()->query('select * from ' . aspas($tabela))->fetchAll();
    $colunas ??= array_values(array_diff(Schema::getColumnListing($tabela), ['created_at', 'updated_at']));
    $texto = fn ($v) => $v === null ? 'NULL' : (string) $v;
    $larg = [];
    foreach ($colunas as $c) {
      $larg[$c] = letras($c);
      foreach ($linhas as $l) $larg[$c] = max($larg[$c], letras($texto($l[$c] ?? null)));
    }
    $sep = '+' . implode('+', array_map(fn ($c) => str_repeat('-', $larg[$c] + 2), $colunas)) . '+';
    $fmt = fn ($vals) => '| ' . implode(' | ', array_map(fn ($c) => $vals[$c] . str_repeat(' ', $larg[$c] - letras($vals[$c])), $colunas)) . ' |';
    $n = count($linhas);
    echo "$tabela ($n " . ($n === 1 ? 'linha' : 'linhas') . ")\n$sep\n", $fmt(array_combine($colunas, $colunas)), "\n$sep\n";
    foreach ($linhas as $l) echo $fmt(array_combine($colunas, array_map(fn ($c) => $texto($l[$c] ?? null), $colunas))), "\n";
    if ($n) echo "$sep\n";
  }

  // os nomes de verdade do Laravel também funcionam (ex.: use Illuminate\Database\Eloquent\Model;)
  foreach ([
    'Model' => 'Illuminate\Database\Eloquent\Model',
    'Migration' => 'Illuminate\Database\Migrations\Migration',
    'Blueprint' => 'Illuminate\Database\Schema\Blueprint',
    'Schema' => 'Illuminate\Support\Facades\Schema',
    'DB' => 'Illuminate\Support\Facades\DB',
    'Route' => 'Illuminate\Support\Facades\Route',
    'Seeder' => 'Illuminate\Database\Seeder',
    'Collection' => 'Illuminate\Support\Collection',
    'Str' => 'Illuminate\Support\Str',
    'QueryException' => 'Illuminate\Database\QueryException',
    'ModelNotFoundException' => 'Illuminate\Database\Eloquent\ModelNotFoundException',
  ] as $curto => $completo) {
    class_alias($curto, $completo);
  }

  // dump() e dd() para quem roda no computador (no site eles já vêm prontos)
  if (!function_exists('dump')) {
    function __ml_fmt(mixed $v, int $n = 0): string {
      $pad = str_repeat('  ', $n);
      if (is_string($v)) return '"' . $v . '"';
      if (is_bool($v)) return $v ? 'true' : 'false';
      if ($v === null) return 'null';
      if (is_int($v) || is_float($v)) return (string) $v;
      if (is_object($v)) {
        [$nome, $vars, $lista] = method_exists($v, '__pgDump') ? $v->__pgDump() : [get_class($v), get_object_vars($v), false];
        $s = $nome . ($lista ? ':' . count($vars) . ' [' : ' {') . "\n";
        foreach ($vars as $k => $x) $s .= $pad . '  ' . ($lista ? $k . ' => ' : '+' . $k . ': ') . __ml_fmt($x, $n + 1) . "\n";
        return $s . $pad . ($lista ? ']' : '}');
      }
      if (is_array($v)) {
        if (!$v) return '[]';
        $s = 'array:' . count($v) . " [\n";
        foreach ($v as $k => $x) $s .= $pad . '  ' . (is_int($k) ? $k : '"' . $k . '"') . ' => ' . __ml_fmt($x, $n + 1) . "\n";
        return $s . $pad . ']';
      }
      return gettype($v);
    }
    function dump(mixed ...$vars): void { foreach ($vars as $v) echo __ml_fmt($v), "\n"; }
    function dd(mixed ...$vars): never { dump(...$vars); exit; }
  }
}
