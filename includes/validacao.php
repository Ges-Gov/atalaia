<?php
/**
 * Validação de contactos escritos pelos visitantes (30/10/2026). CORE: igual em todos os sites.
 *
 * emailValido()     — formato e domínio que existe mesmo (tem MX ou endereço no DNS).
 * telefoneValido()  — telemóvel/fixo português (com ou sem +351) ou número estrangeiro com indicativo;
 *                     rejeita números claramente inventados (999999999, 912345678, 960000000…).
 * telefoneLimpo()   — o número só com algarismos e o «+» inicial, para guardar.
 *
 * Nenhuma validação prova que o número é do próprio — isso só com um código por SMS.
 */

function emailValido($email): bool
{
    static $dominios = [];
    $email = trim((string)$email);
    if ($email === '' || mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }
    $dominio = strtolower(substr(strrchr($email, '@'), 1));
    if (function_exists('idn_to_ascii')) {
        $dominio = idn_to_ascii($dominio, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $dominio;
    }
    if (!isset($dominios[$dominio])) {
        // Sem funções de DNS (não devia acontecer) fica-se pela verificação do formato.
        $dominios[$dominio] = !function_exists('checkdnsrr')
            || checkdnsrr($dominio . '.', 'MX') || checkdnsrr($dominio . '.', 'A') || checkdnsrr($dominio . '.', 'AAAA');
    }
    return $dominios[$dominio];
}

function telefoneLimpo($telefone): string
{
    $t = preg_replace('/[\s.\-\/()]/', '', trim((string)$telefone));
    return str_starts_with($t, '00') ? '+' . substr($t, 2) : $t;
}

/** $soTelemovel: em números portugueses só aceita telemóveis (91, 92, 93, 96). */
function telefoneValido($telefone, bool $soTelemovel = false): bool
{
    $t = telefoneLimpo($telefone);
    if (!preg_match('/^\+?\d+$/', $t)) {
        return false;
    }
    if (str_starts_with($t, '+351')) {
        $t = substr($t, 4);
    } elseif (str_starts_with($t, '+')) {
        // Estrangeiro: indicativo + número, 8 a 15 algarismos (E.164), sem algarismos todos iguais.
        $n = substr($t, 1);
        return strlen($n) >= 8 && strlen($n) <= 15 && !preg_match('/^(\d)\1+$/', substr($n, -7));
    }
    $padrao = $soTelemovel ? '/^9[1236]\d{7}$/' : '/^(9[1236]\d{7}|2\d{8}|30\d{7})$/';
    if (!preg_match($padrao, $t)) {
        return false;
    }
    // Inventados: os 7 últimos algarismos todos iguais ou seguidos (912 345 678, 961 234 567, 987 654 321…).
    $fim = substr($t, 2);
    if (preg_match('/^(\d)\1{6}$/', $fim)
        || str_contains('01234567890123456789', $fim) || str_contains('98765432109876543210', $fim)) {
        return false;
    }
    return true;
}
