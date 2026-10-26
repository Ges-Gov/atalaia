<?php
/**
 * Estado do tempo (CORE) — Open-Meteo, sem chave. Usado pela faixa «Hoje na freguesia» (includes/hoje.php)
 * e pelo endpoint api/signage/tempo.php.
 *
 * Redesign 10/2026: as três funções do tempo do includes/signage.php do modelo novo, tal e qual
 * (a sinalética digital completa ainda não veio). Única diferença: sem junta_lat/junta_lng em tema_config,
 * usa o centro do limite da freguesia (centroFreguesia, includes/mapa.php) — não é preciso escrever
 * coordenadas à mão em cada site.
 */
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/tema.php";
require_once __DIR__ . "/mapa.php";

/** Código WMO do Open-Meteo → [texto, ícone Bootstrap]. */
function signageTempoDescricao(int $c): array
{
    return match (true) {
        $c === 0 => ['Céu limpo', 'bi-sun'],
        in_array($c, [1, 2], true) => ['Pouco nublado', 'bi-cloud-sun'],
        $c === 3 => ['Nublado', 'bi-clouds'],
        in_array($c, [45, 48], true) => ['Nevoeiro', 'bi-cloud-fog2'],
        $c >= 51 && $c <= 57 => ['Chuvisco', 'bi-cloud-drizzle'],
        ($c >= 61 && $c <= 67) || ($c >= 80 && $c <= 82) => ['Chuva', 'bi-cloud-rain'],
        ($c >= 71 && $c <= 77) || in_array($c, [85, 86], true) => ['Neve', 'bi-cloud-snow'],
        $c >= 95 => ['Trovoada', 'bi-cloud-lightning-rain'],
        default => ['—', 'bi-cloud'],
    };
}

/**
 * Estado do tempo na sede da Junta (coordenadas em tema_config) — Open-Meteo, sem chave.
 * Cache de 30 min em cache/tempo.json; se o serviço falhar, devolve a última cópia (ou null).
 */
function signageTempo(): ?array
{
    $cache = __DIR__ . '/../cache/tempo.json';
    if (is_file($cache) && filemtime($cache) > time() - 1800) {
        return json_decode((string)file_get_contents($cache), true) ?: null;
    }
    $lat = temaConfig('junta_lat', ''); $lng = temaConfig('junta_lng', '');
    if (!is_numeric($lat) || !is_numeric($lng)) {   // sem coordenadas da sede: centro da freguesia
        $c = centroFreguesia();
        if (abs($c['lat'] - 39.5) > 1e-9 || abs($c['lng'] + 8.0) > 1e-9) { $lat = round($c['lat'], 4); $lng = round($c['lng'], 4); }
    }
    if (!is_numeric($lat) || !is_numeric($lng) || !function_exists('curl_init')) return null;
    $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
        'latitude' => $lat, 'longitude' => $lng, 'timezone' => 'Europe/Lisbon', 'forecast_days' => 3,
        'current' => 'temperature_2m,weather_code,wind_speed_10m',
        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
    ]);
    try {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 4]);
        $r = curl_exec($ch);
        $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $d = $r !== false && $codigo === 200 ? json_decode($r, true) : null;
        if (empty($d['current'])) throw new RuntimeException('resposta inválida (HTTP ' . $codigo . ')');
        [$txt, $ico] = signageTempoDescricao((int)$d['current']['weather_code']);
        $dias = [];
        foreach (($d['daily']['time'] ?? []) as $i => $dia) {
            [$t, $ic] = signageTempoDescricao((int)$d['daily']['weather_code'][$i]);
            $dias[] = ['data' => $dia, 'max' => round($d['daily']['temperature_2m_max'][$i]), 'min' => round($d['daily']['temperature_2m_min'][$i]),
                'chuva' => $d['daily']['precipitation_probability_max'][$i] ?? null, 'texto' => $t, 'icone' => $ic];
        }
        $tempo = ['temperatura' => round($d['current']['temperature_2m']), 'vento' => round($d['current']['wind_speed_10m']),
            'texto' => $txt, 'icone' => $ico, 'dias' => $dias, 'fonte' => 'Open-Meteo', 'atualizado' => date('c')];
        if (!is_dir(dirname($cache))) @mkdir(dirname($cache), 0755, true);
        @file_put_contents($cache, json_encode($tempo, JSON_UNESCAPED_UNICODE));
        return $tempo;
    } catch (Throwable $e) {
        error_log('signage tempo: ' . $e->getMessage());
        return is_file($cache) ? (json_decode((string)file_get_contents($cache), true) ?: null) : null;
    }
}

/** Resposta JSON dos endpoints: sem cache longa (o ecrã pede de 5 em 5 min). */
function signageResponder(array $dados): void
{
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: public, max-age=60');
    header('X-Content-Type-Options: nosniff');
    echo json_encode(['gerado' => date('c')] + $dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
