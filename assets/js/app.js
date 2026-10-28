/* Transições de página (View Transitions): o browser salta a animação quando o visitante clica outra vez a meio, e a
   promessa rejeitada aparecia como erro na consola («Transition was skipped») — apanha-se aqui; sem animação, aplica-se direto. */
function jfTransicao(fn) {
    try {
        var t = document.startViewTransition(fn);
        ['ready', 'finished', 'updateCallbackDone'].forEach(function (k) { if (t && t[k] && t[k].catch) t[k].catch(function () {}); });
    } catch (e) { fn(); }
}
// Header de vidro adaptativo. Corre logo (não espera pelo DOMContentLoaded): o app.js está no fim
// do <body>, por isso a página já existe e assim o hero sobe para trás da barra antes de pintar.
(function () {
    const header = document.querySelector(".site-header");
    if (!header) return;
    const corpo = document.body;

    function alturaHeader() {
        const h = header.offsetHeight;
        document.documentElement.style.setProperty("--altura-header", h + "px");
        return h;
    }

    // 1) O primeiro bloco da página é um hero com foto/fundo escuro (slider, cabeçalhos das
    //    páginas)? Então sobe para trás da barra, para o vidro mostrar a foto.
    //    Mede-se em coordenadas da PÁGINA (não do ecrã): ao recarregar a página já descida, o
    //    browser repõe o scroll e o ponto logo abaixo da barra calhava a meio do conteúdo — a
    //    foto não subia e ficava uma faixa branca por trás da barra (13/10/2026).
    const h = alturaHeader();
    const cx = window.innerWidth / 2, sy = window.scrollY;
    let hero = null;
    document.querySelectorAll(".hero-slider, section[class*='hero']").forEach(function (el) {
        const r = el.getBoundingClientRect();
        if (r.height && r.left <= cx && r.right >= cx && r.top + sy <= h + 4 && r.bottom + sy >= h + 4) hero = el;   // o mais interior
    });
    if (hero && Math.abs(hero.getBoundingClientRect().top + sy - h) < 4) {
        // Cabeçalhos de página têm o texto no topo: somar a altura da barra ao espaço de cima,
        // senão o título fica colado à barra. O slider tem o texto em baixo — não precisa.
        const somaPadding = !hero.classList.contains("hero-slider");
        const paddingBase = parseFloat(getComputedStyle(hero).paddingTop);
        const ajustarHero = function (alt) { if (somaPadding) hero.style.paddingTop = (paddingBase + alt) + "px"; };
        ajustarHero(h);
        hero.classList.add("hero-sob-header");
        // A barra pode mudar de altura depois (a letra Montserrat chega mais tarde em ligações
        // lentas, o texto do brasão parte em mais linhas…). Sem isto a foto ficava a meio
        // caminho e aparecia uma faixa branca por trás da barra.
        if ("ResizeObserver" in window) {
            let ultima = h;
            new ResizeObserver(function () {
                const alt = alturaHeader();
                if (alt !== ultima) { ultima = alt; ajustarHero(alt); agendar(); }
            }).observe(header);
        }
    }

    // 2) A barra está por cima da foto do topo (hero) ou já por cima do conteúdo?
    //    Sobre a foto: vidro a 20% e texto branco. Sobre o conteúdo (que mistura zonas claras
    //    e escuras lado a lado): vidro mais denso, para o menu se ler sempre (WCAG 1.4.3).
    let pendente = false;
    function avaliar() {
        pendente = false;
        const meio = header.getBoundingClientRect().top + header.offsetHeight / 2;
        header.style.pointerEvents = "none";
        const el = document.elementFromPoint(window.innerWidth / 2, Math.max(0, meio));
        header.style.pointerEvents = "";
        const sobreHero = !!(el && el.closest(".hero-sob-header"));
        corpo.classList.toggle("header-sobre-escuro", sobreHero);
    }
    function agendar() { if (!pendente) { pendente = true; requestAnimationFrame(avaliar); } }

    window.addEventListener("scroll", agendar, { passive: true });
    window.addEventListener("resize", function () { alturaHeader(); agendar(); });
    window.addEventListener("load", agendar);
    agendar();
})();

// Menu em pílula: uma pílula verde desliza para o item por baixo do rato/foco e volta ao
// item da página atual quando o rato sai. Só no computador (no telemóvel o menu é um painel).
document.addEventListener("DOMContentLoaded", function () {
    const nav = document.getElementById("mainNav");
    if (!nav) return;
    const itens = Array.prototype.slice.call(nav.querySelectorAll(":scope > a, :scope > .nav-dropdown > .nav-parent"));
    if (!itens.length) return;
    const indicador = document.createElement("span");
    indicador.className = "nav-indicador";
    indicador.setAttribute("aria-hidden", "true");
    nav.insertBefore(indicador, nav.firstChild);
    const desktop = window.matchMedia("(min-width: 851px)");

    // item da página atual: link direto, ou o "pai" do submenu que tem a página
    const aqui = location.pathname.replace(/\/$/, "/index.php");
    let atual = null;
    itens.forEach(function (it) {
        if (it.tagName === "A" && it.getAttribute("href") === aqui) atual = it;
        else if (it.classList.contains("nav-parent")) {
            const sub = it.parentElement.querySelector('.submenu a[href="' + aqui + '"]');
            if (sub) atual = it;
        }
    });
    if (atual && atual.tagName === "A") atual.setAttribute("aria-current", "page");

    function mover(it) {
        itens.forEach(function (x) { x.classList.toggle("nav-ativa", x === it); });
        if (!it || !desktop.matches) { indicador.classList.remove("visivel"); return; }
        const rn = nav.getBoundingClientRect(), ri = it.getBoundingClientRect();
        indicador.style.width = ri.width + "px";
        indicador.style.transform = "translate(" + (ri.left - rn.left) + "px, -50%)";
        indicador.classList.add("visivel");
    }
    itens.forEach(function (it) {
        it.addEventListener("mouseenter", function () { mover(it); });
        it.addEventListener("focus", function () { mover(it); });
    });
    nav.addEventListener("mouseleave", function () { mover(atual); });
    nav.addEventListener("focusout", function (e) { if (!nav.contains(e.relatedTarget)) mover(atual); });
    window.addEventListener("resize", function () { mover(atual); });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { mover(atual); });
    // sem transição na 1.ª colocação (não desliza do canto esquerdo ao carregar)
    indicador.style.transition = "none";
    mover(atual);
    requestAnimationFrame(function () { requestAnimationFrame(function () { indicador.style.transition = ""; }); });
});

// Ícones dos cartões animados também ao toque (no telemóvel não há "hover")
document.addEventListener("touchstart", function (e) {
    const cartao = e.target.closest && e.target.closest("[class*='card'], [class*='-item'], [class*='tile'], .content-box");
    if (!cartao) return;
    cartao.classList.remove("a-tocar");
    void cartao.offsetWidth; // reinicia a animação se tocar duas vezes seguidas
    cartao.classList.add("a-tocar");
    clearTimeout(cartao._tempoToque);
    cartao._tempoToque = setTimeout(function () { cartao.classList.remove("a-tocar"); }, 900);
}, { passive: true });

// Interruptor claro/escuro (o tema inicial é aplicado por um script no <head>, ver includes/header.php)
document.addEventListener("DOMContentLoaded", function () {
    const botao = document.getElementById("temaToggle");
    if (!botao) return;
    const raiz = document.documentElement;

    function sincronizar() {
        const escuro = raiz.getAttribute("data-tema") === "escuro";
        botao.setAttribute("aria-pressed", escuro ? "true" : "false");
        botao.title = escuro ? "Mudar para modo claro" : "Mudar para modo escuro";
    }

    botao.addEventListener("click", function () {
        const novo = raiz.getAttribute("data-tema") === "escuro" ? "claro" : "escuro";
        const mudar = function () { if (window.jfDefinirTema) window.jfDefinirTema(novo); sincronizar(); };
        const reduz = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        // Transição suave entre temas onde o browser suporta View Transitions
        if (document.startViewTransition && !reduz) jfTransicao(mudar); else mudar();
    });

    new MutationObserver(sincronizar).observe(raiz, { attributes: true, attributeFilter: ["data-tema"] });
    sincronizar();
});

document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");

    if (menuToggle && mainNav) {
        menuToggle.addEventListener("click", function () {
            mainNav.classList.toggle("open");
            document.body.classList.toggle("nav-open", mainNav.classList.contains("open"));
        });
    }
});










document.addEventListener("DOMContentLoaded", function () {
    const slides = document.querySelectorAll(".hero-slide");
    const dots = document.querySelectorAll("#sliderDots button");
    const prev = document.getElementById("prevSlide");
    const next = document.getElementById("nextSlide");
    const slider = document.getElementById("heroSlider");

    let current = 0;
    let timer = null;
    // Autoplay sempre ligado desde o início (5 em 5 segundos), até alguém carregar em pausa
    // (WCAG 2.2.2). Com "reduzir movimento" o slider continua a avançar, mas a troca é
    // instantânea (o CSS anula a transição) — sem deslizes nem zooms.
    const INTERVALO_SLIDER = 5000;
    let pausadoPeloUtilizador = false;
    let pausa = null;

    if (slider && slides.length > 1) {
        pausa = document.createElement("button");
        pausa.type = "button";
        pausa.className = "slider-pausa";
        slider.appendChild(pausa);
        pausa.addEventListener("click", function () {
            pausadoPeloUtilizador = !pausadoPeloUtilizador;
            if (pausadoPeloUtilizador) { stopSlider(); } else { startSlider(); }
            atualizarPausa();
            slider.dispatchEvent(new CustomEvent("jf-slider-pausa", { detail: { pausado: pausadoPeloUtilizador } }));
        });
    }

    function atualizarPausa() {
        if (!pausa) return;
        pausa.setAttribute("aria-label", pausadoPeloUtilizador ? "Retomar apresentação automática" : "Pausar apresentação automática");
        pausa.innerHTML = pausadoPeloUtilizador ? '<i class="bi bi-play-fill" aria-hidden="true"></i>' : '<i class="bi bi-pause-fill" aria-hidden="true"></i>';
    }
    atualizarPausa();

    function showSlide(index) {
        if (!slides.length) return;

        slides[current].classList.remove("active");
        if (dots[current]) dots[current].classList.remove("active");

        current = (index + slides.length) % slides.length;

        slides[current].classList.add("active");
        if (dots[current]) dots[current].classList.add("active");
        if (slider) slider.dispatchEvent(new CustomEvent("jf-slide", { detail: { indice: current } }));
    }

    function nextSlide() {
        showSlide(current + 1);
    }

    function prevSlide() {
        showSlide(current - 1);
    }

    function startSlider() {
        if (timer || pausadoPeloUtilizador) return;   // nunca dois temporizadores ao mesmo tempo
        if (slides.length > 1) {
            timer = setInterval(nextSlide, INTERVALO_SLIDER);
        }
    }

    function stopSlider() {
        clearInterval(timer);
        timer = null;
    }

    // Separador do browser escondido: pausa; ao voltar, retoma.
    // (Já não pausa com o rato por cima: o slider ocupa quase o ecrã todo e parecia parado.)
    document.addEventListener("visibilitychange", function () {
        if (document.hidden) {
            stopSlider();
        } else {
            startSlider();
        }
    });

    if (next) {
        next.addEventListener("click", function () {
            stopSlider();
            nextSlide();
            startSlider();
        });
    }

    if (prev) {
        prev.addEventListener("click", function () {
            stopSlider();
            prevSlide();
            startSlider();
        });
    }

    dots.forEach(function (dot) {
        dot.addEventListener("click", function () {
            stopSlider();
            showSlide(parseInt(dot.dataset.slide));
            startSlider();
        });
    });

    // arrastar com o dedo em tempo real (13/10/2026) — substitui o deslizar antigo (só no fim do gesto)
    if (slider && slides.length > 1 && typeof jfArrastar === "function") {
        slider.dataset.deslizar = "1";
        jfArrastar(slider, {
            elementos: function () { return Array.prototype.slice.call(slides); },
            atual: function () { return current; },
            // como as setas: muda e reinicia o temporizador (o vídeo, se for o caso, volta a pará-lo)
            ir: function (i) { stopSlider(); showSlide(i); startSlider(); },
            inicio: stopSlider,
            fim: function (passou) {
                if (!passou) { startSlider(); slider.dispatchEvent(new CustomEvent("jf-slide", { detail: { indice: current } })); }
            }
        });
    }

    // usado pelos slides de vídeo: o slider espera que o vídeo acabe para avançar
    if (slider) window.jfSlider = {
        seguinte: nextSlide, parar: stopSlider, retomar: startSlider,
        pausado: function () { return pausadoPeloUtilizador; }
    };

    startSlider();
});











document.addEventListener("DOMContentLoaded", function () {
    const dropdowns = document.querySelectorAll(".nav-dropdown");

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

                dropdowns.forEach(function (item) {
                    if (item !== dropdown) {
                        fecharDropdown(item);
                    }
                });

                dropdown.classList.toggle("open", vaiAbrir);
                button.setAttribute("aria-expanded", vaiAbrir ? "true" : "false");
            });
        }
    });

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".nav-dropdown")) {
            dropdowns.forEach(fecharDropdown);
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            dropdowns.forEach(fecharDropdown);
        }
    });
});














