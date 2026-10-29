<?php
// Fundos personalizáveis dos separadores (heros das páginas de secção).
// Geridos no backoffice em admin/separadores-fundo.php. Chave = nome do ficheiro .php sem extensão.

$GLOBALS['SEPARADORES_FUNDO'] = [
    'freguesia'            => 'A Freguesia',
    'historia'             => 'História',
    'heraldica'            => 'Heráldica',
    'executivo'            => 'Executivo',
    'recursos-humanos'     => 'Recursos Humanos',
    'transparencia'        => 'Transparência',
    'contratacao-publica'  => 'Contratação Pública',
    'freguesia-documentos' => 'Documentos da Freguesia',
    'associacoes'          => 'Associações',
    'comercio'             => 'Economia Local',
    'pontos'               => 'Pontos de Interesse',
    'contactos'            => 'Contactos',
    'noticias'             => 'Notícias',
    'eventos'              => 'Eventos',
    'faq'                  => "FAQ's",
    'galeria'              => 'Galeria de Imagens',
    'assembleia-composicao'    => 'Assembleia — Composição',
    'assembleia-funcionamento' => 'Assembleia — Funcionamento',
    'assembleia-competencias'  => 'Assembleia — Atribuições e Competências',
    'assembleia-documentos'    => 'Assembleia — Documentos',
    'assembleia-sessoes'       => 'Assembleia — Sessões',
    // (12/10/2026) restantes páginas com banner no topo — sem imagem definida ficam como estavam.
    // De fora de propósito: notícia, evento e ponto (o banner mostra a fotografia do próprio item).
    'mapa'                     => 'Mapa interativo',
    'utilidades'               => 'Utilidades',
    'procedimentos-concursais' => 'Procedimentos concursais',
    'pedidos'                  => 'Ocorrências',
    'consultar-pedido'         => 'Consultar pedido',
    'requerimentos'            => 'Requerimentos online',
    'reservas'                 => 'Reservar um espaço',
    'marcacoes'                => 'Marcações e requisições',
    'documentos-automaticos'   => 'Documentos automáticos',
    'atestado-residencia'      => 'Atestado de residência',
    'minha-area'               => 'Área do Cidadão',
    'notificacoes'             => 'Notificações do cidadão',
    'cidadao-login'            => 'Entrar (Área do Cidadão)',
    'cidadao-registo'          => 'Criar conta (Área do Cidadão)',
    'recuperar-password'       => 'Recuperar palavra-passe',
    'canal-denuncias'          => 'Canal de Denúncias',
    'denuncias'                => 'Efetuar denúncia',
    'livro-reclamacoes'        => 'Livro de Reclamações',
    'protecao-dados'           => 'Proteção de dados (EPD)',
    'politica-privacidade'     => 'Política de privacidade',
    'politica-cookies'         => 'Política de cookies',
    'declaracao-acessibilidade'=> 'Declaração de acessibilidade',
];

// Devolve o URL da imagem de fundo configurada para a página atual (ex.: "recursos-humanos.php"),
// ou '' se não existir. Faz uma única query por request (cache estática).
function fundoSeparadorImg($paginaAtual) {
    global $pdo;
    static $mapa = null;

    if ($mapa === null) {
        $mapa = [];
        try {
            $rows = $pdo->query("SELECT chave, imagem FROM separadores_fundo WHERE imagem IS NOT NULL AND imagem <> ''")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $mapa[$r['chave']] = $r['imagem'];
            }
        } catch (Exception $e) {
            $mapa = [];
        }
    }

    $chave = preg_replace('/\.php$/', '', (string)$paginaAtual);
    if (empty($mapa[$chave])) {
        return '';
    }
    return '/uploads/separadores/' . $mapa[$chave];
}

// Posição da fotografia (ponto focal, em %) escolhida no admin — 13/10/2026. [x, y]; omissão = centro.
function fundoSeparadorFoco($paginaAtual) {
    global $pdo;
    static $focos = null;
    if ($focos === null) {
        $focos = [];
        try {
            foreach ($pdo->query("SELECT chave, foco_x, foco_y FROM separadores_fundo")->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $focos[$r['chave']] = [(int)$r['foco_x'], (int)$r['foco_y']];
            }
        } catch (Exception $e) { $focos = []; }   // migração 062 ainda não corrida
    }
    $chave = preg_replace('/.php$/', '', (string)$paginaAtual);
    return $focos[$chave] ?? [50, 50];
}
