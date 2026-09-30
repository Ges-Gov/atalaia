<?php
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