document.addEventListener("DOMContentLoaded", function () {
    /* ---------- Revelação ao scroll (motion, 10/2026) ---------------------------------
       Um só motor (IntersectionObserver), sem bibliotecas. Marca os elementos via JS — sem JS,
       ou com "reduzir movimento", tudo aparece logo. Regras (skill jf-motion):
       - nada acima da dobra anima (o primeiro ecrã aparece de imediato);
       - GRELHAS: os cartões entram em cascata DENTRO DA SUA LINHA (esquerda → direita, 70 ms
         entre cartões) — uma linha nova começa sempre do zero, por isso nunca há esperas longas;
       - variantes: "up" (por omissão), "zoom" (fotografias), "left"/"right" (linhas do tempo,
         alternadas); deslocações de 16–24 px e durações ≤ 600 ms;
       - cada elemento anima uma única vez. */
    const semAnimacao = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    // Contentores cujos filhos entram em cascata — [seletor, variante]
    const GRELHAS = [
        [".imersivos__grelha", "zoom"], [".cu-grelha", "up"], [".editorial__grelha", "up"], [".pontos-vitrine", "zoom"],
        [".proximidade__grelha", "up"], [".stats-grid", "up"], [".welcome-highlights", "up"],
        [".services-grid", "up"], [".transp-bento", "up"], [".premium-news-events-grid", "up"],
        [".noticias-clean-grid", "up"], [".eventos-grid-insane", "up"], [".pontos-grid-insane", "zoom"], [".comercio-grid-insane", "up"],
        [".assoc-grid-insane", "up"], [".freguesia-content-grid", "up"], [".mem-arquivo__grelha", "zoom"], [".mem-lendas__grelha", "up"],
        [".mem-fe__grelha", "up"], [".mem-tempo__grelha", "up"], [".mem-marcos", "up"], [".historia-heraldica-grid", "up"],
        [".docs-grid-premium", "up"], [".trans-live-strip", "up"], [".transparencia-grid", "up"], [".galeria-publica-grid", "zoom"],
        [".ct-rapidos", "up"], [".faq-list", "up"], [".comp-stats", "up"], [".comp-grid", "up"], [".compete-stats", "up"],
        [".heraldica-grid", "up"], [".links-uteis__lista", "up"], [".quick-access-grid", "up"], [".sess-grid", "up"],
        // 15/10/2026 (ponto 2b): páginas que ainda tinham blocos sem entrada
        [".executivo-grid-premium", "up"], [".historia-grid", "up"], [".trans-analytics-grid", "up"], [".trans-cta-grid", "up"],
        [".cd-info-grid", "up"], [".dpo-rights", "up"]
    ];
    // Elementos soltos — [seletor, variante]
    const SOLTOS = [
        [".pres__cartao", "up"], [".section-heading", "up"], [".content-box", "up"], [".docs-cta", "up"], [".premium-list-block", "up"],
        [".gshow", "zoom"], [".farmacia-servico", "up"], [".util-tempo", "up"], [".cu-emergencia", "up"],
        [".ct-form", "up"], [".ct-mapa-shell", "zoom"], [".res-form", "up"], [".editorial__filtros", "up"],
        // 15/10/2026 (ponto 2b)
        [".rh-section", "up"], [".comp-section", "up"], [".sess-section-card", "up"], [".compete-card", "up"], [".func-card", "up"],
        [".docs-list-card", "up"], [".cd-panel", "up"], [".cd-cta", "up"], [".lr-panel", "up"], [".lr-cta", "up"], [".map-footer-info", "up"],
        [".mem-sec", "up"], [".localidades-section", "up"], [".freguesia-final-cta", "up"], [".historia-final", "up"]
    ];
    // Linhas do tempo: itens alternam esquerda/direita
    const LINHAS_TEMPO = [".timeline-wrapper"];

    const foraDoPrimeiroEcra = function (el) { return el.getBoundingClientRect().top > window.innerHeight * 0.92; };
    const excluido = function (el) { return el.closest(".site-header, .rm-footer, .rodape, .jlb, [data-zona-ajax].zona-entrou"); };
    function marcar(el, variante) {
        if (el.classList.contains("reveal") || excluido(el) || !foraDoPrimeiroEcra(el)) return;
        el.classList.add("reveal");
        if (variante !== "up") el.dataset.reveal = variante;
    }

    if (!semAnimacao) {
        GRELHAS.forEach(function (g) {
            document.querySelectorAll(g[0]).forEach(function (grelha) {
                // carrossel com deslize lateral (ex.: telemóvel): os cartões à direita nunca entram no
                // scroll vertical — anima o carrossel inteiro, não cada cartão
                const ox = getComputedStyle(grelha).overflowX;
                if ((ox === "auto" || ox === "scroll") && grelha.scrollWidth > grelha.clientWidth + 4) { marcar(grelha, "up"); return; }
                Array.prototype.forEach.call(grelha.children, function (filho) {
                    if (filho.offsetWidth && !filho.hidden) { marcar(filho, g[1]); filho.dataset.revealCascata = "1"; }
                });
            });
        });
        SOLTOS.forEach(function (s) { document.querySelectorAll(s[0]).forEach(function (el) { marcar(el, s[1]); }); });
        LINHAS_TEMPO.forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (t) {
                Array.prototype.forEach.call(t.children, function (filho, i) { marcar(filho, i % 2 ? "right" : "left"); });
            });
        });
        // compatibilidade: blocos antigos que já eram animados
        document.querySelectorAll(".card, .service-card, .quick-card, .quick-action-card, .premium-news-card, .premium-event-card, .welcome-grid > *, .contact-grid > *").forEach(function (el) { marcar(el, "up"); });
    }

    // Posição do cartão dentro da sua linha (0, 1, 2…) — para a cascata
    function posicaoNaLinha(el) {
        if (!el.dataset.revealCascata || !el.parentElement) return 0;
        const topo = el.offsetTop;
        let n = 0;
        for (const irmao of el.parentElement.children) {
            if (irmao === el) break;
            if (!irmao.hidden && Math.abs(irmao.offsetTop - topo) < 8) n++;
        }
        return Math.min(n, 5);
    }

    const reveals = document.querySelectorAll(".reveal");

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                const alvo = entry.target;
                const delay = posicaoNaLinha(alvo) * 70;
                alvo.style.transitionDelay = delay + "ms";
                alvo.classList.add("active");
                observer.unobserve(alvo);
                // o atraso só serve para a entrada; depois sai (senão o hover reagia atrasado)
                setTimeout(function () { alvo.style.transitionDelay = ""; }, delay + 700);
            });
        }, {
            threshold: 0.12,
            rootMargin: "0px 0px -60px 0px"
        });

        reveals.forEach(function (el) {
            observer.observe(el);
        });
    } else {
        // browsers sem IntersectionObserver (muito antigos): mostra tudo logo, sem animação
        reveals.forEach(function (el) { el.classList.add("active"); });
    }

    // Header com efeito de scroll (sombra/blur) — no máximo uma vez por fotograma
    const siteHeader = document.querySelector(".site-header");

    if (siteHeader) {
        let headerPendente = false;
        function toggleHeaderScroll() {
            headerPendente = false;
            siteHeader.classList.toggle("is-scrolled", window.scrollY > 12);
        }

        window.addEventListener("scroll", function () {
            if (!headerPendente) { headerPendente = true; requestAnimationFrame(toggleHeaderScroll); }
        }, { passive: true });
        toggleHeaderScroll();
    }
});













document.addEventListener("DOMContentLoaded", function () {
    const inputImagem = document.getElementById("imagemPedido");
    const fileName = document.getElementById("fileNamePedido");
    const preview = document.getElementById("previewPedido");
    const form = document.querySelector(".form-publico");
    const btnEnviar = document.getElementById("btnEnviarPedido");

    if (inputImagem && fileName && preview) {
        inputImagem.addEventListener("change", function () {
            const file = this.files[0];

            if (!file) {
                fileName.textContent = "JPG, PNG ou WEBP";
                preview.style.display = "none";
                preview.innerHTML = "";
                return;
            }

            fileName.textContent = file.name;

            if (file.type.startsWith("image/")) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview da imagem">`;
                    preview.style.display = "block";
                };

                reader.readAsDataURL(file);
            }
        });
    }

    if (form && btnEnviar) {
        form.addEventListener("submit", function () {
            btnEnviar.classList.add("loading");
        });
    }
});










const input = document.getElementById("imagensPedido");
const preview = document.getElementById("previewPedido");

if (input && preview) {
    input.addEventListener("change", function () {
        preview.innerHTML = "";

        Array.from(this.files).forEach(file => {
            if (!file.type.startsWith("image/")) return;

            const reader = new FileReader();

            reader.onload = function (e) {
                const img = document.createElement("img");
                img.src = e.target.result;
                preview.appendChild(img);
            };

            reader.readAsDataURL(file);
        });

        preview.style.display = "flex";
    });
}


// Slider: deslizar com o dedo (usa as setas existentes, por isso serve qualquer versão do slider).
// Num telemóvel real o browser pode ficar com o gesto e mandar "touchcancel" em vez de "touchend":
// por isso guarda-se a última posição no touchmove e decide-se em ambos os casos.
document.addEventListener("DOMContentLoaded", function () {
    const slider = document.getElementById("heroSlider");
    const next = document.getElementById("nextSlide");
    const prev = document.getElementById("prevSlide");
    if (!slider || !next || !prev || slider.dataset.deslizar === "1") return;
    slider.dataset.deslizar = "1";

    let inicioX = null, inicioY = null, ultimoX = null, ultimoY = null;

    slider.addEventListener("touchstart", function (e) {
        if (e.touches.length !== 1) { inicioX = null; return; }
        inicioX = ultimoX = e.touches[0].clientX;
        inicioY = ultimoY = e.touches[0].clientY;
    }, { passive: true });

    slider.addEventListener("touchmove", function (e) {
        if (inicioX === null) return;
        ultimoX = e.touches[0].clientX;
        ultimoY = e.touches[0].clientY;
    }, { passive: true });

    function terminar(e) {
        if (inicioX === null) return;
        const t = e.changedTouches && e.changedTouches[0];
        const x = t ? t.clientX : ultimoX;
        const y = t ? t.clientY : ultimoY;
        const dx = x - inicioX;
        const dy = y - inicioY;
        inicioX = null;
        if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy) * 1.2) return;
        (dx < 0 ? next : prev).click();
    }

    slider.addEventListener("touchend", terminar, { passive: true });
    slider.addEventListener("touchcancel", terminar, { passive: true });
});


// Menu em telemóvel: "Recursos Humanos" abre as opções em vez de navegar logo
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".dropdown-submenu > .submenu-main").forEach(function (link) {
        if (link.dataset.submenu === "1") return;
        link.dataset.submenu = "1";
        link.addEventListener("click", function (e) {
            if (!window.matchMedia("(max-width: 850px)").matches) return;
            e.preventDefault();
            const sub = link.parentElement;
            const vaiAbrir = !sub.classList.contains("open");
            sub.classList.toggle("open", vaiAbrir);
            link.setAttribute("aria-expanded", vaiAbrir ? "true" : "false");
        });
    });
});


/* ==========================================================================
   FASE 1 DO PORTAL (09/10/2026) — skill jf-portal-visual
   Pesquisa Spotlight (Ctrl+K), voltar ao topo com anel de progresso, pontos de secção
   (ScrollSpy) e filtros das notícias sem recarregar. Tudo CORE e sem dependências.
   ========================================================================== */

function jfReduzMovimento() {
    return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
}

/* ---------- Arrastar com o dedo em tempo real (13/10/2026) ----------------------------------
   Motor comum dos slideshows, galerias, lightbox e quadro rotativo: com o dedo, a
   imagem atual segue o dedo e a seguinte/anterior entra pelo lado ao mesmo tempo. Ao largar:
   passa se arrastou mais de 1/4 da largura ou com um gesto rápido; senão volta ao sítio — sempre
   com uma animação suave (só transform → 60 fps). Gestos verticais continuam a fazer scroll da
   página (decide-se nos primeiros 8 px). O clique que vem a seguir a um arrasto é anulado.
   Com o rato nada muda (setas, pontinhos e teclado continuam iguais).

   jfArrastar(area, {
       elementos: () => [el, el, …],     // camadas empilhadas (slides/fotos)
       atual: () => índice à vista,
       ir: (indice, sentido) => muda de facto (classes do componente; sem transições),
       vizinho: (sentido) => elemento (opcional — ex.: imagem «fantasma» do lightbox),
       pode: () => true|false,          // opcional (ex.: sem zoom)
       inicio / fim: () => …            // opcional (parar/retomar a rotação automática)
   }) */
