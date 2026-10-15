<?php
$adminPageTitle = "Composição da Assembleia";
$adminActive = "assembleia_composicao";

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/includes/membros_admin.php";
requireAssembleiaManager();

$cfgMembros = [
    'tabela'    => 'assembleia_composicao',
    'pagina'    => 'assembleia-composicao.php',
    'titulo'    => 'Composição da Assembleia',
    'descricao' => 'Membros da Assembleia de Freguesia.',
    'publica'   => '../assembleia-composicao.php',
    'cargos'    => ['Presidente da Assembleia', '1.º Secretário', '1.ª Secretária', '2.º Secretário', '2.ª Secretária', 'Vogal'],
    'extras'    => [
        ['descricao', 'Nota / descrição', 'textarea'],
    ],
    // A página pública separa a Mesa dos Vogais pela coluna `grupo` (tem de ser exatamente estes textos).
    'grupos'    => ['Mesa da Assembleia', 'Vogais'],
    'grupo_padrao' => 'Vogais',
    'grupo_sugerido' => 'presidente|secret',
    'ordenar'   => "ORDER BY CASE WHEN grupo='Mesa da Assembleia' THEN 1 WHEN grupo='Vogais' THEN 2 ELSE 3 END, ordem ASC, nome ASC",
    'secoes'    => fn($m) => $m['grupo'],
];

$erro = membrosAdminProcessar($pdo, $cfgMembros);

require_once __DIR__ . "/includes/header.php";

membrosAdminRender($pdo, $cfgMembros, $erro);

require_once __DIR__ . "/includes/footer.php";
