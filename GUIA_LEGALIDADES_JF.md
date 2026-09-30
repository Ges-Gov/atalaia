# Guia — Importar Legalidades (RGPD + Cookies + Acessibilidade) para qualquer site de Junta de Freguesia

Este guia explica como adicionar a **Política de Privacidade**, a **Política de Cookies com banner de
consentimento**, a **Declaração de Acessibilidade** (+ correções de acessibilidade) e os **links legais no
rodapé** a qualquer site de JF baseado neste código (Rio de Moinhos, São Domingos, etc.).

> **Implementação de referência:** `c:\laragon\www\riomoinhos-test\` — todos os ficheiros abaixo já lá estão
> a funcionar. Usa-a como fonte para copiar os trechos.

---

## ⚠️ Regras de ouro (para NÃO estragar o site nem a BD)

1. **ZERO alterações à base de dados.** Este pacote é só ficheiros PHP/CSS. **Não há SQL, não cria tabelas,
   não toca em dados.** A BD fica exatamente na mesma.
2. **Não substituir ficheiros às cegas.** O `header.php`, `footer.php`, `index.php` e `style.css` **variam
   entre sites**. Aplica os **trechos** indicados, não copies o ficheiro inteiro por cima.
3. **Clone-safe:** tudo usa `siteConfig()` e as variáveis de cor (`--cor-principal`/`--cor-secundaria`).
   Não há nome de freguesia, morada nem cores fixas no código — serve para qualquer JF.
4. **Testar sempre numa cópia/sandbox** (pasta + BD separada) antes de tocar no site live. Ver o guia da
   sandbox se precisares.
5. **Não incluir no deploy:** `includes/db_config.php`, `uploads/`, `assets/img/`, `assets/docs/`.

---

## Parte A — Ficheiros NOVOS (copiar tal e qual para a raiz do site)

São autónomos e clone-safe (puxam nome/morada/email de `configuracoes_site` via `siteConfig()`).
Copiar de `riomoinhos-test`:

- `politica-privacidade.php`
- `politica-cookies.php`
- `declaracao-acessibilidade.php`

> Nada a mudar — adaptam-se sozinhos ao nome/contactos de cada freguesia.

---

## Parte B — Edições em ficheiros existentes (aplicar os trechos)

### B1. `includes/footer.php`

**(a) Fechar o `<main>`** — mesmo no início do ficheiro, antes de `<footer ...>`:
```php
</main>

<footer class="rm-footer">
```

**(b) Links legais** — antes de `<div class="rm-footer-bottom">`:
```php
<nav class="rm-footer-legal" aria-label="Ligações legais e institucionais">
    <a href="/politica-privacidade.php">Política de Privacidade</a>
    <a href="/politica-cookies.php">Política de Cookies</a>
    <a href="/protecao-dados.php">Proteção de Dados (DPO)</a>
    <a href="/declaracao-acessibilidade.php">Acessibilidade</a>
    <a href="/canal-denuncias.php">Canal de Denúncias</a>
    <a href="/livro-reclamacoes.php">Livro de Reclamações</a>
</nav>
```
> Remove os links de páginas que não existam nesse site.

**(c) Redes sociais dentro de `<nav>`** — trocar `<div class="rm-footer-social">…</div>` por
`<nav class="rm-footer-social" aria-label="Redes sociais">…</nav>`.

**(d) "Área reservada"** — tirar o `opacity:.75` do link (reduz contraste); usar `color:#dbeafe`.

**(e) Banner de cookies** — antes de `<script src="/assets/js/app.js"></script>`, colar o bloco completo
(CSS + HTML + JS) que está em `riomoinhos-test/includes/footer.php`. Pontos críticos a não falhar:
- A regra **`#rm-cookie-banner[hidden]{display:none}`** TEM de existir (senão o `display:flex` do ID
  vence o atributo `hidden` e o banner nunca fecha).
- O JS só mostra o banner **se a página tiver `iframe[data-cookie-src]`** (conteúdo de terceiros). Em
  sites só com fotos, o banner **não aparece** (só há cookies essenciais → não é obrigatório).

### B2. `includes/header.php`

**(a) Skip link** — logo a seguir a `<body>`:
```php
<style>
.skip-link{position:absolute;left:-9999px;top:0;z-index:10000;background:var(--cor-principal,#0d3b66);color:#fff;padding:12px 18px;border-radius:0 0 10px 0;font-weight:900;text-decoration:none}
.skip-link:focus{left:0}
</style>
<a class="skip-link" href="#conteudo-principal">Saltar para o conteúdo principal</a>
```

**(b) Abrir o `<main>`** — no fim do header, a seguir ao `</nav>` da barra inferior (antes do conteúdo da
página começar):
```php
<main id="conteudo-principal" tabindex="-1">
```
> Confirma que o `</main>` foi fechado no `footer.php` (passo B1a).

**(c) `aria-label` nos botões só com ícone:**
- botão do menu (hambúrguer): `aria-label="Abrir menu"`
- botão de notificações (sino): `aria-label="Notificações"`
- campo de pesquisa (`input`): `aria-label="Pesquisar no site"`

