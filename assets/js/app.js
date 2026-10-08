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

    function showSlide(index) {
        if (!slides.length) return;

        slides[current].classList.remove("active");
        if (dots[current]) dots[current].classList.remove("active");

        current = (index + slides.length) % slides.length;

        slides[current].classList.add("active");
        if (dots[current]) dots[current].classList.add("active");
    }

    function nextSlide() {
        showSlide(current + 1);
    }

    function prevSlide() {
        showSlide(current - 1);
    }

    function startSlider() {
        if (timer) return;                       // nunca dois temporizadores ao mesmo tempo
        if (slides.length > 1) {
            timer = setInterval(nextSlide, 5500);
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
    // Marca automaticamente blocos de conteúdo comuns para animação ao scroll,
    // sem necessidade de alterar cada página individualmente.
    const autoSelectors = [
        ".section-heading",
        ".card",
        ".content-box",
        ".service-card",
        ".quick-card",
        ".quick-action-card",
        ".freguesia-stat-card",
        ".premium-list-block",
        ".premium-news-card",
        ".premium-event-card",
        ".docs-cta",
        ".welcome-grid > *",
        ".contact-grid > *"
    ];

    document.querySelectorAll(autoSelectors.join(",")).forEach(function (el) {
        if (!el.classList.contains("reveal") && !el.closest(".site-header") && !el.closest(".rm-footer")) {
            el.classList.add("reveal");
        }
    });

    const reveals = document.querySelectorAll(".reveal");

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry, index) {
                if (entry.isIntersecting) {
                    const delay = Math.min(index % 4, 3) * 80;
                    entry.target.style.transitionDelay = delay + "ms";
                    entry.target.classList.add("active");
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: "0px 0px -60px 0px"
        });

        reveals.forEach(function (el) {
            observer.observe(el);
        });
    } else {
        // Fallback para browsers sem suporte a IntersectionObserver
        function revealOnScroll() {
            reveals.forEach(function (el) {
                const windowHeight = window.innerHeight;
                const elementTop = el.getBoundingClientRect().top;

                if (elementTop < windowHeight - 80) {
                    el.classList.add("active");
                }
            });
        }

        window.addEventListener("scroll", revealOnScroll);
        revealOnScroll();
    }

    // Header com efeito de scroll (sombra/blur)
    const siteHeader = document.querySelector(".site-header");

    if (siteHeader) {
        function toggleHeaderScroll() {
            siteHeader.classList.toggle("is-scrolled", window.scrollY > 12);
        }

        window.addEventListener("scroll", toggleHeaderScroll);
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


// Fotos de notícias, eventos e pontos de interesse: a foto principal troca com as setas (no ecrã e no
// teclado) e com o dedo; miniaturas por baixo. Clicar abre o visualizador na foto que está à vista.
// A página marca a foto principal com data-carrossel e data-fotos='["url", ...]'; sem JS fica como antes.
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-carrossel]").forEach(function (alvo) {
        if (alvo.dataset.carrosselPronto === "1") return;
        alvo.dataset.carrosselPronto = "1";

        let fotos = [];
        try { fotos = JSON.parse(alvo.dataset.fotos || "[]"); } catch (e) { return; }
        fotos = fotos.filter(function (f, i) { return f && fotos.indexOf(f) === i; });
        if (fotos.length < 2) return;

        const img = alvo.tagName === "IMG" ? alvo : alvo.querySelector("img");
        if (!img) return;
        const focoPrincipal = img.style.objectPosition;
        let atual = 0;

        const moldura = document.createElement("div");
        moldura.className = "carrossel-moldura";
        alvo.parentNode.insertBefore(moldura, alvo);
        moldura.appendChild(alvo);

        function seta(classe, rotulo, texto) {
            const b = document.createElement("button");
            b.type = "button";
            b.className = "carrossel-seta " + classe;
            b.setAttribute("aria-label", rotulo);
            b.textContent = texto;
            moldura.appendChild(b);
            return b;
        }
        const anterior = seta("anterior", "Foto anterior", "‹");
        const seguinte = seta("seguinte", "Foto seguinte", "›");
        const contador = document.createElement("span");
        contador.className = "carrossel-contador";
        moldura.appendChild(contador);

        const tira = document.createElement("div");
        tira.className = "carrossel-miniaturas";
        fotos.forEach(function (src, i) {
            const t = document.createElement("button");
            t.type = "button";
            t.setAttribute("aria-label", "Ver foto " + (i + 1) + " de " + fotos.length);
            const m = document.createElement("img");
            m.src = src;
            m.alt = "";
            m.loading = "lazy";
            t.appendChild(m);
            t.addEventListener("click", function () { mostrar(i); });
            tira.appendChild(t);
        });
        moldura.insertAdjacentElement("afterend", tira);

        function mostrar(i) {
            atual = (i + fotos.length) % fotos.length;
            img.src = fotos[atual];
            img.style.objectPosition = atual === 0 ? focoPrincipal : "50% 50%";
            Array.prototype.forEach.call(tira.children, function (t, k) {
                t.classList.toggle("ativa", k === atual);
                if (k === atual) {
                    const esq = t.offsetLeft - (tira.clientWidth - t.offsetWidth) / 2;
                    tira.scrollTo({ left: Math.max(0, esq), behavior: "smooth" });
                }
            });
            contador.textContent = (atual + 1) + " / " + fotos.length;
        }

        anterior.addEventListener("click", function (e) { e.stopPropagation(); mostrar(atual - 1); });
        seguinte.addEventListener("click", function (e) { e.stopPropagation(); mostrar(atual + 1); });

        // Clicar na foto abre o visualizador (se a página o tiver) na foto que está à vista
        alvo.removeAttribute("onclick");
        alvo.addEventListener("click", function (e) {
            if (typeof window.lbAbrir === "function") {
                e.preventDefault();
                window.lbAbrir(fotos, atual);
            }
        });

        // Setas do teclado (exceto a escrever num campo ou com o visualizador aberto)
        document.addEventListener("keydown", function (e) {
            if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") return;
            const lb = document.getElementById("lbOverlay");
            if (lb && lb.classList.contains("open")) return;
            const t = e.target;
            if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
            mostrar(atual + (e.key === "ArrowRight" ? 1 : -1));
        });

        // Deslizar com o dedo (como no slider: decide também no touchcancel)
        let x0 = null, y0 = null, xU = null, yU = null;
        moldura.addEventListener("touchstart", function (e) {
            if (e.touches.length !== 1) { x0 = null; return; }
            x0 = xU = e.touches[0].clientX;
            y0 = yU = e.touches[0].clientY;
        }, { passive: true });
        moldura.addEventListener("touchmove", function (e) {
            if (x0 === null) return;
            xU = e.touches[0].clientX;
            yU = e.touches[0].clientY;
        }, { passive: true });
        function fimToque(e) {
            if (x0 === null) return;
            const t = e.changedTouches && e.changedTouches[0];
            const dx = (t ? t.clientX : xU) - x0;
            const dy = (t ? t.clientY : yU) - y0;
            x0 = null;
            if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy) * 1.2) return;
            mostrar(atual + (dx < 0 ? 1 : -1));
        }
        moldura.addEventListener("touchend", fimToque, { passive: true });
        moldura.addEventListener("touchcancel", fimToque, { passive: true });

        // A grelha antiga de fotos deixa de ser precisa: as miniaturas substituem-na
        document.querySelectorAll("[data-carrossel-substitui]").forEach(function (g) { g.hidden = true; });

        mostrar(0);
    });
});
