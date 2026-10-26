/* Backoffice — comportamento da interface (12/10/2026)
   Menu lateral: grupos (lembra os abertos), procura com atalho «/» ou Ctrl+K, recolher para ícones,
   dicas ao passar o rato/teclado, menu móvel. Sem dependências. */
(function () {
    'use strict';
    var html = document.documentElement;
    var sidebar = document.getElementById('admSidebar');
    if (!sidebar) return;
    var LS = {
        ler: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        gravar: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
    };
    var reduz = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    var movel = function () { return window.matchMedia('(max-width: 900px)').matches; };

    /* ---------- grupos ---------- */
    var grupos = [].slice.call(sidebar.querySelectorAll('.adm-grupo'));
    var abertos = [];
    try { abertos = JSON.parse(LS.ler('adm-grupos-abertos') || '[]'); } catch (e) {}
    function definirGrupo(g, abrir) {
        g.classList.toggle('is-aberto', abrir);
        g.querySelector('.adm-grupo__botao').setAttribute('aria-expanded', abrir ? 'true' : 'false');
    }
    function guardarGrupos() {
        LS.gravar('adm-grupos-abertos', JSON.stringify(grupos.filter(function (g) { return g.classList.contains('is-aberto'); }).map(function (g) { return g.dataset.grupo; })));
    }
    grupos.forEach(function (g) {
        if (abertos.indexOf(g.dataset.grupo) >= 0) definirGrupo(g, true);   // o grupo da página atual já vem aberto do PHP
        g.querySelector('.adm-grupo__botao').addEventListener('click', function () {
            if (html.classList.contains('adm-recolhido') && !movel()) {      // recolhido: expande o menu e abre este grupo
                recolher(false);
                definirGrupo(g, true);
            } else {
                definirGrupo(g, !g.classList.contains('is-aberto'));
            }
            guardarGrupos();
        });
    });
    var ativo = sidebar.querySelector('.adm-item.is-ativo');
    if (ativo) ativo.scrollIntoView({ block: 'nearest' });

    /* ---------- recolher (desktop) ---------- */
    var botaoRecolher = sidebar.querySelector('[data-adm-recolher]');
    function recolher(sim) {
        html.classList.toggle('adm-recolhido', sim);
        LS.gravar('adm-menu-recolhido', sim ? '1' : '0');
        if (botaoRecolher) {
            botaoRecolher.setAttribute('aria-label', sim ? 'Expandir o menu' : 'Recolher o menu');
            botaoRecolher.dataset.dica = sim ? 'Expandir o menu (mostrar os nomes)' : 'Recolher o menu (fica só com ícones)';
        }
        esconderDica();
    }
    if (botaoRecolher) {
        recolher(html.classList.contains('adm-recolhido'));
        botaoRecolher.addEventListener('click', function () { recolher(!html.classList.contains('adm-recolhido')); });
    }

    /* ---------- procura no menu ---------- */
    var campo = document.getElementById('admProcura');
    var vazio = sidebar.querySelector('.adm-procura__vazio');
    var itens = [].slice.call(sidebar.querySelectorAll('.adm-menu .adm-item'));
    var norm = function (s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    itens.forEach(function (a) {
        var r = a.querySelector('.adm-item__rotulo');
        a._rotulo = r ? r.textContent : '';
        a._procura = norm(a.dataset.procura + ' ' + (a.closest('.adm-grupo') ? a.closest('.adm-grupo').querySelector('.adm-grupo__rotulo').textContent : ''));
    });
    function marcar(a, termos) {
        var r = a.querySelector('.adm-item__rotulo');
        if (!r) return;
        r.textContent = a._rotulo;
        if (!termos.length) return;
        var t = norm(a._rotulo), i = -1, termo = '';
        termos.some(function (x) { i = t.indexOf(x); termo = x; return i >= 0; });
        if (i < 0) return;
        r.innerHTML = '';
        r.append(a._rotulo.slice(0, i));
        var m = document.createElement('mark'); m.textContent = a._rotulo.slice(i, i + termo.length); r.append(m);
        r.append(a._rotulo.slice(i + termo.length));
    }
    function procurar() {
        var q = norm(campo.value.trim()), termos = q ? q.split(/\s+/) : [];
        html.classList.toggle('adm-procurando', termos.length > 0);
        var n = 0;
        itens.forEach(function (a) {
            var ok = termos.every(function (t) { return a._procura.indexOf(t) >= 0; });
            a.hidden = !ok; if (ok) n++;
            marcar(a, termos);
        });
        grupos.forEach(function (g) { g.hidden = termos.length > 0 && !g.querySelector('.adm-item:not([hidden])'); });
        if (vazio) vazio.hidden = n > 0;
    }
    function primeiroVisivel() { return itens.filter(function (a) { return !a.hidden; })[0]; }
    if (campo) {
        campo.addEventListener('input', procurar);
        campo.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { var p = primeiroVisivel(); if (p && campo.value.trim()) { e.preventDefault(); location.href = p.href; } }
            else if (e.key === 'Escape') { campo.value = ''; procurar(); campo.blur(); }
            else if (e.key === 'ArrowDown') { var q = primeiroVisivel(); if (q) { e.preventDefault(); q.focus(); } }
        });
        // recolhido: clicar na lupa expande o menu e põe o cursor na procura
        sidebar.querySelector('.adm-procura').addEventListener('click', function () {
            if (html.classList.contains('adm-recolhido') && !movel()) { recolher(false); campo.focus(); }
        });
        document.addEventListener('keydown', function (e) {
            var aEscrever = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName) || document.activeElement.isContentEditable;
            if ((e.key === '/' && !aEscrever) || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k')) {
                e.preventDefault();
                if (movel()) abrirMenu(true);
                else if (html.classList.contains('adm-recolhido')) recolher(false);
                campo.focus(); campo.select();
            }
        });
    }
    // setas para cima/baixo entre entradas do menu
    sidebar.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
        if (!e.target.matches('.adm-item, .adm-grupo__botao')) return;
        var foco = [].slice.call(sidebar.querySelectorAll('.adm-menu .adm-item, .adm-menu .adm-grupo__botao')).filter(function (el) { return el.offsetParent !== null && !el.closest('[hidden]') && getComputedStyle(el.closest('.adm-grupo__itens') || el).visibility !== 'hidden'; });
        var i = foco.indexOf(e.target);
        var alvo = foco[i + (e.key === 'ArrowDown' ? 1 : -1)];
        if (alvo) { e.preventDefault(); alvo.focus(); }
        else if (e.key === 'ArrowUp' && campo) { e.preventDefault(); campo.focus(); }
    });

    /* ---------- dicas ---------- */
    var dica = document.createElement('div');
    dica.className = 'adm-dica-flutuante';
    dica.setAttribute('role', 'tooltip');
    dica.setAttribute('aria-hidden', 'true');
    document.body.appendChild(dica);
    var temporizador = null, ultimaVez = 0;
    function mostrarDica(el) {
        if (movel()) return;
        var recolhido = html.classList.contains('adm-recolhido');
        var texto = el.dataset.dica || '';
        var rotuloEl = el.querySelector('.adm-item__rotulo, .adm-grupo__rotulo');
        var rotulo = el.dataset.rotulo || (rotuloEl ? rotuloEl.textContent : '');
        if (!texto && !(recolhido && rotulo)) return;
        dica.innerHTML = '';
        if (recolhido && rotulo) { var s = document.createElement('strong'); s.textContent = rotulo; dica.appendChild(s); }
        dica.appendChild(document.createTextNode(texto));
        var r = el.getBoundingClientRect(), sb = sidebar.getBoundingClientRect();
        dica.style.left = (sb.right + 10) + 'px';
        dica.style.top = '0px';
        dica.classList.add('is-visivel');
        var h = dica.offsetHeight, y = r.top + r.height / 2 - h / 2;
        var yMax = window.innerHeight - h - 8, yFinal = Math.max(8, Math.min(y, yMax));
        dica.style.top = yFinal + 'px';
        dica.style.setProperty('--seta-y', (r.top + r.height / 2 - yFinal) + 'px');
        ultimaVez = Date.now();
    }
    function esconderDica() { clearTimeout(temporizador); if (dica) dica.classList.remove("is-visivel"); }
    sidebar.addEventListener('mouseover', function (e) {
        var el = e.target.closest('[data-dica]');
        if (!el || el.contains(e.relatedTarget)) return;
        clearTimeout(temporizador);
        // a primeira dica espera um pouco; as seguintes (a percorrer o menu) aparecem logo
        temporizador = setTimeout(function () { mostrarDica(el); }, Date.now() - ultimaVez < 900 ? 60 : 450);
    });
    sidebar.addEventListener('mouseout', function (e) {
        var el = e.target.closest('[data-dica]');
        if (el && !el.contains(e.relatedTarget)) { esconderDica(); ultimaVez = Date.now(); }
    });
    sidebar.addEventListener('focusin', function (e) {
        var el = e.target.closest('[data-dica]');
        if (el && el.matches(':focus-visible')) mostrarDica(el); else esconderDica();
    });
    sidebar.addEventListener('focusout', esconderDica);
    sidebar.querySelector('.adm-menu').addEventListener('scroll', esconderDica, { passive: true });
    window.addEventListener('blur', esconderDica);

    /* ---------- menu móvel ---------- */
    var botaoMenu = document.querySelector('[data-adm-abrir-menu]');
    function abrirMenu(sim) {
        html.classList.toggle('adm-menu-aberto', sim);
        if (botaoMenu) botaoMenu.setAttribute('aria-expanded', sim ? 'true' : 'false');
        if (sim) { var foco = sidebar.querySelector('.adm-item.is-ativo') || campo; if (foco) setTimeout(function () { foco.focus({ preventScroll: false }); }, reduz ? 0 : 200); }
        else if (botaoMenu) botaoMenu.focus();
    }
    if (botaoMenu) botaoMenu.addEventListener('click', function () { abrirMenu(!html.classList.contains('adm-menu-aberto')); });
    document.querySelectorAll('[data-adm-fechar-menu]').forEach(function (el) { el.addEventListener('click', function () { abrirMenu(false); }); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && html.classList.contains('adm-menu-aberto')) abrirMenu(false);
    });
    window.addEventListener('resize', function () { if (!movel() && html.classList.contains('adm-menu-aberto')) abrirMenu(false); });
})();

