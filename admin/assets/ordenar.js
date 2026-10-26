/* Ordenar listas do backoffice a arrastar ou com setas + interruptores que guardam sozinhos
   (13/10/2026). Usado em Slides do topo e Galeria de fotos (álbuns).

   Marcação:
     <tbody data-ordenar="pagina.php">               → POST acao=ordenar&ids=3,1,2
       <tr data-id="3">
         <span class="sl-pega"></span> <b class="sl-num"></b>
         <button data-mover="-1"> <button data-mover="1">
         <input type="checkbox" data-alternar="visivel"   → POST acao=visivel&id=3&valor=1|0
                data-txt-on="Visível" data-txt-off="Oculto"
                data-msg-on="…" data-msg-off="…" [data-escurecer]>   (data-escurecer: linha a cinzento quando desligado)
   A resposta tem de ser JSON {ok:true} (ou {ok:false, erro:"…"}). */
(function () {
    'use strict';
    var reduz = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    var estado = document.createElement('div');
    estado.className = 'sl-estado'; estado.setAttribute('role', 'status'); estado.setAttribute('aria-live', 'polite');
    document.body.appendChild(estado);
    var temporizador = null;
    function avisar(txt, erro) {
        estado.textContent = txt; estado.classList.toggle('is-erro', !!erro); estado.classList.add('is-visivel');
        clearTimeout(temporizador); temporizador = setTimeout(function () { estado.classList.remove('is-visivel'); }, erro ? 5000 : 1800);
    }
    function enviar(url, campos) {
        var dados = new FormData();
        Object.keys(campos).forEach(function (k) { dados.append(k, campos[k]); });
        return fetch(url, { method: 'POST', body: dados, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (j) { if (!r.ok || !j.ok) throw new Error(j.erro || ''); return j; }); });
    }

    document.querySelectorAll('tbody[data-ordenar]').forEach(function (corpo) {
        var url = corpo.dataset.ordenar;
        var linhas = function () { return [].slice.call(corpo.children).filter(function (tr) { return tr.dataset.id; }); };
        function numerar() {
            var ls = linhas();
            ls.forEach(function (tr, i) {
                var n = tr.querySelector('.sl-num'); if (n) n.textContent = (i + 1) + 'º';
                var a = tr.querySelector('[data-mover="-1"]'), b = tr.querySelector('[data-mover="1"]');
                if (a) a.disabled = i === 0; if (b) b.disabled = i === ls.length - 1;
            });
        }
        function guardar(trMexido) {
            enviar(url, { acao: 'ordenar', ids: linhas().map(function (tr) { return tr.dataset.id; }).join(',') })
                .then(function () {
                    avisar('Ordem guardada');
                    if (trMexido && !reduz) { trMexido.classList.remove('sl-guardado'); void trMexido.offsetWidth; trMexido.classList.add('sl-guardado'); }
                })
                .catch(function (e) { avisar(e.message || 'Não foi possível guardar a ordem.', true); });
        }
        // as linhas deslizam para a nova posição (FLIP) em vez de saltarem
        function animar(mudar) {
            var antes = new Map(); linhas().forEach(function (tr) { antes.set(tr, tr.getBoundingClientRect().top); });
            mudar();
            if (reduz) return;
            linhas().forEach(function (tr) {
                if (tr.classList.contains('sl-a-arrastar')) return;
                var d = antes.get(tr) - tr.getBoundingClientRect().top;
                if (!d) return;
                tr.style.transition = 'none'; tr.style.transform = 'translateY(' + d + 'px)';
                requestAnimationFrame(function () { tr.style.transition = 'transform .22s cubic-bezier(.22,1,.36,1)'; tr.style.transform = ''; });
            });
        }
        corpo.addEventListener('click', function (e) {
            var b = e.target.closest('[data-mover]'); if (!b) return;
            var tr = b.closest('tr'), dir = +b.dataset.mover, alvo = dir < 0 ? tr.previousElementSibling : tr.nextElementSibling;
            if (!alvo) return;
            animar(function () { if (dir < 0) corpo.insertBefore(tr, alvo); else corpo.insertBefore(alvo, tr); });
            numerar(); guardar(tr);
            var mesmo = tr.querySelector('[data-mover="' + dir + '"]');
            (mesmo.disabled ? tr.querySelector('[data-mover="' + (-dir) + '"]') : mesmo).focus();
        });
        corpo.addEventListener('pointerdown', function (e) {
            var pega = e.target.closest('.sl-pega'); if (!pega || e.button > 0) return;
            e.preventDefault();
            var tr = pega.closest('tr'), y0 = e.clientY, desloc = 0, ordemInicial = linhas().indexOf(tr);
            tr.classList.add('sl-a-arrastar'); document.body.classList.add('sl-arrastando');
            try { pega.setPointerCapture(e.pointerId); } catch (x) {}
            function mover(ev) {
                desloc = ev.clientY - y0;
                tr.style.transform = 'translateY(' + desloc + 'px)';
                var meio = tr.getBoundingClientRect().top + tr.offsetHeight / 2;
                var seg = tr.nextElementSibling, ant = tr.previousElementSibling;
                if (seg && meio > seg.getBoundingClientRect().top + seg.offsetHeight / 2) {
                    var h = seg.offsetHeight; animar(function () { corpo.insertBefore(seg, tr); }); y0 += h; desloc -= h;
                    tr.style.transform = 'translateY(' + desloc + 'px)'; numerar();
                } else if (ant && meio < ant.getBoundingClientRect().top + ant.offsetHeight / 2) {
                    var h2 = ant.offsetHeight; animar(function () { corpo.insertBefore(tr, ant); }); y0 -= h2; desloc += h2;
                    tr.style.transform = 'translateY(' + desloc + 'px)'; numerar();
                }
            }
            function largar() {
                pega.removeEventListener('pointermove', mover); pega.removeEventListener('pointerup', largar); pega.removeEventListener('pointercancel', largar);
                document.body.classList.remove('sl-arrastando');
                tr.style.transition = reduz ? 'none' : 'transform .2s cubic-bezier(.22,1,.36,1)'; tr.style.transform = '';
                setTimeout(function () { tr.classList.remove('sl-a-arrastar'); tr.style.transition = ''; }, 200);
                if (linhas().indexOf(tr) !== ordemInicial) guardar(tr);
            }
            pega.addEventListener('pointermove', mover); pega.addEventListener('pointerup', largar); pega.addEventListener('pointercancel', largar);
        });
        // interruptores
        corpo.addEventListener('change', function (e) {
            var c = e.target.closest('[data-alternar]'); if (!c) return;
            var tr = c.closest('tr'), txt = c.parentNode.querySelector('.sl-interruptor__txt'), ligado = c.checked;
            var aplicar = function (v) {
                if (c.hasAttribute('data-escurecer')) tr.classList.toggle('sl-oculto', !v);
                if (txt) txt.textContent = v ? c.dataset.txtOn : c.dataset.txtOff;
            };
            aplicar(ligado);
            enviar(url, { acao: c.dataset.alternar, id: tr.dataset.id, valor: ligado ? '1' : '0' })
                .then(function () { avisar(ligado ? c.dataset.msgOn : c.dataset.msgOff); })
                .catch(function () { c.checked = !ligado; aplicar(!ligado); avisar('Não foi possível guardar. Tente de novo.', true); });
        });
        numerar();
    });
})();
