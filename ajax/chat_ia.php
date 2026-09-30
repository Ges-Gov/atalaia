<?php
/**
 * Assistente de IA do site — responde a perguntas dos visitantes com base no
 * FAQ e no conteúdo público do site (notícias recentes, contactos).
 *
 * Fica inativo por omissão: sem includes/ia_config.php com uma chave válida,
 * devolve sempre a mensagem de "indisponível", nunca tenta chamar a API. Isto
 * torna seguro ter este ficheiro em todos os sites — só "liga" onde alguém
 * decidir mesmo configurar uma chave.
 */

session_start();
require_once __DIR__ . "/../includes/db.php";

header("Content-Type: text/html; charset=UTF-8");

$configPath = __DIR__ . "/../includes/ia_config.php";
if (is_file($configPath)) {
    require_once $configPath;
}

function respostaBot(string $html): void
{
    echo '<div class="chat-ia-msg bot">' . $html . '</div>';
    exit;
}

if (!defined('IA_API_KEY') || IA_API_KEY === '') {
    respostaBot('Assistente indisponível de momento. Contacte-nos diretamente em <a href="/contactos.php" target="_blank" rel="noopener">Contactos</a>.');
}

$pergunta = trim($_POST['pergunta'] ?? '');

if ($pergunta === '') {
    respostaBot('Escreva uma pergunta para eu poder ajudar.');
}

if (mb_strlen($pergunta) > 500) {
    respostaBot('A pergunta é demasiado longa — tente resumir num parágrafo mais curto.');
}

// Limite simples por sessão, para proteger de abuso/custos inesperados.
$limite = defined('IA_LIMITE_POR_HORA') ? IA_LIMITE_POR_HORA : 20;
$agora = time();
if (empty($_SESSION['chat_ia_janela']) || ($agora - $_SESSION['chat_ia_janela']) > 3600) {
    $_SESSION['chat_ia_janela'] = $agora;
    $_SESSION['chat_ia_contagem'] = 0;
}
if ($_SESSION['chat_ia_contagem'] >= $limite) {
    respostaBot('Atingiu o limite de perguntas por agora. Tente novamente daqui a pouco, ou contacte-nos em <a href="/contactos.php" target="_blank" rel="noopener">Contactos</a>.');
}
$_SESSION['chat_ia_contagem']++;

/* ---------- Construir o contexto a partir da base de dados ---------- */

