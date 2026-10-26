<?php
/**
 * Utilidades (CORE): farmácia de serviço e auxiliares do Hub de Utilidades (utilidades.php).
 */

/** Turno de farmácia em curso (ou null). Tabela farmacias_servico (migração 047). */
function farmaciaDeServico(PDO $pdo): ?array
{
    try {
        $st = $pdo->query("SELECT * FROM farmacias_servico WHERE inicio <= NOW() AND fim > NOW() ORDER BY inicio DESC LIMIT 1");
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        return null;   // tabela ainda não criada neste site: simplesmente não mostra
    }
}

/** "268 550 160 / 268 550 161" → [['268 550 160', '+351268550160'], …] para links tel:. */
function telefonesDe(?string $texto): array
{
    $out = [];
    foreach (preg_split('#\s*(?:/|;|,| ou )\s*#u', (string)$texto) as $parte) {
        $digitos = preg_replace('/\D+/', '', $parte);
        if (strlen($digitos) === 9) $out[] = [trim($parte), '+351' . $digitos];
        elseif (strlen($digitos) >= 3 && strlen($digitos) <= 6) $out[] = [trim($parte), $digitos];   // 112, 1414…
        elseif (strlen($digitos) === 12 && strpos($digitos, '351') === 0) $out[] = [trim($parte), '+' . $digitos];
    }
    return $out;
}

/** Bloco da farmácia de serviço (homepage e utilidades). Não imprime nada sem turno em curso. */
function blocoFarmaciaServico(PDO $pdo, bool $compacto = false): void
{
    $f = farmaciaDeServico($pdo);
    if (!$f) return;
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $tels = telefonesDe($f['telefone'] ?? '');
    $morada = trim(($f['morada'] ?? '') . (($f['localidade'] ?? '') !== '' ? ', ' . $f['localidade'] : ''), ', ');
    $mapa = $morada !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(($f['nome'] ?? '') . ', ' . $morada) : '';
    ?>
    <aside class="farmacia-servico<?= $compacto ? ' farmacia-servico--compacta' : '' ?>" aria-labelledby="farmaciaServicoT">
        <span class="farmacia-servico__ico" aria-hidden="true"><i class="bi bi-capsule"></i></span>
        <div class="farmacia-servico__info">
            <span class="farmacia-servico__etq"><?= te('contactos_uteis.farmacia.etiqueta') ?> · até <?= $h(date('d/m', strtotime($f['fim']))) ?> às <?= $h(date('H\hi', strtotime($f['fim']))) ?></span>
            <h2 class="farmacia-servico__nome" id="farmaciaServicoT"><?= $h($f['nome'] ?? '') ?></h2>
            <?php if ($morada !== ''): ?><p class="farmacia-servico__morada"><i class="bi bi-geo-alt" aria-hidden="true"></i> <?= $h($morada) ?></p><?php endif; ?>
            <?php if (!empty($f['observacoes'])): ?><p class="farmacia-servico__obs"><?= $h($f['observacoes']) ?></p><?php endif; ?>
        </div>
        <div class="farmacia-servico__acoes">
            <?php foreach ($tels as [$txt, $num]): ?>
                <a class="farmacia-servico__btn" href="tel:<?= $h($num) ?>"><i class="bi bi-telephone" aria-hidden="true"></i> <?= $h($txt) ?></a>
            <?php endforeach; ?>
            <?php if ($mapa): ?>
                <a class="farmacia-servico__btn farmacia-servico__btn--sec" href="<?= $h($mapa) ?>" target="_blank" rel="noopener"><i class="bi bi-sign-turn-right" aria-hidden="true"></i> <?= te('contactos_uteis.farmacia.como_chegar') ?><span class="sr-only"> (abre numa nova janela)</span></a>
            <?php endif; ?>
        </div>
    </aside>
    <?php
}


/* ---------- Cartão de contacto útil (contactos.php e utilidades.php) ----------
   Tom de cor por categoria (só no quadrado do ícone — ajuda a encontrar à primeira vista),
   ícone de traço fino escolhido no admin, ação principal "Ligar · número" e ações secundárias
   em botões de ícone (e-mail, copiar e-mail, mapa). Sem dados repetidos. */
