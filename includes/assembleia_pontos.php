<?php
/*
 * Pontos da ordem de trabalhos das sessões da Assembleia de Freguesia (20/11/2026, pedido da direção) — CORE.
 * Modelo simplificado das Atas da Intranet (ata_ponto): período, ordem, subpontos, proposta, documentos por ponto e
 * resultado da votação. Usado pelo backoffice (admin/assembleia-sessao-pontos.php), pela convocatória/edital em PDF
 * (admin/assembleia-sessao-pdf.php) e pela página pública (assembleia-sessoes.php).
 * Sem a migração 094, as funções devolvem listas vazias e tudo continua como antes (texto livre).
 */

function assembleiaPeriodos(): array
{
    return ['PAOD' => 'Período antes da ordem do dia', 'OD' => 'Ordem do dia', 'PUBLICO' => 'Período de intervenção do público'];
}

function assembleiaResultados(): array
{
    return ['aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado', 'apreciado' => 'Apreciado', 'adiado' => 'Adiado', 'retirado' => 'Retirado'];
}

/**
 * Pontos de uma sessão: [periodo => [ponto, …]], só os períodos com pontos, pela ordem de assembleiaPeriodos().
 * Cada ponto traz 'num' (1, 2, 2.1…), 'filhos' (subpontos) e 'docs' (documentos ligados).
 */
