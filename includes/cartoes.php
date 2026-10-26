<?php
/**
 * Cartões da página inicial editáveis no backoffice (12/10/2026) — CORE.
 * Tabela cartoes_site (migração 058); editados em admin/cartoes-site.php.
 */

/** Grupos de cartões: onde aparecem e que campos usam (o admin mostra só esses) */
function cartoesGrupos(): array
{
    return [
        'atalhos'    => ['rotulo' => 'Acesso rápido', 'onde' => 'Cartões por baixo do topo da página inicial, junto à pesquisa', 'campos' => ['texto', 'icone', 'imagem', 'link'], 'icone' => 'bi-grid-3x2-gap',
                         'imagem_rotulo' => 'Ilustração', 'imagem_nota' => 'PNG com fundo transparente, quadrado (256 px ou mais) — aparece à esquerda, a sair do cartão. Sem ilustração, mostra o ícone.', 'imagem_tirar' => 'Tirar a ilustração (fica o ícone)'],
        'servicos'   => ['rotulo' => 'Serviços e informação útil', 'onde' => 'Secção «Serviços e informação útil» da página inicial', 'campos' => ['texto', 'icone', 'link'], 'icone' => 'bi-grid'],
        'apoios'     => ['rotulo' => 'Perto de quem precisa', 'onde' => 'Secção «Apoio à população» (cartões com fotografia)', 'campos' => ['texto', 'icone', 'imagem', 'link', 'etiqueta', 'acao'], 'icone' => 'bi-heart-pulse'],
        'descobrir'  => ['rotulo' => 'Descobrir', 'onde' => 'Secção «Descobrir» (cartões com fotografia)', 'campos' => ['texto', 'icone', 'imagem', 'link', 'etiqueta', 'acao'], 'icone' => 'bi-compass'],
        'boasvindas' => ['rotulo' => 'Destaques do Bem-vindo', 'onde' => 'Ligações por baixo do texto de boas-vindas', 'campos' => ['icone', 'link'], 'icone' => 'bi-list-check'],
    ];
}

