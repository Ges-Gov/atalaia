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