/* ==========================================================================
   PARTE 2 — ajudas genéricas nas páginas (valem para todo o backoffice)
   ========================================================================== */
(function () {
    'use strict';
    var main = document.querySelector('.admin-main');
    if (!main) return;
    var norm = function (s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    var CAMPOS = 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=button]):not([type=file]), textarea, select';

    /* 1) Campos sem rótulo (só com texto de exemplo): cria um rótulo visível por cima */
    main.querySelectorAll('form').forEach(function (form) {
        form.querySelectorAll(CAMPOS).forEach(function (c) {
            if (c.closest('.adm-filtro-tabela, td, th')) return;
            var temRotulo = (c.id && form.querySelector('label[for="' + CSS.escape(c.id) + '"]')) || c.closest('label');
            var ant = c.previousElementSibling;
            if (temRotulo || (ant && ant.tagName === 'LABEL') || c.getAttribute('aria-label')) return;
            if (c.parentNode !== form && c.parentNode.querySelector('label')) return;   // o contentor já tem um rótulo
            var texto = (c.getAttribute('placeholder') || '').replace(/[.…:]+$/, '').trim();
            if (!texto) return;
            var l = document.createElement('label');
            l.className = 'adm-rotulo-auto';
            if (!c.id) c.id = 'admCampo' + Math.random().toString(36).slice(2, 8);
            l.htmlFor = c.id;
            l.textContent = texto;
            c.parentNode.insertBefore(l, c);
        });
    });

    /* 2) Caixas de texto crescem com o conteúdo; contador quando há limite de caracteres */
    function crescer(t) { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight + 2, 900) + 'px'; }
    main.querySelectorAll('textarea').forEach(function (t) {
        if (t.closest('[data-sem-auto]')) return;
        if (t.scrollHeight > t.clientHeight) crescer(t);
        t.addEventListener('input', function () { crescer(t); });
    });
    main.querySelectorAll('input[maxlength], textarea[maxlength]').forEach(function (c) {
        var max = +c.getAttribute('maxlength');
        if (!max || max > 5000 || c.type === 'number' || c.closest('td')) return;
        var s = document.createElement('span');
        s.className = 'adm-contador';
        s.setAttribute('aria-live', 'polite');
        var upd = function () { var n = c.value.length; s.textContent = n + ' / ' + max; s.classList.toggle('is-perto', n > max * .9); };
        c.insertAdjacentElement('afterend', s); upd();
        c.addEventListener('input', upd);
    });

    /* 3) Pré-visualizar as imagens escolhidas antes de guardar */
    main.querySelectorAll('input[type=file]').forEach(function (inp) {
        if (!inp.name) return;   // ex.: editor de banner (envia sozinho)
        var aceita = (inp.getAttribute('accept') || '');
        if (aceita && !/image/.test(aceita) && !/\.(jpe?g|png|webp|gif)/i.test(aceita)) return;
        var caixa = document.createElement('div');
        caixa.className = 'adm-previa';
        caixa.hidden = true;
        inp.insertAdjacentElement('afterend', caixa);
        inp.addEventListener('change', function () {
            caixa.innerHTML = '';
            var fs = [].slice.call(inp.files || []).filter(function (f) { return /^image\//.test(f.type); }).slice(0, 12);
            fs.forEach(function (f) {
                var fig = document.createElement('figure'), img = document.createElement('img'), cap = document.createElement('figcaption');
                img.src = URL.createObjectURL(f); img.alt = ''; cap.textContent = f.name;
                fig.append(img, cap); caixa.appendChild(fig);
            });
            caixa.hidden = !fs.length;
        });
    });

    /* 4) Listas compridas: caixa para filtrar as linhas */
    main.querySelectorAll('table').forEach(function (tabela, n) {
        if (tabela.closest('form') || tabela.dataset.semFiltro !== undefined) return;
        var linhas = [].slice.call(tabela.querySelectorAll('tr')).filter(function (tr) { return tr.querySelector('td'); });
        if (linhas.length < 8) return;
        var barra = document.createElement('div');
        barra.className = 'adm-filtro-tabela';
        barra.innerHTML = '<div class="adm-filtro-tabela__campo"><i class="bi bi-funnel" aria-hidden="true"></i>' +
            '<input type="search" placeholder="Filtrar esta lista…" aria-label="Filtrar esta lista" id="admFiltro' + n + '"></div>' +
            '<span class="adm-filtro-tabela__conta" aria-live="polite"></span>';
        var ancora = tabela.closest('.table-box') || tabela;
        ancora.parentNode.insertBefore(barra, ancora);
        var campo = barra.querySelector('input'), conta = barra.querySelector('.adm-filtro-tabela__conta');
        var cols = (linhas[0].children || []).length;
        var vazio = document.createElement('tr');
        vazio.className = 'adm-filtro-vazio'; vazio.hidden = true;
        vazio.innerHTML = '<td colspan="' + cols + '">Nada encontrado com esse texto.</td>';
        linhas[linhas.length - 1].parentNode.appendChild(vazio);
        linhas.forEach(function (tr) { tr._texto = norm(tr.textContent); });
        var aplicar = function () {
            var q = norm(campo.value.trim()), termos = q ? q.split(/\s+/) : [], v = 0;
            linhas.forEach(function (tr) { var ok = termos.every(function (t) { return tr._texto.indexOf(t) >= 0; }); tr.hidden = !ok; if (ok) v++; });
            vazio.hidden = v > 0;
            conta.textContent = termos.length ? v + ' de ' + linhas.length : linhas.length + ' registos';
        };
        campo.addEventListener('input', aplicar);
        campo.addEventListener('keydown', function (e) { if (e.key === 'Escape') { campo.value = ''; aplicar(); } });
        aplicar();
    });

    /* 5) Eliminar sempre com confirmação (links eliminar_*.php que ainda não a pediam) */
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href*="eliminar"]');
        if (!a || a.hasAttribute('onclick') || a.dataset.semConfirmar !== undefined) return;
        if (!confirm('Tem a certeza que quer eliminar? Esta ação não pode ser desfeita.')) e.preventDefault();
    });

    /* 6) Alterações por guardar: aviso ao sair e botão «Guardar» sempre à mão */
    var formsGuardar = [].slice.call(main.querySelectorAll('form')).filter(function (f) {
        if ((f.method || '').toLowerCase() !== 'post' || f.dataset.semAviso !== undefined || f.closest('td, .adm-filtro-tabela')) return false;
        return f.querySelectorAll(CAMPOS + ', input[type=file], input[type=checkbox]').length >= 2;
    });
    var sujo = null;
    var barra = document.createElement('div');
    barra.className = 'adm-guardar';
    barra.setAttribute('role', 'status');
    barra.innerHTML = '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>Tem alterações por guardar</span><button type="button">Guardar</button>';
    if (formsGuardar.length) document.body.appendChild(barra);
    var visivelBotao = true;
    function atualizarBarra() { barra.classList.toggle('is-visivel', !!sujo && !visivelBotao); }
    function botaoGuardar(f) { return f.querySelector('button[type=submit], input[type=submit], button:not([type])'); }
    var obs = 'IntersectionObserver' in window ? new IntersectionObserver(function (en) {
        en.forEach(function (x) { if (sujo && botaoGuardar(sujo) === x.target) { visivelBotao = x.isIntersecting; atualizarBarra(); } });
    }) : null;
    // «sujo» = o estado atual do formulário é diferente do que tinha ao abrir a página
    // (comparar valores evita falsos avisos quando algum script da página dispara eventos sozinho)
    function estado(f) {
        var partes = [];
        f.querySelectorAll('input, textarea, select').forEach(function (c) {
            if (!c.name || c.type === 'hidden' || c.closest('.adm-filtro-tabela')) return;
            if (c.type === 'checkbox' || c.type === 'radio') partes.push(c.name + '=' + c.checked);
            else if (c.type === 'file') partes.push(c.name + '=' + (c.files ? c.files.length : 0));
            else if (c.multiple && c.options) partes.push(c.name + '=' + [].map.call(c.selectedOptions, function (o) { return o.value; }).join(','));
            else partes.push(c.name + '=' + c.value);
        });
        return partes.join('');
    }
    setTimeout(function () { formsGuardar.forEach(function (f) { f._inicial = estado(f); }); }, 400);
    formsGuardar.forEach(function (f) {
        var marcar = function (e) {
            if (e.target.closest('.adm-filtro-tabela') || f._inicial === undefined) return;
            var mudou = estado(f) !== f._inicial;
            if (mudou && sujo !== f) { sujo = f; var b = botaoGuardar(f); visivelBotao = true; if (obs && b) { obs.disconnect(); obs.observe(b); } }
            else if (!mudou && sujo === f) sujo = null;
            atualizarBarra();
        };
        f.addEventListener('input', marcar);
        f.addEventListener('change', marcar);
        f.addEventListener('submit', function () { sujo = null; atualizarBarra(); });
    });
    barra.querySelector('button').addEventListener('click', function () {
        if (!sujo) return;
        var b = botaoGuardar(sujo);
        if (sujo.requestSubmit) sujo.requestSubmit(b || undefined); else if (b) b.click(); else sujo.submit();
    });
    window.addEventListener('beforeunload', function (e) {
        if (sujo) { e.preventDefault(); e.returnValue = ''; }
    });
})();
