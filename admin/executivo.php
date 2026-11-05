<?php
$adminPageTitle = "Executivo";
$adminActive = "executivo";

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/includes/membros_admin.php";

$cfgMembros = [
    'tabela'    => 'executivo_membros',
    'pagina'    => 'executivo.php',
    'titulo'    => 'Executivo',
    'descricao' => 'Membros do executivo da Junta de Freguesia.',
    'publica'   => '../executivo.php',
    'cargos'    => ['Presidente', 'Secretário', 'Secretária', 'Tesoureiro', 'Tesoureira', 'Vogal'],
    'extras'    => [
        ['pelouros', 'Pelouros', 'textarea'],
        ['email', 'Email', 'email'],
        ['biografia', 'Nota curricular', 'textarea'],
    ],
    'grupo'     => null,
    'ordenar'   => 'ORDER BY ordem ASC, id ASC',
    'secoes'    => null,
];

$erro = membrosAdminProcessar($pdo, $cfgMembros);

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/textos_bloco.php";
textosBloco("Texto do topo da página «Executivo»", ['executivo.hero.kicker','executivo.hero.titulo','executivo.hero.texto'], '/executivo.php');
// 05/11/2026: os cartões «O que faz a Junta» (competências) passam a editar-se aqui
textosBloco('Cartões «O que faz a Junta» (competências)', ['Secção' => ['executivo.competencias.kicker','executivo.competencias.titulo','executivo.competencias.texto','executivo.competencias.fonte'], 'Cartão 1' => ['executivo.competencias.cartao1_titulo','executivo.competencias.cartao1_pontos'], 'Cartão 2' => ['executivo.competencias.cartao2_titulo','executivo.competencias.cartao2_pontos'], 'Cartão 3' => ['executivo.competencias.cartao3_titulo','executivo.competencias.cartao3_pontos'], 'Cartão 4' => ['executivo.competencias.cartao4_titulo','executivo.competencias.cartao4_pontos'], 'Cartão 5' => ['executivo.competencias.cartao5_titulo','executivo.competencias.cartao5_pontos'], 'Cartão 6' => ['executivo.competencias.cartao6_titulo','executivo.competencias.cartao6_pontos']], '/executivo.php#exeCompetencias');

membrosAdminRender($pdo, $cfgMembros, $erro);

require_once __DIR__ . "/includes/footer.php";
