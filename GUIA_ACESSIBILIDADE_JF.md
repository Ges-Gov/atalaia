# Guia — Acessibilidade Web (WCAG 2.1 AA + Selo de Usabilidade e Acessibilidade)

Este guia documenta as correções técnicas de **acessibilidade** aplicadas e testadas no site do
**Rio de Moinhos** (o mais avançado, usado como referência), com o objetivo de nota **9-10 no
AccessMonitor** e conformidade com a checklist **"10 Aspetos Críticos de Acessibilidade
Funcional"** — os requisitos do **Selo de Usabilidade e Acessibilidade (Bronze)**.

> **Não confundir com [`GUIA_LEGALIDADES_JF.md`](GUIA_LEGALIDADES_JF.md)** — esse trata de
> RGPD/Política de Privacidade/Cookies/Declaração de Acessibilidade (o *conteúdo legal*). Este
> guia trata do *código* que faz o site pontuar bem nos testes de acessibilidade (WCAG técnico).
> Os dois são complementares e normalmente aplicam-se juntos.

**Percurso real no Rio de Moinhos:** 6,3 → 7,7 → 8,6 → 7,1 (regressão) → 7,7 → 8,7 → 9,2 → 9,6 →
**10/10**. As quedas a meio foram regressões por deploys que perderam fixes já feitos (ver secção
"Armadilhas de deploy" no fim) — não são normais, mas aconteceram, e o guia existe também para não
repetir os mesmos erros.

---

## Ferramentas de avaliação

- **AccessMonitor** — `https://accessmonitor.acessibilidade.gov.pt` — cola o URL público do site,
  dá uma nota 0-10 e uma lista de erros/avisos por critério WCAG.
- **Checklist "10 Aspetos Críticos de Acessibilidade Funcional"** — avaliação manual/semi-manual,
  cobre coisas que o AccessMonitor não testa sozinho (navegação por teclado nos menus, labels
  clicáveis, etc.). Fica em `https://amagovpt.github.io/kit-selo/checklists/checklist-10aspetos`.
- **Selo de Usabilidade e Acessibilidade** — `https://selo.usabilidade.gov.pt` — candidatura
  (Bronze/Prata/Ouro). Requisitos do **Bronze**: Declaração de Acessibilidade publicada + **9-10
  no AccessMonitor** em todas as páginas de amostra + ≥75% nas checklists "10 Aspetos Críticos" e
  "Conteúdo". Ver `GUIA_LEGALIDADES_JF.md` para a Declaração de Acessibilidade em si.

> Para testar um site que só existe em local/sandbox: expor com um túnel temporário
> (`cloudflared tunnel --url http://localhost:PORTA`) e colar esse link no AccessMonitor.

---

## Checklist de correções (aplicar por esta ordem)

### 1. Skip link + landmark `<main>`
No topo do `<body>` (`includes/header.php`), logo a seguir a `<body>`:
```php
<style>
.skip-link{position:absolute;left:-9999px;top:0;z-index:10000;background:var(--cor-principal,#0d3b66);color:#fff;padding:12px 18px;border-radius:0 0 10px 0;font-weight:900;text-decoration:none}
.skip-link:focus{left:0}
</style>
<a class="skip-link" href="#conteudo-principal">Saltar para o conteúdo principal</a>
```
E no fim do header (depois da barra de navegação inferior mobile, antes do conteúdo da página
começar):
```php
<main id="conteudo-principal" tabindex="-1">
```
Fechar `</main>` no `footer.php`, antes de `<footer>`.

### 2. Um só `<h1>` por página
Erro `heading_04`/sucesso `heading_03`. Cuidado especial com **sliders/carrosséis**: se o `<h1>`
estiver dentro de um `foreach` sobre os slides, sai **um `<h1>` por slide** mesmo que só um esteja
visível — o WCAG conta o código, não o CSS. Corrigir assim:
```php
<?php if ($i === 0): ?>
    <h1><?= htmlspecialchars($slide['titulo']) ?></h1>
<?php else: ?>
    <h2><?= htmlspecialchars($slide['titulo']) ?></h2>
<?php endif; ?>
```
Confirma que os outros modos de hero da mesma página (vídeo, fallback sem slides) estão em ramos
`if/else` mutuamente exclusivos — assim nunca renderizam dois `<h1>` ao mesmo tempo.

