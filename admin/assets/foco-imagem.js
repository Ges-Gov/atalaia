// Widget de ponto focal para imagens de capa (notícias/eventos).
// Permite marcar o ponto mais importante da foto para que fique sempre
// visível quando a imagem é cortada (object-fit:cover) em proporções
// diferentes (cartão de listagem vs. topo da página).
function initFocoPicker(opts) {
    var fileInput = document.getElementById(opts.fileInputId);
    var wrap = document.getElementById(opts.previewWrapId);
    var img = document.getElementById(opts.previewImgId);
    var marker = document.getElementById(opts.markerId);
    var hiddenX = document.getElementById(opts.hiddenXId);
    var hiddenY = document.getElementById(opts.hiddenYId);

    if (!fileInput || !wrap || !img || !marker || !hiddenX || !hiddenY) return;

    function setMarker(xPct, yPct) {
        xPct = Math.max(0, Math.min(100, xPct));
        yPct = Math.max(0, Math.min(100, yPct));
        hiddenX.value = Math.round(xPct);
        hiddenY.value = Math.round(yPct);
        marker.style.left = xPct + '%';
        marker.style.top = yPct + '%';
    }

    function showPreview(url) {
        img.src = url;
        wrap.hidden = false;
    }

    function pickFromEvent(e) {
        var rect = img.getBoundingClientRect();
        var xPct = ((e.clientX - rect.left) / rect.width) * 100;
        var yPct = ((e.clientY - rect.top) / rect.height) * 100;
        setMarker(xPct, yPct);
    }

    if (opts.existingUrl) {
        showPreview(opts.existingUrl);
        setMarker(opts.initialX != null ? opts.initialX : 50, opts.initialY != null ? opts.initialY : 50);
    }

    fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files[0]) {
            showPreview(URL.createObjectURL(fileInput.files[0]));
            setMarker(50, 50);
        }
    });

    img.addEventListener('click', pickFromEvent);
}
