<?php
/**
 * Mapa — CORE: ficheiro idêntico em todos os sites.
 *
 * O limite da freguesia vive em assets/geo/freguesia.geojson (um ficheiro por
 * site). Antes, o polígono estava EMBUTIDO como literal JavaScript dentro de
 * index.php, mapa.php, ocorrencias-mapa.php e admin/pedidos.php — centenas de
 * coordenadas coladas no meio do código, o que obrigava esses 4 ficheiros a ser
 * diferentes em cada freguesia.
 *
 * O centro do mapa também estava fixo no código. E estava ERRADO em vários sites:
 * o jfgranho, o saodomingos, o vilaboim e o torrao tinham todos exatamente o mesmo
 * centro (37.9030, -8.5391), herdado por cópia de um deles. O mapa só não parecia
 * partido porque o fitBounds() ao limite corrigia a vista logo a seguir.
 *
 * Agora o centro é CALCULADO a partir do próprio geojson: é sempre o centro da
 * freguesia certa, sem ninguém ter de o configurar.
 */

/** Caminho (relativo à raiz do site) do limite da freguesia. */
const GEOJSON_FREGUESIA = "/assets/geo/freguesia.geojson";

/**
 * Devolve o conteúdo do geojson pronto a ser injetado como literal JavaScript:
 *
 *     const limiteFreguesia = <?= geojsonFreguesiaJs() ?>;
 *
 * Porquê injetar em vez de ir buscar com fetch(): os mapas usam o limite de forma
 * SÍNCRONA em vários sítios (fitBounds, bringToFront, dentro de funções e de
 * condicionais). Trocar para fetch obrigaria a reescrever essa lógica em cada um
 * dos quatro mapas — muito mais risco de partir os mapas do que o que se ganha.
 * Assim, o JS existente continua igual e o polígono deixa de estar no código.
 *
 * Se o ficheiro faltar, devolve uma FeatureCollection vazia: o mapa desenha-se
 * na mesma, apenas sem o contorno da freguesia.
 */
function geojsonFreguesiaJs(): string
{
    $caminho = __DIR__ . "/.." . GEOJSON_FREGUESIA;

    if (is_file($caminho)) {
        $conteudo = trim((string)file_get_contents($caminho));

        // Só devolve se for JSON válido — nunca injetar lixo no meio do JavaScript.
        if ($conteudo !== '' && json_decode($conteudo) !== null) {
            return $conteudo;
        }
    }

    return '{"type":"FeatureCollection","features":[]}';
}

/**
 * Centro e zoom do mapa, calculados a partir da caixa envolvente do geojson.
 * Devolve ['lat' => float, 'lng' => float, 'zoom' => int].
 *
 * Se o ficheiro não existir ou for inválido, devolve um centro neutro de Portugal
 * continental — o mapa continua a funcionar, apenas sem estar centrado.
 */
function centroFreguesia(): array
{
    static $centro = null;

    if ($centro !== null) {
        return $centro;
    }

    $centro = ['lat' => 39.5, 'lng' => -8.0, 'zoom' => 7];

    $caminho = __DIR__ . "/.." . GEOJSON_FREGUESIA;
    if (!is_file($caminho)) {
        return $centro;
    }

    $json = json_decode((string)file_get_contents($caminho), true);
    if (empty($json['features'][0]['geometry']['coordinates'])) {
        return $centro;
    }

    // Percorre todas as coordenadas (o geojson pode ser Polygon ou MultiPolygon,
    // com profundidades de aninhamento diferentes) e apura a caixa envolvente.
    $minLat = $minLng = INF;
    $maxLat = $maxLng = -INF;

    $percorrer = function ($n) use (&$percorrer, &$minLat, &$maxLat, &$minLng, &$maxLng) {
        if (!is_array($n)) {
            return;
        }

        // Um par [lng, lat]?
        if (count($n) === 2 && is_numeric($n[0]) && is_numeric($n[1])) {
            $lng = (float)$n[0];
            $lat = (float)$n[1];
            $minLng = min($minLng, $lng);
            $maxLng = max($maxLng, $lng);
            $minLat = min($minLat, $lat);
            $maxLat = max($maxLat, $lat);
            return;
        }

        foreach ($n as $filho) {
            $percorrer($filho);
        }
    };

    $percorrer($json['features'][0]['geometry']['coordinates']);

    if ($minLat === INF) {
        return $centro;
    }

    // Zoom a partir do tamanho da freguesia: quanto maior a caixa, mais afastado.
    $span = max($maxLat - $minLat, $maxLng - $minLng);
    if     ($span > 0.60) { $zoom = 10; }
    elseif ($span > 0.30) { $zoom = 11; }
    elseif ($span > 0.15) { $zoom = 12; }
    elseif ($span > 0.07) { $zoom = 13; }
    else                  { $zoom = 14; }

    $centro = [
        'lat'  => round(($minLat + $maxLat) / 2, 6),
        'lng'  => round(($minLng + $maxLng) / 2, 6),
        'zoom' => $zoom,
    ];

    return $centro;
}
