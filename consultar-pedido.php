<?php require_once "includes/header.php"; ?>

<?php
$pedido = null;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("
        SELECT * FROM pedidos_junta 
        WHERE codigo = ? AND email = ?
        LIMIT 1
    ");
    $stmt->execute([$codigo, $email]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        $erro = "Pedido não encontrado. Verifique o código e o email utilizado.";
    }
}

/* As duas funções tinham saído com o pacote do modelo novo e a página parava com erro mal o pedido era encontrado
   (não mostrava o estado, a resposta nem o resto) — repostas como no modelo antigo (27/10/2026). */
function estadoPedidoTexto($estado) {
    return match ($estado) {
        'pendente' => 'Pendente',
        'em_analise' => 'Em análise',
        'resolvido' => 'Resolvido',
        'arquivado' => 'Arquivado',
        default => ucfirst(str_replace('_', ' ', (string)$estado))
    };
}

function estadoPedidoClasse($estado) {
    return match ($estado) {
        'pendente' => 'estado-pendente',
        'em_analise' => 'estado-em_analise',
        'resolvido' => 'estado-resolvido',
        'arquivado' => 'estado-arquivado',
        default => ''
    };
}

/* «O que foi feito»: só o que é para o cidadão — mudanças de estado, encaminhamento para outra entidade e as
   respostas escritas pela Junta no pedido. Fica de fora o que é interno (operador atribuído, reclassificação,
   notas da equipa, nomes de funcionários e endereços de email). */
$historico = [];
if ($pedido) {
    $historico[] = ['data' => $pedido['criado_em'], 'icone' => 'bi-inbox', 'titulo' => 'Pedido recebido', 'texto' => ''];
    try {
        $st = $pdo->prepare("SELECT tipo, titulo, criado_em FROM pedido_timeline WHERE pedido_id = ? AND tipo IN ('estado', 'email_externo')");
        $st->execute([$pedido['id']]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $t) {
            if ($t['tipo'] === 'estado') {
                // o título é «Estado alterado para em analise»
                $novo = str_replace(' ', '_', trim((string)preg_replace('/^.*\bpara\s+/u', '', (string)$t['titulo'])));
                $historico[] = ['data' => $t['criado_em'], 'icone' => 'bi-arrow-repeat', 'titulo' => 'Estado: ' . estadoPedidoTexto($novo), 'texto' => ''];
            } else {
                $historico[] = ['data' => $t['criado_em'], 'icone' => 'bi-send', 'titulo' => 'Encaminhado para a entidade competente', 'texto' => ''];
            }
        }
        $st = $pdo->prepare("SELECT mensagem, criado_em FROM pedido_mensagens WHERE pedido_id = ? AND autor_tipo = 'admin'");
        $st->execute([$pedido['id']]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $historico[] = ['data' => $m['criado_em'], 'icone' => 'bi-chat-left-text', 'titulo' => 'Resposta da Junta', 'texto' => $m['mensagem']];
        }
    } catch (Throwable $e) {
        // sem as tabelas do seguimento: fica só «Pedido recebido»
    }
    usort($historico, fn($a, $b) => strcmp((string)$a['data'], (string)$b['data']));
}
// a caixa «Resposta da Junta» usa o campo resposta; se estiver vazio, a última resposta escrita no pedido
$respostaJunta = trim((string)($pedido['resposta'] ?? ''));
if ($respostaJunta === '') {
    foreach ($historico as $h) if ($h['titulo'] === 'Resposta da Junta') $respostaJunta = $h['texto'];
}

?>

<section class="consulta-hero-premium">
    <div class="container">
        <div>
            <span><?= te('consultar_pedido.hero.kicker') ?></span>
            <h1><?= te('consultar_pedido.hero.titulo') ?></h1>
            <p><?= te('consultar_pedido.hero.texto') ?></p>
        </div>

        <div class="consulta-hero-icon"><i class="bi bi-search"></i></div>
    </div>