function assembleiaPontos(PDO $pdo, int $sessaoId, bool $soDocsAtivos = true): array
{
    try {
        $st = $pdo->prepare("SELECT * FROM assembleia_pontos WHERE sessao_id = ? ORDER BY ordem ASC, id ASC");
        $st->execute([$sessaoId]);
        $todos = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
    if (!$todos) return [];

    $docs = [];
    try {
        $ids = implode(',', array_map('intval', array_column($todos, 'id')));
        $sql = "SELECT pd.ponto_id, d.id, d.titulo, d.ficheiro, d.categoria FROM assembleia_ponto_documentos pd
                JOIN assembleia_documentos d ON d.id = pd.documento_id WHERE pd.ponto_id IN ($ids)"
             . ($soDocsAtivos ? " AND d.ativo = 1" : "") . " ORDER BY d.titulo ASC";
        foreach ($pdo->query($sql) as $d) $docs[(int)$d['ponto_id']][] = $d;
    } catch (Throwable $e) {}

    $porPai = [];
    foreach ($todos as $p) {
        $p['docs'] = $docs[(int)$p['id']] ?? [];
        $p['filhos'] = [];
        $porPai[(int)($p['pai_id'] ?? 0)][] = $p;
    }
    $resultado = [];
    foreach (array_keys(assembleiaPeriodos()) as $periodo) {
        $n = 0; $lista = [];
        foreach ($porPai[0] ?? [] as $p) {
            if ($p['periodo'] !== $periodo) continue;
            $p['num'] = (string)(++$n);
            $m = 0;
            foreach ($porPai[(int)$p['id']] ?? [] as $f) { $f['num'] = $p['num'] . '.' . (++$m); $f['filhos'] = []; $p['filhos'][] = $f; }
            $lista[] = $p;
        }
        if ($lista) $resultado[$periodo] = $lista;
    }
    return $resultado;
}

/** Número total de pontos (com subpontos) de uma sessão, sem os carregar. */
function assembleiaContarPontos(PDO $pdo, int $sessaoId): int
{
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM assembleia_pontos WHERE sessao_id = ?");
        $st->execute([$sessaoId]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** Frase do resultado (ex.: «Aprovado por maioria, com 5 votos a favor, 2 contra e 1 abstenção.»), ou '' sem resultado. */
function assembleiaResultadoTexto(array $p): string
{
    $r = (string)($p['resultado'] ?? '');
    if ($r === '') return '';
    $nome = assembleiaResultados()[$r] ?? ucfirst($r);
    if (!in_array($r, ['aprovado', 'rejeitado'], true)) return $nome . '.';
    if (!empty($p['unanimidade'])) return $nome . ' por unanimidade.';
    $partes = [];
    $f = $p['votos_favor']; $c = $p['votos_contra']; $a = $p['votos_abstencao'];
    if ($f !== null && $f !== '') $partes[] = (int)$f . ((int)$f === 1 ? ' voto a favor' : ' votos a favor');
    if ($c !== null && $c !== '') $partes[] = (int)$c . ' contra';
    if ($a !== null && $a !== '') $partes[] = (int)$a . ((int)$a === 1 ? ' abstenção' : ' abstenções');
    if (!$partes) return $nome . ($r === 'aprovado' ? ' por maioria.' : '.');
    $ultimo = array_pop($partes);
    return $nome . ($r === 'aprovado' ? ' por maioria' : '') . ', com ' . ($partes ? implode(', ', $partes) . ' e ' : '') . $ultimo . '.';
}

/**
 * Converte o texto antigo da ordem de trabalhos (uma linha por ponto) em pontos: tira numerações do início
 * («1.», «2)», «a)», «I -», «Ponto 3:», «-», «•») e reconhece os títulos dos períodos. Devolve [[periodo, titulo], …].
 */
function assembleiaTextoParaPontos(string $texto): array
{
    $periodo = 'OD'; $pontos = [];
    foreach (preg_split('/\R/u', $texto) as $linha) {
        $linha = trim($linha);
        if ($linha === '') continue;
        $semPontuacao = mb_strtolower(trim($linha, " :.-–—\t"));
        if (preg_match('/^per[ií]odo\s+(de\s+)?antes\s+da\s+ordem\s+do\s+dia$/u', $semPontuacao)) { $periodo = 'PAOD'; continue; }
        if (preg_match('/^(per[ií]odo\s+(da\s+)?)?ordem\s+do\s+dia$/u', $semPontuacao)) { $periodo = 'OD'; continue; }
        if (preg_match('/^per[ií]odo\s+(de\s+)?interven[cç][aã]o\s+do\s+p[uú]blico$/u', $semPontuacao)) { $periodo = 'PUBLICO'; continue; }
        $titulo = preg_replace('/^(ponto\s+)?(\d+(\.\d+)*|[ivxlc]+|[a-z])\s*[\.\)\-–—:º°]+\s*/iu', '', $linha);
        $titulo = preg_replace('/^[\-–—•·*]\s*/u', '', (string)$titulo);
        $titulo = trim((string)$titulo);
        if ($titulo !== '') $pontos[] = [$periodo, mb_substr($titulo, 0, 500)];
    }
    return $pontos;
}

/**
 * Ordem de trabalhos em HTML (site público): por período, lista numerada com subpontos, proposta, documentos do ponto
 * e, com $comResultados, o resultado da votação.
 */
function assembleiaPontosHtml(array $porPeriodo, bool $comResultados, string $urlDocs = '/uploads/assembleia/'): string
{
    $periodos = assembleiaPeriodos();
    $ponto = function (array $p) use (&$ponto, $comResultados, $urlDocs): string {
        $h = '<li><span class="ordem-num">' . htmlspecialchars($p['num']) . '.</span> <span class="ordem-titulo">' . htmlspecialchars($p['titulo']) . '</span>';
        if (!empty($p['proposta_ref'])) $h .= ' <span class="ordem-ref">(' . htmlspecialchars($p['proposta_ref']) . ')</span>';
        if ($comResultados && ($t = assembleiaResultadoTexto($p)) !== '') {
            $h .= ' <span class="ordem-resultado ordem-resultado--' . htmlspecialchars((string)$p['resultado']) . '">' . htmlspecialchars($t) . '</span>';
        }
        if (!empty($p['docs'])) {
            $h .= '<span class="ordem-docs">';
            foreach ($p['docs'] as $d) {
                $h .= '<a href="' . htmlspecialchars($urlDocs . rawurlencode((string)$d['ficheiro'])) . '" target="_blank" rel="noopener">'
                    . '<i class="bi bi-file-earmark-text" aria-hidden="true"></i> ' . htmlspecialchars($d['titulo']) . '</a>';
            }
            $h .= '</span>';
        }
        if (!empty($p['filhos'])) $h .= '<ol class="ordem-lista ordem-sub">' . implode('', array_map($ponto, $p['filhos'])) . '</ol>';
        return $h . '</li>';
    };
    $html = '';
    foreach ($porPeriodo as $periodo => $lista) {
        if (count($porPeriodo) > 1 || $periodo !== 'OD') $html .= '<p class="ordem-periodo">' . htmlspecialchars($periodos[$periodo] ?? $periodo) . '</p>';
        $html .= '<ol class="ordem-lista ordem-pontos">' . implode('', array_map($ponto, $lista)) . '</ol>';
    }
    return $html;
}

/** Presidente da Mesa da Assembleia (nome), para a convocatória e o edital. */
function assembleiaPresidenteMesa(PDO $pdo): string
{
    try {
        $st = $pdo->query("SELECT nome FROM assembleia_composicao WHERE ativo = 1 AND grupo = 'Mesa da Assembleia'
                           AND cargo LIKE 'Presidente%' ORDER BY ordem ASC, id ASC LIMIT 1");
        return (string)($st->fetchColumn() ?: '');
    } catch (Throwable $e) {
        return '';
    }
}
