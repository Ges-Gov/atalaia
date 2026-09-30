<?php
require_once __DIR__ . "/../includes/db.php";

header("Content-Type: text/html; charset=UTF-8");

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    exit;
}

$like = "%" . $q . "%";
$resultados = [];

function adicionarResultado(&$resultados, $tipo, $titulo, $descricao, $link, $icone) {
    $resultados[] = [
        'tipo' => $tipo,
        'titulo' => $titulo,
        'descricao' => $descricao,
        'link' => $link,
        'icone' => $icone
    ];
}

/* Notícias */
try {
    $stmt = $pdo->prepare("
        SELECT titulo, descricao
        FROM noticias
        WHERE titulo LIKE ? OR descricao LIKE ?
        ORDER BY data DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        adicionarResultado(
            $resultados,
            "Notícia",
            $r['titulo'],
            mb_substr(strip_tags($r['descricao'] ?? ''), 0, 90),
            "/noticias.php",
            '<i class="bi bi-newspaper"></i>'
        );
    }
} catch (Exception $e) {}

/* Eventos */
try {
    $stmt = $pdo->prepare("
        SELECT titulo, descricao
        FROM eventos
        WHERE titulo LIKE ? OR descricao LIKE ?
        ORDER BY data_evento DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        adicionarResultado(
            $resultados,
            "Evento",
            $r['titulo'],
            mb_substr(strip_tags($r['descricao'] ?? ''), 0, 90),
            "/eventos.php",
            '<i class="bi bi-calendar-event-fill"></i>'
        );
    }
} catch (Exception $e) {}

/* Pontos de Interesse */
try {
    $stmt = $pdo->prepare("
        SELECT nome, descricao, localizacao
        FROM pontos_interesse
        WHERE nome LIKE ? OR descricao LIKE ? OR localizacao LIKE ?
        ORDER BY id DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        adicionarResultado(
            $resultados,
            "Ponto de Interesse",
            $r['nome'],
            mb_substr(strip_tags(($r['localizacao'] ?? '') . ' ' . ($r['descricao'] ?? '')), 0, 90),
            "/pontos.php",
            '<i class="bi bi-geo-alt-fill"></i>'
        );
    }
} catch (Exception $e) {}

/* Documentos */
try {
    $stmt = $pdo->prepare("
        SELECT titulo, tipo, ano
        FROM documentos
        WHERE ativo = 1
        AND (titulo LIKE ? OR tipo LIKE ? OR ano LIKE ?)
        ORDER BY criado_em DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        adicionarResultado(
            $resultados,
            "Documento",
            $r['titulo'],
            trim(($r['tipo'] ?? '') . ' ' . ($r['ano'] ?? '')),
            "/freguesia-documentos.php",
            '<i class="bi bi-file-earmark-text-fill"></i>'
        );
    }
} catch (Exception $e) {}

/* Associações */
try {
    $stmt = $pdo->prepare("
        SELECT nome, descricao
        FROM associacoes
        WHERE nome LIKE ? OR descricao LIKE ?
        ORDER BY id DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        adicionarResultado(
            $resultados,
            "Associação",
            $r['nome'],
            mb_substr(strip_tags($r['descricao'] ?? ''), 0, 90),
            "/associacoes.php",
            '<i class="bi bi-people-fill"></i>'
        );
    }
} catch (Exception $e) {}

/* Economia Local */
try {
    $stmt = $pdo->prepare("
        SELECT nome, descricao, categoria
        FROM comercio_local
        WHERE nome LIKE ? OR descricao LIKE ? OR categoria LIKE ?
        ORDER BY id DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        adicionarResultado(
            $resultados,
            "Economia Local",
            $r['nome'],
            mb_substr(strip_tags(($r['categoria'] ?? '') . ' ' . ($r['descricao'] ?? '')), 0, 90),
            "/comercio.php",
            '<i class="bi bi-shop"></i>'
        );
    }
} catch (Exception $e) {}

/* Páginas fixas úteis */
$paginasFixas = [
    ["A Freguesia", "História, visão geral e informação institucional.", "/freguesia.php", '<i class="bi bi-bank2"></i>'],
    ["História", "Conheça a história da freguesia.", "/historia.php", '<i class="bi bi-file-earmark-text-fill"></i>'],
    ["Executivo", "Composição do executivo da Junta.", "/executivo.php", '<i class="bi bi-people-fill"></i>'],
    ["Contactos", "Telefone, email, morada e formulário de contacto.", "/contactos.php", '<i class="bi bi-telephone-fill"></i>'],
    // Balcão Virtual temporariamente oculto da pesquisa: ["Junta Virtual", "Pedidos, requerimentos, marcações e serviços online.", "/minha-area.php", '<i class="bi bi-laptop"></i>'],
    ["Pedidos à Junta", "Submeter pedidos online à Junta de Freguesia.", "/pedidos.php", '<i class="bi bi-envelope-fill"></i>'],
    ["Requerimentos Online", "Submeter requerimentos e documentos digitais.", "/requerimentos.php", '<i class="bi bi-file-earmark-text-fill"></i>'],
    ["Marcação de Atendimento", "Agendar atendimento com a Junta.", "/marcacoes.php", '<i class="bi bi-calendar-event-fill"></i>'],
    ["Transparência", "Consultar indicadores e informação pública.", "/transparencia.php", '<i class="bi bi-bar-chart-fill"></i>'],
];

foreach ($paginasFixas as $p) {
    if (
        stripos($p[0], $q) !== false ||
        stripos($p[1], $q) !== false
    ) {
        adicionarResultado($resultados, "Página", $p[0], $p[1], $p[2], $p[3]);
    }
}

$resultados = array_slice($resultados, 0, 12);

if (empty($resultados)) {
    echo '<div class="search-empty">Não foram encontrados resultados.</div>';
    exit;
}

foreach ($resultados as $r): ?>
    <a class="search-result-item" href="<?= htmlspecialchars($r['link']) ?>">
        <span class="search-result-icon"><?= $r['icone'] ?></span>

        <div>
            <small><?= htmlspecialchars($r['tipo']) ?></small>
            <strong><?= htmlspecialchars($r['titulo']) ?></strong>

            <?php if (!empty($r['descricao'])): ?>
                <p><?= htmlspecialchars($r['descricao']) ?>...</p>
            <?php endif; ?>
        </div>
    </a>
<?php endforeach; ?>