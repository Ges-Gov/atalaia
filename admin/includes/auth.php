<?php
/*
 * Plataforma de sites de Juntas de Freguesia — © 2026 GesGov. Todos os direitos reservados.
 * Licença GG-ATALAIA-005E52FF — uso exclusivo de: Freguesias de Atalaia e Alto Estanqueiro-Jardia, concelho do Montijo.
 * Proibida a cópia, modificação, redistribuição ou venda sem autorização escrita da GesGov.
 */
session_start();

if (!isset($_SESSION['admin'])) {
    $current = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
    header("Location: login.php?redirect=" . urlencode($current));
    exit;
}

$adminId = $_SESSION['admin_id'] ?? null;
$adminNome = $_SESSION['admin_nome'] ?? ($_SESSION['admin'] ?? 'Administrador');
$adminTipo = $_SESSION['admin_tipo'] ?? 'admin';

function isAdminMaster(){ return ($_SESSION['admin_tipo'] ?? 'admin') === 'admin'; }
function isOperador(){ return ($_SESSION['admin_tipo'] ?? '') === 'operador'; }
function isVogal(){ return ($_SESSION['admin_tipo'] ?? '') === 'vogal'; }
function isPresidenteAssembleia(){ return ($_SESSION['admin_tipo'] ?? '') === 'presidente_assembleia'; }
function isAdminDenuncias(){ return ($_SESSION['admin_tipo'] ?? '') === 'admin_denuncias'; }
function canManageAssembleia(){ return isAdminMaster() || isPresidenteAssembleia(); }
function canManageDenuncias(){ return isAdminMaster() || isAdminDenuncias(); }

/**
 * Utilizadores OCULTOS da GesGov. Existem e fazem login, mas não aparecem na lista
 * de utilizadores do backoffice — a mesma lista de $utilizadoresOcultos usada em
 * admin/utilizadores.php (mantida aqui em duplicado de propósito, para o auth.php
 * não depender dessa página).
 */
function utilizadoresGesGov(){ return ['admin', 'admin@denuncias']; }

/**
 * É o utilizador da GesGov? Só ele pode criar PERFIS e associá-los a utilizadores
 * (ver admin/perfis.php e a migração 007_perfis.sql). Os administradores da junta
 * não veem sequer o separador.
 */
function isGesGov(){
    return in_array($_SESSION['admin'] ?? '', utilizadoresGesGov(), true);
}

function requireGesGov(){
    if (!isGesGov()) die("Acesso reservado à GesGov.");
}

/**
 * Permissões do perfil associado ao utilizador com sessão iniciada.
 * Devolve [] se o utilizador não tiver perfil (nesse caso vale só o `tipo`, como antes).
 * Lido uma única vez por pedido.
 */
