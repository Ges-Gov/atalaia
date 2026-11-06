<?php
/**
 * Texto com formatação simples nas respostas às ocorrências (Atalaia, 06/11/2026, pedido do presidente):
 * negrito, itálico, sublinhado, cor, tamanho da letra e listas.
 *
 * Guarda-se HTML já limpo (textoRicoLimpar) e volta-se a limpar ao mostrar (textoRicoHtml). Só passam as etiquetas
 * e os estilos da lista abaixo; tudo o resto (scripts, ligações, atributos, imagens…) é retirado.
 * As respostas antigas, em texto simples, continuam a aparecer como antes (nl2br).
 */

const TEXTO_RICO_TAGS = ['b', 'strong', 'i', 'em', 'u', 'br', 'p', 'div', 'ul', 'ol', 'li', 'span', 'font'];
const TEXTO_RICO_TAGS_FORA = ['script', 'style', 'iframe', 'object', 'embed', 'head', 'title', 'meta', 'link', 'form',
    'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript'];
// tamanhos do execCommand('fontSize') 1–7
const TEXTO_RICO_TAMANHOS = ['1' => '0.75em', '2' => '0.875em', '3' => '1em', '4' => '1.125em', '5' => '1.35em', '6' => '1.7em', '7' => '2.2em'];

/** O texto tem etiquetas da formatação simples? (senão é texto simples, das respostas antigas) */
function textoRicoEhHtml(string $t): bool {
    return (bool)preg_match('#<(b|strong|i|em|u|br|p|div|ul|ol|li|span|font)\b[^>]*>#i', $t);
}

/** Cor aceite: #rgb/#rrggbb, rgb()/rgba() com números, ou um nome só de letras. */
function textoRicoCor(string $c): ?string {
    $c = strtolower(trim($c));
    if (preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/', $c)) return $c;
    if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $c)) return $c;
    if (preg_match('/^[a-z]{3,20}$/', $c)) return $c;
    return null;
}

/** Estilos aceites (cor, tamanho, negrito, itálico, sublinhado); devolve o atributo style limpo ou ''. */
function textoRicoEstilo(string $style): string {
    $ok = [];
    foreach (explode(';', $style) as $decl) {
        if (strpos($decl, ':') === false) continue;
        [$prop, $val] = array_map('trim', explode(':', $decl, 2));
        $prop = strtolower($prop); $v = strtolower($val);
        if ($prop === 'color' && ($cor = textoRicoCor($v)) !== null) $ok[] = "color: $cor";
        elseif ($prop === 'font-size' && preg_match('/^(\d{1,2}(\.\d+)?(px|em|rem|%)|x-small|small|medium|large|x-large|xx-large)$/', $v)) $ok[] = "font-size: $v";
        elseif ($prop === 'font-weight' && in_array($v, ['bold', '700', 'normal', '400'], true)) $ok[] = "font-weight: $v";
        elseif ($prop === 'font-style' && in_array($v, ['italic', 'normal'], true)) $ok[] = "font-style: $v";
        elseif ($prop === 'text-decoration' && preg_match('/^(underline|none)$/', $v)) $ok[] = "text-decoration: $v";
    }
    return implode('; ', $ok);
}

function textoRicoLimparNo(DOMNode $no): void {
    for ($filho = $no->firstChild; $filho !== null; $filho = $seguinte) {
        $seguinte = $filho->nextSibling;
        if ($filho instanceof DOMText) continue;
        if (!$filho instanceof DOMElement) { $no->removeChild($filho); continue; }   // comentários, etc.
        $tag = strtolower($filho->nodeName);
        if (in_array($tag, TEXTO_RICO_TAGS_FORA, true)) { $no->removeChild($filho); continue; }
        textoRicoLimparNo($filho);
        if (!in_array($tag, TEXTO_RICO_TAGS, true)) {
            // etiqueta desconhecida (a, img, h1, table…): fica só o conteúdo
            while ($filho->firstChild) $no->insertBefore($filho->firstChild, $filho);
            $no->removeChild($filho);
            continue;
        }
        $estilo = '';
        if ($tag === 'font') {
            // o execCommand do browser gera <font color> e <font size>: passa a <span style>
            $partes = [];
            if (($cor = textoRicoCor((string)$filho->getAttribute('color'))) !== null) $partes[] = "color: $cor";
            $tam = (string)$filho->getAttribute('size');
            if (isset(TEXTO_RICO_TAMANHOS[$tam])) $partes[] = 'font-size: ' . TEXTO_RICO_TAMANHOS[$tam];
            $estilo = implode('; ', $partes);
            $span = $filho->ownerDocument->createElement('span');
            while ($filho->firstChild) $span->appendChild($filho->firstChild);
            $no->replaceChild($span, $filho);
            $filho = $span; $tag = 'span';
        } elseif (in_array($tag, ['span', 'p', 'div', 'li', 'b', 'strong', 'i', 'em', 'u'], true)) {
            $estilo = textoRicoEstilo((string)$filho->getAttribute('style'));
        }
        // fora o estilo limpo, não fica nenhum atributo (on*, href, class, id…)
        while ($filho->attributes->length) $filho->removeAttribute($filho->attributes->item(0)->nodeName);
        if ($estilo !== '') $filho->setAttribute('style', $estilo);
        if ($tag === 'span' && $estilo === '') {
            while ($filho->firstChild) $no->insertBefore($filho->firstChild, $filho);
            $no->removeChild($filho);
        }
    }
}

