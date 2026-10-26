<?php
/**
 * SEO (CORE): dados estruturados schema.org em JSON-LD e endereço base do site.
 * Só se publicam dados que existem na BD — campos vazios ficam de fora (o Google penaliza
 * dados inventados, e não há forma de os confirmar: horários, preços, etc.).
 *
 * - seoOrganizacao()      → GovernmentOrganization da Junta (todas as páginas, no header.php)
 * - seoNoticia($n)        → NewsArticle
 * - seoEvento($e)         → Event
 * - seoPonto($p)          → TouristAttraction
 * - seoComercio($lista)   → ItemList de LocalBusiness / Restaurant / LodgingBusiness…
 * - seoJsonLd($dados)     → imprime o <script type="application/ld+json">
 */

/** Endereço base do site: o domínio configurado no admin ou, sem ele, o do pedido atual. */
function seoBase(): string
{
    $dom = trim((string)siteConfig('dominio', ''));
    if ($dom !== '' && preg_match('#^https?://#i', $dom)) return rtrim($dom, '/');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/** URL absoluto de um caminho do site ("/assets/img/x.jpg" ou "x.jpg" em assets/img). */
function seoUrl(string $caminho, bool $imagem = false): string
{
    if ($caminho === '') return '';
    if (preg_match('#^https?://#i', $caminho)) return $caminho;
    if ($imagem && $caminho[0] !== '/') $caminho = '/assets/img/' . $caminho;
    return seoBase() . '/' . ltrim($caminho, '/');
}

/** Tira chaves vazias (null, '', []) — recursivo. */
function seoLimpar(array $d): array
{
    foreach ($d as $k => $v) {
        if (is_array($v)) $v = $d[$k] = seoLimpar($v);
        if ($v === null || $v === '' || $v === []) unset($d[$k]);
    }
    return $d;
}

function seoJsonLd(array $dados): void
{
    $dados = seoLimpar(['@context' => 'https://schema.org'] + $dados);
    // JSON_HEX_TAG: impede que um "</script>" num título feche o bloco
    echo '<script type="application/ld+json">' . json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n";
}

/** Morada "Rua X, 7470-231 Sousel" → PostalAddress (o código postal separa rua e localidade). */
function seoMorada(string $morada, string $localidadePadrao = ''): array
{
    $morada = trim(preg_replace('/\s+/', ' ', $morada));
    $a = ['@type' => 'PostalAddress', 'addressCountry' => 'PT'];
    if (preg_match('/^(.*?)[,\s]*(\d{4}-\d{3})\s*(.*)$/u', $morada, $m)) {
        $a['streetAddress'] = trim($m[1], " ,");
        $a['postalCode'] = $m[2];
        $a['addressLocality'] = trim($m[3]) ?: $localidadePadrao;
    } else {
        $a['streetAddress'] = $morada;
        $a['addressLocality'] = $localidadePadrao;
    }
    return $a;
}

function seoGeo($lat, $lng): array
{
    if (!is_numeric($lat) || !is_numeric($lng) || ((float)$lat == 0 && (float)$lng == 0)) return [];
    return ['@type' => 'GeoCoordinates', 'latitude' => (float)$lat, 'longitude' => (float)$lng];
}

/** A Junta como entidade (usada também como autor/organizador/editor). */
function seoJunta(bool $completa = false): array
{
    $j = [
        '@type' => 'GovernmentOrganization',
        '@id' => seoBase() . '/#junta',
        'name' => siteConfig('nome_site', 'Junta de Freguesia'),
        'url' => seoBase() . '/',
        'logo' => siteConfig('logo') ? seoUrl(siteConfig('logo'), true) : '',
    ];
    if (!$completa) return $j;
    $redes = [];
    foreach (['facebook', 'instagram', 'youtube', 'x_twitter', 'tiktok', 'linkedin'] as $r) {
        $u = trim((string)siteConfig($r));
        // só perfis a sério: a página inicial de uma rede (ex.: https://www.instagram.com/) não é a Junta
        if ($u !== '' && preg_match('#^https?://#i', $u) && trim((string)parse_url($u, PHP_URL_PATH), '/') !== '') $redes[] = $u;
    }
    return $j + [
        'telephone' => siteConfig('telefone'),
        'email' => siteConfig('email'),
        'address' => siteConfig('morada') ? seoMorada(siteConfig('morada'), siteConfig('municipio')) : [],
        'areaServed' => ['@type' => 'AdministrativeArea', 'name' => 'Freguesia ' . deFreguesia()],
        'sameAs' => $redes,
    ];
}

function seoOrganizacao(): void
{
    seoJsonLd(seoJunta(true));
}

function seoNoticia(array $n): void
{
    $data = !empty($n['data']) ? date('c', strtotime($n['data'])) : '';
    seoJsonLd([
        '@type' => 'NewsArticle',
        'headline' => mb_substr(trim((string)$n['titulo']), 0, 110),
        'description' => mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string)($n['descricao'] ?? '')))), 0, 300),
        'image' => !empty($n['imagem']) ? [seoUrl($n['imagem'], true)] : [],
        'datePublished' => $data,
        'mainEntityOfPage' => seoBase() . '/noticia.php?id=' . (int)$n['id'],
        'author' => seoJunta(),
        'publisher' => seoJunta(),
    ]);
}