const CONTACTO_TONS = [
    'autarquia' => 'verde', 'saúde' => 'azul', 'saude' => 'azul', 'segurança' => 'indigo', 'seguranca' => 'indigo',
    'emergência' => 'vermelho', 'emergencia' => 'vermelho', 'educação' => 'ambar', 'educacao' => 'ambar',
    'serviços públicos' => 'ardosia', 'servicos publicos' => 'ardosia', 'ação social' => 'rosa', 'acao social' => 'rosa',
    'economia' => 'teal', 'cultura' => 'violeta', 'desporto' => 'teal', 'transportes' => 'ardosia',
];

function contactoTom(string $categoria): string
{
    return CONTACTO_TONS[mb_strtolower(trim($categoria))] ?? 'verde';
}

/** Ícone sempre na versão de traço (sem "-fill"), para todos terem o mesmo peso visual. */
function contactoIcone(?string $icone): string
{
    $i = trim((string)$icone);
    if (!preg_match('/^bi-[a-z0-9-]+$/', $i)) $i = 'bi-telephone';
    return preg_replace('/-fill$/', '', $i);
}

function cartaoContactoUtil(array $c): string
{
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $nome = trim((string)($c['nome'] ?? ''));
    $cat = trim((string)($c['categoria'] ?? '')) ?: 'Contactos úteis';
    $tels = telefonesDe($c['telefone'] ?? '');
    $email = trim((string)($c['email'] ?? ''));
    $morada = trim((string)($c['morada'] ?? ''));
    $horario = trim((string)($c['horario'] ?? ''));
    $desc = trim((string)($c['descricao'] ?? ''));
    $mapa = $morada !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($nome . ', ' . $morada) : '';
    $procura = mb_strtolower($nome . ' ' . $cat . ' ' . $desc . ' ' . $morada . ' ' . ($c['telefone'] ?? '') . ' ' . $email);

    $o = '<article class="cu" data-categoria="' . $h($cat) . '" data-procura="' . $h($procura) . '" data-tom="' . contactoTom($cat) . '">';
    // capa: fotografia (admin → Contactos) ou, sem ela, o tom da categoria — os cartões ficam sempre alinhados
    $img = trim((string)($c['imagem'] ?? ''));
    $temImg = $img !== '' && strpos($img, '..') === false && is_file(__DIR__ . '/../assets/img/' . $img);
    $o .= '<div class="cu__capa" aria-hidden="true">' . ($temImg ? '<img src="/assets/img/' . $h($img) . '" alt="" loading="lazy" decoding="async">' : '') . '</div>';
    $o .= '<header class="cu__topo"><span class="cu__ico" aria-hidden="true"><i class="bi ' . $h(contactoIcone($c['icone'] ?? '')) . '"></i></span>'
        . '<div class="cu__titulo"><h3>' . $h($nome) . '</h3><span class="cu__cat">' . $h($cat) . '</span></div></header>';
    if ($desc !== '' || $morada !== '' || $horario !== '' || count($tels) > 1) {
        $o .= '<ul class="cu__info">';
        if ($desc !== '') $o .= '<li class="cu__desc">' . $h($desc) . '</li>';
        if ($morada !== '') $o .= '<li><i class="bi bi-geo-alt" aria-hidden="true"></i><span>' . $h($morada) . '</span></li>';
        if ($horario !== '') $o .= '<li><i class="bi bi-clock" aria-hidden="true"></i><span>' . $h($horario) . '</span></li>';
        // números além do principal
        foreach (array_slice($tels, 1) as [$txt, $num]) $o .= '<li><i class="bi bi-telephone" aria-hidden="true"></i><a href="tel:' . $h($num) . '">' . $h($txt) . '</a></li>';
        $o .= '</ul>';
    }
    $o .= '<div class="cu__acoes">';
    if ($tels) {
        [$txt, $num] = $tels[0];
        $o .= '<a class="cu__ligar" href="tel:' . $h($num) . '" aria-label="Ligar para ' . $h($nome) . ': ' . $h($txt) . '"><i class="bi bi-telephone" aria-hidden="true"></i><span>' . $h($txt) . '</span></a>';
    } elseif ($email !== '') {
        $o .= '<a class="cu__ligar" href="mailto:' . $h($email) . '"><i class="bi bi-envelope" aria-hidden="true"></i><span>Enviar e-mail</span></a>';
    }
    if ($email !== '') {
        if ($tels) $o .= '<a class="cu__btn" href="mailto:' . $h($email) . '" aria-label="Enviar e-mail para ' . $h($email) . '" title="' . $h($email) . '"><i class="bi bi-envelope" aria-hidden="true"></i></a>';
        $o .= '<button type="button" class="cu__btn" data-copiar="' . $h($email) . '" aria-label="Copiar o e-mail ' . $h($email) . '" title="Copiar e-mail"><i class="bi bi-copy" aria-hidden="true"></i></button>';
    }
    if ($mapa) $o .= '<a class="cu__btn" href="' . $h($mapa) . '" target="_blank" rel="noopener" aria-label="Ver ' . $h($nome) . ' no mapa (abre numa nova janela)" title="Como chegar"><i class="bi bi-sign-turn-right" aria-hidden="true"></i></a>';
    $o .= '</div></article>';
    return $o;
}

