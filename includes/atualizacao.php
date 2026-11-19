<?php
/**
 * «Página atualizada em …» (CORE), 19/11/2026 — checklist «Conteúdo» do Selo de Usabilidade e Acessibilidade,
 * requisito 1.3 (cada bloco de conteúdo tem a data da sua atualização).
 *
 * A data é a mais recente que se CONHECE para o conteúdo da página: colunas atualizado_em/criado_em das tabelas que a
 * alimentam (a migração 091 acrescentou atualizado_em onde faltava) e os textos editados no backoffice (textos_site,
 * pelo prefixo das chaves da página). Sem nenhuma data conhecida não mostra nada — nunca inventa uma.
 *
 *   echo paginaAtualizada(['pagina_freguesia' => 'atualizado_em', 'faqs' => ['atualizado_em', 'criado_em']], ['freguesia.']);
 *
 * $dataCodigo: para textos que vivem no código (ex.: políticas), a data em que esse texto mudou pela última vez (AAAA-MM-DD).
 */
function paginaAtualizadaData(array $tabelas = [], array $prefixosTextos = [], ?string $dataCodigo = null): ?int
{
    global $pdo;
    $datas = [];
    if ($dataCodigo && ($t = strtotime($dataCodigo)) !== false) $datas[] = $t;
    if (!isset($pdo)) return $datas ? max($datas) : null;

    foreach ($tabelas as $tabela => $colunas) {
        if (!preg_match('/^[a-z0-9_]+$/', $tabela)) continue;
        foreach ((array) $colunas as $coluna) {
            if (!preg_match('/^[a-z0-9_]+$/', $coluna)) continue;
            try {
                $v = $pdo->query("SELECT MAX(`$coluna`) FROM `$tabela`")->fetchColumn();
                if ($v && ($t = strtotime((string) $v)) !== false) $datas[] = $t;
            } catch (Throwable $e) {
                // tabela ou coluna ainda inexistente neste site (migração por correr): ignora
            }
        }
    }
    foreach ($prefixosTextos as $prefixo) {
        try {
            $s = $pdo->prepare("SELECT MAX(atualizado_em) FROM textos_site WHERE chave LIKE ?");
            $s->execute([addcslashes($prefixo, '%_') . '%']);
            $v = $s->fetchColumn();
            if ($v && ($t = strtotime((string) $v)) !== false) $datas[] = $t;
        } catch (Throwable $e) {
        }
    }
    return $datas ? max($datas) : null;
}

function paginaAtualizada(array $tabelas = [], array $prefixosTextos = [], ?string $dataCodigo = null): string
{
    $t = paginaAtualizadaData($tabelas, $prefixosTextos, $dataCodigo);
    if (!$t) return '';
    return '<p class="pagina-atualizada"><i class="bi bi-clock-history" aria-hidden="true"></i> Página atualizada em <time datetime="'
        . date('Y-m-d', $t) . '">' . date('d/m/Y', $t) . '</time></p>';
}