function jfArrastar(area, cfg) {
    if (!area || area.dataset.jfArrasto) return;
    area.dataset.jfArrasto = "1";
    const DUR = jfReduzMovimento() ? 0 : 340;
    const CURVA = "cubic-bezier(.22, 1, .36, 1)";
    let id = null, x0 = 0, y0 = 0, dx = 0, eixo = null, largura = 1, hist = [];
    let a = null, b = null, sentido = 0, ocupado = false, arrastou = false;

    const estilo = function (el, tx, trans) {
        if (!el) return;
        el.style.setProperty("transition", trans ? "transform " + DUR + "ms " + CURVA : "none", "important");
        el.style.setProperty("transform", "translate3d(" + tx + "px,0,0)", "important");
    };
    const limpar = function (el) {
        if (!el) return;
        ["transition", "transform", "opacity", "visibility", "z-index"].forEach(function (p) { el.style.removeProperty(p); });
    };
    function vizinhoDe(s) {
        if (cfg.vizinho) return cfg.vizinho(s);
        const els = cfg.elementos();
        if (els.length < 2) return null;
        return els[(cfg.atual() + s + els.length) % els.length];
    }
    function mostrarVizinho(s) {
        if (s === sentido) return;
        if (b && b !== a) limpar(b);
        sentido = s;
        b = vizinhoDe(s);
        if (!b || b === a) { b = null; return; }
        b.style.setProperty("opacity", "1", "important");
        b.style.setProperty("visibility", "visible", "important");
        b.style.setProperty("z-index", getComputedStyle(a).zIndex === "auto" ? "1" : getComputedStyle(a).zIndex, "important");
        b.removeAttribute("loading");
    }
    function desenhar() {
        // sem vizinho (uma só foto): resistência elástica
        const d = b ? dx : dx / 3;
        estilo(a, d, false);
        if (b) estilo(b, d + (sentido > 0 ? largura : -largura), false);
    }

    /* Entrada por EVENTOS DE TOQUE (14/10/2026): no iPhone o Safari tomava conta do gesto a meio e
       cancelava os pointer events sem mandar o «largar» — a imagem ficava parada a meio e o slider
       deixava de responder. Com touchmove não-passivo o gesto fica do site logo que é horizontal;
       qualquer fim (touchend, touchcancel, perda do dedo) termina o arrasto, e um vigia garante
       que nunca fica preso. */
    let vigia = null;
    const ponto = function (e) { const t = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0]); return t ? { x: t.clientX, y: t.clientY } : null; };
    area.addEventListener("touchstart", function (e) {
        if (ocupado || id !== null || e.touches.length !== 1) { if (id !== null && e.touches.length > 1) largar(true); return; }
        if (e.target.closest("input, textarea, select, [data-sem-arrasto]")) return;
        if (cfg.pode && !cfg.pode()) return;
        if (!cfg.elementos().length) return;
        const p = ponto(e);
        id = 1; x0 = p.x; y0 = p.y; dx = 0; eixo = null; arrastou = false;
        hist = [[e.timeStamp || performance.now(), p.x]];
    }, { passive: true });
    area.addEventListener("touchmove", function (e) {
        if (id === null) return;
        const p = ponto(e); if (!p) return;
        const mx = p.x - x0, my = p.y - y0;
        if (!eixo) {
            if (Math.abs(mx) < 8 && Math.abs(my) < 8) return;
            eixo = Math.abs(mx) > Math.abs(my) ? "x" : "y";
            if (eixo === "y") { id = null; return; }        // scroll vertical: o browser trata
            a = cfg.elementos()[cfg.atual()];
            largura = area.clientWidth || 1;
            sentido = 0; b = null;
            area.classList.add("jf-a-arrastar");
            arrastou = true;
            if (cfg.inicio) cfg.inicio();
        }
        if (e.cancelable) e.preventDefault();               // o gesto horizontal é do site (iOS não o cancela)
        dx = mx;
        mostrarVizinho(dx < 0 ? 1 : -1);
        hist.push([e.timeStamp || performance.now(), p.x]);
        if (hist.length > 6) hist.shift();
        desenhar();
        clearTimeout(vigia);
        vigia = setTimeout(function () { if (id !== null && eixo === "x") largar(false); }, 1500);
    }, { passive: false });
    area.addEventListener("touchend", function () { if (id !== null) largar(false); }, { passive: true });
    area.addEventListener("touchcancel", function () { if (id !== null) largar(false); }, { passive: true });

    // cancelado: decide pela distância percorrida (passa ou volta), nunca fica a meio
    function largar(cancelar) {
        clearTimeout(vigia);
        id = null;
        if (eixo !== "x" || !a) return;
        setTimeout(function () { arrastou = false; }, 450);
        // velocidade nos últimos ~100 ms (px/ms)
        const ult = hist[hist.length - 1], pri = hist.find(function (h) { return ult[0] - h[0] < 100; }) || hist[0];
        const v = (ult[1] - pri[1]) / Math.max(1, ult[0] - pri[0]);
        const passa = !!b && !cancelar &&
            (Math.abs(dx) > largura * 0.25 || (Math.abs(v) > 0.35 && Math.sign(v) === Math.sign(dx)));
        ocupado = true;
        const aa = a, bb = b, s = sentido;
        if (passa) {
            estilo(aa, s > 0 ? -largura : largura, true);
            estilo(bb, 0, true);
        } else {
            estilo(aa, 0, true);
            if (bb) estilo(bb, s > 0 ? largura : -largura, true);
        }
        setTimeout(function () {
            try { if (passa) cfg.ir((cfg.atual() + s + cfg.elementos().length) % cfg.elementos().length, s); } catch (x) {}
            limpar(aa); limpar(bb);
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    area.classList.remove("jf-a-arrastar");
                    ocupado = false;
                    if (cfg.fim) cfg.fim(passa);
                });
            });
        }, DUR + 30);
        a = b = null; sentido = 0;
    }
    // o clique que o browser manda no fim do arrasto não abre links/lightbox
    area.addEventListener("click", function (e) {
        if (arrastou) { e.preventDefault(); e.stopPropagation(); arrastou = false; }
    }, true);
}

function jfSemAcentos(t) {
    return (t || "").toString().toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
}


/* ---------- Pesquisa Spotlight ------------------------------------------------
   Abre com Ctrl+K / Cmd+K, com "/" (fora de campos de texto) e com qualquer botão
   .js-spotlight. Os serviços (páginas fixas) filtram-se logo no browser; o conteúdo
   (notícias, eventos, PDFs, contactos úteis…) vem de ajax/spotlight.php.
   Acessível: role="dialog" + aria-modal, combobox com listbox e aria-activedescendant,
   setas/Enter/Esc, foco preso dentro e devolvido ao botão que abriu. */
(function () {
    const SERVICOS = [
        ["Comunicar uma ocorrência", "/pedidos.php", "bi-exclamation-triangle-fill", "ocorrencia buraco lampada iluminacao lixo avaria problema reportar estrada passeio"],
        ["Acompanhar um pedido ou ocorrência", "/consultar-pedido.php", "bi-search", "consultar estado codigo acompanhar pedido"],
        ["Contactos e horário da Junta", "/contactos.php", "bi-telephone-fill", "telefone email morada horario atendimento contacto"],
        ["Executivo da Junta", "/executivo.php", "bi-people-fill", "presidente secretario tesoureiro executivo"],
        ["Assembleia de Freguesia", "/assembleia-composicao.php", "bi-bank2", "assembleia mesa vogais sessoes"],
        ["Documentos da Freguesia", "/freguesia-documentos.php", "bi-file-earmark-text-fill", "documentos regulamentos orcamento contas pdf"],
        ["Documentos da Assembleia (atas e editais)", "/assembleia-documentos.php", "bi-file-earmark-text-fill", "atas editais convocatorias deliberacoes"],
        ["Transparência", "/transparencia.php", "bi-bar-chart-fill", "transparencia contas orcamento prestacao"],
        ["Canal de Denúncias", "/canal-denuncias.php", "bi-shield-lock-fill", "denuncia corrupcao irregularidade lei 93/2021"],
        ["Livro de Reclamações", "/livro-reclamacoes.php", "bi-journal-text", "reclamacao queixa livro"],
        ["Proteção de Dados (RGPD)", "/protecao-dados.php", "bi-shield-check", "rgpd dpo dados pessoais privacidade"],
        ["Procedimentos concursais (emprego)", "/procedimentos-concursais.php", "bi-briefcase-fill", "emprego concurso vaga recrutamento recursos humanos"],
        ["Contratação pública", "/contratacao-publica.php", "bi-file-earmark-ruled-fill", "contratos ajuste direto contratacao"],
        ["Notícias", "/noticias.php", "bi-newspaper", "noticias atualidade"],
        ["Agenda de eventos", "/eventos.php", "bi-calendar-event-fill", "eventos agenda festas feira"],
        ["Mapa interativo", "/mapa.php", "bi-map-fill", "mapa como chegar localizacao"],
        ["Pontos de interesse", "/pontos.php", "bi-geo-alt-fill", "patrimonio visitar turismo igreja serra"],
        ["Associações", "/associacoes.php", "bi-people", "associacoes clubes coletividades banda"],
        ["Comércio local", "/comercio.php", "bi-shop", "comercio restaurantes cafes lojas economia"],
        ["História e heráldica", "/historia.php", "bi-book-half", "historia heraldica brasao bandeira"],
        ["Galeria de imagens", "/galeria.php", "bi-images", "fotografias galeria fotos"],
        ["Perguntas frequentes", "/faq.php", "bi-question-circle-fill", "ajuda duvidas faq perguntas"]
    ];
    const SUGERIDOS = [0, 2, 5, 8, 14, 16];

    let raiz = null, entrada = null, lista = null, estado = null, origem = null;
    let itens = [], ativo = -1, pedido = 0, temporizador = null;

    function criar() {
        raiz = document.createElement("div");
        raiz.className = "spotlight";
        raiz.hidden = true;
        raiz.innerHTML =
            '<div class="spotlight__fundo" data-fechar></div>' +
            '<div class="spotlight__painel" role="dialog" aria-modal="true" aria-labelledby="spotlightTitulo">' +
                '<h2 class="sr-only" id="spotlightTitulo">Pesquisar no site</h2>' +
                '<div class="spotlight__campo">' +
                    '<i class="bi bi-search" aria-hidden="true"></i>' +
                    '<label class="sr-only" for="spotlightEntrada">O que precisa de resolver hoje?</label>' +
                    '<input id="spotlightEntrada" type="search" autocomplete="off" spellcheck="false" ' +
                        'placeholder="O que precisa de resolver hoje?" role="combobox" aria-expanded="true" ' +
                        'aria-controls="spotlightLista" aria-autocomplete="list">' +
                    '<button type="button" class="spotlight__fechar" data-fechar aria-label="Fechar pesquisa">Esc</button>' +
                '</div>' +
                '<div class="spotlight__resultados">' +
                    '<ul id="spotlightLista" role="listbox" aria-label="Resultados"></ul>' +
                    '<p class="spotlight__estado" aria-live="polite"></p>' +
                '</div>' +
                '<div class="spotlight__rodape" aria-hidden="true">' +
                    '<span><kbd>↑</kbd><kbd>↓</kbd> navegar</span><span><kbd>Enter</kbd> abrir</span><span><kbd>Esc</kbd> fechar</span>' +
                '</div>' +
            '</div>';
        document.body.appendChild(raiz);
        entrada = raiz.querySelector("input");
        lista = raiz.querySelector("ul");
        estado = raiz.querySelector(".spotlight__estado");

        raiz.addEventListener("click", function (e) {
            if (e.target.closest("[data-fechar]")) fechar();
            const op = e.target.closest("[role=option]");
            if (op) abrirItem(itens[+op.dataset.i]);
        });
        lista.addEventListener("mousemove", function (e) {
            const op = e.target.closest("[role=option]");
            if (op && +op.dataset.i !== ativo) marcar(+op.dataset.i, false);
        });
        entrada.addEventListener("input", function () {
            const q = entrada.value.trim();
            mostrar(servicosPara(q), q ? "A procurar…" : "");
            clearTimeout(temporizador);
            if (q.length >= 2) temporizador = setTimeout(function () { buscar(q); }, 180);
        });
        raiz.addEventListener("keydown", teclas);
    }

    function servicosPara(q) {
        const n = jfSemAcentos(q);
        const escolhidos = n
            ? SERVICOS.filter(function (s) { return jfSemAcentos(s[0] + " " + s[3]).indexOf(n) !== -1; })
            : SUGERIDOS.map(function (i) { return SERVICOS[i]; });
        return escolhidos.slice(0, n ? 5 : 6).map(function (s) {
            return { grupo: n ? "Serviços e páginas" : "Mais procurados", titulo: s[0], link: s[1], icone: s[2] };
        });
    }

    function buscar(q) {
        const meu = ++pedido;
        fetch("/ajax/spotlight.php?q=" + encodeURIComponent(q))
            .then(function (r) { return r.json(); })
            .then(function (dados) {
                if (meu !== pedido || entrada.value.trim() !== q) return;
                const todos = servicosPara(q).concat(dados.resultados || []);
                mostrar(todos, todos.length ? "" : "Sem resultados para «" + q + "». Experimente outra palavra ou ligue para a Junta.");
            })
            .catch(function () {
                if (meu === pedido) estado.textContent = "Não foi possível pesquisar agora. Tente novamente.";
            });
    }

    function mostrar(novos, msg) {
        itens = novos;
        lista.innerHTML = "";
        let grupoAtual = null;
        novos.forEach(function (it, i) {
            if (it.grupo !== grupoAtual) {
                grupoAtual = it.grupo;
                const g = document.createElement("li");
                g.className = "spotlight__grupo";
                g.setAttribute("role", "presentation");
                g.textContent = it.grupo;
                lista.appendChild(g);
            }
            const li = document.createElement("li");
            li.id = "spot-op-" + i;
            li.className = "spotlight__item";
            li.setAttribute("role", "option");
            li.setAttribute("aria-selected", "false");
            li.dataset.i = i;
            const icone = document.createElement("span");
            icone.className = "spotlight__icone";
            icone.innerHTML = '<i class="bi ' + (it.icone || "bi-arrow-right") + '" aria-hidden="true"></i>';
            const texto = document.createElement("span");
            texto.className = "spotlight__texto";
            const t = document.createElement("strong"); t.textContent = it.titulo;
            texto.appendChild(t);
            if (it.descricao) { const d = document.createElement("small"); d.textContent = it.descricao; texto.appendChild(d); }
            li.appendChild(icone); li.appendChild(texto);
            lista.appendChild(li);
        });
        estado.textContent = msg || (novos.length ? novos.length + " resultado" + (novos.length === 1 ? "" : "s") : "");
        marcar(novos.length ? 0 : -1, false);
    }

    function marcar(i, rolar) {
        const antigo = lista.querySelector("[aria-selected=true]");
        if (antigo) antigo.setAttribute("aria-selected", "false");
        ativo = i;
        if (i < 0) { entrada.removeAttribute("aria-activedescendant"); return; }
        const el = document.getElementById("spot-op-" + i);
        if (!el) return;
        el.setAttribute("aria-selected", "true");
        entrada.setAttribute("aria-activedescendant", el.id);
        if (rolar) el.scrollIntoView({ block: "nearest" });
    }

    function abrirItem(it) {
        if (!it) return;
        if (it.novo_separador) { window.open(it.link, "_blank", "noopener"); fechar(); }
        else window.location.href = it.link;
    }

    function teclas(e) {
        if (e.key === "Escape") { e.preventDefault(); fechar(); }
        else if (e.key === "ArrowDown") { e.preventDefault(); if (itens.length) marcar((ativo + 1) % itens.length, true); }
        else if (e.key === "ArrowUp") { e.preventDefault(); if (itens.length) marcar((ativo - 1 + itens.length) % itens.length, true); }
        else if (e.key === "Enter" && e.target === entrada) { e.preventDefault(); abrirItem(itens[ativo]); }
        else if (e.key === "Tab") {
            // foco preso entre o campo e o botão fechar
            const focaveis = [entrada, raiz.querySelector(".spotlight__fechar")];
            const i = focaveis.indexOf(document.activeElement);
            e.preventDefault();
            focaveis[(i + (e.shiftKey ? -1 : 1) + focaveis.length) % focaveis.length].focus();
        }
    }

    function abrir(botao) {
        if (!raiz) criar();
        origem = botao || document.activeElement;
        raiz.hidden = false;
        document.documentElement.classList.add("spotlight-aberto");
        document.querySelectorAll(".js-spotlight").forEach(function (b) { b.setAttribute("aria-expanded", "true"); });
        entrada.value = "";
        mostrar(servicosPara(""), "");
        requestAnimationFrame(function () { raiz.classList.add("spotlight--visivel"); entrada.focus(); });
    }

    function fechar() {
        if (!raiz || raiz.hidden) return;
        raiz.classList.remove("spotlight--visivel");
        document.documentElement.classList.remove("spotlight-aberto");
        document.querySelectorAll(".js-spotlight").forEach(function (b) { b.setAttribute("aria-expanded", "false"); });
        setTimeout(function () { raiz.hidden = true; }, jfReduzMovimento() ? 0 : 180);
        if (origem && origem.focus) origem.focus();
    }

    window.jfAbrirPesquisa = abrir;

    document.addEventListener("click", function (e) {
        const b = e.target.closest(".js-spotlight");
        if (b) { e.preventDefault(); abrir(b); }
    });
    document.addEventListener("keydown", function (e) {
        const t = e.target;
        const aEscrever = t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName));
        if ((e.ctrlKey || e.metaKey) && (e.key === "k" || e.key === "K")) { e.preventDefault(); raiz && !raiz.hidden ? fechar() : abrir(); }
        else if (e.key === "/" && !aEscrever && (!raiz || raiz.hidden)) { e.preventDefault(); abrir(); }
    });

    // Mostrar o atalho certo (⌘K no Mac, Ctrl K nos outros)
    document.addEventListener("DOMContentLoaded", function () {
        const mac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
        document.querySelectorAll("[data-atalho-pesquisa]").forEach(function (k) { k.textContent = mac ? "⌘ K" : "Ctrl K"; });
    });
})();


