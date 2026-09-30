<?php
$adminPageTitle = "Ver Pedido";
$adminActive = "pedidos";
require_once "includes/header.php";
require_once "../includes/mail_helper.php";

/* LOG OPERACIONAL */
function criarLogOperacional($pdo, $adminId, $pedidoId, $tipo, $mensagem)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_operacionais
            (admin_id, pedido_id, tipo, mensagem)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $pedidoId, $tipo, $mensagem]);
    } catch (Exception $e) {}
}

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("
    SELECT
        p.*,
        u.nome AS operador_nome,
        u.username AS operador_username
    FROM pedidos_junta p
    LEFT JOIN admin_utilizadores u
        ON u.id = p.operador_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("Pedido não encontrado.");
}

/* Lista operadores (só o admin master atribui) */
$operadores = [];
if (isAdminMaster()) {
    $stmtOps = $pdo->query("
        SELECT id, nome, username
        FROM admin_utilizadores
        WHERE tipo = 'operador'
        AND ativo = 1
        ORDER BY nome ASC
    ");
    $operadores = $stmtOps->fetchAll(PDO::FETCH_ASSOC);
}

/* Prioridades e Estados geridos (Configuração de Ocorrências) */
$prioridadesDb = $pdo->query("SELECT slug, designacao, cor FROM ocorrencias_prioridades WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll(PDO::FETCH_ASSOC);
$estadosDb = $pdo->query("SELECT slug, designacao, cor, valor_percentual FROM ocorrencias_estados WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll(PDO::FETCH_ASSOC);
$acoesDb = $pdo->query("SELECT designacao FROM ocorrencias_acoes WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll(PDO::FETCH_COLUMN);
$entidadesExternas = $pdo->query("SELECT designacao, email FROM ocorrencias_entidades_externas WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll(PDO::FETCH_ASSOC);

$origensLabel = [
    'website' => 'Website', 'telefone' => 'Telefone', 'balcao' => 'Balcão', 'oficio' => 'Ofício',
    'redes_sociais' => 'Redes Sociais', 'app_movel' => 'Aplicação Móvel', 'email' => 'Email', 'outro' => 'Outro',
];

/* Guardar tudo sobre a ocorrência — um único botão */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_ocorrencia'])) {
    $estadoAntigo = $pedido['estado'];
    $operadorIdAntigo = $pedido['operador_id'];

    $novaCategoria = trim($_POST['categoria'] ?? '') ?: $pedido['categoria'];
    $novaSubcategoria = trim($_POST['subcategoria'] ?? '');
    $novoAssunto = trim($_POST['assunto'] ?? '') ?: $pedido['assunto'];
    $novaMensagem = trim($_POST['mensagem'] ?? '') ?: $pedido['mensagem'];

    $reclassificado = $novaCategoria !== $pedido['categoria']
        || $novaSubcategoria !== ($pedido['subcategoria'] ?? '')
        || $novoAssunto !== $pedido['assunto'];

    $prioridadesValidas = array_column($prioridadesDb, 'slug');
    $novaPrioridade = in_array($_POST['prioridade'] ?? '', $prioridadesValidas, true) ? $_POST['prioridade'] : ($prioridadesValidas[0] ?? 'normal');
    $novaCompetencia = trim($_POST['competencia'] ?? '') ?: 'Junta de Freguesia';
    $novaAcao = trim($_POST['acao'] ?? '') ?: null;
    $novaOrigem = array_key_exists($_POST['origem'] ?? '', $origensLabel) ? $_POST['origem'] : $pedido['origem'];

    $estadosValidos = array_column($estadosDb, 'slug');
    $novoEstado = in_array($_POST['estado'] ?? '', $estadosValidos, true) ? $_POST['estado'] : $pedido['estado'];
    $novaResposta = $_POST['resposta'] ?? ($pedido['resposta'] ?? '');

    $estadosInternosValidos = ['por_tratar', 'em_tratamento', 'tratado'];
    $novoEstadoInterno = in_array($_POST['estado_interno'] ?? '', $estadosInternosValidos, true) ? $_POST['estado_interno'] : 'por_tratar';

    $novoPublico = isset($_POST['publico']) ? 1 : 0;

    $operadorId = $operadorIdAntigo;
    if (isAdminMaster()) {
        $operadorId = !empty($_POST['operador_id']) ? (int) $_POST['operador_id'] : null;
    }

    $pdo->prepare("
        UPDATE pedidos_junta
        SET categoria = ?, subcategoria = ?, assunto = ?, mensagem = ?,
            prioridade = ?, competencia = ?, acao = ?, origem = ?,
            estado = ?, resposta = ?, estado_interno = ?, publico = ?, operador_id = ?,
            atualizado_em = NOW()
        WHERE id = ?
    ")->execute([
        $novaCategoria, $novaSubcategoria !== '' ? $novaSubcategoria : null, $novoAssunto, $novaMensagem,
        $novaPrioridade, $novaCompetencia, $novaAcao, $novaOrigem,
        $novoEstado, $novaResposta, $novoEstadoInterno, $novoPublico, $operadorId,
        $id,
    ]);

    $pdo->prepare("DELETE FROM pedidos_tags WHERE pedido_id = ?")->execute([$id]);
    $tagsEscolhidas = array_map('intval', $_POST['tags'] ?? []);
    if (!empty($tagsEscolhidas)) {
        $stmtTag = $pdo->prepare("INSERT INTO pedidos_tags (pedido_id, tag_id) VALUES (?, ?)");
        foreach (array_unique($tagsEscolhidas) as $tagId) {
            $stmtTag->execute([$id, $tagId]);
        }
    }

    if (isAdminMaster()) {
        $pdo->prepare("DELETE FROM pedidos_funcionarios WHERE pedido_id = ?")->execute([$id]);
        $funcionariosEscolhidos = array_map('intval', $_POST['funcionarios'] ?? []);
        if (!empty($funcionariosEscolhidos)) {
            $stmtFunc = $pdo->prepare("INSERT INTO pedidos_funcionarios (pedido_id, admin_id) VALUES (?, ?)");
            foreach (array_unique($funcionariosEscolhidos) as $funcId) {
                $stmtFunc->execute([$id, $funcId]);
            }
        }

        if ((int) $operadorId !== (int) $operadorIdAntigo) {
            $nomeOperador = 'Sem operador';
            foreach ($operadores as $op) {
                if ((int) $op['id'] === (int) $operadorId) {
                    $nomeOperador = $op['nome'] ?: $op['username'];
                    break;
                }
            }
            criarLogOperacional($pdo, $adminId, $id, 'atribuicao_operador', 'Operador atribuído: ' . $nomeOperador);
            $pdo->prepare("
                INSERT INTO pedido_timeline (pedido_id, tipo, titulo, descricao)
                VALUES (?, 'atribuicao', 'Operador atribuído', ?)
            ")->execute([$id, 'Operador responsável: ' . $nomeOperador]);
        }
    }

    criarLogOperacional($pdo, $adminId, $id, 'guardar_ocorrencia', 'Ocorrência atualizada por ' . $adminNome);

    if ($reclassificado) {
        $pdo->prepare("
            INSERT INTO pedido_timeline (pedido_id, tipo, titulo, descricao)
            VALUES (?, 'reclassificacao', 'Ocorrência reclassificada', ?)
        ")->execute([$id, 'Categoria/assunto ajustados por ' . $adminNome . '. Consulte "Dados Originais" para ver o que foi submetido primeiro.']);
    }

    if ($novoEstado !== $estadoAntigo) {
        criarLogOperacional($pdo, $adminId, $id, 'estado_ocorrencia', 'Estado alterado para: ' . strtoupper(str_replace('_', ' ', $novoEstado)));

        $pdo->prepare("
            INSERT INTO pedido_timeline (pedido_id, tipo, titulo, descricao)
            VALUES (?, 'estado', ?, ?)
        ")->execute([
            $id,
            'Estado alterado para ' . str_replace('_', ' ', $novoEstado),
            $adminNome . ' alterou o estado desta ocorrência.',
        ]);

        if (!empty($pedido['email'])) {
            $html = "
                <h2>Atualização do pedido</h2>
                <p>Olá {$pedido['nome']},</p>
                <p>O estado do seu pedido foi atualizado:</p>
                <p><strong>{$novoAssunto}</strong></p>
                <p><strong>Novo estado:</strong> " . strtoupper(str_replace('_', ' ', $novoEstado)) . "</p>
            ";
            enviarEmailSistema($pedido['email'], "Atualização do pedido", $html);
        }
    }

    header("Location: ver_pedido.php?id=" . $id);
    exit;
}

/* Marcar mensagens do cidadão como lidas pelo admin */
$pdo->prepare("
    UPDATE pedido_mensagens
    SET lida = 1
    WHERE pedido_id = ?
    AND autor_tipo = 'cidadao'
")->execute([$id]);

/* Mensagem admin (chat) */
if (isset($_POST['mensagem_admin'])) {
    $mensagem = trim($_POST['mensagem_admin']);

    if ($mensagem !== '') {
        $stmtMsg = $pdo->prepare("
            INSERT INTO pedido_mensagens
            (pedido_id, cidadao_id, autor_tipo, autor_nome, mensagem, lida)
            VALUES (?, ?, 'admin', 'Junta de Freguesia', ?, 0)
        ");
        $stmtMsg->execute([$id, $pedido['cidadao_id'], $mensagem]);

        criarLogOperacional($pdo, $adminId, $id, 'chat_admin', 'Nova resposta no chat da ocorrência');

        if (!empty($pedido['email'])) {
            $html = "
                <h2>Nova resposta da Junta</h2>
                <p><strong>Pedido:</strong> {$pedido['codigo']}</p>
                <p><strong>Assunto:</strong> {$pedido['assunto']}</p>
                <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
                <br>
                <p>Consulte o pedido no Balcão Virtual.</p>
            ";
            enviarEmailSistema($pedido['email'], "Nova resposta ao seu pedido", $html);
        }

        echo "<script>window.location.href='ver_pedido.php?id=" . (int) $id . "';</script>";
        exit;
    }
}

/* Nota interna (privada, nunca visível ao cidadão) — substitui o antigo
   "Tratamento interno", que era a mesma coisa com outro nome. */
if (isset($_POST['nova_nota_interna'])) {
    $notaInterna = trim($_POST['nota_interna'] ?? '');

    if ($notaInterna !== '') {
        $pdo->prepare("
            INSERT INTO pedidos_notas_internas (pedido_id, admin_id, autor_nome, nota)
            VALUES (?, ?, ?, ?)
        ")->execute([$id, $adminId, $adminNome, $notaInterna]);
    }

    header("Location: ver_pedido.php?id=" . $id);
    exit;
}

/* Email para entidade externa (ex.: Câmara Municipal) */
if (isset($_POST['enviar_email_externo'])) {
    $destinatarioExterno = trim($_POST['destinatario_externo'] ?? '');
    $assuntoExterno = trim($_POST['assunto_externo'] ?? '');
    $corpoExterno = trim($_POST['corpo_externo'] ?? '');

    if ($destinatarioExterno !== '' && emailValido($destinatarioExterno) && $assuntoExterno !== '' && $corpoExterno !== '') {
        $htmlExterno = nl2br(htmlspecialchars($corpoExterno));

        enviarEmailSistema($destinatarioExterno, $assuntoExterno, $htmlExterno);

        $pdo->prepare("
            INSERT INTO pedidos_emails_externos (pedido_id, destinatario, assunto, corpo, enviado_por_nome)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$id, $destinatarioExterno, $assuntoExterno, $corpoExterno, $adminNome]);

        $pdo->prepare("
            INSERT INTO pedido_timeline (pedido_id, tipo, titulo, descricao)
            VALUES (?, 'email_externo', 'Ocorrência enviada para entidade externa', ?)
        ")->execute([$id, 'Enviado para ' . $destinatarioExterno . ' por ' . $adminNome]);
    }

    header("Location: ver_pedido.php?id=" . $id);
    exit;
}

/* Imagens */
$stmtImgs = $pdo->prepare("SELECT * FROM pedidos_imagens WHERE pedido_id = ? ORDER BY id ASC");
$stmtImgs->execute([$id]);
$imagens = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);

/* Categorias/subcategorias (para reclassificar a ocorrência) */
$categoriasOcorrencia = $pdo->query("
    SELECT id, designacao FROM ocorrencias_categorias WHERE ativo = 1 ORDER BY ordem ASC, designacao ASC
")->fetchAll(PDO::FETCH_ASSOC);

$assuntosPorCategoria = [];
$stmtAssuntosVer = $pdo->query("
    SELECT a.designacao, c.designacao AS categoria
    FROM ocorrencias_assuntos a
    JOIN ocorrencias_categorias c ON c.id = a.categoria_id
    WHERE a.ativo = 1
    ORDER BY a.ordem ASC, a.designacao ASC
");
foreach ($stmtAssuntosVer->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $assuntosPorCategoria[$a['categoria']][] = $a['designacao'];
}

/* Tags: todas as disponíveis + as já associadas a este pedido */
$todasTags = $pdo->query("SELECT id, designacao FROM ocorrencias_tags WHERE ativo = 1 ORDER BY designacao ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmtTagsPedido = $pdo->prepare("SELECT tag_id FROM pedidos_tags WHERE pedido_id = ?");
$stmtTagsPedido->execute([$id]);
$tagsDoPedido = $stmtTagsPedido->fetchAll(PDO::FETCH_COLUMN);

/* Funcionários colaboradores: todos os utilizadores ativos + os já atribuídos */
$todosFuncionarios = $pdo->query("
    SELECT id, nome, username FROM admin_utilizadores
    WHERE tipo IN ('admin', 'operador') AND ativo = 1
    ORDER BY nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

$stmtFuncPedido = $pdo->prepare("SELECT admin_id FROM pedidos_funcionarios WHERE pedido_id = ?");
$stmtFuncPedido->execute([$id]);
$funcionariosDoPedido = $stmtFuncPedido->fetchAll(PDO::FETCH_COLUMN);

/* Histórico de Resolução (timeline automática de eventos-chave) */
$stmtTimeline = $pdo->prepare("SELECT * FROM pedido_timeline WHERE pedido_id = ? ORDER BY criado_em DESC, id DESC");
$stmtTimeline->execute([$id]);
$timeline = $stmtTimeline->fetchAll(PDO::FETCH_ASSOC);

/* Notas internas — "Tratamentos Internos" e "Notas Internas" eram duas
   secções para exatamente a mesma coisa (privadas, nunca visíveis ao
   cidadão, sem nenhuma diferença de permissões nem de uso no resto do
   código) — confundia quem usava. Juntas numa só lista, ordenada por
   data; as entradas antigas de pedidos_tratamentos continuam a aparecer
   (não se perde histórico), só deixam de se poder criar mais assim —
   novas entradas vão sempre para pedidos_notas_internas. */
$stmtNotasInternas = $pdo->prepare("
    SELECT * FROM (
        SELECT id, admin_id, autor_nome, nota, criado_em FROM pedidos_tratamentos WHERE pedido_id = ?
        UNION ALL
        SELECT id, admin_id, autor_nome, nota, criado_em FROM pedidos_notas_internas WHERE pedido_id = ?
    ) t
    ORDER BY criado_em DESC, id DESC
");
$stmtNotasInternas->execute([$id, $id]);
$notasInternas = $stmtNotasInternas->fetchAll(PDO::FETCH_ASSOC);

/* Emails para entidades externas */
$stmtEmailsExternos = $pdo->prepare("SELECT * FROM pedidos_emails_externos WHERE pedido_id = ? ORDER BY criado_em DESC, id DESC");
$stmtEmailsExternos->execute([$id]);
$emailsExternos = $stmtEmailsExternos->fetchAll(PDO::FETCH_ASSOC);

/* Chat */
$stmtMsgs = $pdo->prepare("
    SELECT * FROM pedido_mensagens
    WHERE pedido_id = ?
    ORDER BY criado_em ASC
");
$stmtMsgs->execute([$id]);
$mensagens = $stmtMsgs->fetchAll(PDO::FETCH_ASSOC);

/* Estado — rótulo, cor e % para a barra de progresso (da BD, com recurso fixo se a BD estiver vazia) */
$estadosInfoFallback = [
    'pendente'    => ['label' => 'Pendente',    'cor' => '#f59f00', 'percent' => 15],
    'em_analise'  => ['label' => 'Em análise',  'cor' => '#2563eb', 'percent' => 55],
    'resolvido'   => ['label' => 'Resolvido',   'cor' => '#2b8a3e', 'percent' => 100],
    'arquivado'   => ['label' => 'Arquivado',   'cor' => '#6b7280', 'percent' => 100],
];
$estadosInfo = [];
foreach ($estadosDb as $e) {
    $estadosInfo[$e['slug']] = ['label' => $e['designacao'], 'cor' => $e['cor'], 'percent' => (int) $e['valor_percentual']];
}
if (empty($estadosInfo)) {
    $estadosInfo = $estadosInfoFallback;
}
$estadoInfo = $estadosInfo[$pedido['estado']] ?? reset($estadosInfo);

$estadosInternosLabel = ['por_tratar' => 'Por tratar', 'em_tratamento' => 'Em tratamento', 'tratado' => 'Tratado'];

$temCoordenadas = !empty($pedido['latitude']) && !empty($pedido['longitude']);
?>

<style>
.vp-progresso { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 20px 24px; margin-bottom: 24px; box-shadow: 0 10px 25px rgba(15,23,42,.05); }
.vp-progresso-label { display: flex; justify-content: space-between; font-weight: 900; color: var(--tema-admin-escuro); margin-bottom: 10px; }
.vp-progresso-track { height: 14px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
.vp-progresso-fill { height: 100%; border-radius: 999px; }
.vp-grid { display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start; }
.vp-mapa-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 16px; box-shadow: 0 10px 25px rgba(15,23,42,.05); }
.vp-mapa-gps { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-weight: 800; color: var(--tema-admin-escuro); }
#vpMapaOcorrencia { width: 100%; height: 260px; border-radius: 14px; }
.vp-sem-mapa { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 30px; text-align: center; color: #64748b; }
@media (max-width: 960px) {
    .vp-grid { grid-template-columns: 1fr; }
}

/* Título de grupo acima de "Tratamentos/Notas/Emails" — deixa claro que
   estas 3 secções são a mesma família ("só a equipa vê"), em vez de
   parecerem 3 caixas soltas sem relação nenhuma. */
.vp-secao-grupo { display: flex; align-items: center; gap: 10px; margin: 32px 0 0; padding-bottom: 10px; border-bottom: 2px solid #eef2f7; font-size: 16px; color: var(--tema-admin-escuro); }
.vp-secao-grupo i { color: var(--cor-principal); }

/* Secções do formulário principal — antes era uma lista plana de campos
   sem agrupamento nenhum, difícil de ler à primeira vista. */
.vp-secao { padding: 20px 0; border-bottom: 1px solid #eef2f7; }
.vp-secao:first-of-type { padding-top: 4px; }
.vp-secao:last-of-type { border-bottom: none; }
.vp-secao-titulo { display: flex; align-items: center; gap: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin: 0 0 16px; font-weight: 800; }
.vp-secao-titulo i { color: var(--cor-principal); font-size: 15px; }
.vp-form-acoes { display: flex; gap: 10px; padding-top: 20px; }

/* Tags/Funcionários como "chips" clicáveis, em vez de checkboxes soltas */
.vp-chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 0; }
.vp-chip { position: relative; cursor: pointer; }
.vp-chip input { position: absolute; opacity: 0; width: 0; height: 0; }
.vp-chip span { display: inline-block; padding: 8px 14px; border-radius: 999px; border: 1px solid #dbe3ea; background: #f8fafc; font-weight: 700; font-size: 13px; color: #475569; transition: .15s ease; }
.vp-chip input:checked + span { background: var(--cor-principal); border-color: var(--cor-principal); color: #fff; }
.vp-chip input:focus-visible + span { outline: 2px solid var(--cor-principal); outline-offset: 2px; }

.vp-checkbox-inline { display: flex; align-items: center; gap: 8px; font-weight: 600; margin-top: 4px; }

/* Títulos dos painéis (Histórico, Tratamentos, Notas, Emails, Chat) — antes
   eram só um <h2> a preto, sem nenhum destaque visual. */
.vp-painel-titulo { display: flex; align-items: center; gap: 12px; margin: 0 0 4px; }
.vp-painel-titulo .vp-painel-icone { width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; background: color-mix(in srgb, var(--cor-principal) 10%, #fff); color: var(--cor-principal); }
.vp-painel-titulo h2 { margin: 0; font-size: 18px; }
.vp-painel-sub { color: #64748b; font-size: 13px; margin: 0 0 4px 50px; }

/* Histórico de Resolução — timeline a sério, com ícone por tipo de evento
   e linha a ligar as entradas, em vez de só uma barra à esquerda. */
.vp-timeline { position: relative; margin-top: 18px; }
.vp-timeline-item { display: flex; gap: 14px; position: relative; padding-bottom: 22px; }
.vp-timeline-item:last-child { padding-bottom: 0; }
.vp-timeline-item:not(:last-child)::before { content: ''; position: absolute; left: 15px; top: 34px; bottom: 0; width: 2px; background: #eef2f7; }
.vp-timeline-icone { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; z-index: 1; }
.vp-timeline-icone.tipo-criacao { background: #ecfdf5; color: #047857; }
.vp-timeline-icone.tipo-atribuicao { background: #eef2ff; color: #4338ca; }
.vp-timeline-icone.tipo-estado { background: #fef3c7; color: #92400e; }
.vp-timeline-icone.tipo-reclassificacao { background: #f0f9ff; color: #0369a1; }
.vp-timeline-conteudo { flex: 1; padding-top: 3px; }
.vp-timeline-conteudo strong { display: block; font-size: 14px; }
.vp-timeline-conteudo p { margin: 3px 0; color: #64748b; font-size: 13px; }
.vp-timeline-conteudo small { color: #94a3b8; }

/* Cartões de entradas nos painéis (tratamentos, notas, emails) — davam-se
   bem, só precisavam de um pouco mais de ritmo visual (ícone do autor). */
.vp-entrada { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 14px; padding: 12px 16px; display: flex; gap: 12px; }
.vp-entrada.vp-entrada-nota { background: #fffbeb; border-color: #fde68a; }
.vp-entrada-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--cor-principal); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; flex-shrink: 0; }
.vp-entrada-corpo { flex: 1; }
.vp-entrada-corpo strong { font-size: 13px; }
.vp-entrada-corpo p { margin: 4px 0; font-size: 14px; }
.vp-entrada-corpo small { color: #94a3b8; }

/* Caixa de "compor" (nova nota/tratamento/email) — antes era só uma
   textarea solta a seguir à lista, sem nenhuma moldura a separar. */
.vp-compor { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 16px; padding: 14px; margin-top: 16px; }
.vp-compor textarea, .vp-compor input { background: #fff; }
.vp-compor .btn { margin-top: 10px; }

/* Bolhas do chat — as classes .chat-box/.chat-msg já existem em
   assets/css/style.css, mas as páginas de admin só carregam admin.css,
   por isso nunca chegavam a aplicar-se aqui. Repetidas em local, só para
   esta página, para não ficar dependente de outro ficheiro CSS. */
.chat-box { display: flex; flex-direction: column; gap: 14px; margin: 16px 0; }
.chat-msg { max-width: 75%; padding: 14px 16px; border-radius: 18px; box-shadow: 0 10px 25px rgba(0,0,0,.06); }
.chat-msg.cidadao { align-self: flex-start; background: #f3f4f6; color: var(--tema-admin-escuro); }
.chat-msg.admin { align-self: flex-end; background: var(--cor-principal); color: #fff; }
.chat-msg strong { font-size: 13px; }
.chat-msg p { margin: 6px 0; line-height: 1.5; }
.chat-msg small { opacity: .75; }
</style>

<div class="vp-progresso">
    <div class="vp-progresso-label">
        <span><?= htmlspecialchars($estadoInfo['label']) ?></span>
        <span><?= (int) $estadoInfo['percent'] ?>%</span>
    </div>
    <div class="vp-progresso-track">
        <div class="vp-progresso-fill" style="width:<?= (int) $estadoInfo['percent'] ?>%;background:<?= $estadoInfo['cor'] ?>"></div>
    </div>
</div>

<div class="vp-grid">
    <div class="content-box">
        <h2>#<?= htmlspecialchars($pedido['codigo'] ?? $id) ?> — <?= htmlspecialchars($pedido['assunto']) ?></h2>

        <div class="form-grid">
            <p><strong>Data de Submissão:</strong> <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></p>
            <p><strong>Origem atual:</strong> <?= htmlspecialchars($origensLabel[$pedido['origem'] ?? 'website'] ?? 'Website') ?> <small>(edita mais abaixo)</small></p>
        </div>

        <div class="form-grid">
            <p><strong>Nome:</strong> <?= htmlspecialchars($pedido['nome']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($pedido['email'] ?? '-') ?></p>
        </div>
        <p><strong>Telefone:</strong> <?= htmlspecialchars($pedido['telefone'] ?? '-') ?></p>
        <p><strong>Localização:</strong> <?= htmlspecialchars($pedido['localizacao'] ?? '-') ?></p>

        <hr>

        <form method="POST">
            <input type="hidden" name="guardar_ocorrencia" value="1">

            <div class="vp-secao">
                <h3 class="vp-secao-titulo"><i class="bi bi-tag"></i> Classificação</h3>

                <div class="form-grid">
                    <div>
                        <label>Categoria</label>
                        <select name="categoria" id="categoriaOcorrenciaEdit">
                            <?php foreach ($categoriasOcorrencia as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['designacao']) ?>" <?= $pedido['categoria'] === $cat['designacao'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['designacao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Subcategoria</label>
                        <select name="subcategoria" id="subcategoriaOcorrenciaEdit">
                            <option value="">Subcategoria (opcional)</option>
                        </select>
                    </div>
                </div>

                <label>Assunto</label>
                <input type="text" name="assunto" value="<?= htmlspecialchars($pedido['assunto']) ?>">

                <label>Descrição</label>
                <textarea name="mensagem"><?= htmlspecialchars($pedido['mensagem']) ?></textarea>
            </div>

            <div class="vp-secao">
                <h3 class="vp-secao-titulo"><i class="bi bi-signpost-split"></i> Prioridade e Encaminhamento</h3>

                <div class="form-grid">
                    <div>
                        <label>Prioridade</label>
                        <select name="prioridade">
                            <?php foreach ($prioridadesDb as $pr): ?>
                                <option value="<?= htmlspecialchars($pr['slug']) ?>" <?= ($pedido['prioridade'] ?? 'normal') === $pr['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($pr['designacao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Origem</label>
                        <select name="origem">
                            <?php foreach ($origensLabel as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($pedido['origem'] ?? 'website') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div>
                        <label>Competência <small>(entidade responsável)</small></label>
                        <select name="competencia">
                            <?php foreach ($entidadesExternas as $ent): ?>
                                <option value="<?= htmlspecialchars($ent['designacao']) ?>" <?= ($pedido['competencia'] ?? 'Junta de Freguesia') === $ent['designacao'] ? 'selected' : '' ?>><?= htmlspecialchars($ent['designacao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Ação</label>
                        <select name="acao">
                            <option value="">— Sem ação definida —</option>
                            <?php foreach ($acoesDb as $ac): ?>
                                <option value="<?= htmlspecialchars($ac) ?>" <?= ($pedido['acao'] ?? '') === $ac ? 'selected' : '' ?>><?= htmlspecialchars($ac) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label>Tags</label>
                <div class="vp-chips">
                    <?php foreach ($todasTags as $tg): ?>
                        <label class="vp-chip">
                            <input type="checkbox" name="tags[]" value="<?= (int) $tg['id'] ?>" <?= in_array($tg['id'], $tagsDoPedido, true) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($tg['designacao']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="vp-secao">
                <h3 class="vp-secao-titulo"><i class="bi bi-flag"></i> Estado</h3>

                <div class="form-grid">
                    <div>
                        <label>Estado <small>(o cidadão vê)</small></label>
                        <select name="estado">
                            <?php foreach ($estadosDb as $es): ?>
                                <option value="<?= htmlspecialchars($es['slug']) ?>" <?= $pedido['estado'] === $es['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($es['designacao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Estado Interno <small>(só a equipa vê)</small></label>
                        <select name="estado_interno">
                            <?php foreach ($estadosInternosLabel as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($pedido['estado_interno'] ?? 'por_tratar') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label>Resposta / Observações internas</label>
                <textarea name="resposta"><?= htmlspecialchars($pedido['resposta'] ?? '') ?></textarea>

                <label class="vp-checkbox-inline">
                    <input type="checkbox" name="publico" value="1" <?= !empty($pedido['publico']) ? 'checked' : '' ?>>
                    Mostrar esta ocorrência no mapa público
                </label>
            </div>

            <?php if (isAdminMaster()): ?>
                <div class="vp-secao">
                    <h3 class="vp-secao-titulo"><i class="bi bi-person-badge"></i> Atribuição</h3>

                    <label>Operador responsável</label>
                    <select name="operador_id">
                        <option value="">Sem operador</option>
                        <?php foreach ($operadores as $op): ?>
                            <option value="<?= (int) $op['id'] ?>" <?= (int) ($pedido['operador_id'] ?? 0) === (int) $op['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($op['nome'] ?: $op['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label style="margin-top:16px;">Funcionários colaboradores</label>
                    <p><small>Além do operador responsável, pode associar outros funcionários que estão a colaborar nesta ocorrência.</small></p>
                    <div class="vp-chips">
                        <?php foreach ($todosFuncionarios as $fu): ?>
                            <label class="vp-chip">
                                <input type="checkbox" name="funcionarios[]" value="<?= (int) $fu['id'] ?>" <?= in_array($fu['id'], $funcionariosDoPedido, true) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($fu['nome'] ?: $fu['username']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="vp-form-acoes">
                <button class="btn">Guardar</button>
                <a class="btn secondary" href="pedidos.php">Voltar</a>
            </div>
        </form>

        <?php if (!empty($imagens)): ?>
            <hr>
            <h3>Imagens anexadas</h3>
            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:12px;">
                <?php foreach ($imagens as $img): ?>
                    <a href="../assets/img/<?= htmlspecialchars($img['imagem']) ?>" target="_blank">
                        <img src="../assets/img/<?= htmlspecialchars($img['imagem']) ?>" style="width:150px;height:105px;object-fit:cover;border-radius:14px;box-shadow:0 10px 25px rgba(0,0,0,.12);">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="vp-mapa-box">
            <div class="vp-mapa-gps">
                <span>Local</span>
                <?php if ($temCoordenadas): ?>
                    <span>GPS: (<?= htmlspecialchars($pedido['latitude']) ?>, <?= htmlspecialchars($pedido['longitude']) ?>)</span>
                <?php endif; ?>
            </div>

            <?php if ($temCoordenadas): ?>
                <div id="vpMapaOcorrencia"></div>
            <?php else: ?>
                <div class="vp-sem-mapa">Sem localização registada no mapa.</div>
            <?php endif; ?>
        </div>

        <div class="content-box" style="margin-top:16px;">
            <h2>Dados Originais</h2>
            <p><small>O que foi submetido/registado da primeira vez — nunca é alterado, mesmo que a ocorrência seja reclassificada ao lado.</small></p>

            <p><strong>Nome:</strong> <?= htmlspecialchars($pedido['nome_original'] ?? $pedido['nome']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($pedido['email_original'] ?? $pedido['email'] ?? '-') ?></p>
            <p><strong>Telefone:</strong> <?= htmlspecialchars($pedido['telefone_original'] ?? $pedido['telefone'] ?? '-') ?></p>
            <p><strong>Categoria:</strong> <?= htmlspecialchars($pedido['categoria_original'] ?? $pedido['categoria']) ?><?= !empty($pedido['subcategoria_original']) ? ' &raquo; ' . htmlspecialchars($pedido['subcategoria_original']) : '' ?></p>
            <p><strong>Assunto:</strong> <?= htmlspecialchars($pedido['assunto_original'] ?? $pedido['assunto']) ?></p>
            <p><strong>Descrição:</strong></p>
            <p><?= nl2br(htmlspecialchars($pedido['mensagem_original'] ?? $pedido['mensagem'])) ?></p>
            <p><strong>Localização:</strong> <?= htmlspecialchars($pedido['localizacao_original'] ?? $pedido['localizacao'] ?? '-') ?></p>
        </div>
    </div>
</div>

<?php
$timelineIcones = [
    'criacao' => 'bi-plus-circle-fill',
    'atribuicao' => 'bi-person-badge-fill',
    'estado' => 'bi-flag-fill',
    'reclassificacao' => 'bi-tag-fill',
];
function vpIniciais($nome) {
    $nome = trim((string) $nome);
    if ($nome === '') return '?';
    $partes = preg_split('/\s+/', $nome);
    $iniciais = mb_substr($partes[0], 0, 1);
    if (count($partes) > 1) {
        $iniciais .= mb_substr(end($partes), 0, 1);
    }
    return mb_strtoupper($iniciais);
}
?>

<!-- 1. Histórico — logo a seguir ao formulário, porque é o "o que já
     aconteceu com este pedido" que faz sentido ver assim que se decide o
     que fazer a seguir. Antes estava perdido numa grelha 2x2 ao lado de
     coisas sem relação nenhuma com ele. -->
<div class="content-box" style="margin-top:24px;">
    <div class="vp-painel-titulo">
        <span class="vp-painel-icone"><i class="bi bi-clock-history"></i></span>
        <h2>Histórico de Resolução</h2>
    </div>
    <?php if (empty($timeline)): ?>
        <p>Sem histórico ainda.</p>
    <?php else: ?>
        <div class="vp-timeline">
            <?php foreach ($timeline as $ev): ?>
                <div class="vp-timeline-item">
                    <div class="vp-timeline-icone tipo-<?= htmlspecialchars($ev['tipo']) ?>">
                        <i class="bi <?= $timelineIcones[$ev['tipo']] ?? 'bi-record-circle-fill' ?>"></i>
                    </div>
                    <div class="vp-timeline-conteudo">
                        <strong><?= htmlspecialchars($ev['titulo']) ?></strong>
                        <p><?= htmlspecialchars($ev['descricao'] ?? '') ?></p>
                        <small><?= date('d/m/Y H:i', strtotime($ev['criado_em'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- 2. Conversa com o cidadão — é a comunicação principal do pedido, por
     isso vem logo a seguir ao histórico, antes das notas só-da-equipa. -->
<div class="content-box" style="margin-top:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;">
        <div class="vp-painel-titulo" style="margin:0;">
            <span class="vp-painel-icone"><i class="bi bi-chat-dots-fill"></i></span>
            <h2>Conversa com o Cidadão</h2>
        </div>
        <a class="btn danger"
           href="limpar_chat_pedido.php?id=<?= $id ?>"
           onclick="return confirm('Tem a certeza que quer limpar toda a conversa deste pedido? Esta ação não pode ser revertida.')">
            Limpar conversa
        </a>
    </div>

    <div class="chat-box" id="chatBox">
        <?php if (!empty($mensagens)): ?>
            <?php foreach ($mensagens as $m): ?>
                <div class="chat-msg <?= $m['autor_tipo'] ?>">
                    <strong><?= htmlspecialchars($m['autor_nome']) ?></strong>
                    <p><?= nl2br(htmlspecialchars($m['mensagem'])) ?></p>
                    <small><?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Sem mensagens ainda.</p>
        <?php endif; ?>
    </div>

    <div id="typingStatus" style="font-size:13px;color:#6b7280;margin-top:8px;"></div>

    <form method="POST" class="vp-compor">
        <textarea id="msgInputAdmin" name="mensagem_admin" placeholder="Responder ao cidadão..." required></textarea>
        <button class="btn"><i class="bi bi-send"></i> Enviar resposta</button>
    </form>
</div>

<!-- 3. Registo interno da equipa — as 3 coisas que só a equipa vê, uma a
     seguir à outra (nunca lado a lado), com um título de grupo a deixar
     claro que são da mesma família, ao contrário da grelha 2x2 antiga. -->
<h3 class="vp-secao-grupo"><i class="bi bi-people-fill"></i> Registo Interno da Equipa</h3>

<div class="content-box" style="margin-top:14px;">
    <div class="vp-painel-titulo">
        <span class="vp-painel-icone"><i class="bi bi-lock-fill"></i></span>
        <h2>Notas Internas</h2>
    </div>
    <p class="vp-painel-sub">Privadas — nunca visíveis ao cidadão.</p>

    <?php if (!empty($notasInternas)): ?>
        <div style="display:grid;gap:10px;margin:12px 0;">
            <?php foreach ($notasInternas as $nt): ?>
                <div class="vp-entrada vp-entrada-nota">
                    <div class="vp-entrada-avatar"><?= htmlspecialchars(vpIniciais($nt['autor_nome'])) ?></div>
                    <div class="vp-entrada-corpo">
                        <strong><?= htmlspecialchars($nt['autor_nome']) ?></strong>
                        <p><?= nl2br(htmlspecialchars($nt['nota'])) ?></p>
                        <small><?= date('d/m/Y H:i', strtotime($nt['criado_em'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="vp-compor">
        <input type="hidden" name="nova_nota_interna" value="1">
        <textarea name="nota_interna" placeholder="Nota privada da equipa..." required></textarea>
        <button class="btn"><i class="bi bi-plus-lg"></i> Adicionar nota</button>
    </form>
</div>

<div class="content-box" style="margin-top:16px;">
    <div class="vp-painel-titulo">
        <span class="vp-painel-icone"><i class="bi bi-envelope-paper-fill"></i></span>
        <h2>Emails para Entidades Externas</h2>
    </div>
    <p class="vp-painel-sub">Ex.: reencaminhar para a Câmara Municipal ou outra entidade responsável.</p>

    <?php if (!empty($emailsExternos)): ?>
        <div style="display:grid;gap:10px;margin:12px 0;">
            <?php foreach ($emailsExternos as $em): ?>
                <div class="vp-entrada">
                    <div class="vp-entrada-avatar"><i class="bi bi-send-fill" style="font-size:12px;"></i></div>
                    <div class="vp-entrada-corpo">
                        <p style="margin:0;"><strong>Para:</strong> <?= htmlspecialchars($em['destinatario']) ?></p>
                        <p style="margin:4px 0;"><strong>Assunto:</strong> <?= htmlspecialchars($em['assunto']) ?></p>
                        <p><?= nl2br(htmlspecialchars($em['corpo'])) ?></p>
                        <small>Enviado por <?= htmlspecialchars($em['enviado_por_nome'] ?? '-') ?> em <?= date('d/m/Y H:i', strtotime($em['criado_em'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    $emailEntidadeAtual = '';
    foreach ($entidadesExternas as $ent) {
        if ($ent['designacao'] === ($pedido['competencia'] ?? '') && !empty($ent['email'])) {
            $emailEntidadeAtual = $ent['email'];
            break;
        }
    }
    ?>
    <form method="POST" class="vp-compor">
        <input type="hidden" name="enviar_email_externo" value="1">
        <label>Destinatário <?= $emailEntidadeAtual ? '<small>(email da competência atual: ' . htmlspecialchars($emailEntidadeAtual) . ')</small>' : '' ?></label>
        <input type="email" name="destinatario_externo" placeholder="nome@entidade.pt" value="<?= htmlspecialchars($emailEntidadeAtual) ?>" required>
        <label>Assunto</label>
        <input type="text" name="assunto_externo" value="<?= htmlspecialchars($pedido['assunto']) ?>" required>
        <label>Mensagem</label>
        <textarea name="corpo_externo" required><?= htmlspecialchars($pedido['mensagem']) ?></textarea>
        <button class="btn"><i class="bi bi-send"></i> Enviar para entidade externa</button>
    </form>
</div>

<?php if ($temCoordenadas): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const mapaDiv = document.getElementById('vpMapaOcorrencia');
    if (!mapaDiv || typeof L === 'undefined') return;

    const lat = <?= json_encode((float) $pedido['latitude']) ?>;
    const lng = <?= json_encode((float) $pedido['longitude']) ?>;

    const mapa = L.map('vpMapaOcorrencia').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapa);
    L.marker([lat, lng]).addTo(mapa);

    setTimeout(function () { mapa.invalidateSize(); }, 300);
});
</script>
<?php endif; ?>

<script>
(function () {
    const assuntosPorCategoria = <?= json_encode($assuntosPorCategoria, JSON_UNESCAPED_UNICODE) ?>;
    const subcategoriaAtual = <?= json_encode($pedido['subcategoria'] ?? '', JSON_UNESCAPED_UNICODE) ?>;

    const selectCategoria = document.getElementById('categoriaOcorrenciaEdit');
    const selectSubcategoria = document.getElementById('subcategoriaOcorrenciaEdit');

    if (!selectCategoria || !selectSubcategoria) return;

    function criarOpcao(valor, texto) {
        const opt = document.createElement('option');
        opt.value = valor;
        opt.textContent = texto;
        return opt;
    }

    function atualizarSubcategorias(manterSelecao) {
        const lista = assuntosPorCategoria[selectCategoria.value] || [];

        selectSubcategoria.replaceChildren(criarOpcao('', 'Subcategoria (opcional)'));

        lista.forEach(function (designacao) {
            selectSubcategoria.appendChild(criarOpcao(designacao, designacao));
        });

        selectSubcategoria.disabled = lista.length === 0;

        if (manterSelecao && lista.includes(subcategoriaAtual)) {
            selectSubcategoria.value = subcategoriaAtual;
        }
    }

    selectCategoria.addEventListener('change', function () { atualizarSubcategorias(false); });
    atualizarSubcategorias(true);
})();

const pedidoId = <?= (int) $id ?>;
const chatBox = document.getElementById("chatBox");
const msgInputAdmin = document.getElementById("msgInputAdmin");
const typingStatus = document.getElementById("typingStatus");

function loadChat() {
    fetch('../chat_fetch.php?pedido_id=' + pedidoId)
        .then(res => res.text())
        .then(html => {
            if (chatBox) {
                const range = document.createRange();
                range.selectNodeContents(chatBox);
                chatBox.replaceChildren(range.createContextualFragment(html));
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        });
}

function sendTyping() {
    fetch("../typing_update.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: "pedido_id=" + pedidoId + "&tipo=admin"
    });
}

function loadTyping() {
    fetch("../typing_fetch.php?pedido_id=" + pedidoId + "&ver=admin")
        .then(res => res.text())
        .then(txt => {
            if (typingStatus) typingStatus.innerText = txt;
        });
}

if (msgInputAdmin) {
    msgInputAdmin.addEventListener("input", sendTyping);
}

loadChat();
loadTyping();

setInterval(loadChat, 4000);
setInterval(loadTyping, 1000);
</script>

<?php require_once "includes/footer.php"; ?>