### 3. `aria-label` em botões só com ícone
Setas/pontos de slider, botão de menu hambúrguer, sino de notificações, botões de fechar:
```php
<button class="slider-arrow prev" aria-label="Slide anterior">‹</button>
<button class="slider-arrow next" aria-label="Slide seguinte">›</button>
<button class="..." data-slide="<?= $i ?>" aria-label="Ir para o slide <?= $i+1 ?>"></button>
<button class="menu-toggle" aria-label="Abrir menu">...</button>
<button class="notif-btn" aria-label="Notificações">...</button>
```

### 4. Menu por teclado (dropdowns) — `a_07` + "10 Aspetos: Menus"
**Dois problemas em um.** O menu "A Freguesia ▾" etc. tem de (a) ser operável por teclado e (b) os
links do submenu não podem estar "fora de um elemento `<nav>`".

**HTML** — o `<div class="submenu">` passa a `<nav>`:
```php
<button class="nav-parent" type="button" aria-haspopup="true" aria-expanded="false">
    A Freguesia <span>▾</span>
</button>
<nav class="submenu" aria-label="A Freguesia">
    <a href="/executivo.php">Executivo</a>
    ...
</nav>
```
(A classe `.submenu` no CSS não muda — continua a fazer match por classe, não por tag.)

**JS** (`assets/js/app.js`) — sincronizar `aria-expanded` e fechar com Esc:
```js
function fecharDropdown(item) {
    item.classList.remove("open");
    const btn = item.querySelector(".nav-parent");
    if (btn) btn.setAttribute("aria-expanded", "false");
}
dropdowns.forEach(function (dropdown) {
    const button = dropdown.querySelector(".nav-parent");
    if (button) {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const vaiAbrir = !dropdown.classList.contains("open");
            dropdowns.forEach(function (item) { if (item !== dropdown) fecharDropdown(item); });
            dropdown.classList.toggle("open", vaiAbrir);
            button.setAttribute("aria-expanded", vaiAbrir ? "true" : "false");
        });
    }
});
document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") dropdowns.forEach(fecharDropdown);
});
```
> Um `<button>` nativo já ativa com Enter/Espaço — não é preciso `onkeydown` extra no botão em si.

### 5. Label de pesquisa (`label_02`) — `aria-label` sozinho NÃO chega
O AccessMonitor exige uma **`<label>` genuinamente visível** (não `clip`/`sr-only`) e
**estruturalmente próxima** do `<input>` (mesmo container, não vários `<div>` de distância):
```php
<div class="global-search-box" style="position:relative;">
    <label for="globalSearchInput" class="global-search-label">Pesquisar no site</label>
    <span class="global-search-icon">...</span>
    <input type="text" id="globalSearchInput" placeholder="..." autocomplete="off">
</div>
```
```css
.global-search-label{
    position:absolute; top:-22px; left:6px;
    font-size:12px; font-weight:900; color:#475569;
    text-transform:uppercase; letter-spacing:.6px;
}
```
Isto mantém o aspeto visual (legenda pequena por cima da barra) mas fica **dentro** do mesmo
elemento do input, não como irmão do `.global-search-wrapper` lá fora.

### 6. Imagens/botões clicáveis (lightbox, galerias) — `ehandler_02`
**Nunca** pôr `onclick` diretamente num `<img>` ou `<div>` — não são operáveis por teclado. Envolver
sempre num `<button type="button">`:
```php
<!-- ERRADO -->
<img src="..." onclick="lbAbrir(...)">
<div class="galeria-item" onclick="lbAbrir(...)">...</div>

<!-- CORRETO -->
<button type="button" class="lb-trigger" onclick="lbAbrir(...)">
    <img src="..." alt="...">
</button>
<button type="button" class="galeria-publica-item" onclick="lbAbrir(...)">
    <img src="..."><span class="legenda">...</span>
</button>
```
CSS de reset para o botão não parecer botão:
```css
.lb-trigger{display:block;width:100%;padding:0;border:0;background:none;font:inherit;text-align:left;cursor:zoom-in;}
```
Se o botão for filho direto de uma **CSS grid** com regras tipo `img:first-child{grid-row:span 2}`,
mover essas regras para o novo `<button>` (ele é agora o item da grid, não a `<img>`).