/* ---------- Voltar ao topo com anel de progresso ---------------------------- */
document.addEventListener("DOMContentLoaded", function () {
    const botao = document.createElement("button");
    botao.type = "button";
    botao.className = "voltar-topo";
    botao.setAttribute("aria-label", "Voltar ao topo da página");
    botao.innerHTML =
        '<svg class="voltar-topo__anel" viewBox="0 0 48 48" aria-hidden="true">' +
            '<circle class="voltar-topo__trilho" cx="24" cy="24" r="21"></circle>' +
            '<circle class="voltar-topo__progresso" cx="24" cy="24" r="21"></circle>' +
        '</svg>' +
        '<i class="bi bi-arrow-up" aria-hidden="true"></i>';
    document.body.appendChild(botao);

    const anel = botao.querySelector(".voltar-topo__progresso");
    const PERIMETRO = 2 * Math.PI * 21;
    anel.style.strokeDasharray = PERIMETRO;

    let pendente = false;
    function atualizar() {
        pendente = false;
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const p = max > 0 ? Math.min(1, window.scrollY / max) : 0;
        anel.style.strokeDashoffset = PERIMETRO * (1 - p);
        botao.classList.toggle("voltar-topo--visivel", window.scrollY > 300);
    }
    window.addEventListener("scroll", function () { if (!pendente) { pendente = true; requestAnimationFrame(atualizar); } }, { passive: true });
    window.addEventListener("resize", atualizar);
    atualizar();

    botao.addEventListener("click", function () {
        window.scrollTo({ top: 0, behavior: jfReduzMovimento() ? "auto" : "smooth" });
        // leva também o foco do teclado para o início do conteúdo
        const main = document.getElementById("conteudo-principal");
        if (main) setTimeout(function () { main.focus({ preventScroll: true }); }, jfReduzMovimento() ? 0 : 500);
    });
});


/* ---------- Pontos de secção (ScrollSpy) -------------------------------------
   Monta-se sozinho a partir das secções da página que têm título (h1/h2). Uma secção pode
   escolher o nome com data-spy="Nome" ou ficar de fora com data-spy="nao". Só aparece em
   ecrãs largos e em páginas com pelo menos 4 secções. */
document.addEventListener("DOMContentLoaded", function () {
    const main = document.getElementById("conteudo-principal");
    if (!main || !("IntersectionObserver" in window)) return;

    const seccoes = Array.prototype.filter.call(main.querySelectorAll("section"), function (s) {
        if (s.dataset.spy === "nao" || s.parentElement.closest("section")) return false;
        if (s.offsetHeight < 260) return false;
        return s.dataset.spy || s.querySelector("h1, h2");
    });
    if (seccoes.length < 4) return;

    const nav = document.createElement("nav");
    nav.className = "scrollspy";
    nav.setAttribute("aria-label", "Secções desta página");
    const ul = document.createElement("ul");
    nav.appendChild(ul);

    const links = seccoes.map(function (s, i) {
        const titulo = s.querySelector("h1, h2");
        let nome = s.dataset.spy || (s.classList.contains("hero-slider") || i === 0 ? "Início" : (titulo ? titulo.textContent : ""));
        nome = nome.replace(/\s+/g, " ").trim();
        if (nome.length > 34) nome = nome.slice(0, 32).trim() + "…";
        if (!s.id) s.id = "seccao-" + (i + 1) + "-" + jfSemAcentos(nome).replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "").slice(0, 30);
        s.classList.add("scrollspy-alvo");

        const li = document.createElement("li");
        const a = document.createElement("a");
        a.href = "#" + s.id;
        a.className = "scrollspy__ponto";
        const numero = String(i + 1).padStart(2, "0");
        a.innerHTML = '<span class="scrollspy__dica"><b>' + numero + '.</b> </span>';
        a.querySelector(".scrollspy__dica").appendChild(document.createTextNode(nome));
        a.addEventListener("click", function (e) {
            e.preventDefault();
            s.scrollIntoView({ behavior: jfReduzMovimento() ? "auto" : "smooth", block: "start" });
            history.replaceState(null, "", "#" + s.id);
        });
        li.appendChild(a);
        ul.appendChild(li);
        return a;
    });
    document.body.appendChild(nav);

    // A secção "ativa" é a que atravessa a linha a 40% da altura do ecrã
    const visiveis = new Map();
    const obs = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (en) { visiveis.set(en.target, en.isIntersecting); });
        let ativa = -1;
        seccoes.forEach(function (s, i) { if (visiveis.get(s)) ativa = i; });
        if (ativa === -1) return;
        links.forEach(function (a, i) {
            if (i === ativa) a.setAttribute("aria-current", "true"); else a.removeAttribute("aria-current");
        });
    }, { rootMargin: "-40% 0px -59% 0px" });
    seccoes.forEach(function (s) { obs.observe(s); });

    // Mostrar só depois de passar o primeiro ecrã (no hero tapava a seta do slider)
    let pendente = false;
    function visibilidade() {
        pendente = false;
        nav.classList.toggle("scrollspy--visivel", window.scrollY > window.innerHeight * 0.6);
    }
    window.addEventListener("scroll", function () { if (!pendente) { pendente = true; requestAnimationFrame(visibilidade); } }, { passive: true });
    visibilidade();
});


/* ---------- Lightbox reutilizável ([data-lightbox]) ---------------------------------
   Qualquer elemento com data-lightbox='{"titulo":"…","fotos":["url",…],"link":"…","linkTexto":"…"}'
   abre as fotos num visualizador em ecrã inteiro, em vez de seguir o link (o link continua a
   funcionar sem JS, e há um botão para a página dentro do visualizador).
   - Setas no ecrã, ←/→ no teclado, deslizar com o dedo; Esc ou clicar fora fecha.
   - Clicar na foto (ou no botão lupa / tecla Z) aproxima 40% a partir do ponto clicado; com
     zoom, a foto acompanha o rato. Clicar de novo afasta.
   - role="dialog", foco preso dentro, foco devolvido ao cartão ao fechar. */
