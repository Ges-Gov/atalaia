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

membrosAdminRender($pdo, $cfgMembros, $erro);

require_once __DIR__ . "/includes/footer.php";
