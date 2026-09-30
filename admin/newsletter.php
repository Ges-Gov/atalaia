<?php
$adminPageTitle = "Newsletter";
$adminActive = "newsletter";
require_once "includes/header.php";
require_once __DIR__ . "/../includes/newsletter.php";

$mensagem = '';
$erro = '';
$reabrirModalEnvio = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['guardar_definicoes'])) {
        $autoAtivo = isset($_POST['auto_ativo']) ? 1 : 0;
        $diaEnvio = max(1, min(31, (int) ($_POST['dia_envio'] ?? 1)));
        $conteudoManual = trim($_POST['conteudo_manual'] ?? '');

        $destaqueRaw = trim($_POST['destaque'] ?? '');
        $destaqueTipo = null;
        $destaqueId = null;
        if ($destaqueRaw !== '') {
            [$destaqueTipo, $destaqueId] = explode(':', $destaqueRaw, 2);
        }

        $pdo->prepare("
            UPDATE newsletter_config
            SET auto_ativo = ?, dia_envio = ?, conteudo_manual = ?, destaque_tipo = ?, destaque_id = ?
            WHERE id = 1
        ")->execute([$autoAtivo, $diaEnvio, $conteudoManual ?: null, $destaqueTipo, $destaqueId]);

        $mensagem = "Definições guardadas.";

    } elseif (isset($_POST['enviar_agora'])) {
        $emailsSelecionados = $_POST['emails'] ?? [];

        $totalAtivosAgora = (int) $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE ativo = 1")->fetchColumn();

        if (empty($emailsSelecionados)) {
            $erro = "Escolha pelo menos um subscritor.";
            $reabrirModalEnvio = true;
        } elseif (count($emailsSelecionados) === $totalAtivosAgora) {
            // Todos os ativos selecionados = o envio oficial do mês (regista histórico).
            $enviados = newsletterEnviar($pdo);
            if ($enviados === 0) {
                $erro = "Não foi enviado nenhum email — verifique a configuração de envio (includes/mail_helper.php). Não ficou registado como o envio do mês, pode tentar de novo.";
                $reabrirModalEnvio = true;
            } else {
                $mensagem = "Newsletter enviada a {$enviados} subscritor(es) (envio oficial do mês).";
            }
        } else {
            // Seleção parcial = reenvio seletivo, não conta como o envio do mês.
            $enviados = newsletterEnviar($pdo, $emailsSelecionados);
            if ($enviados === 0) {
                $erro = "Não foi enviado nenhum email — verifique a configuração de envio (includes/mail_helper.php).";
                $reabrirModalEnvio = true;
            } else {
                $mensagem = "Newsletter reenviada a {$enviados} subscritor(es) selecionado(s) (não conta como o envio oficial do mês).";
            }
        }
    }
}

// NUNCA chamar isto "$config" — colide com a variável global do mesmo
// nome que includes/config.php usa para a configuração do SITE (lida por
// siteConfig()). Já aconteceu: o logo e o nome do site na pré-visualização
// ficavam sempre com o valor por omissão, porque este array substituía
// silenciosamente o outro.
$nlConfig = newsletterConfig($pdo);
$itens = newsletterItensDoMes($pdo);
$previewConteudo = !empty($nlConfig['conteudo_manual']) ? $nlConfig['conteudo_manual'] : newsletterComporMesAtual($pdo);
$previewHtml = newsletterTemplateEmail($previewConteudo);

$subscritoresAtivos = $pdo->query("SELECT email, nome FROM newsletter_subscribers WHERE ativo = 1 ORDER BY email ASC")->fetchAll(PDO::FETCH_ASSOC);
$totalSubscritoresAtivos = count($subscritoresAtivos);
$jaEnviadoEsteMes = $nlConfig['ultimo_envio'] === date('Y-m');

