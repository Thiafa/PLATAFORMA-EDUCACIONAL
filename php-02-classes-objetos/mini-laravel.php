<?php
// MINI-LARAVEL DO PLAYGROUND
// Uma versão de brinquedo (bem pequena) do que o Laravel faz quando chega um pedido.
// No site ele já vem carregado (como o dd); no computador: require "mini-laravel.php";
//   Route::get($url, $acao)      registra uma rota (como em routes/web.php)
//   criar_view($nome, $html)     faz o papel de um arquivo em resources/views
//   view($nome, $dados)          monta o HTML da view com os dados
//   visitar($url)                simula o navegador pedindo uma URL
namespace App\Http\Controllers {
  // no Laravel: app/Http/Controllers/Controller.php
  abstract class Controller {}
}

namespace {
  class_alias(App\Http\Controllers\Controller::class, 'Controller');

  class Route {
    private static array $rotas = [];

    public static function get(string $url, array|Closure $acao): void {
      self::$rotas[$url] = $acao;
    }

    public static function despachar(string $url): string {
      foreach (self::$rotas as $padrao => $acao) {
        // "/cursos/{id}" vira a expressão #^/cursos/([^/]+)$#
        $regex = '#^' . preg_replace('#\{\w+\}#', '([^/]+)', $padrao) . '$#';
        if (preg_match($regex, $url, $partes)) {
          array_shift($partes);   // sobram só os pedaços da URL: os {parâmetros}
          if (is_array($acao)) {
            [$classe, $metodo] = $acao;
            $acao = [new $classe(), $metodo];
          }
          return (string) $acao(...$partes);
        }
      }
      return "404 | Página não encontrada";
    }
  }

  class View {
    public static array $arquivos = [];
  }

  function criar_view(string $nome, string $html): void {
    View::$arquivos[$nome] = $html;
  }

  function view(string $__view, array $__dados = []): string {
    if (!isset(View::$arquivos[$__view])) {
      return "Erro: a view \"$__view\" não existe (crie com criar_view)";
    }
    extract($__dados);   // cada chave do array vira uma variável dentro da view
    ob_start();
    eval('?>' . View::$arquivos[$__view]);
    return ob_get_clean();
  }

  function visitar(string $url): void {
    echo Route::despachar($url), "\n";
  }
}