/** Cartões ativos de um grupo, pela ordem definida */
function cartoesSite(string $grupo): array
{
    global $pdo;
    try {
        $s = $pdo->prepare("SELECT * FROM cartoes_site WHERE grupo = ? AND ativo = 1 ORDER BY ordem, id");
        $s->execute([$grupo]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/** Ícones sugeridos no backoffice (Bootstrap Icons) */
function cartoesIconesSugeridos(): array
{
    return ['bi-exclamation-triangle-fill', 'bi-file-earmark-text-fill', 'bi-people-fill', 'bi-geo-alt-fill', 'bi-telephone-fill',
        'bi-map-fill', 'bi-shop', 'bi-calendar-event-fill', 'bi-newspaper', 'bi-envelope-fill', 'bi-house-heart', 'bi-heart-pulse',
        'bi-people', 'bi-mortarboard', 'bi-balloon-heart', 'bi-bank', 'bi-egg-fried', 'bi-tree', 'bi-clock-history', 'bi-laptop',
        'bi-camera-fill', 'bi-bicycle', 'bi-bus-front', 'bi-capsule', 'bi-shield-check', 'bi-trophy', 'bi-music-note-beamed',
        'bi-book', 'bi-briefcase-fill', 'bi-recycle', 'bi-droplet-fill', 'bi-lightning-charge-fill', 'bi-wifi', 'bi-info-circle-fill',
        'bi-question-circle-fill', 'bi-star-fill', 'bi-compass', 'bi-signpost-split-fill', 'bi-cup-hot', 'bi-flower1'];
}

/**
 * Ícones animados dos atalhos da página inicial (15/10/2026) — SVG de traço, na cor da marca.
 * Escolhidos pelo ícone Bootstrap do cartão (o admin continua a escolher o ícone); para os ícones
 * sem versão animada devolve null e o cartão mostra o ícone Bootstrap normal.
 * Os traços têm pathLength="1" para a animação de «desenhar» (CSS .qa-ico).
 */
function atalhoIconeSvg(string $icone): ?string
{
    $tipo = [
        'bi-exclamation-triangle' => 'alerta', 'bi-exclamation-triangle-fill' => 'alerta',
        'bi-file-earmark-text' => 'documento', 'bi-file-earmark-text-fill' => 'documento',
        'bi-people' => 'pessoas', 'bi-people-fill' => 'pessoas',
        'bi-geo-alt' => 'local', 'bi-geo-alt-fill' => 'local',
        'bi-telephone' => 'telefone', 'bi-telephone-fill' => 'telefone',
    ][trim($icone)] ?? null;
    if ($tipo === null) return null;

    $corpo = [
        'alerta' => '<path class="qa-t" pathLength="1" d="M10.3 4.2 2.9 17.4A2 2 0 0 0 4.6 20.4h14.8a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z"/>'
                  . '<g class="qa-marca"><path pathLength="1" d="M12 9.2v4.6"/><circle class="qa-ponto" cx="12" cy="17" r=".6"/></g>',
        'documento' => '<g class="qa-folha"><path class="qa-t" pathLength="1" d="M14 2.8H7.2a2 2 0 0 0-2 2v14.4a2 2 0 0 0 2 2h9.6a2 2 0 0 0 2-2V7.6Z"/>'
                     . '<path pathLength="1" d="M14 2.8v4.8h4.8"/>'
                     . '<path class="qa-l qa-l1" pathLength="1" d="M8.8 12.2h6.4"/><path class="qa-l qa-l2" pathLength="1" d="M8.8 15.2h6.4"/><path class="qa-l qa-l3" pathLength="1" d="M8.8 18.2h3.6"/></g>',
        'pessoas' => '<g class="qa-p1"><circle pathLength="1" cx="9" cy="7.6" r="3.4"/><path pathLength="1" d="M2.6 20.2a6.4 6.4 0 0 1 12.8 0"/></g>'
                   . '<g class="qa-p2"><circle pathLength="1" cx="17.2" cy="8.8" r="2.6"/><path pathLength="1" d="M16.4 14.1a5 5 0 0 1 5.4 5.1"/></g>',
        'local' => '<ellipse class="qa-sombra" cx="12" cy="21.4" rx="3.6" ry=".9"/>'
                 . '<g class="qa-pino"><path class="qa-t" pathLength="1" d="M18.6 9.8c0 5-6.6 10.2-6.6 10.2S5.4 14.8 5.4 9.8a6.6 6.6 0 0 1 13.2 0Z"/><circle pathLength="1" cx="12" cy="9.8" r="2.4"/></g>',
        'telefone' => '<path class="qa-t qa-auscultador" pathLength="1" d="M21 16.4v2.8a1.9 1.9 0 0 1-2 1.9 18.7 18.7 0 0 1-8.2-2.9 18.4 18.4 0 0 1-5.7-5.7A18.7 18.7 0 0 1 2.2 4.2 1.9 1.9 0 0 1 4.1 2.2h2.8a1.9 1.9 0 0 1 1.9 1.6c.1.9.3 1.8.7 2.6a1.9 1.9 0 0 1-.4 2L7.9 9.6a15.2 15.2 0 0 0 5.7 5.7l1.2-1.2a1.9 1.9 0 0 1 2-.4c.8.3 1.7.6 2.6.7a1.9 1.9 0 0 1 1.6 2Z"/>'
                    . '<path class="qa-onda qa-o1" pathLength="1" d="M14.6 6.2a4 4 0 0 1 3.2 3.2"/><path class="qa-onda qa-o2" pathLength="1" d="M14.6 2.4a7.8 7.8 0 0 1 7 7"/>',
    ][$tipo];

    return '<svg class="qa-svg qa-svg--' . $tipo . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $corpo . '</svg>';
}
