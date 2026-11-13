// Worker que roda PHP (php-wasm) e devolve a saída.
// O build web do emscripten espera window/document: no worker, criamos um substituto inofensivo.
self.window = self; self.document = { getElementById() { return null; }, addEventListener() {}, removeEventListener() {} };
// Helpers do Laravel disponíveis no playground: dump() e dd() (dump and die).
const PRELUDE = `<?php
function __pg_fmt($v, $n = 0) {
  $pad = str_repeat("  ", $n);
  if (is_string($v)) return '"' . $v . '"';
  if (is_bool($v)) return $v ? "true" : "false";
  if (is_null($v)) return "null";
  if (is_int($v) || is_float($v)) return (string) $v;
  if (is_array($v)) {
    if (!count($v)) return "[]";
    $s = "array:" . count($v) . " [\\n";
    foreach ($v as $k => $x) $s .= $pad . "  " . (is_int($k) ? $k : '"' . $k . '"') . " => " . __pg_fmt($x, $n + 1) . "\\n";
    return $s . $pad . "]";
  }
  if (is_object($v)) {
    // models e collections do mini-Laravel dizem o que mostrar (__pgDump)
    [$nome, $vars, $lista] = method_exists($v, '__pgDump') ? $v->__pgDump() : [get_class($v), get_object_vars($v), false];
    $s = $nome . ($lista ? ":" . count($vars) . " [" : " {") . "\\n";
    foreach ($vars as $k => $x) $s .= $pad . "  " . ($lista ? $k . " => " : "+" . $k . ": ") . __pg_fmt($x, $n + 1) . "\\n";
    return $s . $pad . ($lista ? "]" : "}");
  }
  return gettype($v);
}
function dump(...$vars) { foreach ($vars as $v) echo __pg_fmt($v), "\\n"; }
function dd(...$vars) { dump(...$vars); exit; }
`;
let php = null, out = [], version = '';
async function boot() {
  const { PhpWeb } = await import('./PhpWeb.mjs');
  // runner.js?libs=sqlite carrega também as extensões sqlite3 e pdo_sqlite (só as aulas de banco de dados precisam)
  const libs = new URL(self.location.href).searchParams.get('libs') || '';
  const sharedLibs = libs.split(',').includes('sqlite') ? [
    { name: 'php8.4-sqlite.so', url: new URL('./sqlite/php8.4-sqlite.so', import.meta.url), ini: true },
    { name: 'php8.4-pdo-sqlite.so', url: new URL('./sqlite/php8.4-pdo-sqlite.so', import.meta.url), ini: true },
    { name: 'libsqlite3.so', url: new URL('./sqlite/libsqlite3.so', import.meta.url) },
  ] : [];
  php = new PhpWeb({ version: '8.4', sharedLibs });
  php.addEventListener('output', e => out.push(['out', e.detail.join('')]));
  php.addEventListener('error', e => out.push(['err', e.detail.join('')]));
  await php.binary;
  out = []; await php.run('<?php echo PHP_VERSION;'); version = out.map(x => x[1]).join('').trim();
}
const ready = boot();
self.onmessage = async (e) => {
  // kit: código extra que a página carrega antes do aluno (ex.: o mini-Laravel da L2)
  const { id, code, kit } = e.data;
  await ready;
  out = [];
  let exit = 0, fatal = null;
  try { await php.refresh(); await php.run(PRELUDE); if (kit) await php.run(kit); out = []; exit = await php.run(code); } catch (err) { fatal = String(err && err.message || err); }
  // flush
  await new Promise(r => setTimeout(r, 10));
  self.postMessage({ id, exit, fatal, out });
};
ready.then(() => self.postMessage({ ready: true, version }));
