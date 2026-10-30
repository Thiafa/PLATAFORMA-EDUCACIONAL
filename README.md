# PLATAFORMA-EDUCACIONAL

Material do curso de programação básica da **Greenn School** (aulas aos sábados) e a plataforma dos alunos: **https://site-alunos-nu.vercel.app**

## O que tem aqui

| Pasta | Conteúdo |
|---|---|
| `site-alunos/` | O site publicado na Vercel: home por módulos (HTML, CSS, JavaScript, Laravel), um playground por aula com exemplos ao vivo e exercícios com correção automática, apostilas em PDF e os projetos finais de cada módulo (portfólio do aluno) |
| `html-0N-*/`, `css-0N-*/` | Pasta de cada aula de HTML e CSS: README (plano), gabarito e apostila |
| `aula-0N-*/`, `aula-bonus-*/` | Pasta de cada aula de JavaScript: README, exemplos, exercícios, gabarito e apostila |
| `php-0N-*/` | Pasta de cada aula do módulo Laravel (PHP rodando no navegador via php-wasm): README, exemplos, exercícios, gabarito e apostila |
| `projeto-portfolio/` | Roteiros em PDF, checklist e gabarito do projeto final (o portfólio que evolui a cada módulo) |

## Como publicar o site

```
cd site-alunos
npx vercel --prod --yes
```

(ou conecte este repositório ao projeto `site-alunos` na Vercel com Root Directory = `site-alunos`, e cada push na `main` publica sozinho).

## Observações

- `site-alunos/php/` contém o runtime do PHP 8.4 compilado para WebAssembly (php-wasm, ~13 MB), carregado uma vez pelo navegador do aluno.
- Os gabaritos estão neste repositório: mantenha-o privado ou separe-os antes de abrir para os alunos.