function seoEvento(array $e): void
{
    $diaInteiro = function_exists('eventoDiaInteiro') && eventoDiaInteiro($e);
    $fmt = fn($d) => $d ? ($diaInteiro ? date('Y-m-d', strtotime($d)) : date('c', strtotime($d))) : '';
    $local = trim((string)($e['local'] ?? ''));
    seoJsonLd([
        '@type' => 'Event',
        'name' => trim((string)$e['titulo']),
        'description' => mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string)($e['descricao'] ?? '')))), 0, 300),
        'startDate' => $fmt($e['data_evento'] ?? ''),
        'endDate' => $fmt($e['data_fim'] ?? ''),
        'eventStatus' => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'image' => !empty($e['imagem']) ? [seoUrl($e['imagem'], true)] : [],
        'url' => seoBase() . '/evento.php?id=' . (int)$e['id'],
        'location' => [
            '@type' => 'Place',
            'name' => $local ?: nomeFreguesia(),
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => nomeFreguesia(), 'addressCountry' => 'PT'],
            'geo' => seoGeo($e['latitude'] ?? null, $e['longitude'] ?? null),
        ],
        'organizer' => seoJunta(),
    ]);
}

function seoPonto(array $p, array $fotos = []): void
{
    $imgs = [];
    if (!empty($p['imagem'])) $imgs[] = seoUrl($p['imagem'], true);
    foreach ($fotos as $f) $imgs[] = seoUrl($f);
    seoJsonLd([
        '@type' => 'TouristAttraction',
        'name' => trim((string)$p['nome']),
        'description' => mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string)($p['descricao'] ?? '')))), 0, 300),
        'image' => array_values(array_unique($imgs)),
        'url' => seoBase() . '/ponto.php?id=' . (int)$p['id'],
        'geo' => seoGeo($p['latitude'] ?? null, $p['longitude'] ?? null),
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => nomeFreguesia(), 'addressCountry' => 'PT'],
    ]);
}

/** Tipo schema.org a partir do tipo livre do estabelecimento ("Restaurante", "Café", …). */
function seoTipoNegocio(string $tipo): string
{
    $t = mb_strtolower($tipo);
    $mapa = [
        'LodgingBusiness' => ['alojamento', 'hotel', 'hostel', 'turismo', 'pousada', 'residencial', 'monte', 'guest'],
        'Restaurant' => ['restaurante', 'churrasqueira', 'marisqueira', 'tasca', 'pizzaria', 'grelhados'],
        'CafeOrCoffeeShop' => ['café', 'cafe', 'cafetaria', 'snack'],
        'BarOrPub' => ['bar', 'pub'],
        'Bakery' => ['padaria', 'pastelaria', 'confeitaria'],
        'GroceryStore' => ['mercearia', 'supermercado', 'minimercado', 'super'],
        'Pharmacy' => ['farmácia', 'farmacia'],
    ];
    foreach ($mapa as $schema => $palavras) {
        foreach ($palavras as $p) if (preg_match('/\b' . preg_quote($p, '/') . '/u', $t)) return $schema;
    }
    return 'LocalBusiness';
}

function seoComercio(array $lista): void
{
    $itens = [];
    foreach (array_values($lista) as $i => $c) {
        $web = trim((string)($c['website'] ?? ''));
        $itens[] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => [
                '@type' => seoTipoNegocio((string)($c['tipo'] ?? '')),
                'name' => trim((string)$c['nome']),
                'image' => !empty($c['imagem']) ? seoUrl($c['imagem'], true) : '',
                'telephone' => trim((string)($c['telefone'] ?? '')),
                'email' => trim((string)($c['email'] ?? '')),
                'address' => !empty($c['morada']) ? seoMorada($c['morada'], siteConfig('municipio')) : [],
                'geo' => seoGeo($c['latitude'] ?? null, $c['longitude'] ?? null),
                'url' => preg_match('#^https?://#i', $web) ? $web : '',
            ],
        ];
    }
    if ($itens) seoJsonLd(['@type' => 'ItemList', 'name' => 'Comércio local', 'itemListElement' => $itens]);
}