(function () {
    let raiz, img, legenda, contador, link, btnZoom, fotos = [], idx = 0, origem = null, zoom = false;
    const ESCALA = 1.4;

    function criar() {
        raiz = document.createElement("div");
        raiz.className = "jlb";
        raiz.hidden = true;
        raiz.setAttribute("role", "dialog");
        raiz.setAttribute("aria-modal", "true");
        raiz.setAttribute("aria-labelledby", "jlbTitulo");
        raiz.innerHTML =
            '<div class="jlb__topo">' +
                '<div class="jlb__info"><h2 class="jlb__titulo" id="jlbTitulo"></h2><span class="jlb__contador" aria-live="polite"></span></div>' +
                '<div class="jlb__acoes">' +
                    '<button type="button" class="jlb__btn" data-jlb="zoom" aria-pressed="false" aria-label="Aproximar fotografia"><i class="bi bi-zoom-in" aria-hidden="true"></i></button>' +
                    '<a class="jlb__btn jlb__btn--texto" data-jlb="link" href="#"></a>' +
                    '<button type="button" class="jlb__btn" data-jlb="fechar" aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></button>' +
                '</div>' +
            '</div>' +
            '<button type="button" class="jlb__seta jlb__seta--ant" data-jlb="ant" aria-label="Fotografia anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>' +
            '<div class="jlb__palco" data-jlb="palco"><img class="jlb__img" alt=""></div>' +
            '<button type="button" class="jlb__seta jlb__seta--seg" data-jlb="seg" aria-label="Fotografia seguinte"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>' +
            '<div class="jlb__miniaturas" role="group" aria-label="Fotografias"></div>';
        document.body.appendChild(raiz);
        img = raiz.querySelector(".jlb__img");
        legenda = raiz.querySelector(".jlb__titulo");
        contador = raiz.querySelector(".jlb__contador");
        link = raiz.querySelector('[data-jlb="link"]');
        btnZoom = raiz.querySelector('[data-jlb="zoom"]');

        raiz.addEventListener("click", function (e) {
            const a = e.target.closest("[data-jlb]");
            const acao = a ? a.dataset.jlb : null;
            if (acao === "fechar") fechar();
            else if (acao === "ant") mover(-1);
            else if (acao === "seg") mover(1);
            else if (acao === "zoom") alternarZoom();
            // a imagem ocupa a área toda (object-fit: contain): clicar NA FOTO faz zoom; clicar nas
            // margens escuras à volta fecha (como clicar fora). Com zoom ativo, qualquer clique afasta.
            else if (e.target === img) { if (zoom || dentroDaFoto(e)) alternarZoom(e); else fechar(); }
            else if (e.target.closest("[data-jlb-i]")) mostrar(+e.target.closest("[data-jlb-i]").dataset.jlbI);
            // qualquer outro clique na zona escura (título, barra das miniaturas, espaços entre elas,
            // margens) FECHA — antes só a área da foto fechava e o resto não fazia nada: o lightbox
            // ficava aberto e o site parecia "preso"
            else if (!e.target.closest("button, a")) fechar();
        });
        // com zoom, a foto acompanha o rato
        img.addEventListener("mousemove", function (e) { if (zoom) origemZoom(e); });
        // teclado ouvido na página inteira enquanto está aberto (clicar na foto tira o foco dos botões)
        document.addEventListener("keydown", function (e) { if (!raiz.hidden) teclas(e); });

        // arrastar com o dedo em tempo real (sem zoom): a foto vizinha é desenhada numa imagem
        // «fantasma» que entra pelo lado; ao passar, a foto principal fica com ela (13/10/2026)
        const palco = raiz.querySelector(".jlb__palco");
        const fantasma = document.createElement("img");
        fantasma.className = "jlb__img jlb__img--fantasma";
        fantasma.alt = "";
        fantasma.setAttribute("aria-hidden", "true");
        palco.appendChild(fantasma);
        jfArrastar(palco, {
            elementos: function () { return [img]; },
            atual: function () { return 0; },
            pode: function () { return !zoom; },
            vizinho: function (s) {
                if (fotos.length < 2) return null;
                fantasma.src = fotos[(idx + s + fotos.length) % fotos.length];
                return fantasma;
            },
            ir: function (i, s) { img.src = fantasma.src; mostrar(idx + s); }
        });
    }

    // O clique caiu dentro da área onde a foto é desenhada (com contain há margens à volta)?
    // Usa a caixa SEM o transform do zoom: getBoundingClientRect inclui a escala (também a meio da
    // transição de afastar), e a margem era confundida com a foto — o lightbox não fechava.
    function dentroDaFoto(e) {
        const t = img.getBoundingClientRect();
        const W = img.offsetWidth, H = img.offsetHeight;
        if (!img.naturalWidth || !W || !H) return true;
        const s = t.width / W;
        const o = getComputedStyle(img).transformOrigin.split(" ").map(parseFloat);
        const r = { left: t.left - (o[0] || 0) * (1 - s), top: t.top - (o[1] || 0) * (1 - s), width: W, height: H };
        const nat = img.naturalWidth / img.naturalHeight;
        const w = Math.min(r.width, r.height * nat), h = w / nat;
        const x0 = r.left + (r.width - w) / 2, y0 = r.top + (r.height - h) / 2;
        return e.clientX >= x0 && e.clientX <= x0 + w && e.clientY >= y0 && e.clientY <= y0 + h;
    }

    function origemZoom(e) {
        const r = img.getBoundingClientRect();
        const x = Math.min(100, Math.max(0, (e.clientX - r.left) / r.width * 100));
        const y = Math.min(100, Math.max(0, (e.clientY - r.top) / r.height * 100));
        img.style.transformOrigin = x + "% " + y + "%";
    }

    function alternarZoom(e) {
        zoom = !zoom;
        if (zoom && e) origemZoom(e); else if (zoom) img.style.transformOrigin = "50% 50%";
        img.style.transform = zoom ? "scale(" + ESCALA + ")" : "";
        raiz.classList.toggle("jlb--zoom", zoom);
        btnZoom.setAttribute("aria-pressed", zoom ? "true" : "false");
        btnZoom.setAttribute("aria-label", zoom ? "Afastar fotografia" : "Aproximar fotografia (40%)");
        btnZoom.innerHTML = '<i class="bi ' + (zoom ? "bi-zoom-out" : "bi-zoom-in") + '" aria-hidden="true"></i>';
    }

    function mostrar(i) {
        if (zoom) alternarZoom();
        idx = (i + fotos.length) % fotos.length;
        raiz.classList.add("jlb--a-trocar");
        const nova = new Image();
        nova.onload = nova.onerror = function () {
            img.src = fotos[idx];
            raiz.classList.remove("jlb--a-trocar");
        };
        nova.src = fotos[idx];
        img.alt = legenda.textContent + " — fotografia " + (idx + 1) + " de " + fotos.length;
        contador.textContent = fotos.length > 1 ? (idx + 1) + " / " + fotos.length : "";
        raiz.querySelectorAll("[data-jlb-i]").forEach(function (m, k) {
            m.classList.toggle("ativa", k === idx);
            if (k === idx) { m.setAttribute("aria-current", "true"); m.scrollIntoView({ block: "nearest", inline: "center" }); }
            else m.removeAttribute("aria-current");
        });
        // pré-carrega a seguinte
        if (fotos.length > 1) { const p = new Image(); p.src = fotos[(idx + 1) % fotos.length]; }
    }

    function mover(d) { if (fotos.length > 1) mostrar(idx + d); }

    function teclas(e) {
        if (e.key === "Escape") { e.preventDefault(); if (zoom) alternarZoom(); else fechar(); }
        else if (e.key === "ArrowLeft") { e.preventDefault(); mover(-1); }
        else if (e.key === "ArrowRight") { e.preventDefault(); mover(1); }
        else if (e.key === "z" || e.key === "Z") { alternarZoom(); }
        else if (e.key === "Tab") {
            const f = Array.prototype.filter.call(raiz.querySelectorAll("button, a[href]"), function (el) { return el.offsetParent !== null; });
            const i = f.indexOf(document.activeElement);
            e.preventDefault();
            f[(i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
        }
    }

    let temporizadorFecho = null;
    function abrir(dados, el) {
        if (!raiz) criar();
        // reabrir logo depois de fechar: o temporizador do fecho anterior já não o esconde
        clearTimeout(temporizadorFecho);
        fotos = dados.fotos || [];
        if (!fotos.length) return false;
        origem = el;
        legenda.textContent = dados.titulo || "";
        if (dados.link) { link.hidden = false; link.href = dados.link; link.setAttribute("aria-label", dados.linkTexto || "Abrir página"); link.innerHTML = '<span>' + (dados.linkTexto || "Abrir página") + '</span> <i class="bi bi-arrow-right" aria-hidden="true"></i>'; }
        else link.hidden = true;
        const varias = fotos.length > 1;
        raiz.querySelectorAll(".jlb__seta").forEach(function (b) { b.hidden = !varias; });
        const tira = raiz.querySelector(".jlb__miniaturas");
        tira.innerHTML = "";
        tira.hidden = !varias;
        if (varias) fotos.forEach(function (src, k) {
            const b = document.createElement("button");
            b.type = "button";
            b.dataset.jlbI = k;
            b.setAttribute("aria-label", "Fotografia " + (k + 1) + " de " + fotos.length);
            b.innerHTML = '<img src="' + src.replace(/"/g, "&quot;") + '" alt="" loading="lazy">';
            tira.appendChild(b);
        });
        raiz.hidden = false;
        document.documentElement.classList.add("jlb-aberto");
        requestAnimationFrame(function () { raiz.classList.add("jlb--visivel"); });
        mostrar(+dados.inicio || 0);
        raiz.querySelector('[data-jlb="fechar"]').focus();
        return true;
    }

    function fechar() {
        if (!raiz || raiz.hidden) return;
        if (zoom) alternarZoom();
        raiz.classList.remove("jlb--visivel");
        document.documentElement.classList.remove("jlb-aberto");
        clearTimeout(temporizadorFecho);
        temporizadorFecho = setTimeout(function () { raiz.hidden = true; img.removeAttribute("src"); }, jfReduzMovimento() ? 0 : 200);
        if (origem && origem.focus) origem.focus({ preventScroll: true });
    }

    // Rede de segurança: a página nunca fica sem scroll/cliques com o lightbox fechado — ex.: ao voltar
    // com "Atrás" (o browser restaura a página tal como estava, com o lightbox aberto)
    window.addEventListener("pageshow", function (e) {
        if (e.persisted && raiz && !raiz.hidden) { raiz.classList.remove("jlb--visivel"); raiz.hidden = true; }
        if (!raiz || raiz.hidden) document.documentElement.classList.remove("jlb-aberto");
    });

    window.jfAbrirLightbox = abrir;

    document.addEventListener("click", function (e) {
        const el = e.target.closest("[data-lightbox]");
        if (!el || e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1) return;  // Ctrl+clique abre a página noutro separador
        let dados;
        try { dados = JSON.parse(el.getAttribute("data-lightbox")); } catch (err) { return; }
        // começa na foto indicada (ex.: a que a apresentação está a mostrar)
        if (el.dataset.lightboxInicio) dados.inicio = parseInt(el.dataset.lightboxInicio, 10) || 0;
        if (abrir(dados, el)) e.preventDefault();
    });
})();


/* ---------- Filtros e paginação sem recarregar ([data-zona-ajax]) -------------------
   Uma zona <... data-zona-ajax="nome"> é atualizada em segundo plano quando se clica num link
   dentro dela que aponta para a MESMA página (filtros, paginação), ou quando se usa um
   formulário GET com data-ajax-alvo="nome" (escolher numa lista filtra logo; escrever filtra
   após uma pequena pausa). Vai buscar a página com fetch e troca só o conteúdo da zona, com
   transição suave; atualiza o endereço (botão "voltar" funciona). Sem JS, tudo funciona como
   antes (links e formulários normais). */
(function () {
    const anuncio = document.createElement("p");
    anuncio.className = "sr-only";
    anuncio.setAttribute("aria-live", "polite");
    document.addEventListener("DOMContentLoaded", function () { document.body.appendChild(anuncio); });

    function carregar(nome, url, empurrar) {
        const zona = document.querySelector('[data-zona-ajax="' + nome + '"]');
        if (!zona) { window.location.href = url; return; }
        zona.setAttribute("aria-busy", "true");
        zona.classList.add("zona-a-carregar");
        fetch(url, { headers: { "X-Requested-With": "fetch" } })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then(function (html) {
                const nova = new DOMParser().parseFromString(html, "text/html").querySelector('[data-zona-ajax="' + nome + '"]');
                if (!nova) throw new Error("zona");
                const trocar = function () { zona.innerHTML = nova.innerHTML; };
                if (document.startViewTransition && !jfReduzMovimento()) jfTransicao(trocar); else trocar();
                zona.classList.remove("zona-a-carregar");
                zona.removeAttribute("aria-busy");
                zona.classList.remove("zona-entrou"); void zona.offsetWidth; zona.classList.add("zona-entrou");
                // marcar fotos já carregadas (esqueleto) e anunciar o resultado a leitores de ecrã
                zona.querySelectorAll("img").forEach(function (i) { const m = i.closest(".ponto-tile, .mosaico-tile, .editorial__media"); if (m) m.classList.add("carregada"); });
                const cont = zona.querySelector("[data-contagem], .comercio-count, .docs-count");
                anuncio.textContent = cont ? cont.textContent.trim() : "Resultados atualizados.";
                if (empurrar) history.pushState({ zona: nome }, "", url);
                const topo = zona.getBoundingClientRect().top;
                if (topo < 0) zona.scrollIntoView({ behavior: jfReduzMovimento() ? "auto" : "smooth", block: "start" });
            })
            .catch(function () { window.location.href = url; });   // se algo falhar, navega normalmente
    }

    document.addEventListener("click", function (e) {
        const a = e.target.closest("a[href]");
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0 || a.target === "_blank") return;
        const zona = a.closest("[data-zona-ajax]");
        if (!zona) return;
        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin || url.pathname !== location.pathname) return;
        if (url.hash && url.search === location.search) return;   // âncora na mesma página: deixa o browser tratar
        url.hash = "";
        e.preventDefault();
        carregar(zona.dataset.zonaAjax, url.href, true);
    });

    function enviarForm(form) {
        const url = new URL(form.getAttribute("action") || location.pathname, location.href);
        url.search = new URLSearchParams(new FormData(form)).toString();
        carregar(form.dataset.ajaxAlvo, url.href, true);
    }
    document.addEventListener("submit", function (e) {
        const f = e.target.closest("form[data-ajax-alvo]");
        if (!f || (f.method || "get").toLowerCase() !== "get") return;
        e.preventDefault();
        enviarForm(f);
    });
    let pausa = null;
    document.addEventListener("change", function (e) {
        const f = e.target.closest("form[data-ajax-alvo]");
        if (f && e.target.matches("select")) enviarForm(f);
    });
    document.addEventListener("input", function (e) {
        const f = e.target.closest("form[data-ajax-alvo]");
        if (!f || !e.target.matches('input[type="text"], input[type="search"]')) return;
        clearTimeout(pausa);
        pausa = setTimeout(function () { enviarForm(f); }, 400);
    });
    window.addEventListener("popstate", function () {
        const z = document.querySelector("[data-zona-ajax]");
        if (z) carregar(z.dataset.zonaAjax, location.href, false);
    });
})();


/* ---------- Pré-visualização de PDF num painel lateral ----------------------------
   Qualquer link para um .pdf do próprio site abre num painel à direita (leitor de PDF do
   browser), com Descarregar / Abrir em nova janela / Fechar. Em ecrãs pequenos ou de toque
   abre como antes (os browsers de telemóvel não mostram PDF embutido). Ctrl+clique e
   links com data-sem-previa seguem o comportamento normal. */
(function () {
    let painel, frame, titulo, linkAbrir, linkDesc, origem = null;
    function criar() {
        painel = document.createElement("div");
        painel.className = "pdfp";
        painel.hidden = true;
        painel.innerHTML =
            '<div class="pdfp__fundo" data-pdfp-fechar></div>' +
            '<div class="pdfp__painel" role="dialog" aria-modal="true" aria-labelledby="pdfpTitulo">' +
                '<div class="pdfp__topo">' +
                    '<i class="bi bi-file-earmark-pdf-fill pdfp__ico" aria-hidden="true"></i>' +
                    '<h2 id="pdfpTitulo" class="pdfp__titulo"></h2>' +
                    '<a class="pdfp__btn" data-pdfp="desc" download><i class="bi bi-download" aria-hidden="true"></i> <span>Descarregar</span></a>' +
                    '<a class="pdfp__btn" data-pdfp="abrir" target="_blank" rel="noopener" aria-label="Abrir em nova janela"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>' +
                    '<button type="button" class="pdfp__btn" data-pdfp-fechar aria-label="Fechar"><i class="bi bi-x-lg" aria-hidden="true"></i></button>' +
                '</div>' +
                '<iframe class="pdfp__frame" title="Pré-visualização do documento"></iframe>' +
            '</div>';
        document.body.appendChild(painel);
        frame = painel.querySelector("iframe");
        titulo = painel.querySelector(".pdfp__titulo");
        linkAbrir = painel.querySelector('[data-pdfp="abrir"]');
        linkDesc = painel.querySelector('[data-pdfp="desc"]');
        painel.addEventListener("click", function (e) { if (e.target.closest("[data-pdfp-fechar]")) fechar(); });
        document.addEventListener("keydown", function (e) {
            if (painel.hidden) return;
            if (e.key === "Escape") fechar();
            if (e.key === "Tab") {
                const f = Array.prototype.slice.call(painel.querySelectorAll("a[href], button"));
                const i = f.indexOf(document.activeElement);
                if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
                else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
            }
        });
    }
    function abrir(url, nome, a) {
        if (!painel) criar();
        origem = a;
        titulo.textContent = nome;
        linkAbrir.href = url; linkDesc.href = url;
        frame.src = url + "#view=FitH";
        painel.hidden = false;
        document.documentElement.classList.add("jlb-aberto");
        requestAnimationFrame(function () { painel.classList.add("pdfp--visivel"); });
        painel.querySelector("[data-pdfp-fechar].pdfp__btn").focus();
    }
    function fechar() {
        painel.classList.remove("pdfp--visivel");
        document.documentElement.classList.remove("jlb-aberto");
        setTimeout(function () { painel.hidden = true; frame.removeAttribute("src"); }, jfReduzMovimento() ? 0 : 250);
        if (origem) origem.focus();
    }
    document.addEventListener("click", function (e) {
        const a = e.target.closest('a[href$=".pdf" i], a[href*=".pdf?" i]');
        if (!a || a.hasAttribute("data-sem-previa") || a.closest(".pdfp") || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin) return;
        if (window.matchMedia("(max-width: 760px), (pointer: coarse)").matches) return;
        e.preventDefault();
        const card = a.closest("article, .transp-cel, li, .doc-card-premium");
        const t = (card && card.querySelector("h2, h3, h4, strong")) || a;
        abrir(url.href, t.textContent.trim().replace(/\s+/g, " ") || "Documento", a);
    });
})();


/* ---------- Botões de envio: estado "a enviar" e sem cliques duplos ---------------
   Nos formulários normais (POST), ao enviar o botão mostra um indicador a rodar e o texto
   "A enviar…", e um segundo clique é ignorado. (Não usa "disabled" no momento do envio, para
   o valor do botão continuar a ir com o formulário.) */
document.addEventListener("submit", function (e) {
    const f = e.target;
    if (!(f instanceof HTMLFormElement) || f.dataset.ajaxAlvo || e.defaultPrevented) return;
    if ((f.method || "get").toLowerCase() !== "post") return;
    if (f.dataset.aEnviar === "1") { e.preventDefault(); return; }
    f.dataset.aEnviar = "1";
    const b = e.submitter || f.querySelector('button[type="submit"], button:not([type])');
    if (b) {
        b.classList.add("btn-a-enviar");
        b.setAttribute("aria-disabled", "true");
        b.dataset.textoOriginal = b.innerHTML;
        b.insertAdjacentHTML("afterbegin", '<span class="btn-spinner" aria-hidden="true"></span>');
    }
    // se a página não mudar (ex.: validação do browser falhou depois), volta a permitir
    setTimeout(function () { f.dataset.aEnviar = ""; if (b) { b.classList.remove("btn-a-enviar"); b.removeAttribute("aria-disabled"); const s = b.querySelector(".btn-spinner"); if (s) s.remove(); } }, 8000);
});


/* ---------- Esqueleto de carregamento: marca a moldura quando a foto chega ---------- */
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".ponto-tile, .mosaico-tile, .editorial__media").forEach(function (moldura) {
        const img = moldura.querySelector("img");
        if (!img) { moldura.classList.add("carregada"); return; }
        function pronto() { moldura.classList.add("carregada"); }
        if (img.complete && img.naturalWidth) pronto();
        else { img.addEventListener("load", pronto, { once: true }); img.addEventListener("error", pronto, { once: true }); }
    });
});