$historico = $pdo->query("SELECT * FROM newsletter_envios ORDER BY enviado_em DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.news-grid{display:grid;grid-template-columns:minmax(360px,.9fr) minmax(0,1.1fr);gap:20px;align-items:start;}
.news-preview-frame{width:100%;height:640px;border:1px solid #e5e7eb;border-radius:12px;background:#f1f5f9;}
.news-field{margin-bottom:16px;}
.news-field label{display:block;font-weight:800;margin-bottom:6px;font-size:13px;}
.news-check{display:flex;align-items:center;gap:8px;margin-bottom:16px;}
.news-hist-item{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #eef2f7;}
.news-hist-item:last-child{border-bottom:none;}
.news-alert{padding:14px 16px;border-radius:14px;margin-bottom:16px;font-weight:800;}
.news-alert.ok{background:#dcfce7;color:#166534;}
.news-alert.err{background:#fee2e2;color:#991b1b;}
.news-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,32,.55);z-index:1000;align-items:center;justify-content:center;padding:20px;}
.news-modal-overlay.aberto{display:flex;}
.news-modal{background:#fff;border-radius:20px;max-width:560px;width:100%;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 22px 60px rgba(0,0,0,.16);}
.news-modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px 0;}
.news-modal-header h3{margin:0;}
.news-modal-close{background:none;border:none;font-size:26px;line-height:1;cursor:pointer;color:#6b7280;padding:0;}
.news-modal-desc{padding:8px 24px 0;color:#64748b;font-size:13px;margin:0;}
.news-modal-search{margin:16px 24px 0;padding:10px 14px;border:1px solid #e5e7eb;border-radius:10px;width:calc(100% - 48px);}
.news-modal-toggle{padding:14px 24px 6px;border-bottom:1px solid #eef2f7;}
.news-modal-toggle label{display:flex;align-items:center;gap:8px;font-weight:800;margin:0;}
.news-modal-lista{overflow-y:auto;padding:10px 24px;display:grid;grid-template-columns:1fr 1fr;gap:2px 16px;}
.news-modal-item{display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px;padding:6px 0;}
.news-modal-item.oculto{display:none;}
.news-modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px 24px;border-top:1px solid #eef2f7;margin-top:6px;}
@media(max-width:1100px){.news-grid{grid-template-columns:1fr;}}
@media(max-width:600px){.news-modal-lista{grid-template-columns:1fr;}}
</style>

<div class="content-box">
    <p>A newsletter compõe-se sozinha todos os meses a partir das notícias e eventos publicados — não precisa de escrever nada, a não ser que queira substituir o texto ou escolher um destaque. É enviada pela mesma conta de email partilhada por todos os sites (não usa a caixa de correio da freguesia).</p>
</div>

<?php if ($mensagem): ?><div class="news-alert ok" style="margin-top:15px;"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="news-alert err" style="margin-top:15px;"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="news-grid" style="margin-top:15px;">

    <div class="content-box">
        <h3>Definições e envio</h3>

        <form method="POST">
            <div class="news-check">
                <input type="checkbox" name="auto_ativo" id="autoAtivo" <?= $nlConfig['auto_ativo'] ? 'checked' : '' ?>>
                <label for="autoAtivo" style="margin:0;">Enviar automaticamente todos os meses</label>
            </div>

            <div class="news-field">
                <label>Dia do mês do envio automático</label>
                <input type="number" name="dia_envio" min="1" max="31" value="<?= (int) $nlConfig['dia_envio'] ?>" style="max-width:120px;">
                <small style="display:block;color:#64748b;margin-top:4px;">Se o mês não tiver esse dia (ex: 31 em fevereiro), envia no último dia do mês.</small>
            </div>

            <div class="news-field">
                <label>Destaque no topo</label>
                <select name="destaque">
                    <option value="">Automático (1ª notícia do mês)</option>
                    <?php foreach ($itens['noticias'] as $n): ?>
                        <option value="noticia:<?= (int) $n['id'] ?>" <?= ($nlConfig['destaque_tipo'] === 'noticia' && (int) $nlConfig['destaque_id'] === (int) $n['id']) ? 'selected' : '' ?>>
                            Notícia: <?= htmlspecialchars($n['titulo']) ?>
                        </option>
                    <?php endforeach; ?>
                    <?php foreach ($itens['eventos'] as $e): ?>
                        <option value="evento:<?= (int) $e['id'] ?>" <?= ($nlConfig['destaque_tipo'] === 'evento' && (int) $nlConfig['destaque_id'] === (int) $e['id']) ? 'selected' : '' ?>>
                            Evento: <?= htmlspecialchars($e['titulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="news-field">
                <label>Substituir por texto escrito à mão (opcional)</label>
                <div id="quillEditor" style="min-height:180px;background:#fff;"><?= $nlConfig['conteudo_manual'] ?? '' ?></div>
                <textarea name="conteudo_manual" id="conteudoManual" style="display:none;"><?= htmlspecialchars($nlConfig['conteudo_manual'] ?? '') ?></textarea>
                <button type="button" class="btn secondary" id="btnPreencherAuto" style="margin-top:8px;">Preencher com a composição automática</button>
                <small style="display:block;color:#64748b;margin-top:4px;">Se preencher isto, substitui totalmente o mês corrente (incluindo imagens que insira aqui). Limpa-se sozinho depois do próximo envio.</small>
            </div>

            <button class="btn" type="submit" name="guardar_definicoes" value="1" id="btnGuardarDefinicoes">Guardar definições</button>
        </form>

        <hr>

        <h3>Enviar agora</h3>
        <p><strong><?= $totalSubscritoresAtivos ?></strong> subscritor(es) ativo(s). Todos vêm pré-selecionados — desmarque quem não quiser incluir neste envio. Só conta como o envio oficial do mês se enviar a todos; desmarcar algum passa a ser um reenvio seletivo (ex: corrigir um email e reenviar só a essa pessoa), sem mexer no registo do mês.</p>
        <?php if ($jaEnviadoEsteMes): ?>
            <p style="color:#64748b;">Já foi enviada este mês (<?= date('d/m/Y', strtotime($historico[0]['enviado_em'] ?? 'now')) ?>). Enviar a todos outra vez substitui o registo deste mês.</p>
        <?php endif; ?>

        <?php if (empty($subscritoresAtivos)): ?>
            <p>Sem subscritores ativos.</p>
        <?php else: ?>
            <button type="button" class="btn" id="btnAbrirEnviarAgora">Enviar agora</button>

            <div class="news-modal-overlay" id="newsModalOverlay">
                <div class="news-modal">
                    <div class="news-modal-header">
                        <h3>Enviar a newsletter agora</h3>
                        <button type="button" class="news-modal-close" id="newsModalClose">&times;</button>
                    </div>
                    <p class="news-modal-desc">Não espera pelo dia configurado nem pelo interruptor de envio automático. Esta ação não pode ser desfeita.</p>
                    <form method="POST" id="newsEnviarForm" onsubmit="return confirm('Enviar a newsletter aos subscritores selecionados?');">
                        <input type="text" id="newsFiltro" class="news-modal-search" placeholder="Procurar por email...">
                        <div class="news-modal-toggle">
                            <label>
                                <input type="checkbox" id="newsSelecionarTodos" checked> Selecionar todos / nenhum
                            </label>
                        </div>
                        <div class="news-modal-lista">
                            <?php foreach ($subscritoresAtivos as $s): ?>
                                <label class="news-modal-item" data-email="<?= strtolower(htmlspecialchars($s['email'])) ?>">
                                    <input type="checkbox" name="emails[]" value="<?= htmlspecialchars($s['email']) ?>" class="news-check-email" checked>
                                    <?= htmlspecialchars($s['nome'] ? $s['nome'] . ' (' . $s['email'] . ')' : $s['email']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="news-modal-footer">
                            <button type="button" class="btn secondary" id="newsModalCancelar">Cancelar</button>
                            <button class="btn" type="submit" name="enviar_agora" value="1">Enviar</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <hr>

        <h3>Envios anteriores</h3>
        <?php if (empty($historico)): ?>
            <p>Ainda não houve nenhum envio.</p>
        <?php else: ?>
            <?php foreach ($historico as $h): ?>
                <div class="news-hist-item">
                    <span><?= date('m/Y', strtotime($h['mes_referencia'] . '-01')) ?> — <?= (int) $h['total_destinatarios'] ?> destinatário(s)</span>
                    <a class="btn secondary" href="ver_newsletter_envio.php?id=<?= (int) $h['id'] ?>">Ver</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="content-box">
        <h3>Pré-visualização</h3>
        <p style="color:#64748b;font-size:13px;">Exatamente como vai ficar o email (ou como está agora, se ainda não enviou este mês).</p>
        <iframe class="news-preview-frame" srcdoc="<?= htmlspecialchars($previewHtml, ENT_QUOTES, 'UTF-8') ?>"></iframe>
    </div>

</div>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
const quill = new Quill('#quillEditor', {
    theme: 'snow',
    modules: {
        toolbar: {
            container: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                ['link', 'image'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['clean'],
            ],
            handlers: {
                // Upload real para o servidor, nunca base64 — muitos
                // clientes de email (Outlook em particular) não mostram
                // imagens embutidas em base64.
                image: function () {
                    const input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    input.click();
                    input.onchange = function () {
                        const ficheiro = input.files[0];
                        if (!ficheiro) return;
                        const dados = new FormData();
                        dados.append('imagem', ficheiro);
                        fetch('newsletter-upload-imagem.php', { method: 'POST', body: dados })
                            .then(function (r) { return r.json(); })
                            .then(function (resposta) {
                                if (resposta.url) {
                                    const posicao = quill.getSelection(true).index;
                                    quill.insertEmbed(posicao, 'image', resposta.url);
                                    quill.setSelection(posicao + 1);
                                } else {
                                    alert(resposta.erro || 'Erro ao enviar a imagem.');
                                }
                            })
                            .catch(function () { alert('Erro ao enviar a imagem.'); });
                    };
                },
            },
        },
    },
});

// O formulário submete o <textarea> escondido, não o editor visível —
// sincroniza o HTML do Quill para lá mesmo antes de enviar.
document.getElementById('btnGuardarDefinicoes').closest('form').addEventListener('submit', function () {
    const vazio = quill.getText().trim() === '';
    document.getElementById('conteudoManual').value = vazio ? '' : quill.root.innerHTML;
});

document.getElementById('btnPreencherAuto').addEventListener('click', function () {
    if (confirm('Substitui o texto atual pela composição automática de agora. Continuar?')) {
        quill.clipboard.dangerouslyPasteHTML(<?= json_encode($previewConteudo, JSON_UNESCAPED_UNICODE) ?>);
    }
});

const selecionarTodos = document.getElementById('newsSelecionarTodos');
if (selecionarTodos) {
    const checksEmail = document.querySelectorAll('.news-check-email');
    selecionarTodos.addEventListener('change', function () {
        checksEmail.forEach(function (c) { c.checked = selecionarTodos.checked; });
    });
    checksEmail.forEach(function (c) {
        c.addEventListener('change', function () {
            selecionarTodos.checked = Array.from(checksEmail).every(function (x) { return x.checked; });
        });
    });
}

// Modal "Enviar agora": evita uma lista de checkboxes infinita na própria
// página quando há muitos subscritores (ex: 200+) — replica o padrão da
// Intranet (Filament abre um modal com pesquisa e "selecionar todos").
const newsOverlay = document.getElementById('newsModalOverlay');
const newsBtnAbrir = document.getElementById('btnAbrirEnviarAgora');
if (newsOverlay && newsBtnAbrir) {
    const abrirModalEnvio = function () { newsOverlay.classList.add('aberto'); };
    const fecharModalEnvio = function () { newsOverlay.classList.remove('aberto'); };

    newsBtnAbrir.addEventListener('click', abrirModalEnvio);
    document.getElementById('newsModalClose').addEventListener('click', fecharModalEnvio);
    document.getElementById('newsModalCancelar').addEventListener('click', fecharModalEnvio);
    newsOverlay.addEventListener('click', function (ev) {
        if (ev.target === newsOverlay) fecharModalEnvio();
    });

    const newsFiltro = document.getElementById('newsFiltro');
    newsFiltro.addEventListener('input', function () {
        const termo = newsFiltro.value.trim().toLowerCase();
        document.querySelectorAll('.news-modal-item').forEach(function (item) {
            item.classList.toggle('oculto', !item.dataset.email.includes(termo));
        });
    });

    <?php if ($reabrirModalEnvio): ?>
    abrirModalEnvio();
    <?php endif; ?>
}
</script>

<?php require_once "includes/footer.php"; ?>