**(d) SEO (opcional mas recomendado)** — `<title>` por página + `<meta name="description">`:
```php
<?php
    $tituloPagina = !empty($meta_titulo)
        ? $meta_titulo . ' | ' . siteConfig('nome_site', 'Junta de Freguesia')
        : siteConfig('nome_site', 'Junta de Freguesia');
    $descricaoPagina = !empty($meta_descricao)
        ? $meta_descricao
        : siteConfig('nome_site', 'Junta de Freguesia') . ' — notícias, eventos, documentos e serviços ao munícipe da freguesia.';
?>
<title><?= htmlspecialchars($tituloPagina) ?></title>
<meta name="description" content="<?= htmlspecialchars($descricaoPagina) ?>">
```

### B3. `index.php` (homepage) — acessibilidade + consentimento do vídeo

**(a) Vídeo do YouTube só com consentimento:**
- Na função `youtubeEmbedHome()`, trocar `youtube.com/embed/` por **`youtube-nocookie.com/embed/`** (nos
  `return` e no `str_replace` do ramo que já vem em formato embed).
- No `<iframe>` do vídeo, trocar `src="…"` por **`data-cookie-src="…"`** e adicionar `title`.
- Adicionar um fundo sólido de reserva e um botão de consentimento (ver `riomoinhos-test/index.php`):
  `<div class="home-video-fallback" style="…var(--cor-principal)…"></div>` e um
  `<button class="rm-needs-consent" onclick="window.rmAcceptCookies && window.rmAcceptCookies()">`.

**(b) Slider com um só `<h1>`** — no `foreach` dos slides, o primeiro é `<h1>`, os restantes `<h2>`:
```php
<?php if ($i === 0): ?>
    <h1><?= htmlspecialchars($slide['titulo']) ?></h1>
<?php else: ?>
    <h2><?= htmlspecialchars($slide['titulo']) ?></h2>
<?php endif; ?>
```

**(c) `aria-label` nos controlos do slider:** setas (`Slide anterior` / `Slide seguinte`) e bolinhas
(`Ir para o slide N`).

**(d) Cartões de notícia → artigo próprio:** no cartão de notícia, `href="/noticia.php?id=<id>"` em vez de
apontarem todos para `/noticias.php` (corrige "links adjacentes para o mesmo destino").

### B4. `assets/css/style.css` (contraste — WCAG 1.4.3 AA)

O AccessMonitor não consegue medir contraste sobre **gradientes/translúcidos** → marca falsos positivos.
Tornar sólidos os fundos escuros resolve (visual quase idêntico):

- **Rodapé** `.rm-footer` → `background:#102a43;` (em vez do gradiente).
- **Caixas do rodapé** `.rm-footer-grid > div` → `background:#17395c;` (em vez de `rgba(255,255,255,.07)`).
- **Secções escuras** com `linear-gradient(135deg,var(--cor-principal),#102a43)` + `color:white` →
  `background:#102a43;`.

> As secções com **imagem de fundo** (hero de vídeo, CTA do mapa) **não** se solidificam — o texto branco
> sobre a imagem escura está correto. São falsos positivos que se justificam por **verificação manual** na
> Declaração de Acessibilidade.

---

## Parte C — Depois de aplicar (por site)

1. **Testar** em local/sandbox (`http://localhost:PORTA` ou o `.test`).
2. **AccessMonitor** (`accessmonitor.acessibilidade.gov.pt`) — se for local, expor com um túnel
   (cloudflared/ngrok) e analisar o link.
3. **DPO valida** a Política de Privacidade (prazos de conservação, identificação do Encarregado).
4. **Declaração de Acessibilidade** — gerar a versão oficial em `acessibilidade.gov.pt` e registar a
   verificação manual dos falsos positivos de contraste.
5. **Deploy (FastPanel):** ZIP só com os ficheiros alterados → Extract no web root. **Sem BD.**

---

## Checklist rápido (por cada site)

- [ ] Copiar 3 páginas novas (`politica-privacidade`, `politica-cookies`, `declaracao-acessibilidade`)
- [ ] `footer.php`: `</main>` + links legais + banner cookies (com `[hidden]`) + social em `<nav>` + tirar opacity
- [ ] `header.php`: skip link + `<main>` + aria-labels (menu/sino/pesquisa) + (SEO title/desc)
- [ ] `index.php`: YouTube nocookie+consentimento + slider 1 h1 + aria slider + cartões notícia → artigo
- [ ] `style.css`: rodapé e secções escuras a sólido
- [ ] **Base de dados: NADA** (este pacote não mexe na BD)
- [ ] Testar + AccessMonitor
- [ ] DPO valida privacidade + Declaração oficial de acessibilidade
- [ ] Deploy (só ficheiros)

---

## Notas finais

- **Não é aconselhamento jurídico.** O guia cobre o essencial técnico do RGPD/ePrivacy/DL 83/2018; a
  validação formal cabe ao DPO/jurista da Junta.
- **Prefixos e conteúdos** específicos de cada freguesia (ex.: código de denúncias `RM-` → `SD-`) não fazem
  parte deste pacote — ver o `CLAUDE.md` do projeto.
- O `protecao-dados.php` (canal do DPO), `canal-denuncias.php` e `livro-reclamacoes.php` já costumam existir
  nos sites; os links legais assumem que sim (ajustar se algum não existir).
