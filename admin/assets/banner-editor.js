/* Editor do banner do topo (13/10/2026) — ver admin/includes/banner_editor.php.
   As pré-visualizações têm a forma que o banner tem no site (mesma fórmula do includes/header.php),
   a fotografia em "cover" e o mesmo véu. Arrastar muda o ponto focal (background-position %),
   igual em computador e telemóvel. Guarda sozinho ao largar (admin/banner.php). */
(function () {
    'use strict';
    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
    // altura do banner no site para um ecrã (largura × altura) e uma fotografia com proporção r
    function alturaBanner(lw, la, r) {
        if (lw <= 850) return clamp(Math.min(lw / r + 120, la * 0.56), 260, 520);
        return clamp(Math.min(lw / r, la * 0.52 + 45), 340, 720);
    }

    document.querySelectorAll('[data-banner-editor]').forEach(function (ed) {
        var chave = ed.dataset.chave, url = ed.dataset.url, x = +ed.dataset.x, y = +ed.dataset.y;
        var quadros = [].slice.call(ed.querySelectorAll('[data-bn-quadro]'));
        var vistas = ed.querySelector('.bn__vistas'), vazio = ed.querySelector('.bn__vazio'), estado = ed.querySelector('.bn__estado');
        var nat = { w: 1600, h: 900 }, temporizador = null;

        function avisar(t, erro) { estado.textContent = t; estado.classList.toggle('is-erro', !!erro); clearTimeout(avisar.t); avisar.t = setTimeout(function () { estado.textContent = ''; }, erro ? 6000 : 2200); }
        function pintar() {
            quadros.forEach(function (q) {
                q.style.backgroundImage = url ? 'url("' + url + '")' : '';
                q.style.backgroundPosition = x + '% ' + y + '%';
                var lw = +q.dataset.largura, la = +q.dataset.alturaEcra;
                q.style.aspectRatio = lw + ' / ' + Math.round(alturaBanner(lw, la, nat.w / nat.h));
                if (q.hasAttribute('aria-valuetext')) q.setAttribute('aria-valuetext', Math.round(x) + '% horizontal, ' + Math.round(y) + '% vertical');
            });
            vistas.hidden = !url; vazio.hidden = !!url;
        }
        function carregar() {
            if (!url) { pintar(); return; }
            var im = new Image();
            im.onload = function () { nat = { w: im.naturalWidth, h: im.naturalHeight }; pintar(); };
            im.src = url;
        }
        function enviar(campos) {
            var d = new FormData(); d.append('chave', chave);
            Object.keys(campos).forEach(function (k) { d.append(k, campos[k]); });
            return fetch('banner.php', { method: 'POST', body: d, credentials: 'same-origin' })
                .then(function (r) { return r.json().then(function (j) { if (!r.ok || !j.ok) throw new Error(j.erro || 'Erro ao guardar.'); return j; }); });
        }
        function guardarPosicao() {
            clearTimeout(temporizador);
            temporizador = setTimeout(function () {
                enviar({ acao: 'posicao', x: Math.round(x), y: Math.round(y) })
                    .then(function () { avisar('Posição guardada — já está assim no site.'); })
                    .catch(function (e) { avisar(e.message, true); });
            }, 250);
        }
        // quanto a fotografia "sobra" para fora do quadro (em px) — define quanto se pode mover
        function sobra(q) {
            var fw = q.clientWidth, fh = q.clientHeight, esc = Math.max(fw / nat.w, fh / nat.h);
            return { x: nat.w * esc - fw, y: nat.h * esc - fh };
        }
        quadros.forEach(function (q) {
            q.addEventListener('pointerdown', function (e) {
                if (!url || e.button > 0) return;
                e.preventDefault();
                var x0 = e.clientX, y0 = e.clientY, fx = x, fy = y, s = sobra(q);
                q.classList.add('is-arrastar'); q.setPointerCapture(e.pointerId);
                function mover(ev) {
                    // arrastar a foto para a direita mostra mais do lado esquerdo → % diminui
                    if (s.x > 1) x = clamp(fx - (ev.clientX - x0) / s.x * 100, 0, 100);
                    if (s.y > 1) y = clamp(fy - (ev.clientY - y0) / s.y * 100, 0, 100);
                    pintar();
                }
                function largar() {
                    q.classList.remove('is-arrastar');
                    q.removeEventListener('pointermove', mover); q.removeEventListener('pointerup', largar); q.removeEventListener('pointercancel', largar);
                    if (x !== fx || y !== fy) guardarPosicao();
                }
                q.addEventListener('pointermove', mover); q.addEventListener('pointerup', largar); q.addEventListener('pointercancel', largar);
            });
            q.addEventListener('keydown', function (e) {
                var p = e.shiftKey ? 10 : 2, m = { ArrowLeft: [-p, 0], ArrowRight: [p, 0], ArrowUp: [0, -p], ArrowDown: [0, p] }[e.key];
                if (!m || !url) return;
                e.preventDefault(); x = clamp(x + m[0], 0, 100); y = clamp(y + m[1], 0, 100); pintar(); guardarPosicao();
            });
        });
        ed.querySelector('[data-bn-centrar]').addEventListener('click', function () { x = 50; y = 50; pintar(); guardarPosicao(); });
        ed.querySelector('[data-bn-ficheiro]').addEventListener('change', function (e) {
            var f = e.target.files[0]; if (!f) return;
            avisar('A enviar a imagem…');
            enviar({ acao: 'imagem', imagem: f })
                .then(function (j) { url = j.url; x = j.x; y = j.y; carregar(); avisar('Imagem trocada — já está no site. Ajuste a posição se quiser.'); })
                .catch(function (er) { avisar(er.message, true); });
            e.target.value = '';
        });
        carregar();
    });
})();
