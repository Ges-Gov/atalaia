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
        if (slides.length > 1) {
            timer = setInterval(nextSlide, 5500);
        }
    }

    function stopSlider() {
        clearInterval(timer);
    }

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

    if (slider) {
        slider.addEventListener("mouseenter", stopSlider);
        slider.addEventListener("mouseleave", startSlider);
    }

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
