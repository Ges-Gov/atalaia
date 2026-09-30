<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/config.php";
require_once "includes/cidadao_auth.php";
require_once "includes/mail_helper.php";

$cidadaoId = $_SESSION['cidadao_id'];
$pedidoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT * FROM pedidos_junta
    WHERE id = ? AND cidadao_id = ?
    LIMIT 1
");
$stmt->execute([$pedidoId, $cidadaoId]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("Pedido não encontrado.");
}

$pdo->prepare("
    UPDATE pedido_mensagens 
    SET lida = 1 
    WHERE pedido_id = ? 
    AND autor_tipo = 'admin'
")->execute([$pedidoId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($mensagem !== '') {

        $stmt = $pdo->prepare("
            INSERT INTO pedido_mensagens 
            (pedido_id, cidadao_id, autor_tipo, autor_nome, mensagem, lida)
            VALUES (?, ?, 'cidadao', ?, ?, 0)
        ");

        $stmt->execute([
            $pedidoId,
            $cidadaoId,
            $_SESSION['cidadao_nome'],
            $mensagem
        ]);

        $emailJunta = siteConfig('email_notificacoes', siteConfig('email'));

        if (!empty($emailJunta)) {

            $html = "
                <h2>Nova mensagem num pedido</h2>
                <p><strong>Pedido:</strong> " . htmlspecialchars($pedido['codigo']) . "</p>
                <p><strong>Assunto:</strong> " . htmlspecialchars($pedido['assunto']) . "</p>
                <p><strong>Cidadão:</strong> " . htmlspecialchars($_SESSION['cidadao_nome']) . "</p>
                <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
            ";

            enviarEmailSistema(
                $emailJunta,
                "Nova mensagem no pedido " . $pedido['codigo'],
                $html
            );
        }

        header("Location: /pedido-detalhe.php?id=" . $pedidoId);
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT *
    FROM pedido_mensagens
    WHERE pedido_id = ?
    ORDER BY criado_em ASC
");
$stmt->execute([$pedidoId]);
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estadoClasse = 'estado-' . ($pedido['estado'] ?? 'pendente');

require_once "includes/header.php";
?>

<section class="page-hero">
    <div class="container">
        <h1>Pedido <?= htmlspecialchars($pedido['codigo']) ?></h1>
        <p><?= htmlspecialchars($pedido['assunto']) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">

        <div class="pedido-consulta-topo content-box">

            <div>
                <span class="section-kicker">Área do Cidadão</span>
                <h2><?= htmlspecialchars($pedido['assunto']) ?></h2>
                <p>
                    Acompanhe o estado do seu pedido e converse diretamente com a Junta.
                </p>
            </div>

            <div>
                <span class="estado-badge <?= htmlspecialchars($estadoClasse) ?>">
                    <?= htmlspecialchars(str_replace('_', ' ', $pedido['estado'])) ?>
                </span>
            </div>

        </div>

        <div class="pedido-consulta-grid">

            <div>
                <strong>Código</strong>
                <span><?= htmlspecialchars($pedido['codigo']) ?></span>
            </div>

            <div>
                <strong>Categoria</strong>
                <span><?= htmlspecialchars($pedido['categoria'] ?? '-') ?></span>
            </div>

            <div>
                <strong>Data</strong>
                <span><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></span>
            </div>

            <div>
                <strong>Estado</strong>
                <span><?= htmlspecialchars(str_replace('_', ' ', $pedido['estado'])) ?></span>
            </div>

        </div>

        <div class="content-box" style="margin-bottom:25px;">
            <h2>Mensagem inicial</h2>

            <div class="resposta-junta-box">
                <?= nl2br(htmlspecialchars($pedido['mensagem'])) ?>
            </div>

            <?php if (!empty($pedido['resposta'])): ?>
                <div style="margin-top:22px;">
                    <h3>Resposta da Junta</h3>
                    <div class="resposta-junta-box">
                        <?= nl2br(htmlspecialchars($pedido['resposta'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-box">
            <div style="display:flex;justify-content:space-between;gap:15px;align-items:center;flex-wrap:wrap;margin-bottom:18px;">
                <div>
                    <span class="section-kicker">Conversação</span>
                    <h2 style="margin:6px 0 0;">Chat com a Junta</h2>
                </div>

                <a class="btn secondary" href="/minha-area.php">
                    Voltar à Área do Cidadão
                </a>
            </div>

            <div class="chat-box" id="chatBox">

                <?php if (!empty($mensagens)): ?>

                    <?php foreach ($mensagens as $m): ?>

                        <div class="chat-msg <?= $m['autor_tipo'] === 'cidadao' ? 'cidadao' : 'admin' ?>">
                            <strong>
                                <?= $m['autor_tipo'] === 'cidadao' ? '<i class="bi bi-person"></i> ' : '<i class="bi bi-bank"></i> ' ?>
                                <?= htmlspecialchars($m['autor_nome']) ?>
                            </strong>

                            <p><?= nl2br(htmlspecialchars($m['mensagem'])) ?></p>

                            <small>
                                <?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?>
                            </small>
                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="resposta-junta-box">
                        Ainda não existem mensagens neste pedido.
                    </div>

                <?php endif; ?>

            </div>

            <div id="typingStatus" style="font-size:13px;color:#6b7280;margin-top:8px;"></div>

            <form method="POST" class="form-publico chat-form">

                <textarea
                    id="msgInput"
                    name="mensagem"
                    placeholder="Escreva uma mensagem para a Junta..."
                    required
                ></textarea>

                <button class="btn btn-submit-pro" type="submit">
                    <span>Enviar mensagem</span>
                </button>

            </form>
        </div>

    </div>
</section>

<script>
const pedidoId = <?= (int)$pedidoId ?>;

const chatBox = document.getElementById("chatBox");
const msgInput = document.getElementById("msgInput");
const typingStatus = document.getElementById("typingStatus");

function loadChat() {
    fetch('/chat_fetch.php?pedido_id=' + pedidoId)
        .then(res => res.text())
        .then(html => {
            if (chatBox && html.trim() !== '') {
                chatBox.innerHTML = html;
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        })
        .catch(() => {});
}

function sendTyping() {
    fetch("/typing_update.php", {
        method: "POST",
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: "pedido_id=" + pedidoId + "&tipo=cidadao"
    }).catch(() => {});
}

function loadTyping() {
    fetch("/typing_fetch.php?pedido_id=" + pedidoId + "&ver=cidadao")
        .then(res => res.text())
        .then(txt => {
            if (typingStatus) {
                typingStatus.innerText = txt;
            }
        })
        .catch(() => {});
}

if (msgInput) {
    msgInput.addEventListener("input", sendTyping);
}

if (chatBox) {
    chatBox.scrollTop = chatBox.scrollHeight;
}

setInterval(loadChat, 4000);
setInterval(loadTyping, 1000);
</script>

<?php require_once "includes/footer.php"; ?>