### 7. Links de cartões — `a_06` (links adjacentes para o mesmo destino)
Cada cartão de notícia/evento/associação tem de apontar para o **seu próprio artigo**, não para a
página de listagem genérica:
```diff
- <a href="/noticias.php" class="premium-news-card">
+ <a href="/noticia.php?id=<?= (int)$n['id'] ?>" class="premium-news-card">
```

### 8. `<br>` a mais — `br_01` ("desconfio que está a usá-los para representar uma lista")
**Importante:** esta regra parece contar **todos os `<br>` dentro do mesmo `<p>`**, não só os
adjacentes/consecutivos. Um texto com 2 parágrafos separados por linha em branco, processado com
`nl2br()`, gera 4 `<br>` dentro de **um único `<p>`** — e isso já dispara o aviso, mesmo sem 3
seguidos sem texto a separar.

**Fix errado** (não resolve, só reduz o nº de `\n` consecutivos):
```php
nl2br(htmlspecialchars(preg_replace('/\n{3,}/', "\n\n", $texto)))
```

**Fix certo** (estrutural — elimina os `<br>`, usa `<p>` a sério por parágrafo):
```php
<?php foreach (preg_split('/\n{2,}/', trim($texto)) as $paragrafo): ?>
    <?php if (trim($paragrafo) !== ''): ?>
        <p><?= htmlspecialchars(trim($paragrafo)) ?></p>
    <?php endif; ?>
<?php endforeach; ?>
```
Para **texto de pré-visualização truncado** (ex.: `mb_substr(..., 0, 450)` seguido de "..."), mais
simples ainda — nem faz sentido ter parágrafos numa prévia curta, substituir quebras por espaço:
```php
htmlspecialchars(mb_substr(preg_replace('/\s*\n+\s*/', ' ', trim($texto)), 0, 450))
```

> Se corrigires isto e a nota **não mexer**, confirma que não é cache do AccessMonitor: procura na
> página **renderizada ao vivo** (não no código) por sequências reais de `<br><br><br>` — se não
> houver nenhuma, o relatório está desatualizado, não é preciso continuar a "corrigir" código que
> já está certo.

### 9. Contraste de cor — `color_02` (o mais trabalhoso)

**A cilada nº1: fundos translúcidos/gradiente "escondem" más leituras.** O AccessMonitor (e a
avaliação manual) não conseguem medir com confiança contraste sobre:
- `background: linear-gradient(...)` — mesmo entre duas cores escuras, uma zona da transição pode
  ler como mais clara.
- `background: rgba(255,255,255,.1)` (translúcido) sobre outro fundo — o cálculo por trás não
  resolve bem a mistura.
- Fotografias (`background-image: url(...)`) — zonas claras da foto podem estar por baixo do texto.

**Fix (gradientes/translúcidos): tornar sólido.** Troca a cor por um hex sólido equivalente:
```diff
- background: linear-gradient(135deg, var(--cor-principal), #102a43);
+ background: #102a43;
```
```diff
- background: rgba(255,255,255,.1);
+ background: #1e3c60;  /* aproximação sólida da mistura visual */
```
Isto **não muda visualmente quase nada** (é a mesma família de cor, só sem transparência) e
resolve a leitura de contraste de forma garantida.

**Fix (fotografias): dar ao bloco de texto um cartão sólido próprio.** Escurecer o overlay
(`rgba(...,.94)` → `.97`) muitas vezes **não chega** se a foto tiver uma zona muito clara
exatamente por trás do texto. A solução definitiva e garantida matematicamente:
```css
.mapa-cta-texto {
    background: #102a43;      /* sólido, opaco, independente da foto por trás */
    padding: 30px 34px;
    border-radius: 26px;
    box-shadow: 0 22px 55px rgba(0,0,0,.28);
}
```
Fica visualmente como um "cartão flutuante" sobre a foto — aspeto comum e elegante, e o contraste
deixa de depender de nada imprevisível.