function permissoesDoPerfil(){
    static $perms = null;

    if ($perms === null) {
        global $pdo;
        $perms = [];

        try {
            $stmt = $pdo->prepare("
                SELECT p.permissoes
                FROM admin_utilizadores u
                JOIN perfis p ON p.id = u.perfil_id AND p.ativo = 1
                WHERE u.id = ?
            ");
            $stmt->execute([$_SESSION['admin_id'] ?? 0]);
            $lista = (string)$stmt->fetchColumn();

            if ($lista !== '') {
                $perms = array_filter(array_map('trim', explode(',', $lista)));
            }
        } catch (Exception $e) {
            // Tabela ainda não migrada: sem perfil, comporta-se como antes.
            $perms = [];
        }
    }

    return $perms;
}

/** O utilizador tem perfil atribuído? */
function temPerfil(){ return !empty(permissoesDoPerfil()); }

/**
 * Pode aceder a uma secção do backoffice ('freguesia', 'home', 'virtual',
 * 'assembleia', 'denuncias', 'sistema')?
 *
 * Se o utilizador tiver PERFIL, é o perfil que manda (mesmo sendo 'admin').
 * Se não tiver, mantém-se exatamente o comportamento antigo baseado no `tipo`.
 * A GesGov vê tudo, sempre.
 */
function podeAceder($seccao){
    if (isGesGov()) return true;

    if (temPerfil()) {
        return in_array($seccao, permissoesDoPerfil(), true);
    }

    if ($seccao === 'assembleia') return canManageAssembleia();
    if ($seccao === 'denuncias')  return canManageDenuncias();

    return isAdminMaster();
}

function requireAdminMaster(){
    if (!isAdminMaster()) die("Acesso reservado ao administrador.");
}

function requireAssembleiaManager(){
    if (!canManageAssembleia()) die("Acesso reservado ao administrador ou presidente da Assembleia.");
}

/* ---------------------------------------------------------------------------------------------------------------
 * Permissões verificadas no servidor (20/11/2026). Até aqui os perfis e os tipos de utilizador só escondiam entradas
 * do menu: quem soubesse o endereço abria qualquer página do backoffice. Agora cada página só abre a quem a vê no seu
 * menu (a mesma regra do menu.php — definido uma vez, usado nos dois sítios). Os scripts de ação (eliminar_*, …)
 * seguem a página a que pertencem; as verificações próprias de cada página (ex.: só o admin elimina) continuam.
 * ------------------------------------------------------------------------------------------------------------- */

/** Scripts que não aparecem no menu → página do menu a que pertencem. */
function adminPaginasAcoes(): array {
    return [
        'arquivar_pedido.php' => 'pedidos.php', 'eliminar_pedido.php' => 'pedidos.php', 'limpar_chat_pedido.php' => 'pedidos.php',
        'editar_homepage.php' => 'homepage.php', 'eliminar_slide.php' => 'slides.php',
        'eliminar_noticia.php' => 'noticias.php', 'eliminar_evento.php' => 'eventos.php', 'evento_ficheiro.php' => 'eventos.php',
        'eliminar_ponto.php' => 'pontos.php', 'eliminar_associacao.php' => 'associacoes.php', 'eliminar_comercio.php' => 'comercio.php',
        'eliminar_documento.php' => 'documentos.php', 'eliminar_faq.php' => 'faqs.php', 'eliminar_alerta.php' => 'alertas.php',
        'eliminar_contacto.php' => 'contactos.php', 'eliminar_requerimento.php' => 'requerimentos.php',
        'newsletter-upload-imagem.php' => 'newsletter.php',
        'comercio-candidaturas.php' => 'comercio.php', 'comercio_candidatura_imagem.php' => 'comercio.php',   // Atalaia
    ];
}

/** Páginas fora do menu completo, com a secção de permissões a que pertencem. */
function adminPaginasSeccao(): array {
    return ['denuncias.php' => 'denuncias', 'ver_denuncia.php' => 'denuncias'];
}

/** Menu deste utilizador (o do seu tipo) — sem os filtros de «módulo escondido», que não são permissões. */
function adminMenuPermissoes(bool $completo = false): array {
    static $menus = null;
    if ($menus === null) $menus = menuAdminDefinicao(['badgeSugestoes' => 0]);
    return $menus[$completo ? 1 : 0];
}

/** Páginas de um menu: [página => true]. Com $filtrar, só os grupos/entradas a que este utilizador tem acesso. */
function adminPaginasDoMenu(array $menu, bool $filtrar): array {
    $paginas = [];
    $juntar = function (array $e) use (&$paginas) {
        foreach (array_merge([$e['href'] ?? ''], $e['rel'] ?? []) as $h) {
            $h = basename((string)strtok((string)$h, '?#'));
            if ($h !== '' && substr($h, -4) === '.php') $paginas[$h] = true;
        }
    };
    foreach ($menu as $e) {
        if (isset($e['itens'])) {
            if ($filtrar && ($e['perm'] ?? null) !== null && !podeAceder($e['perm'])) continue;
            foreach ($e['itens'] as $i) if (!isset($i['sub'])) $juntar($i);
        } else {
            if ($filtrar && !empty($e['perm']) && !podeAceder($e['perm'])) continue;
            $juntar($e);
        }
    }
    return $paginas;
}

/** Páginas que abrem para este utilizador: [página => true]. */
function adminPaginasPermitidas(): array {
    static $paginas = null;
    if ($paginas === null) {
        $paginas = adminPaginasDoMenu(adminMenuPermissoes(), true);
        foreach (adminPaginasSeccao() as $p => $s) if (podeAceder($s)) $paginas[$p] = true;
    }
    return $paginas;
}

/** Esta página do backoffice abre para o utilizador com sessão iniciada? */
function adminPaginaPermitida(string $pagina): bool {
    if (isGesGov()) return true;
    $pagina = basename($pagina);
    $mae = adminPaginasAcoes()[$pagina] ?? $pagina;
    if (isset(adminPaginasPermitidas()[$mae])) return true;
    // Página que não está em menu nenhum (ex.: ferramenta própria de um só site): só o administrador sem perfil
    static $conhecidas = null;
    if ($conhecidas === null) $conhecidas = adminPaginasDoMenu(adminMenuPermissoes(true), false) + adminPaginasSeccao();
    return !isset($conhecidas[$mae]) && isAdminMaster() && !temPerfil();
}

/** Primeira página do menu deste utilizador (para onde vai quando não pode abrir a pedida). */
function adminPaginaInicial(): string {
    foreach (adminMenuPermissoes() as $e) {
        if (isset($e['itens']) && ($e['perm'] ?? null) !== null && !podeAceder($e['perm'])) continue;
        foreach (isset($e['itens']) ? $e['itens'] : [$e] as $i) {
            if (isset($i['sub']) || empty($i['href']) || ($i['se'] ?? true) === false) continue;
            $h = (string)strtok((string)$i['href'], '?#');
            if (strpos($h, '../') === 0) return $h;                       // vogais: área pública dos vogais
            if (is_file(__DIR__ . '/../' . $h) && adminPaginaPermitida($h)) return $h;
        }
    }
    return 'logout.php';
}

function adminExigirPagina(): void {
    $pagina = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($pagina === '' || adminPaginaPermitida($pagina)) return;

    $inicio = adminPaginaInicial();
    // O Painel é a página de entrada: quem não o tem no menu vai direto para a sua primeira página
    if ($pagina === 'dashboard.php' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        header('Location: ' . $inicio);
        exit;
    }
    adminSemPermissao('Sem permissão para esta página',
        'A sua conta não tem acesso a esta parte do backoffice. Se precisar dela, peça ao administrador da Junta para lhe dar essa permissão.');
}

/** Responde 403 com uma página simples (ou texto, num pedido POST/AJAX) e termina. */
function adminSemPermissao(string $titulo, string $texto): void {
    while (ob_get_level() > 0) ob_end_clean();   // o header.php abre um buffer: não mostrar meia página
    http_response_code(403);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: text/plain; charset=utf-8');
        exit($titulo . '. ' . $texto);
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . htmlspecialchars($titulo) . ' | Backoffice</title><style>'
        . 'body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f3f5f8;color:#1b2430;font:16px/1.55 system-ui,-apple-system,"Segoe UI",sans-serif;padding:16px;box-sizing:border-box}'
        . 'main{max-width:520px;background:#fff;border:1px solid #e2e7ee;border-radius:18px;padding:32px;box-shadow:0 12px 32px rgba(15,23,42,.08)}'
        . 'h1{font-size:1.5rem;margin:0 0 10px}p{margin:0 0 14px;color:#3f4a59}'
        . 'a{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 18px;border-radius:12px;background:#1b2430;color:#fff;font-weight:600;text-decoration:none}'
        . 'a:focus-visible{outline:3px solid #2563eb;outline-offset:3px}</style></head><body><main>'
        . '<h1>' . htmlspecialchars($titulo) . '</h1><p>' . htmlspecialchars($texto) . '</p>'
        . '<a href="' . htmlspecialchars(adminPaginaInicial(), ENT_QUOTES) . '">Voltar à minha página inicial</a></main></body></html>';
    exit;
}

/**
 * Pode tratar esta ocorrência? O operador só as que lhe foram atribuídas ou em que colabora (pedidos_funcionarios);
 * os outros, com acesso às Ocorrências, todas. $pedido precisa de 'id' e 'operador_id'.
 */
function adminPodeTratarPedido(PDO $pdo, array $pedido): bool {
    if (isGesGov() || !isOperador()) return true;
    $eu = (int)($_SESSION['admin_id'] ?? 0);
    if ($eu > 0 && (int)($pedido['operador_id'] ?? 0) === $eu) return true;
    try {
        $st = $pdo->prepare("SELECT 1 FROM pedidos_funcionarios WHERE pedido_id = ? AND admin_id = ?");
        $st->execute([(int)$pedido['id'], $eu]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

// Fora de qualquer função, para o $pdo ficar global (como quando a página o carrega)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/tema.php';
require_once __DIR__ . '/menu.php';
adminExigirPagina();