</section>

<section class="section consulta-premium-section">
    <div class="container">

        <div class="consulta-wrapper">

            <div class="consulta-form-card">
                <span class="section-kicker"><?= te('consultar_pedido.formulario.kicker') ?></span>
                <h2><?= te('consultar_pedido.formulario.titulo') ?></h2>
                <p><?= te('consultar_pedido.formulario.intro') ?></p>

                <form method="POST" class="consulta-form-premium">
                    <input 
                        type="text" 
                        name="codigo" 
                        placeholder="<?= te('consultar_pedido.formulario.ph_codigo') ?>"
                        value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>"
                        required
                    >

                    <input 
                        type="email" 
                        name="email" 
                        placeholder="<?= te('consultar_pedido.formulario.ph_email') ?>"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >

                    <button class="hero-btn" type="submit"><?= te('consultar_pedido.formulario.botao') ?></button>
                </form>

                <?php if ($erro): ?>
                    <div class="alerta-erro consulta-alerta">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="consulta-help-card">
                <h3><?= te('consultar_pedido.passos.titulo') ?></h3>

                <div class="consulta-step">
                    <strong>1</strong>
                    <span><?= te('consultar_pedido.passos.p1') ?></span>
                </div>

                <div class="consulta-step">
                    <strong>2</strong>
                    <span><?= te('consultar_pedido.passos.p2') ?></span>
                </div>

                <div class="consulta-step">
                    <strong>3</strong>
                    <span><?= te('consultar_pedido.passos.p3') ?></span>
                </div>

                <a href="/pedidos.php" class="hero-btn">
                    <?= te('consultar_pedido.passos.botao') ?>
                </a>
            </div>

        </div>

        <?php if ($pedido): ?>
            <div class="pedido-track-card">

                <div class="pedido-track-header">
                    <div>
                        <span class="section-kicker"><?= te('consultar_pedido.resultado.titulo') ?></span>
                        <h2><?= htmlspecialchars($pedido['assunto']) ?></h2>
                        <p><?= te('consultar_pedido.resultado.codigo') ?> <strong><?= htmlspecialchars($pedido['codigo'] ?? '-') ?></strong></p>
                    </div>

                    <span class="estado-badge <?= estadoPedidoClasse($pedido['estado']) ?>">
                        <?= estadoPedidoTexto($pedido['estado']) ?>
                    </span>
                </div>

                <style>
                    /* redesign 10/2026: no telemóvel os 3 passos repartem a largura (antes rolava para o lado e cortava «Resolvido») */
                    @media (max-width: 700px) {
                        .pedido-timeline { overflow-x: visible; }
                        .pedido-timeline .timeline-step { min-width: 0; flex: 0 0 auto; max-width: 30%; }
                        .pedido-timeline .timeline-step strong { display: block; font-size: 12px; line-height: 1.25; }
                        .pedido-timeline .timeline-line { min-width: 12px; }
                    }
                </style>
                <div class="pedido-timeline">
                    <div class="timeline-step active">
                        <span>1</span>
                        <strong>Recebido</strong>
                    </div>

                    <div class="timeline-line"></div>

                    <div class="timeline-step <?= in_array($pedido['estado'], ['em_analise', 'resolvido', 'arquivado']) ? 'active' : '' ?>">
                        <span>2</span>
                        <strong>Em análise</strong>
                    </div>

                    <div class="timeline-line"></div>

                    <div class="timeline-step <?= $pedido['estado'] === 'resolvido' ? 'active' : '' ?>">
                        <span>3</span>
                        <strong>Resolvido</strong>
                    </div>
                </div>

                <div class="pedido-consulta-grid premium">
                    <div>
                        <strong><?= te('consultar_pedido.resultado.categoria') ?></strong>
                        <span><?= htmlspecialchars($pedido['categoria']) ?></span>
                    </div>

                    <div>
                        <strong><?= te('consultar_pedido.resultado.localizacao') ?></strong>
                        <span><?= htmlspecialchars($pedido['localizacao'] ?: '-') ?></span>
                    </div>

                    <div>
                        <strong><?= te('consultar_pedido.resultado.data_envio') ?></strong>
                        <span><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></span>
                    </div>

                    <div>
                        <strong><?= te('consultar_pedido.resultado.atualizacao') ?></strong>
                        <span>
                            <?= !empty($pedido['atualizado_em']) 
                                ? date('d/m/Y H:i', strtotime($pedido['atualizado_em'])) 
                                : 'Ainda sem atualização' 
                            ?>
                        </span>
                    </div>
                </div>

                <div class="pedido-message-grid">
                    <div class="pedido-message-box">
                        <h3><?= te('consultar_pedido.resultado.mensagem') ?></h3>
                        <p><?= nl2br(htmlspecialchars($pedido['mensagem'])) ?></p>
                    </div>

                    <div class="pedido-message-box resposta">
                        <h3><?= te('consultar_pedido.resultado.resposta') ?></h3>

                        <?php if ($respostaJunta !== ''): ?>
                            <p><?= nl2br(htmlspecialchars($respostaJunta)) ?></p>
                        <?php else: ?>
                            <p><?= te('consultar_pedido.resultado.sem_resposta') ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <style>
                    .pedido-historico{margin-top:22px;background:#f8fafc;border:1px solid #eef2f7;border-radius:24px;padding:24px}
                    .pedido-historico h3{margin:0 0 16px;color:#11151B}
                    .pedido-historico ol{list-style:none;margin:0;padding:0}
                    .pedido-historico li{display:grid;grid-template-columns:40px 1fr;gap:14px;position:relative;padding-bottom:18px}
                    .pedido-historico li:last-child{padding-bottom:0}
                    .pedido-historico li:not(:last-child)::before{content:"";position:absolute;left:19px;top:40px;bottom:0;width:2px;background:#e2e8f0}
                    .pedido-historico .hist-icone{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;background:#fff;border:2px solid var(--cor-principal);color:var(--cor-principal)}
                    .pedido-historico strong{display:block;color:#11151B;padding-top:2px}
                    .pedido-historico time{font-size:13px;color:#64748b}
                    .pedido-historico .hist-texto{margin:8px 0 0;color:#334155;line-height:1.7;background:#fff;border:1px solid #eef2f7;border-radius:14px;padding:12px 14px}
                    .pedido-historico .hist-vazio{margin:14px 0 0;color:#64748b}
                    :root[data-tema="escuro"] .pedido-historico{background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.08)}
                    :root[data-tema="escuro"] .pedido-historico h3,:root[data-tema="escuro"] .pedido-historico strong{color:#f1f5f9}
                    :root[data-tema="escuro"] .pedido-historico .hist-texto,:root[data-tema="escuro"] .pedido-historico .hist-icone{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.14);color:#e2e8f0}
                </style>
                <div class="pedido-historico">
                    <h3><?= te('consultar_pedido.resultado.historico') ?></h3>
                    <ol>
                        <?php foreach ($historico as $h): ?>
                            <li>
                                <span class="hist-icone" aria-hidden="true"><i class="bi <?= $h['icone'] ?>"></i></span>
                                <div>
                                    <strong><?= htmlspecialchars($h['titulo']) ?></strong>
                                    <time datetime="<?= date('c', strtotime($h['data'])) ?>"><?= date('d/m/Y H:i', strtotime($h['data'])) ?></time>
                                    <?php if ($h['texto'] !== ''): ?><div class="hist-texto"><?= nl2br(htmlspecialchars($h['texto'])) ?></div><?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php if (count($historico) === 1): ?>
                        <p class="hist-vazio"><?= te('consultar_pedido.resultado.sem_historico') ?></p>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>