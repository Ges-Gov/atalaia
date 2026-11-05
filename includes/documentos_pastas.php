<?php
/**
 * Pastas por ano nas páginas de documentos (05/11/2026, pedido da Atalaia).
 * Sem pesquisa nem filtros, a página mostra primeiro uma pasta por ano («Documentos 2026», «Documentos 2025»…,
 * mais «Sem data» se houver); dentro de cada pasta (?ano=AAAA ou ?ano=sem) aparecem os documentos como antes.
 * Usado em freguesia-documentos.php e assembleia-documentos.php.
 */

/** $pastas: lista de ['ano' => 2025 ou null (sem data), 'n' => número de documentos] */
function documentosPastas(array $pastas, string $pagina): void
{ ?>
    <?php documentosPastasCss(); ?>
    <div class="docs-pastas" role="list">
        <?php foreach ($pastas as $p):
            $ano = $p['ano'] === null || $p['ano'] === '' ? null : (string)$p['ano'];
            $n = (int)$p['n']; ?>
            <a class="docs-pasta" role="listitem" href="<?= htmlspecialchars($pagina) ?>?ano=<?= $ano === null ? 'sem' : rawurlencode($ano) ?>">
                <i class="bi bi-folder-fill" aria-hidden="true"></i>
                <div>
                    <strong><?= $ano === null ? 'Documentos sem data' : 'Documentos ' . htmlspecialchars($ano) ?></strong>
                    <span><?= $n ?> <?= $n === 1 ? 'documento' : 'documentos' ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php }

/** Ligação de volta às pastas (dentro de uma pasta de ano) */
function documentosVoltarPastas(string $pagina): void
{ documentosPastasCss(); ?>
    <a class="docs-voltar" href="<?= htmlspecialchars($pagina) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Todas as pastas</a>
<?php }

/** Estilos das pastas (uma vez por página) */
function documentosPastasCss(): void
{
    static $feito = false;
    if ($feito) return;
    $feito = true; ?>
    <style>
    .docs-pastas{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:18px;margin:8px 0 40px;}
    .docs-pasta{display:flex;align-items:center;gap:16px;padding:22px 24px;background:#fff;border:1px solid #e5e7eb;border-radius:20px;
        box-shadow:0 10px 30px rgba(15,23,42,.06);text-decoration:none;color:#11151B;transition:transform .15s ease,box-shadow .15s ease;}
    .docs-pasta:hover,.docs-pasta:focus-visible{transform:translateY(-2px);box-shadow:0 16px 40px rgba(15,23,42,.1);}
    .docs-pasta i{font-size:38px;color:var(--cor-secundaria,#C8A02E);line-height:1;}
    .docs-pasta strong{display:block;font-size:17px;white-space:nowrap;}
    .docs-pasta span{display:block;color:#64748b;font-size:14px;margin-top:2px;}
    .docs-voltar{display:inline-flex;align-items:center;gap:8px;margin:0 0 18px;font-weight:700;color:var(--cor-principal);text-decoration:none;}
    :root[data-tema="escuro"] .docs-pasta{background:var(--escuro-superficie);border-color:var(--escuro-borda);color:var(--escuro-texto);}
    :root[data-tema="escuro"] .docs-pasta span{color:var(--escuro-texto-2);}
    </style>
<?php }