/* ---------- Galerias de fotografias: apresentação com miniaturas (.gshow) -------------
   Um só componente para todas as galerias do site:
   - Homepage: marcação feita no PHP (index.php) com data-gshow="auto" → roda a cada 4 s.
   - Notícias, eventos e pontos de interesse: a página marca a foto principal com
     data-carrossel e data-fotos='["url", …]'; aqui é trocada pela apresentação (sem rodar
     sozinha). Com uma só foto, clicar na foto abre o lightbox.
   Setas, miniaturas, ←/→ e deslizar com o dedo mudam de foto. Clicar na foto abre o lightbox
   reutilizável (zoom de 40%, setas, miniaturas) na foto que está à vista. A rotação automática
   pára com o rato/foco na foto, fora do ecrã e com o botão de pausa (WCAG 2.2.2); com
   "reduzir movimento" não roda. */
function jfIniciarGshow(show, automatico) {
    const INTERVALO = 3000;   // 3 s por foto (12/10/2026, a pedido: ver mais imagens)
    const palco = show.querySelector(".gshow__palco");
    const fotos = palco.querySelectorAll(":scope > img");
    const minis = show.querySelectorAll("[data-gshow-ir]");
    const legenda = show.querySelector("[data-gshow-legenda]");
    const contador = show.querySelector("[data-gshow-contador]");
    const anuncio = show.querySelector("[data-gshow-anuncio]");
    const abrir = show.querySelector("[data-lightbox-inicio]");
    const botao = show.querySelector("[data-gshow-pausa]");
    const tira = show.querySelector(".gshow__miniaturas");
    // fundo desfocado com a própria foto, nas margens que sobram (a foto vê-se sempre inteira)
    const fundo = function (img) { if (img) palco.style.setProperty("--gshow-fundo", "url(\"" + (img.currentSrc || img.src).replace(/"/g, "%22") + "\")"); };
    fundo(palco.querySelector(":scope > img.ativa") || fotos[0]);
    if (fotos.length < 2) return;
    let atual = 0, timer = null, pausado = !automatico || jfReduzMovimento(), visivel = false, porCima = false;
    // fotos com data-src (homepage): só se descarregam quando chega a vez delas (redesign 10/2026)
    const carregar = function (img) { img.removeAttribute("loading"); if (img.dataset.src) { img.src = img.dataset.src; img.removeAttribute("data-src"); } };
    if (fotos[1]) carregar(fotos[1]);

    function mostrar(i, peloUtilizador) {
        fotos[atual].classList.remove("ativa");
        minis[atual].classList.remove("ativa");
        minis[atual].removeAttribute("aria-current");
        atual = (i + fotos.length) % fotos.length;
        const f = fotos[atual];
        carregar(f);
        fundo(f);
        f.classList.add("ativa");
        minis[atual].classList.add("ativa");
        minis[atual].setAttribute("aria-current", "true");
        if (legenda) legenda.textContent = f.dataset.legenda || "";
        if (contador) contador.textContent = (atual + 1) + " / " + fotos.length;
        if (abrir) abrir.dataset.lightboxInicio = atual;
        // pré-carrega a seguinte para o crossfade não mostrar uma foto a meio
        carregar(fotos[(atual + 1) % fotos.length]);
        // miniatura ativa à vista, sem mexer no scroll da página
        if (tira) {
            const m = minis[atual];
            tira.scrollTo({ left: m.offsetLeft - (tira.clientWidth - m.offsetWidth) / 2, behavior: jfReduzMovimento() ? "auto" : "smooth" });
        }
        if (peloUtilizador) {
            if (anuncio) anuncio.textContent = "Fotografia " + (atual + 1) + " de " + fotos.length + (f.dataset.legenda ? ": " + f.dataset.legenda : "");
            reiniciar();
        }
    }
    // barra de progresso dourada: recomeça a cada troca
    function barra() { show.classList.remove("a-correr"); void show.offsetWidth; if (timer) show.classList.add("a-correr"); }
    function reiniciar() { if (timer) { clearInterval(timer); timer = null; } decidir(); barra(); }
    function decidir() {
        const deveCorrer = !pausado && visivel && !porCima;
        if (deveCorrer && !timer) { timer = setInterval(function () { mostrar(atual + 1); barra(); }, INTERVALO); barra(); }
        if (!deveCorrer && timer) { clearInterval(timer); timer = null; show.classList.remove("a-correr"); }
    }
    function atualizarBotao() {
        if (!botao) return;
        botao.setAttribute("aria-label", pausado ? "Retomar apresentação de fotografias" : "Pausar apresentação de fotografias");
        botao.innerHTML = '<i class="bi ' + (pausado ? "bi-play-fill" : "bi-pause-fill") + '" aria-hidden="true"></i>';
    }

    show.style.setProperty("--gshow-intervalo", INTERVALO + "ms");
    show.querySelector("[data-gshow-ant]").addEventListener("click", function () { mostrar(atual - 1, true); });
    show.querySelector("[data-gshow-seg]").addEventListener("click", function () { mostrar(atual + 1, true); });
    minis.forEach(function (m) { m.addEventListener("click", function () { mostrar(parseInt(m.dataset.gshowIr, 10), true); }); });
    show.addEventListener("keydown", function (e) {
        if (e.key === "ArrowLeft") { e.preventDefault(); mostrar(atual - 1, true); }
        if (e.key === "ArrowRight") { e.preventDefault(); mostrar(atual + 1, true); }
    });
    if (botao) botao.addEventListener("click", function () { pausado = !pausado; atualizarBotao(); reiniciar(); });
    palco.addEventListener("mouseenter", function () { porCima = true; decidir(); });
    palco.addEventListener("mouseleave", function () { porCima = false; reiniciar(); });
    show.addEventListener("focusin", function () { porCima = true; decidir(); });
    show.addEventListener("focusout", function (e) { if (!show.contains(e.relatedTarget)) { porCima = false; reiniciar(); } });
    // arrastar com o dedo em tempo real: a foto segue o dedo e a seguinte entra pelo lado (13/10/2026)
    jfArrastar(palco, {
        elementos: function () { return Array.prototype.slice.call(fotos); },
        atual: function () { return atual; },
        ir: function (i) { mostrar(i, true); },
        inicio: function () { porCima = true; decidir(); },
        fim: function () { porCima = false; reiniciar(); }
    });
    if (automatico && "IntersectionObserver" in window) {
        new IntersectionObserver(function (e) { visivel = e[0].isIntersecting; decidir(); }, { threshold: 0.3 }).observe(palco);
    } else { visivel = true; }
    atualizarBotao();
    decidir();
}

/* Cria a marcação .gshow a partir de uma lista de fotos (notícias, eventos, pontos). */
function jfCriarGshow(fotos, titulo, focoPrimeira) {
    const esc = function (t) { return String(t).replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;"); };
    const n = fotos.length;
    const dados = JSON.stringify({ titulo: titulo, fotos: fotos });
    let html = '<div class="gshow gshow--detalhe"><div class="gshow__palco">';
    fotos.forEach(function (src, k) {
        html += '<img src="' + esc(src) + '" alt="' + esc(titulo + " — fotografia " + (k + 1) + " de " + n) + '" decoding="async"' +
            (k === 0 ? ' class="ativa"' + (focoPrimeira ? ' style="object-position:' + esc(focoPrimeira) + '"' : "") : ' loading="lazy"') + '>';
    });
    html += '<button type="button" class="gshow__abrir" aria-haspopup="dialog" aria-label="Ampliar fotografia" data-lightbox-inicio="0" data-lightbox="' + esc(dados) + '"></button>' +
        '<div class="gshow__info" aria-hidden="true"><span class="gshow__ver"><i class="bi bi-arrows-angle-expand"></i> Clique para ampliar</span></div>' +
        '<button type="button" class="gshow__seta gshow__seta--ant" data-gshow-ant aria-label="Fotografia anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>' +
        '<button type="button" class="gshow__seta gshow__seta--seg" data-gshow-seg aria-label="Fotografia seguinte"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>' +
        '<div class="gshow__topo"><span class="gshow__contador" data-gshow-contador>1 / ' + n + '</span></div>' +
        '<p class="sr-only" aria-live="polite" data-gshow-anuncio></p></div>' +
        '<div class="gshow__miniaturas" role="group" aria-label="Escolher fotografia">';
    fotos.forEach(function (src, k) {
        html += '<button type="button" class="gshow__mini' + (k === 0 ? ' ativa" aria-current="true"' : '"') + ' data-gshow-ir="' + k + '" aria-label="Fotografia ' + (k + 1) + ' de ' + n + '">' +
            '<img src="' + esc(src) + '" alt="" loading="lazy" decoding="async"></button>';
    });
    const caixa = document.createElement("div");
    caixa.innerHTML = html + "</div></div>";
    return caixa.firstChild;
}

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-gshow]").forEach(function (show) { jfIniciarGshow(show, show.dataset.gshow === "auto"); });

    const tituloPagina = ((document.querySelector("h1") || {}).textContent || document.title).trim();
    document.querySelectorAll("[data-carrossel]").forEach(function (alvo) {
        if (alvo.dataset.carrosselPronto === "1") return;
        alvo.dataset.carrosselPronto = "1";
        let fotos = [];
        try { fotos = JSON.parse(alvo.dataset.fotos || "[]"); } catch (e) { return; }
        fotos = fotos.filter(function (f, i) { return f && fotos.indexOf(f) === i; });
        const img = alvo.tagName === "IMG" ? alvo : alvo.querySelector("img");
        if (!img || !fotos.length) return;

        if (fotos.length < 2) {
            // uma só foto: clicar abre o lightbox (com zoom)
            alvo.removeAttribute("onclick");
            alvo.classList.add("foto-ampliavel");
            if (alvo.tagName === "IMG") { alvo.tabIndex = 0; alvo.setAttribute("role", "button"); alvo.setAttribute("aria-label", "Ampliar fotografia: " + tituloPagina); }
            const abrirUnica = function (e) { e.preventDefault(); window.jfAbrirLightbox({ titulo: tituloPagina, fotos: fotos }, alvo); };
            alvo.addEventListener("click", abrirUnica);
            alvo.addEventListener("keydown", function (e) { if (alvo.tagName === "IMG" && (e.key === "Enter" || e.key === " ")) abrirUnica(e); });
            return;
        }
        const show = jfCriarGshow(fotos, tituloPagina, img.style.objectPosition);
        alvo.parentNode.replaceChild(show, alvo);
        jfIniciarGshow(show, false);
        // a grelha antiga de fotos deixa de ser precisa: as miniaturas substituem-na
        document.querySelectorAll("[data-carrossel-substitui]").forEach(function (g) { g.hidden = true; });
    });

    /* Grupos de fotografias soltas ([data-galeria-lightbox="Título"]): cada <img> do grupo
       abre o lightbox com todas as fotos do grupo, a começar na que foi clicada. */
    document.querySelectorAll("[data-galeria-lightbox]").forEach(function (grupo) {
        const imgs = Array.prototype.filter.call(grupo.querySelectorAll("img"), function (i) { return !i.closest("[data-sem-lightbox]"); });
        const fotos = imgs.map(function (i) { return i.getAttribute("src"); });
        imgs.forEach(function (i, k) {
            i.classList.add("foto-ampliavel");
            i.tabIndex = 0;
            i.setAttribute("role", "button");
            i.setAttribute("aria-label", "Ampliar fotografia" + (i.alt ? ": " + i.alt : ""));
            const abrir = function (e) { e.preventDefault(); window.jfAbrirLightbox({ titulo: grupo.dataset.galeriaLightbox || tituloPagina, fotos: fotos, inicio: k }, i); };
            i.addEventListener("click", abrir);
            i.addEventListener("keydown", function (e) { if (e.key === "Enter" || e.key === " ") abrir(e); });
        });
    });

    /* Páginas antigas (galeria, notícias, eventos) chamam lbAbrir(lista, i) — passa a abrir o
       lightbox novo (zoom de 40%, setas, miniaturas) em vez do visualizador próprio de cada página. */
    if (window.jfAbrirLightbox) {
        window.lbAbrir = function (lista, i) {
            return window.jfAbrirLightbox({ titulo: tituloPagina, fotos: lista || [], inicio: i || 0 }, document.activeElement);
        };
    }
});


/* ---------- Separadores acessíveis ([data-separadores]) ----------------------------
   Padrão ARIA "tabs": clique ou setas ←/→ (Home/End) mudam de separador; só o separador
   ativo está na ordem do Tab. */
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-separadores]").forEach(function (bloco) {
        const tabs = Array.prototype.slice.call(bloco.querySelectorAll('[role="tab"]'));
        function ativar(tab, focar) {
            tabs.forEach(function (t) {
                const sel = t === tab;
                t.setAttribute("aria-selected", sel ? "true" : "false");
                t.tabIndex = sel ? 0 : -1;
                const painel = document.getElementById(t.getAttribute("aria-controls"));
                if (painel) painel.hidden = !sel;
            });
            if (focar) tab.focus();
        }
        tabs.forEach(function (t, i) {
            t.addEventListener("click", function () { ativar(t, false); });
            t.addEventListener("keydown", function (e) {
                let alvo = null;
                if (e.key === "ArrowRight") alvo = tabs[(i + 1) % tabs.length];
                else if (e.key === "ArrowLeft") alvo = tabs[(i - 1 + tabs.length) % tabs.length];
                else if (e.key === "Home") alvo = tabs[0];
                else if (e.key === "End") alvo = tabs[tabs.length - 1];
                if (alvo) { e.preventDefault(); ativar(alvo, true); }
            });
        });
    });
});