/**
 * Secção completa: emergência, pesquisa, filtros com contagem e grelha que se preenche sempre
 * (a última linha estica — nunca ficam buracos). Filtro e pesquisa em app.js ([data-cu]).
 */
function seccaoContactosUteis(array $contactos, string $idTitulo = 'cuTitulo', string $kicker = 'Na freguesia', string $titulo = 'Contactos úteis', bool $emergencia = true): void
{
    if (!$contactos) return;
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $cats = [];
    foreach ($contactos as $c) { $k = trim((string)($c['categoria'] ?? '')) ?: 'Contactos úteis'; $cats[$k] = ($cats[$k] ?? 0) + 1; }
    ?>
    <section class="cu-sec" data-cu aria-labelledby="<?= $h($idTitulo) ?>">
        <div class="cu-sec__cab">
            <div>
                <?php if ($kicker): ?><span class="ct-kicker"><?= $h($kicker) ?></span><?php endif; ?>
                <h2 id="<?= $h($idTitulo) ?>"><?= $h($titulo) ?></h2>
            </div>
            <label class="cu-procura">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span class="sr-only"><?= te('contactos_uteis.pesquisa.rotulo') ?></span>
                <input type="search" placeholder="Procurar contacto…" autocomplete="off" data-cu-procura>
            </label>
        </div>
        <?php if ($emergencia): ?>
            <div class="cu-emergencia" role="note">
                <span class="cu-emergencia__t"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i> <?= te('contactos_uteis.emergencia.titulo') ?></span>
                <a href="tel:112"><strong>112</strong> <?= te('contactos_uteis.emergencia.n112') ?></a>
                <a href="tel:808242424"><strong>808 24 24 24</strong> SNS 24</a>
            </div>
        <?php endif; ?>
        <?php if (count($cats) > 1): ?>
            <div class="cu-filtros" role="group" aria-label="Filtrar por categoria">
                <button type="button" class="cu-filtro" data-cu-filtro="*" aria-pressed="true"><?= te('contactos_uteis.filtros.todos') ?> <span><?= count($contactos) ?></span></button>
                <?php foreach ($cats as $k => $n): ?>
                    <button type="button" class="cu-filtro" data-cu-filtro="<?= $h($k) ?>" data-tom="<?= contactoTom($k) ?>" aria-pressed="false"><?= $h($k) ?> <span><?= (int)$n ?></span></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="cu-grelha"><?php foreach ($contactos as $c) echo cartaoContactoUtil($c); ?></div>
        <p class="cu-vazio" data-cu-vazio hidden><?= te('contactos_uteis.vazio.texto') ?> <button type="button" data-cu-limpar><?= te('contactos_uteis.vazio.botao') ?></button>.</p>
        <p class="sr-only" aria-live="polite" data-cu-estado></p>
    </section>
    <?php
}