/** Limpa o HTML vindo do editor. Texto simples (sem etiquetas) fica como está. */
function textoRicoLimpar(string $html): string {
    $html = trim($html);
    if ($html === '' || !textoRicoEhHtml($html)) return $html;
    $doc = new DOMDocument();
    $antes = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="texto-rico-raiz">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($antes);
    $raiz = (new DOMXPath($doc))->query('//div[@id="texto-rico-raiz"]')->item(0);
    if (!$raiz) return nl2br(htmlspecialchars(strip_tags($html)));
    textoRicoLimparNo($raiz);
    $out = '';
    foreach ($raiz->childNodes as $c) $out .= $doc->saveHTML($c);
    $out = trim($out);
    // só quebras de linha / espaços: conta como vazio
    if (trim(html_entity_decode(strip_tags($out), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\xC2\xA0") === '') return '';
    return $out;
}

/** Para mostrar: HTML limpo, ou o texto simples antigo com as mudanças de linha. */
function textoRicoHtml(?string $t): string {
    $t = (string)$t;
    return textoRicoEhHtml($t) ? textoRicoLimpar($t) : nl2br(htmlspecialchars($t));
}

/** Versão em texto simples (resumos, onde a formatação não cabe). */
function textoRicoTexto(?string $t): string {
    $t = (string)$t;
    if (!textoRicoEhHtml($t)) return $t;
    $t = preg_replace('#<br\s*/?>|</(p|div|li)>|<(ul|ol|p|div|li)\b[^>]*>#i', "\n", $t);
    $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace("/\n{2,}/", "\n", str_replace("\u{A0}", ' ', $t)));
}

/** Editor (barra de formatação) para as <textarea data-texto-rico> do backoffice. Chamar uma vez na página.
 *  O conteúdo inicial da textarea deve vir já limpo (textoRicoLimpar); o editor volta a filtrá-lo no browser. */
function textoRicoEditor(): void {
    static $feito = false;
    if ($feito) return;
    $feito = true;
    ?>
<style>
.tr-editor{border:1px solid #d6dde8;border-radius:12px;background:#fff;overflow:hidden}
.tr-barra{display:flex;flex-wrap:wrap;gap:4px;align-items:center;padding:6px 8px;border-bottom:1px solid #e5eaf1;background:#f8fafc}
.tr-barra button{border:1px solid transparent;background:none;border-radius:7px;min-width:32px;height:32px;padding:0 8px;cursor:pointer;color:#1f2937;font-size:15px;line-height:1}
.tr-barra button:hover{background:#e9eef5;border-color:#d6dde8}
.tr-barra .tr-sep{width:1px;height:22px;background:#d6dde8;margin:0 4px}
.tr-barra .tr-cor{width:20px;height:20px;min-width:20px;border-radius:50%;padding:0;border:2px solid #fff;box-shadow:0 0 0 1px #cbd5e1}
/* !important: os formulários do backoffice (ex. .vp-compor) esticam inputs e selects a 100% */
.tr-editor .tr-barra input[type=color]{width:28px!important;height:28px!important;min-height:0!important;border:none!important;background:none!important;padding:0!important;margin:0!important;box-shadow:none!important;cursor:pointer;flex:0 0 auto}
.tr-editor .tr-barra select{width:auto!important;height:32px!important;min-height:0!important;margin:0!important;border:1px solid #d6dde8!important;border-radius:7px!important;background:#fff!important;padding:0 6px!important;font-size:13px!important;box-shadow:none!important;flex:0 0 auto}
.tr-editor .tr-barra button{width:auto;margin:0;box-shadow:none;flex:0 0 auto}
.tr-area{min-height:110px;max-height:420px;overflow:auto;padding:12px 14px;outline:none;line-height:1.5;font-size:15px;text-align:left}
.tr-area:empty:before{content:attr(data-placeholder);color:#94a3b8}
.tr-area ul,.tr-area ol{margin:4px 0 4px 22px;padding:0}
</style>
<script>
(function () {
    var PERMITIDAS = ['B', 'STRONG', 'I', 'EM', 'U', 'BR', 'P', 'DIV', 'UL', 'OL', 'LI', 'SPAN', 'FONT'];
    function temHtml(t) { return /<(b|strong|i|em|u|br|p|div|ul|ol|li|span|font)\b[^>]*>/i.test(t); }
    // copia para o destino só as etiquetas permitidas, com estilo/cor/tamanho; o resto fica só texto
    function copiarLimpo(origem, destino) {
        origem.childNodes.forEach(function (n) {
            if (n.nodeType === 3) { destino.appendChild(document.createTextNode(n.nodeValue)); return; }
            if (n.nodeType !== 1) return;
            if (PERMITIDAS.indexOf(n.nodeName) === -1) { copiarLimpo(n, destino); return; }
            var el = document.createElement(n.nodeName.toLowerCase());
            if (n.getAttribute('style')) el.setAttribute('style', n.getAttribute('style'));
            if (n.nodeName === 'FONT') {
                if (n.getAttribute('color')) el.setAttribute('color', n.getAttribute('color'));
                if (n.getAttribute('size')) el.setAttribute('size', n.getAttribute('size'));
            }
            destino.appendChild(el);
            copiarLimpo(n, el);
        });
    }
    function textoComQuebras(t, destino) {
        t.split('\n').forEach(function (linha, i) {
            if (i) destino.appendChild(document.createElement('br'));
            destino.appendChild(document.createTextNode(linha));
        });
    }
    function montar(ta) {
        if (ta.dataset.trMontado) return; ta.dataset.trMontado = '1';
        var caixa = document.createElement('div'); caixa.className = 'tr-editor';
        var barra = document.createElement('div'); barra.className = 'tr-barra';
        var area = document.createElement('div'); area.className = 'tr-area'; area.contentEditable = 'true';
        area.setAttribute('data-placeholder', ta.getAttribute('placeholder') || '');
        if (temHtml(ta.value)) copiarLimpo(new DOMParser().parseFromString(ta.value, 'text/html').body, area);
        else if (ta.value !== '') textoComQuebras(ta.value, area);
        var obrigatorio = ta.required; ta.required = false;
        function cmd(c, v) { area.focus(); document.execCommand('styleWithCSS', false, false); document.execCommand(c, false, v || null); sinc(); }
        function botao(rotulo, titulo, fn, etiqueta) {
            var b = document.createElement('button'); b.type = 'button'; b.title = titulo;
            if (etiqueta) { var e = document.createElement(etiqueta); e.textContent = rotulo; b.appendChild(e); }
            else if (rotulo) { var ic = document.createElement('i'); ic.className = 'bi ' + rotulo; b.appendChild(ic); }
            b.addEventListener('mousedown', function (ev) { ev.preventDefault(); });
            b.addEventListener('click', fn); barra.appendChild(b); return b;
        }
        function sep() { var s = document.createElement('span'); s.className = 'tr-sep'; barra.appendChild(s); }
        botao('N', 'Negrito', function () { cmd('bold'); }, 'b');
        botao('I', 'Itálico', function () { cmd('italic'); }, 'i');
        botao('S', 'Sublinhado', function () { cmd('underline'); }, 'u');
        sep();
        ['#111827', '#b91c1c', '#1d4ed8', '#15803d', '#c2410c'].forEach(function (c) {
            var b = botao('', 'Cor do texto', function () { cmd('foreColor', c); }); b.className = 'tr-cor'; b.style.background = c;
        });
        var outra = document.createElement('input'); outra.type = 'color'; outra.title = 'Outra cor'; outra.value = '#7c3aed';
        outra.addEventListener('input', function () { cmd('foreColor', outra.value); }); barra.appendChild(outra);
        sep();
        var tam = document.createElement('select'); tam.title = 'Tamanho da letra';
        [['', 'Tamanho'], ['2', 'Pequena'], ['3', 'Normal'], ['5', 'Grande'], ['6', 'Muito grande']].forEach(function (o) { var op = document.createElement('option'); op.value = o[0]; op.textContent = o[1]; tam.appendChild(op); });
        tam.addEventListener('change', function () { if (tam.value) cmd('fontSize', tam.value); tam.value = ''; }); barra.appendChild(tam);
        sep();
        botao('bi-list-ul', 'Lista com marcas', function () { cmd('insertUnorderedList'); });
        botao('bi-list-ol', 'Lista numerada', function () { cmd('insertOrderedList'); });
        botao('bi-eraser', 'Limpar formatação', function () { cmd('removeFormat'); });
        function sinc() {
            var vazio = area.textContent.replace(/ /g, ' ').trim() === '' && !area.querySelector('li');
            ta.value = vazio ? '' : area.innerHTML;
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        }
        area.addEventListener('input', sinc);
        area.addEventListener('blur', sinc);
        area.addEventListener('paste', function (e) {
            // colar como texto simples (o Word e as páginas trazem formatação e lixo)
            e.preventDefault();
            document.execCommand('insertText', false, (e.clipboardData || window.clipboardData).getData('text/plain'));
        });
        ta.style.display = 'none';
        ta.parentNode.insertBefore(caixa, ta);
        caixa.appendChild(barra); caixa.appendChild(area);
        if (ta.form) ta.form.addEventListener('submit', function (e) {
            sinc();
            if (obrigatorio && ta.value === '') { e.preventDefault(); area.focus(); alert('Escreva a mensagem.'); }
        });
    }
    function iniciar() { document.querySelectorAll('textarea[data-texto-rico]').forEach(montar); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
</script>
    <?php
}