/* ---------- Números com contagem animada ([data-contar]) ---------------------
   Conta de 0 até ao valor quando o número entra no ecrã (~1,2 s, abranda no fim).
   O valor final já vem no HTML: sem JS, ou com "reduzir movimento", fica logo certo.
   O nome acessível do link (aria-label) tem o valor final, por isso os leitores de
   ecrã não leem os números intermédios. */
document.addEventListener("DOMContentLoaded", function () {
    const numeros = document.querySelectorAll("[data-contar]");
    if (!numeros.length || jfReduzMovimento() || !("IntersectionObserver" in window)) return;

    function contar(el) {
        const alvo = parseInt(el.dataset.contar, 10) || 0;
        const duracao = Math.min(1600, 700 + alvo * 25);
        const inicio = performance.now();
        function passo(agora) {
            const t = Math.min(1, (agora - inicio) / duracao);
            const suave = 1 - Math.pow(1 - t, 3);          // abranda no fim
            el.textContent = Math.round(alvo * suave);
            if (t < 1) requestAnimationFrame(passo);
        }
        requestAnimationFrame(passo);
    }

    const obs = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (en) {
            if (!en.isIntersecting) return;
            obs.unobserve(en.target);
            contar(en.target);
        });
    }, { threshold: 0.6 });

    numeros.forEach(function (el) {
        // só os que ainda não estão à vista começam a 0 (os visíveis contariam "do nada")
        if (el.getBoundingClientRect().top > window.innerHeight) el.textContent = "0";
        obs.observe(el);
    });
});


/* ---------- Notícias: filtros por categoria sem recarregar -------------------
   A homepage imprime as notícias recentes com data-categoria; os botões
   [data-filtro-noticias] mostram só as da categoria escolhida. A primeira visível
   fica em destaque (grande), as três seguintes na coluna lateral. */
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-editorial]").forEach(function (bloco) {
        const botoes = bloco.querySelectorAll("[data-filtro-noticias]");
        const cartoes = Array.prototype.slice.call(bloco.querySelectorAll("[data-categoria]"));
        const vazio = bloco.querySelector("[data-editorial-vazio]");
        const anuncio = bloco.querySelector("[data-editorial-estado]");

        function aplicar(cat) {
            let n = 0;
            cartoes.forEach(function (c) {
                const ok = cat === "*" || c.dataset.categoria === cat;
                const mostra = ok && n < 4;
                c.hidden = !mostra;
                c.classList.toggle("editorial__item--destaque", mostra && n === 0);
                if (mostra) n++;
            });
            if (vazio) vazio.hidden = n > 0;
            botoes.forEach(function (b) { b.setAttribute("aria-pressed", b.dataset.filtroNoticias === cat ? "true" : "false"); });
            if (anuncio) anuncio.textContent = n ? "A mostrar " + n + " notícia" + (n === 1 ? "" : "s") + (cat === "*" ? "" : " de " + cat) : "Sem notícias desta categoria";
        }

        botoes.forEach(function (b) {
            b.addEventListener("click", function () {
                const cat = b.dataset.filtroNoticias;
                const grelha = bloco.querySelector(".editorial__grelha");
                if (document.startViewTransition && !jfReduzMovimento()) jfTransicao(function () { aplicar(cat); });
                else aplicar(cat);
                if (grelha) grelha.setAttribute("aria-busy", "false");
            });
        });
        aplicar("*");
    });
});


/* PWA (service worker e convite para instalar) do modelo novo: não incluída nesta fase. */


/* ---------- Copiar para a área de transferência ([data-copiar="texto"]) -------------
   Ex.: e-mails nos cartões de contactos (utilidades.php). Feedback visível no botão
   («Copiado!») e anunciado aos leitores de ecrã (#avisoCopiar, se existir). */
document.addEventListener("click", function (e) {
    const b = e.target.closest("[data-copiar]");
    if (!b) return;
    const texto = b.dataset.copiar;
    function ok() {
        // botão só com ícone: troca o ícone por um visto; com texto: escreve "Copiado!"
        const icone = b.querySelector(".bi");
        const soIcone = icone && b.textContent.trim() === "";
        if (!b.dataset.original) b.dataset.original = soIcone ? icone.className : b.textContent;
        if (!b.dataset.titulo) b.dataset.titulo = b.getAttribute("title") || "";
        if (soIcone) icone.className = "bi bi-check2"; else b.textContent = "Copiado!";
        b.setAttribute("title", "E-mail copiado");
        b.classList.add("copiado");
        // anúncio aos leitores de ecrã: o da página, ou o da secção de contactos
        const aviso = document.getElementById("avisoCopiar") || (b.closest("[data-cu]") || document).querySelector("[data-cu-estado]");
        if (aviso) aviso.textContent = "E-mail copiado: " + texto;
        clearTimeout(b._t);
        b._t = setTimeout(function () {
            if (soIcone) icone.className = b.dataset.original; else b.textContent = b.dataset.original;
            b.setAttribute("title", b.dataset.titulo);
            b.classList.remove("copiado");
        }, 2000);
    }
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto).then(ok, function () { location.href = "mailto:" + texto; });
    } else {
        // sem acesso à área de transferência (http): seleção num campo temporário
        const t = document.createElement("textarea");
        t.value = texto;
        t.setAttribute("readonly", "");
        t.style.position = "fixed";
        t.style.opacity = "0";
        document.body.appendChild(t);
        t.select();
        let feito = false;
        try { feito = document.execCommand("copy"); } catch (err) {}
        t.remove();
        if (feito) ok(); else location.href = "mailto:" + texto;
    }
});



/* ---------- Contactos úteis: filtro por categoria + pesquisa instantânea ([data-cu]) -------
   Pesquisa sem acentos nem maiúsculas ("saude" encontra "Saúde"). A grelha é flex, por isso
   os cartões que ficam visíveis preenchem sempre a linha. O resultado é anunciado. */
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-cu]").forEach(function (sec) {
        const cartoes = Array.prototype.slice.call(sec.querySelectorAll(".cu"));
        const filtros = sec.querySelectorAll("[data-cu-filtro]");
        const procura = sec.querySelector("[data-cu-procura]");
        const vazio = sec.querySelector("[data-cu-vazio]");
        const estado = sec.querySelector("[data-cu-estado]");
        const limpa = function (t) { return (t || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, ""); };
        cartoes.forEach(function (c) { c._procura = limpa(c.dataset.procura); });
        let categoria = "*", timer = null;

        function aplicar() {
            const termos = limpa(procura ? procura.value.trim() : "").split(/\s+/).filter(Boolean);
            let n = 0;
            cartoes.forEach(function (c) {
                const ok = (categoria === "*" || c.dataset.categoria === categoria) && termos.every(function (t) { return c._procura.indexOf(t) !== -1; });
                c.hidden = !ok;
                if (ok) n++;
            });
            if (vazio) vazio.hidden = n > 0;
            equilibrar();
            clearTimeout(timer);
            timer = setTimeout(function () { if (estado) estado.textContent = n === 1 ? "1 contacto" : n + " contactos"; }, 400);
        }

        /* Linhas equilibradas: com N cartões e no máximo M por linha (cabem ~290px cada), reparte
           por linhas o mais iguais possível (10 em 4 colunas → 4+3+3, nunca 4+4+2 ou 3+3+3+1).
           Cada cartão recebe a largura exata da sua linha: (100% − espaços) / cartões da linha. */
        const grelha = sec.querySelector(".cu-grelha");
        function equilibrar() {
            if (!grelha) return;
            const vis = cartoes.filter(function (c) { return !c.hidden; });
            const largura = grelha.clientWidth;
            const max = Math.max(1, Math.min(6, Math.floor((largura + 14) / (290 + 14))));
            const n = vis.length;
            if (!n) return;
            const linhas = Math.ceil(n / max);
            const base = Math.floor(n / linhas), extra = n % linhas;
            let i = 0;
            for (let l = 0; l < linhas; l++) {
                const naLinha = base + (l < extra ? 1 : 0);
                const w = "calc((100% - " + (naLinha - 1) + " * var(--cu-gap)) / " + naLinha + " - 0.01px)";
                for (let k = 0; k < naLinha; k++, i++) vis[i].style.flexBasis = w;
            }
            grelha.classList.add("cu-grelha--equilibrada");
        }
        if ("ResizeObserver" in window && grelha) {
            let ultima = 0;
            new ResizeObserver(function () { if (grelha.clientWidth !== ultima) { ultima = grelha.clientWidth; equilibrar(); } }).observe(grelha);
        } else {
            window.addEventListener("resize", equilibrar);
        }
        equilibrar();

        filtros.forEach(function (b) {
            b.addEventListener("click", function () {
                categoria = b.dataset.cuFiltro;
                filtros.forEach(function (x) { x.setAttribute("aria-pressed", x === b ? "true" : "false"); });
                aplicar();
            });
        });
        if (procura) procura.addEventListener("input", aplicar);
        const limpar = sec.querySelector("[data-cu-limpar]");
        if (limpar) limpar.addEventListener("click", function () {
            if (procura) procura.value = "";
            categoria = "*";
            filtros.forEach(function (x) { x.setAttribute("aria-pressed", x.dataset.cuFiltro === "*" ? "true" : "false"); });
            aplicar();
            if (procura) procura.focus();
        });
    });
});


/* ---------- Slides de vídeo do YouTube (12/10/2026) ----------------------------
   O vídeo do slide toca sem som enquanto o slide está à vista e pára quando se muda de slide.
   13/10/2026, a pedido: no slide de vídeo o slider NÃO avança sozinho — o vídeo repete (loop) e
   só se sai dele com as setas/pontinhos/teclado. Sem cookies aceites não há leitor e o slide
   comporta-se como os outros. Qualidade: o leitor tem sempre 1920 px reais (o YouTube escolhe a
   qualidade pelo tamanho do leitor → 1080p) e é reduzido com scale() para cobrir o slide. O botão de pausa do slider pausa também o vídeo (WCAG 2.2.2). Sem cookies
   aceites não há iframe: o slide mostra a imagem e funciona como os outros.
   «Ver com som» abre o leitor completo do YouTube numa janela, onde se escolhe a qualidade.
   Comunicação com o leitor pela API de postMessage do YouTube (sem carregar scripts extra). */
