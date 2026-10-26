<?php
/**
 * Bloco «Textos da página no site» (12/10/2026): mostra, dentro de uma página do backoffice que já
 * existe, só os textos importantes da página pública correspondente (etiqueta, título, introdução).
 * Substitui a antiga página «Textos do site», que tinha 1 222 campos e era confusa.
 * Os textos continuam guardados na tabela textos_site (só os alterados); vazio = texto original.
 *
 * Uso (depois do header.php, que tem ob_start):
 *   textosBloco('Texto do topo da página «Notícias»', ['noticias.hero.kicker', 'noticias.hero.titulo', 'noticias.hero.texto'], '/noticias.php');
 *   ou com grupos: textosBloco('Títulos das secções', ['Notícias' => ['inicio.noticias.kicker', ...], ...], '/');
 */
require_once __DIR__ . '/../../includes/textos.php';

function textosBloco(string $titulo, array $chaves, string $verSite = '', bool $aberto = false): void
{
    global $pdo;
    $grupos = array_is_list($chaves) ? ['' => $chaves] : $chaves;
    $campos = textosCampos();
    $todas = array_values(array_filter(array_merge(...array_values($grupos)), fn($k) => isset($campos[$k])));
    $idBloco = 'txb-' . substr(md5(implode(',', $todas)), 0, 8);

    // guardar (só as chaves deste bloco)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['textos_bloco'] ?? '') === $idBloco) {
        $apagar = $pdo->prepare("DELETE FROM textos_site WHERE chave = ?");
        $gravar = $pdo->prepare("INSERT INTO textos_site (chave, valor, atualizado_por) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor), atualizado_por = VALUES(atualizado_por)");
        foreach ($todas as $k) {
            $v = trim(str_replace("\r\n", "\n", (string)($_POST['tx'][$k] ?? '')));
            if ($campos[$k][2] === 'linha') $v = preg_replace('/\s*\n\s*/', ' ', $v);
            if ($v === '' || $v === $campos[$k][1]) $apagar->execute([$k]);
            else $gravar->execute([$k, mb_substr($v, 0, 5000), $_SESSION['admin_id'] ?? null]);
        }
        $_SESSION['aviso_admin'] = 'Textos guardados. Já aparecem no site.';
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#' . $idBloco);
        exit;
    }

    $guardados = textosGuardados();
    $nAlterados = count(array_intersect_key($guardados, array_flip($todas)));
    static $estilo = false;
    if (!$estilo) { $estilo = true; ?>
        <style>
        .txb{margin:0 0 22px;background:#fff;border:1px solid var(--adm-borda);border-radius:var(--adm-raio-lg);box-shadow:var(--adm-sombra-1)}
        .txb > summary{display:flex;align-items:center;gap:10px;padding:16px 20px;cursor:pointer;font-weight:600;list-style:none}
        .txb > summary::-webkit-details-marker{display:none}
        .txb > summary .bi-chevron-down{margin-left:auto;transition:transform var(--dur-2) var(--ease-out)}
        .txb[open] > summary .bi-chevron-down{transform:rotate(180deg)}
        .txb > summary b{font-size:12px;padding:2px 9px;border-radius:999px;background:color-mix(in srgb,var(--adm-acento) 22%,transparent);color:var(--ouro-texto, #7a5c00)}
        .admin-main .txb form{border:0;box-shadow:none;border-top:1px solid var(--adm-borda);border-radius:0;padding:18px 20px 20px}
        .txb .txb-grupo{margin:18px 0 6px;font-size:12px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--adm-texto-2)}
        .txb .txb-grupo:first-child{margin-top:0}
        .txb .txb-grelha{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,320px),1fr));gap:0 18px}
        .txb .txb-rodape{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:6px}
        .txb .txb-rodape small{margin:0!important}
        </style>
    <?php } ?>
    <details class="txb" id="<?= $idBloco ?>" <?= $aberto ? 'open' : '' ?>>
        <summary><i class="bi bi-fonts" aria-hidden="true"></i> <?= htmlspecialchars($titulo) ?>
            <?php if ($nAlterados): ?><b><?= $nAlterados ?> alterado<?= $nAlterados === 1 ? '' : 's' ?></b><?php endif; ?>
            <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
        <form method="post">
            <input type="hidden" name="textos_bloco" value="<?= $idBloco ?>">
            <?php foreach ($grupos as $nomeGrupo => $lista): ?>
                <?php if ($nomeGrupo !== ''): ?><p class="txb-grupo"><?= htmlspecialchars($nomeGrupo) ?></p><?php endif; ?>
                <div class="txb-grelha">
                <?php foreach ($lista as $k): if (!isset($campos[$k])) continue;
                    [$rot, $orig, $tipo] = $campos[$k];
                    $val = (isset($guardados[$k]) && trim($guardados[$k]) !== '') ? $guardados[$k] : $orig;
                    $id = 'txb_' . preg_replace('/[^a-z0-9]/i', '_', $k);
                    $rot = preg_replace('/\s*\(.*\)$/', '', $rot); ?>
                    <div>
                        <label for="<?= $id ?>"><?= htmlspecialchars($rot) ?></label>
                        <?php if ($tipo === 'texto'): ?>
                            <textarea id="<?= $id ?>" name="tx[<?= htmlspecialchars($k) ?>]" rows="2" placeholder="<?= htmlspecialchars($orig) ?>"><?= htmlspecialchars($val) ?></textarea>
                        <?php else: ?>
                            <input type="text" id="<?= $id ?>" name="tx[<?= htmlspecialchars($k) ?>]" value="<?= htmlspecialchars($val) ?>" placeholder="<?= htmlspecialchars($orig) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <div class="txb-rodape">
                <small>Deixe um campo vazio para voltar ao texto original.<?php if ($verSite): ?> <a href="<?= htmlspecialchars($verSite) ?>" target="_blank" rel="noopener">Ver no site</a><?php endif; ?></small>
                <button type="submit" class="btn"><i class="bi bi-check2" aria-hidden="true"></i> Guardar textos</button>
            </div>
        </form>
    </details>
    <?php
}