**A cilada nº2: uma regra CSS mais específica pode estar a mudar a cor sem dares por isso.**
Encontrámos um bug real: `.hero-btn { color: #102a43 }` (texto escuro, correto) estava a ser
**substituído** por `.hero-actions a { color: #fff }` (texto branco) porque esta segunda regra tem
maior especificidade (classe + elemento vs. só classe) — resultado: texto quase invisível sobre
fundo dourado. **Antes de mexer na cor "óbvia", confirma sempre a cor final computada** (inspeciona
no browser, ou procura todas as regras que tocam no mesmo seletor). Corrigir com uma regra ainda
mais específica, sem tocar na genérica (que pode servir outros botões de propósito):
```css
.hero-actions a.hero-btn { color: #102a43; }
```

**A cilada nº3: um `.section-kicker`/etiqueta que estava pensado para fundo claro, usado sem
querer dentro de um cartão escuro.** Sempre que envolveres um bloco de texto num cartão escuro
novo, confirma se o `.section-kicker` (ou equivalente) lá dentro também precisa de um override de
cor (padrão já usado no site — dourado `var(--cor-secundaria)` em vez do azul-escuro por defeito):
```css
.novo-cartao-escuro .section-kicker { color: var(--cor-secundaria); }
```

---

## Armadilhas de deploy (para não perder trabalho já feito)

1. **Sandbox/working-copy divergente da produção real.** Se testaste os fixes numa cópia (sandbox)
   diferente da pasta que realmente é enviada para produção, os fixes ficam presos lá e nunca vão
   para o ar. **Aplica sempre os fixes na pasta que é mesmo a fonte do deploy**, e confirma contra
   o site ao vivo (`curl`/inspecionar) antes de dares como concluído.
2. **O FastPanel pode criar uma pasta extra ao extrair** (em vez de sobrepor os ficheiros na raiz).
   Depois de extrair, confirma que `includes/`, `assets/`, `index.php`, etc. ficaram na **raiz do
   site**, não dentro de uma pasta nova com o nome do zip. Se acontecer: mover o conteúdo para cima
   e apagar a pasta vazia.
3. **Um deploy de responsividade/layout pode reverter fixes de acessibilidade já feitos** (e
   vice-versa) se vier de uma versão mais antiga do ficheiro. Antes de reconstruir um ZIP, confirma
   que a base (`index.php`/`style.css`/`header.php`) já tem TODOS os fixes anteriores, não só o
   novo que estás a adicionar.
4. **Confirma sempre contra o site ao vivo**, nunca só contra os teus ficheiros locais — usa
   `curl`/inspeção do HTML renderido para verificar presença de cada fix antes de o dar como
   resolvido.
5. **Encoding**: o `grep` do git-bash rebenta com acentos — usar sempre `export LC_ALL=C.UTF-8`
   antes, ou gravar o output para ficheiro (`curl ... -o ficheiro.html`) e fazer grep ao ficheiro,
   evitando corrupção ao passar conteúdo acentuado por variáveis bash.
6. **Não misturar sites.** Confirma sempre, antes e depois de qualquer ZIP, que o conteúdo é do
   site certo (`grep` pelo nome da freguesia; 0 menções a outra freguesia) — já aconteceu enviar um
   ZIP do Rio de Moinhos para o site errado por engano no FastPanel.

---

## Checklist rápido por site
- [ ] Skip link + `<main id="conteudo-principal">`
- [ ] Um só `<h1>` renderizado (cuidado com sliders/loops)
- [ ] `aria-label` em todos os botões só-ícone (menu, notif, setas/pontos de slider)
- [ ] Dropdowns do menu: `<nav aria-label>` + `aria-expanded` sincronizado + Esc fecha
- [ ] Label de pesquisa visível e estruturalmente próxima do input
- [ ] Lightbox/galerias: `<button>` em vez de `onclick` em `<img>`/`<div>`
- [ ] Cartões de notícia/evento: link próprio por item, não para a listagem genérica
- [ ] Textos com `nl2br()` multi-parágrafo → separar em `<p>` próprios
- [ ] Fundos com gradiente/translúcido atrás de texto → sólidos
- [ ] Fundos com foto atrás de texto → cartão sólido próprio para o texto
- [ ] Confirmar cor final computada (não assumir pela regra "óbvia")
- [ ] Testar no AccessMonitor **contra o site ao vivo**, não só local
- [ ] Confirmar pós-deploy que os ficheiros ficaram no sítio certo (sem pasta extra)