$config = $pdo->query("SELECT * FROM configuracoes_site ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

$faqs = $pdo->query("SELECT pergunta, resposta FROM faqs WHERE ativo = 1 ORDER BY ordem ASC")->fetchAll(PDO::FETCH_ASSOC);

$noticiasRecentes = $pdo->query("
    SELECT titulo, descricao, data
    FROM noticias
    ORDER BY data DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$eventosFuturos = $pdo->query("
    SELECT titulo, descricao, data_evento, data_fim
    FROM eventos
    WHERE data_evento >= CURDATE()
    ORDER BY data_evento ASC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$contactos = $pdo->query("
    SELECT nome, categoria, telefone, email, morada, horario
    FROM contactos_uteis
    WHERE ativo = 1
    ORDER BY destaque DESC, ordem ASC
    LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);

$comercio = $pdo->query("
    SELECT nome, tipo, telefone, morada
    FROM comercio_local
    ORDER BY nome ASC
    LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

$historia = $pdo->query("SELECT * FROM pagina_historia ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
$freguesia = $pdo->query("SELECT * FROM pagina_freguesia ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
$heraldica = $pdo->query("SELECT * FROM heraldica_pagina WHERE ativo = 1 ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

$executivo = $pdo->query("
    SELECT nome, cargo, pelouros
    FROM executivo_membros
    WHERE ativo = 1
    ORDER BY ordem ASC
")->fetchAll(PDO::FETCH_ASSOC);

$documentosList = $pdo->query("
    SELECT titulo, categoria, data_documento
    FROM documentos
    WHERE ativo = 1 AND area = 'freguesia'
    ORDER BY data_documento DESC
    LIMIT 40
")->fetchAll(PDO::FETCH_ASSOC);

$assembleiaComposicao = $pdo->query("
    SELECT nome, cargo, grupo, partido
    FROM assembleia_composicao
    WHERE ativo = 1
    ORDER BY ordem ASC
")->fetchAll(PDO::FETCH_ASSOC);

$assembleiaSessoes = $pdo->query("
    SELECT titulo, tipo, data_sessao, hora_sessao, estado
    FROM assembleia_sessoes
    WHERE ativo = 1
    ORDER BY data_sessao DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$assembleiaCompetencias = $pdo->query("
    SELECT titulo, conteudo
    FROM assembleia_competencias_blocos
    WHERE ativo = 1
    ORDER BY ordem ASC
")->fetchAll(PDO::FETCH_ASSOC);

$associacoesList = $pdo->query("
    SELECT nome, descricao
    FROM associacoes
    ORDER BY nome ASC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

$pontosList = $pdo->query("
    SELECT nome, descricao
    FROM pontos_interesse
    ORDER BY nome ASC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

$contexto = "Identidade: " . ($config['nome_site'] ?? 'Junta de Freguesia') . ", concelho de " . ($config['municipio'] ?? '') . ".\n";
$contexto .= "Contacto geral: email " . ($config['email'] ?? '') . ", telefone " . ($config['telefone'] ?? '') . ", morada " . ($config['morada'] ?? '') . ", horário " . ($config['horario'] ?? '') . ".\n\n";

// Páginas fixas do site — sempre presentes, iguais em todas as Juntas.
// Sem isto, o assistente não sabe que existem e diz "não disponível"
// para perguntas sobre políticas, DPO, acessibilidade, etc.
$contexto .= "Páginas fixas do site (usa o link exato quando a pergunta corresponder):\n";
$contexto .= "- Pedidos à Junta: /pedidos.php\n";
$contexto .= "- Requerimentos: /requerimentos.php\n";
$contexto .= "- Marcações: /marcacoes.php\n";
$contexto .= "- Livro de Reclamações: /livro-reclamacoes.php\n";
$contexto .= "- Transparência: /transparencia.php\n";
$contexto .= "- Notícias: /noticias.php\n";
$contexto .= "- Eventos: /eventos.php\n";
$contexto .= "- Contactos: /contactos.php\n";
$contexto .= "- Perguntas Frequentes (FAQ): /faq.php\n";
$contexto .= "- Política de Privacidade: /politica-privacidade.php\n";
$contexto .= "- Política de Cookies: /politica-cookies.php\n";
$contexto .= "- Proteção de Dados (DPO): /protecao-dados.php\n";
$contexto .= "- Declaração de Acessibilidade: /declaracao-acessibilidade.php\n";
$contexto .= "- Canal de Denúncias: /canal-denuncias.php\n\n";

if (!empty($faqs)) {
    $contexto .= "Perguntas frequentes:\n";
    foreach ($faqs as $f) {
        $contexto .= "P: " . $f['pergunta'] . "\nR: " . strip_tags($f['resposta']) . "\n\n";
    }
}

if (!empty($noticiasRecentes)) {
    $contexto .= "Notícias recentes:\n";
    foreach ($noticiasRecentes as $n) {
        $contexto .= "- " . $n['titulo'] . " (" . date('d/m/Y', strtotime($n['data'])) . "): "
            . mb_substr(strip_tags($n['descricao'] ?? ''), 0, 200) . "\n";
    }
    $contexto .= "\n";
}

if (!empty($eventosFuturos)) {
    $contexto .= "Próximos eventos:\n";
    foreach ($eventosFuturos as $e) {
        $linha = "- " . $e['titulo'] . " (" . date('d/m/Y', strtotime($e['data_evento']));
        if (!empty($e['data_fim'])) $linha .= " a " . date('d/m/Y', strtotime($e['data_fim']));
        $linha .= "): " . mb_substr(strip_tags($e['descricao'] ?? ''), 0, 200);
        $contexto .= $linha . "\n";
    }
    $contexto .= "\n";
} else {
    $contexto .= "Não há eventos futuros agendados de momento.\n\n";
}

if (!empty($contactos)) {
    $contexto .= "Contactos úteis:\n";
    foreach ($contactos as $c) {
        $linha = "- " . $c['nome'];
        if (!empty($c['categoria'])) $linha .= " (" . $c['categoria'] . ")";
        if (!empty($c['telefone'])) $linha .= " · tel: " . $c['telefone'];
        if (!empty($c['horario']))  $linha .= " · horário: " . $c['horario'];
        $contexto .= $linha . "\n";
    }
    $contexto .= "\n";
}

if (!empty($comercio)) {
    $contexto .= "Comércio local:\n";
    foreach ($comercio as $c) {
        $linha = "- " . $c['nome'];
        if (!empty($c['tipo'])) $linha .= " (" . $c['tipo'] . ")";
        if (!empty($c['morada'])) $linha .= " · " . $c['morada'];
        if (!empty($c['telefone'])) $linha .= " · tel: " . $c['telefone'];
        $contexto .= $linha . "\n";
    }
    $contexto .= "\n";
}

if (!empty($associacoesList)) {
    $contexto .= "Associações da freguesia:\n";
    foreach ($associacoesList as $a) {
        $contexto .= "- " . $a['nome'] . ": " . mb_substr(strip_tags($a['descricao'] ?? ''), 0, 150) . "\n";
    }
    $contexto .= "\n";
}

if (!empty($pontosList)) {
    $contexto .= "Pontos de interesse / património:\n";
    foreach ($pontosList as $p) {
        $contexto .= "- " . $p['nome'] . ": " . mb_substr(strip_tags($p['descricao'] ?? ''), 0, 150) . "\n";
    }
    $contexto .= "\n";
}

if (!empty($executivo)) {
    $contexto .= "Executivo da Junta:\n";
    foreach ($executivo as $e) {
        $linha = "- " . $e['nome'] . " (" . $e['cargo'] . ")";
        if (!empty($e['pelouros'])) $linha .= " · pelouros: " . strip_tags($e['pelouros']);
        $contexto .= $linha . "\n";
    }
    $contexto .= "\n";
}

if (!empty($documentosList)) {
    $contexto .= "Documentos disponíveis (página /freguesia-documentos.php):\n";
    foreach ($documentosList as $d) {
        $linha = "- " . $d['titulo'];
        if (!empty($d['categoria'])) $linha .= " (" . $d['categoria'] . ")";
        if (!empty($d['data_documento'])) $linha .= " · " . date('d/m/Y', strtotime($d['data_documento']));
        $contexto .= $linha . "\n";
    }
    $contexto .= "\n";
}

if (!empty($assembleiaComposicao)) {
    $contexto .= "Composição da Assembleia de Freguesia:\n";
    foreach ($assembleiaComposicao as $m) {
        $linha = "- " . $m['nome'] . " (" . $m['cargo'] . ", " . $m['grupo'] . ")";
        if (!empty($m['partido'])) $linha .= " · " . $m['partido'];
        $contexto .= $linha . "\n";
    }
    $contexto .= "\n";
}

if (!empty($assembleiaSessoes)) {
    $contexto .= "Sessões da Assembleia de Freguesia (mais recentes):\n";
    foreach ($assembleiaSessoes as $s) {
        $contexto .= "- " . $s['titulo'] . " (" . $s['tipo'] . "), " . date('d/m/Y', strtotime($s['data_sessao'])) . " às " . substr($s['hora_sessao'], 0, 5) . " · estado: " . $s['estado'] . "\n";
    }
    $contexto .= "\n";
}

if (!empty($assembleiaCompetencias)) {
    $contexto .= "Competências da Assembleia de Freguesia:\n";
    foreach ($assembleiaCompetencias as $c) {
        $contexto .= "- " . $c['titulo'] . ": " . mb_substr(strip_tags($c['conteudo'] ?? ''), 0, 200) . "\n";
    }
    $contexto .= "\n";
}

$textoFreguesia = trim(
    ($freguesia['intro_texto'] ?? '') . ' ' .
    ($freguesia['historia_texto'] ?? '') . ' ' .
    ($freguesia['identidade_texto'] ?? '') . ' ' .
    ($freguesia['patrimonio_texto'] ?? '') . ' ' .
    ($freguesia['localidades_texto'] ?? '')
);
if ($textoFreguesia !== '') {
    $contexto .= "Sobre a freguesia:\n" . mb_substr(strip_tags($textoFreguesia), 0, 1500) . "\n\n";
}

$textoHistoria = trim(
    ($historia['intro_texto'] ?? '') . ' ' .
    ($historia['bloco1_texto'] ?? '') . ' ' .
    ($historia['bloco2_texto'] ?? '')
);
if ($textoHistoria !== '') {
    $contexto .= "História da freguesia:\n" . mb_substr(strip_tags($textoHistoria), 0, 1500) . "\n\n";
}

if (!empty($heraldica['texto_intro'])) {
    $contexto .= "Heráldica (brasão):\n" . mb_substr(strip_tags($heraldica['texto_intro']), 0, 800) . "\n\n";
}

$prompt_sistema = "És o assistente virtual do site da " . ($config['nome_site'] ?? 'Junta de Freguesia') . ". "
    . "Responde SEMPRE em português de Portugal, de forma curta, direta e simpática. "
    . "Usa APENAS a informação abaixo para responder — nunca inventes horários, contactos, valores ou factos que não estejam aqui. "
    . "Se a pergunta não tiver resposta na informação disponível, diz isso claramente e sugere contactar a Junta, sem inventar. "
    . "Nunca escrevas o caminho técnico de uma página à parte (ex.: nunca escrevas '/contactos.php' como texto solto) — "
    . "isso não faz sentido para quem não é programador. Sempre que referires uma página do site, "
    . "usa o formato [Nome legível da página](/caminho.php), por exemplo [Contactos](/contactos.php). "
    . "Não respondas a perguntas sem relação com a freguesia ou os seus serviços.\n\n"
    . $contexto;

/* ---------- Chamar a API do Google Gemini ---------- */
// Documentação: https://ai.google.dev/api/generate-content
// A chave vai no URL (query string) — é o padrão documentado pela Google
// para esta API; não há cabeçalho de autenticação alternativo mais simples.

$payload = json_encode([
    'contents' => [
        ['parts' => [['text' => $pergunta]]],
    ],
    'systemInstruction' => [
        'parts' => [['text' => $prompt_sistema]],
    ],
    'generationConfig' => [
        'maxOutputTokens' => 500,
    ],
]);
$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode(IA_MODELO) . ':generateContent?key=' . rawurlencode(IA_API_KEY);

$httpClient = curl_init($url);
curl_setopt_array($httpClient, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'content-type: application/json',
    ],
    CURLOPT_TIMEOUT => 20,
]);

$resposta = curl_exec($httpClient);
$erroCurl = curl_error($httpClient);
$codigoHttp = curl_getinfo($httpClient, CURLINFO_HTTP_CODE);
curl_close($httpClient);

if ($erroCurl || $codigoHttp !== 200) {
    respostaBot('Não consegui responder de momento. Tente novamente, ou contacte-nos em <a href="/contactos.php" target="_blank" rel="noopener">Contactos</a>.');
}

$dados = json_decode($resposta, true);
$texto = $dados['candidates'][0]['content']['parts'][0]['text'] ?? '';

if ($texto === '') {
    respostaBot('Não consegui responder de momento. Tente novamente, ou contacte-nos em <a href="/contactos.php" target="_blank" rel="noopener">Contactos</a>.');
}

// O modelo é instruído a referir páginas do site como [Nome](/caminho.php).
// Depois de escapar tudo (segurança), convertemos só esse padrão em <a> real —
// a expressão regular só aceita caminhos internos ".php", nunca http(s):// nem
// javascript:, por isso não abre nenhuma brecha de XSS/open-redirect.
$textoComLinks = preg_replace_callback(
    '/\[([^\[\]]{1,80})\]\((\/[a-z0-9\-]+\.php)\)/i',
    static function (array $m): string {
        return '<a href="' . htmlspecialchars($m[2]) . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
    },
    htmlspecialchars($texto)
);

respostaBot(nl2br($textoComLinks));