document.addEventListener("DOMContentLoaded", function () {
    const slider = document.getElementById("heroSlider");
    if (!slider) return;
    const videos = Array.prototype.slice.call(slider.querySelectorAll(".hero-slide--video"));
    const modal = document.getElementById("heroVideoModal");
    if (!videos.length) return;
    const reduz = jfReduzMovimento();
    let esperaArranque = null;

    const iframe = function (s) { return s.querySelector(".hero-video iframe"); };
    const temLeitor = function (s) { const f = iframe(s); return !!(f && f.getAttribute("src")); };
    function comando(s, func, args) {
        const f = iframe(s);
        if (f && f.getAttribute("src") && f.contentWindow) {
            f.contentWindow.postMessage(JSON.stringify({ event: "command", func: func, args: args || [] }), "*");
        }
    }
    const api = function () { return window.jfSlider; };
    const modalAberto = function () { return modal && modal.open; };

    // 16/10/2026: a imagem de capa fica 2 s à vista depois de o slide entrar; só depois o vídeo arranca
    // (e aparece com o desvanecer de .9 s) — antes a capa mal se via e parecia um salto imagem → vídeo
    const CAPA_MS = 2000;
    let esperaCapa = null;
    function sincronizar() {
        clearTimeout(esperaArranque);
        clearTimeout(esperaCapa);
        videos.forEach(function (s) {
            const ativo = s.classList.contains("active");
            if (ativo && !s._entrou) s._entrou = Date.now();
            if (!ativo) s._entrou = 0;
            const tocar = ativo && temLeitor(s) && !reduz && !document.hidden && !modalAberto() && !(api() && api().pausado());
            const falta = tocar && !s.classList.contains("a-tocar") ? CAPA_MS - (Date.now() - s._entrou) : 0;
            if (falta > 0) {
                comando(s, "pauseVideo");
                if (api()) api().parar();            // o slider não avança enquanto se espera pelo vídeo
                esperaCapa = setTimeout(sincronizar, falta + 20);
                return;
            }
            if (tocar) {
                comando(s, "mute");
                comando(s, "playVideo");
                // no slide de vídeo o slider fica parado; se o vídeo demorar a arrancar, insiste
                if (api()) api().parar();
                esperaArranque = setTimeout(function () { if (!s.classList.contains("a-tocar") && s.classList.contains("active")) { comando(s, "mute"); comando(s, "playVideo"); } }, 2500);
            } else {
                comando(s, "pauseVideo");
                if (!ativo) { comando(s, "seekTo", [0, true]); s.classList.remove("a-tocar"); }
            }
        });
    }

    // o leitor avisa quando está pronto e quando muda de estado
    videos.forEach(function (s, n) {
        const f = iframe(s);
        if (!f) return;
        f.addEventListener("load", function () {
            f.contentWindow.postMessage(JSON.stringify({ event: "listening", id: "heroVideo" + n, channel: "widget" }), "*");
            setTimeout(sincronizar, 300);
        });
    });
    window.addEventListener("message", function (e) {
        if (!/youtube(-nocookie)?\.com$/.test((e.origin || "").replace(/^https?:\/\//, "").replace(/^www\./, ""))) return;
        let d; try { d = typeof e.data === "string" ? JSON.parse(e.data) : e.data; } catch (x) { return; }
        if (!d) return;
        const s = videos.filter(function (v) { const f = iframe(v); return f && f.contentWindow === e.source; })[0];
        if (!s) return;
        let estado = null;
        if (d.event === "onReady") { sincronizar(); return; }
        if (d.event === "onStateChange") estado = d.info;
        else if (d.event === "infoDelivery" && d.info && typeof d.info.playerState === "number") estado = d.info.playerState;
        if (estado === 1) {                         // a tocar
            s.classList.add("a-tocar");
            clearTimeout(esperaArranque);
            if (s.classList.contains("active") && api()) api().parar();
        } else if (estado === 0 && s.classList.contains("active")) {   // acabou: recomeça (loop)
            comando(s, "seekTo", [0, true]);
            if (!(api() && api().pausado()) && !modalAberto() && !document.hidden) comando(s, "playVideo");
        }
    });

    // leitor com 1920 px reais, reduzido para cobrir (ou caber, em «ajustar») o slide
    function escalar() {
        videos.forEach(function (s) {
            const f = parseFloat(getComputedStyle(s).getPropertyValue("--video-formato")) || 1.7778;
            const w = s.clientWidth, h = s.clientHeight;
            if (!w || !h) return;
            const kx = w / 1920, ky = h / (1920 / f);
            s.style.setProperty("--video-escala", (s.classList.contains("hero-slide--ajustar") ? Math.min(kx, ky) : Math.max(kx, ky)).toFixed(4));
        });
    }
    escalar();
    window.addEventListener("resize", escalar);

    // depois de o slider mudar (o setTimeout deixa os botões reiniciarem o temporizador primeiro)
    slider.addEventListener("jf-slide", function () { setTimeout(sincronizar, 0); });
    slider.addEventListener("jf-slider-pausa", function () { setTimeout(sincronizar, 0); });
    document.addEventListener("visibilitychange", function () { setTimeout(sincronizar, 0); });

    /* ---- «Ver com som»: leitor completo numa janela ---- */
    if (!modal) return;
    const quadro = modal.querySelector(".hero-video-modal__quadro");
    const consentir = modal.querySelector(".hero-video-modal__consentir");
    const titulo = modal.querySelector(".hero-video-modal__titulo");
    let slideDoModal = null, origem = null;
    const aceitou = function () { try { return localStorage.getItem("rm_cookie_consent") === "accepted"; } catch (x) { return false; } };

    function carregarLeitor() {
        consentir.hidden = true;
        quadro.hidden = false;
        quadro.innerHTML = "";
        const f = document.createElement("iframe");
        f.src = slideDoModal.dataset.videoLeitor;
        f.title = titulo.textContent;
        f.allow = "autoplay; encrypted-media; picture-in-picture; fullscreen";
        f.allowFullscreen = true;
        quadro.appendChild(f);
    }
    slider.addEventListener("click", function (e) {
        const b = e.target.closest("[data-video-abrir]");
        if (!b) return;
        slideDoModal = b.closest(".hero-slide");
        origem = b;
        const h = slideDoModal.querySelector(".hero-overlay h1, .hero-overlay h2");
        titulo.textContent = h ? h.textContent.trim() : "";
        if (api()) api().parar();
        modal.showModal();
        sincronizar();                                  // pausa o vídeo de fundo
        if (aceitou()) carregarLeitor();
        else { quadro.hidden = true; consentir.hidden = false; }
    });
    modal.querySelector("[data-video-aceitar]").addEventListener("click", function () {
        if (window.rmAcceptCookies) window.rmAcceptCookies();
        carregarLeitor();
    });
    modal.querySelector("[data-video-fechar]").addEventListener("click", function () { modal.close(); });
    modal.addEventListener("click", function (e) { if (e.target === modal) modal.close(); });   // clicar fora fecha
    modal.addEventListener("close", function () {
        quadro.innerHTML = "";                          // pára o som
        if (origem) origem.focus({ preventScroll: true });
        sincronizar();
        const ativo = slider.querySelector(".hero-slide.active");
        if (api() && !(ativo && ativo.classList.contains("hero-slide--video") && temLeitor(ativo))) api().retomar();
    });
});


/* ---------- Pontos de interesse: quadro grande em apresentação (12/10/2026) -------
   Roda pelos pontos que não estão nos cartões pequenos (lista feita no PHP). 5 s cada; pára com
   o rato ou o foco no quadro, fora do ecrã, com o separador escondido e com o botão de pausa
   (WCAG 2.2.2). Com «reduzir movimento» não roda sozinho (os pontinhos continuam a funcionar).
   Só o rosto à vista é focável/lido (os outros têm inert + aria-hidden). */
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-pontos-rotativo]").forEach(function (caixa) {
        const faces = Array.prototype.slice.call(caixa.querySelectorAll(":scope > .ponto-tile"));
        const botoes = Array.prototype.slice.call(caixa.querySelectorAll("[data-rotativo-ir]"));
        const pausa = caixa.querySelector("[data-rotativo-pausa]");
        if (faces.length < 2) { if (faces[0]) faces[0].classList.add("is-ativo"); return; }
        const INTERVALO = 5000;
        caixa.style.setProperty("--rot-intervalo", INTERVALO + "ms");
        let atual = 0, timer = null, pausadoUtilizador = jfReduzMovimento(), rato = false, visivel = false;

        function mostrar(i) {
            atual = (i + faces.length) % faces.length;
            faces.forEach(function (f, k) {
                const sim = k === atual;
                f.classList.toggle("is-ativo", sim);
                if (sim) { f.removeAttribute("inert"); f.removeAttribute("aria-hidden"); }
                else { f.setAttribute("inert", ""); f.setAttribute("aria-hidden", "true"); }
            });
            botoes.forEach(function (b, k) { if (k === atual) b.setAttribute("aria-current", "true"); else b.removeAttribute("aria-current"); });
            // reinicia a barra de tempo do ponto ativo
            caixa.classList.remove("a-rodar"); void caixa.offsetWidth; if (timer) caixa.classList.add("a-rodar");
        }
        function parar() { clearInterval(timer); timer = null; caixa.classList.remove("a-rodar"); }
        function decidir() {
            const deve = !pausadoUtilizador && !rato && visivel && !document.hidden;
            if (deve && !timer) { timer = setInterval(function () { mostrar(atual + 1); }, INTERVALO); caixa.classList.add("a-rodar"); }
            else if (!deve && timer) parar();
        }
        function atualizarPausa() {
            if (!pausa) return;
            pausa.setAttribute("aria-label", pausadoUtilizador ? "Retomar a apresentação" : "Pausar a apresentação");
            pausa.innerHTML = '<i class="bi ' + (pausadoUtilizador ? "bi-play-fill" : "bi-pause-fill") + '" aria-hidden="true"></i>';
        }

        faces.forEach(function (f, k) { f.classList.toggle("is-ativo", k === 0); });
        // arrastar com o dedo (13/10/2026); no telemóvel estreito a fila de cartões já desliza
        // na horizontal, por isso aí o gesto fica para a fila
        if (!window.matchMedia || window.matchMedia("(min-width: 561px)").matches) {
            jfArrastar(caixa, {
                elementos: function () { return faces; },
                atual: function () { return atual; },
                ir: function (i) { parar(); mostrar(i); },
                inicio: function () { rato = true; decidir(); },
                fim: function () { rato = false; decidir(); }
            });
        }
        botoes.forEach(function (b) {
            b.addEventListener("click", function () { parar(); mostrar(+b.dataset.rotativoIr); decidir(); });
        });
        if (pausa) pausa.addEventListener("click", function () { pausadoUtilizador = !pausadoUtilizador; atualizarPausa(); parar(); decidir(); });
        // setas anterior / seguinte (16/10/2026)
        caixa.querySelectorAll("[data-rotativo-ant], [data-rotativo-seg]").forEach(function (s) {
            s.addEventListener("click", function (e) { e.preventDefault(); e.stopPropagation(); parar(); mostrar(atual + (s.hasAttribute("data-rotativo-seg") ? 1 : -1)); decidir(); });
        });
        caixa.addEventListener("keydown", function (e) {
            if (e.target.closest("input, textarea")) return;
            if (e.key === "ArrowLeft" || e.key === "ArrowRight") { e.preventDefault(); parar(); mostrar(atual + (e.key === "ArrowRight" ? 1 : -1)); decidir(); }
        });
        caixa.addEventListener("mouseenter", function () { rato = true; decidir(); });
        caixa.addEventListener("mouseleave", function () { rato = false; decidir(); });
        caixa.addEventListener("focusin", function () { rato = true; decidir(); });
        caixa.addEventListener("focusout", function (e) { if (!caixa.contains(e.relatedTarget)) { rato = false; decidir(); } });
        document.addEventListener("visibilitychange", decidir);
        if ("IntersectionObserver" in window) {
            new IntersectionObserver(function (en) { visivel = en[0].isIntersecting; decidir(); }, { threshold: 0.35 }).observe(caixa);
        } else { visivel = true; }
        atualizarPausa();
        decidir();
    });
});

/* ---------- «Foto partilhada» entre a lista e o detalhe (15/10/2026, ponto 2a) ----------
   Ao clicar num cartão que leva a uma notícia, evento ou ponto de interesse, a fotografia do cartão recebe o
   nome «foto-destaque» — o mesmo da fotografia grande da página de detalhe (CSS) — e o browser anima uma na
   outra (View Transitions entre páginas). Só no clique: nunca há dois elementos com o mesmo nome na página. */
(function () {
    if (!("onpagereveal" in window) && !CSS.supports("view-transition-name: a")) return;
    const ALVO = /\/(noticia|evento|ponto)\.php\?id=\d+/;
    let marcada = null;
    function limpar() { if (marcada) { marcada.style.viewTransitionName = ""; marcada = null; } }
    document.addEventListener("click", function (ev) {
        if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
        const a = ev.target.closest && ev.target.closest("a[href]");
        if (!a || a.target === "_blank" || !ALVO.test(a.getAttribute("href"))) return;
        if (typeof jfReduzMovimento === "function" && jfReduzMovimento()) return;
        // a fotografia do cartão: dentro do link ou, se o link for só o título, no cartão à volta
        const cartao = a.querySelector("img") ? a : a.closest("article, li, [class*='card'], [class*='tile'], [class*='item']");
        const img = cartao && cartao.querySelector("img");
        if (!img || !img.complete || !img.naturalWidth) return;
        limpar();
        img.style.viewTransitionName = "foto-destaque";
        marcada = img;
        setTimeout(limpar, 3000);   // se a navegação não acontecer (ex.: abriu um lightbox), tira o nome
    }, true);
    // voltar atrás (cache do browser): a página regressa sem nomes pendurados
    window.addEventListener("pageshow", limpar);
})();

/* ---------- Nome acessível dos campos de formulário (16/10/2026, auditoria móvel) ----------
   Muitos formulários têm o rótulo como <label> solto antes do campo, sem «for»: o leitor de ecrã não anunciava
   o nome do campo e tocar no rótulo não punha o cursor no campo. Liga cada rótulo solto ao campo que vem logo a
   seguir; os campos que mesmo assim fiquem sem nome recebem-no do placeholder ou da 1.ª opção («Escolha…»). */
(function () {
    const CAMPO = "input:not([type=hidden]):not([type=submit]):not([type=button]), select, textarea";
    let n = 0;
    document.querySelectorAll("label:not([for])").forEach(function (l) {
        if (l.querySelector(CAMPO)) return;
        let c = l.nextElementSibling;
        if (c && !c.matches(CAMPO)) c = c.querySelector(":scope > " + CAMPO.split(", ").join(", :scope > "));
        if (!c || !c.matches(CAMPO) || c.labels && c.labels.length) return;
        if (!c.id) c.id = "campo-auto-" + (++n);
        l.htmlFor = c.id;
    });
    document.querySelectorAll(CAMPO).forEach(function (c) {
        if ((c.labels && c.labels.length) || c.getAttribute("aria-label") || c.getAttribute("aria-labelledby") || c.title) return;
        let nome = c.placeholder || "";
        if (!nome && c.tagName === "SELECT" && c.options.length && c.options[0].value === "") nome = c.options[0].text;
        if (nome) c.setAttribute("aria-label", nome.replace(/\s*\*\s*$/, "").trim());
    });
    // tabelas que deslizam na horizontal (telemóvel) têm de se poder focar para deslizar com o teclado
    document.querySelectorAll(".legal-table, .table-responsive, [data-desliza]").forEach(function (t) {
        if (t.scrollWidth > t.clientWidth + 1 && !t.hasAttribute("tabindex")) { t.tabIndex = 0; t.setAttribute("role", t.getAttribute("role") || "region"); if (!t.getAttribute("aria-label")) t.setAttribute("aria-label", "Tabela (deslize para ver tudo)"); }
    });
})();

// Barra de atalhos (ícones) no telemóvel: desliza devagar sozinha, como os parceiros do gesgov.pt.
// Tocar pausa (o deslizar com o dedo é o scroll nativo do browser) e retoma 1,5 s depois de largar.
// A lista é duplicada (cópias escondidas no computador) para o ciclo parecer infinito.
document.addEventListener("DOMContentLoaded", function () {
    const faixa = document.querySelector(".home-atalhos-grid");
    if (!faixa || faixa.dataset.carrossel === "1") return;
    faixa.dataset.carrossel = "1";

    Array.prototype.slice.call(faixa.children).forEach(function (a) {
        const c = a.cloneNode(true);
        c.setAttribute("data-copia", "");
        c.setAttribute("aria-hidden", "true");
        c.tabIndex = -1;
        faixa.appendChild(c);
    });

    const telemovel = window.matchMedia("(max-width: 700px)");
    const semMovimento = window.matchMedia("(prefers-reduced-motion: reduce)");
    let pausado = false, retomar = null, posicao = 0, anterior = performance.now();

    function pausar() { pausado = true; clearTimeout(retomar); }
    function retomarDepois() { clearTimeout(retomar); retomar = setTimeout(function () { pausado = false; }, 1500); }
    faixa.addEventListener("touchstart", pausar, { passive: true });
    faixa.addEventListener("touchend", retomarDepois, { passive: true });
    faixa.addEventListener("touchcancel", retomarDepois, { passive: true });

    function passo(agora) {
        const dt = (agora - anterior) / 1000;
        anterior = agora;
        if (telemovel.matches && !semMovimento.matches && !document.hidden) {
            if (pausado) {
                posicao = faixa.scrollLeft;
            } else {
                posicao += 26 * dt;                       // px por segundo: devagar de propósito
                const metade = faixa.scrollWidth / 2;
                if (posicao >= metade) posicao -= metade;
                faixa.scrollLeft = posicao;
            }
        }
        requestAnimationFrame(passo);
    }
    requestAnimationFrame(passo);
});
