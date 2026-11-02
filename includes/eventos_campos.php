<?php
/**
 * Campos extra das inscrições em eventos — CORE: ficheiro idêntico em todos os sites.
 *
 * Até 02/11/2026 só havia dois tipos (texto e checkbox). Passam a existir os mesmos
 * tipos dos eventos do site da GesGov na intranet — texto curto, texto longo, número,
 * data, escolha numa lista de opções, foto/ficheiro — mais o «Sim/Não» que já havia.
 *
 * As respostas continuam em eventos_inscricoes.campos_extra (JSON: id do campo => valor).
 * Num campo de ficheiro o valor é {"ficheiro": nome gravado, "nome": nome original}; o
 * ficheiro fica em uploads/eventos-inscricoes/ e só se abre pelo backoffice
 * (admin/evento_ficheiro.php), porque pode ter dados pessoais.
 */

require_once __DIR__ . "/galeria.php"; // erroUploadTexto()

const EVENTO_CAMPO_TIPOS = [
    'texto'       => 'Texto curto',
    'texto_longo' => 'Texto longo',
    'numero'      => 'Número',
    'data'        => 'Data',
    'escolha'     => 'Escolha numa lista',
    'checkbox'    => 'Caixa sim/não',
    'ficheiro'    => 'Foto/ficheiro',
];

const EVENTO_FICHEIROS_DIR = __DIR__ . "/../uploads/eventos-inscricoes/";
const EVENTO_FICHEIRO_MAX_MB = 10;
const EVENTO_FICHEIRO_TIPOS = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
    'heic' => 'image/heic', 'pdf' => 'application/pdf',
];

function eventoCampoTipo($tipo): string
{
    return isset(EVENTO_CAMPO_TIPOS[$tipo]) ? $tipo : 'texto';
}

/** Opções de um campo «escolha» (guardadas em JSON na coluna opcoes). */
function eventoCampoOpcoes(array $campo): array
{
    $opcoes = json_decode((string)($campo['opcoes'] ?? ''), true);
    return is_array($opcoes) ? array_values(array_filter($opcoes, 'is_string')) : [];
}

/**
 * Valida a definição de um campo vinda do backoffice (opções: uma por linha).
 * Devolve [label, tipo, opcoes em JSON ou null, erro].
 */
function eventoCampoDefinicao($label, $tipo, $opcoesTexto): array
{
    $label = mb_substr(trim((string)$label), 0, 190);
    $tipo = eventoCampoTipo($tipo);
    $opcoes = null;
    if ($tipo === 'escolha') {
        $lista = array_values(array_unique(array_filter(array_map(
            fn($o) => mb_substr(trim($o), 0, 190),
            preg_split('/\R/u', (string)$opcoesTexto)
        ), 'strlen')));
        if (count($lista) < 2) {
            return [$label, $tipo, null, "O campo «" . ($label ?: 'sem nome') . "» é uma escolha: escreva pelo menos duas opções, uma por linha."];
        }
        $opcoes = json_encode($lista, JSON_UNESCAPED_UNICODE);
    }
    return [$label, $tipo, $opcoes, ''];
}

/** HTML do campo no formulário público de inscrição. */
function eventoCampoHtml(array $campo): string
{
    $nome = 'campo_' . (int)$campo['id'];
    $tipo = eventoCampoTipo($campo['tipo']);
    $rotulo = htmlspecialchars($campo['label']) . ($campo['obrigatorio'] ? ' *' : '');
    $req = $campo['obrigatorio'] ? ' required' : '';
    $atual = is_string($_POST[$nome] ?? null) ? $_POST[$nome] : '';
    $v = htmlspecialchars($atual);
    $label = '<label for="' . $nome . '">' . $rotulo . '</label>';

    switch ($tipo) {
        case 'checkbox':
            return '<div class="evento-campo evento-campo-checkbox"><label><input type="checkbox" name="' . $nome . '"'
                . ($atual !== '' ? ' checked' : '') . $req . '> ' . $rotulo . '</label></div>';
        case 'texto_longo':
            return '<div class="evento-campo evento-campo-largo">' . $label
                . '<textarea id="' . $nome . '" name="' . $nome . '" maxlength="2000"' . $req . '>' . $v . '</textarea></div>';
        case 'numero':
            return '<div class="evento-campo">' . $label
                . '<input type="number" step="any" inputmode="decimal" id="' . $nome . '" name="' . $nome . '" value="' . $v . '"' . $req . '></div>';
        case 'data':
            return '<div class="evento-campo">' . $label
                . '<input type="date" id="' . $nome . '" name="' . $nome . '" value="' . $v . '"' . $req . '></div>';
        case 'escolha':
            $html = '<div class="evento-campo">' . $label . '<select id="' . $nome . '" name="' . $nome . '"' . $req . '>'
                . '<option value="">— Escolha uma opção —</option>';
            foreach (eventoCampoOpcoes($campo) as $o) {
                $html .= '<option value="' . htmlspecialchars($o) . '"' . ($o === $atual ? ' selected' : '') . '>' . htmlspecialchars($o) . '</option>';
            }
            return $html . '</select></div>';
        case 'ficheiro':
            return '<div class="evento-campo">' . $label
                . '<input type="file" id="' . $nome . '" name="' . $nome . '" accept="image/*,.pdf"' . $req . '>'
                . '<small class="evento-campo-ajuda">Fotografia ou PDF, até ' . EVENTO_FICHEIRO_MAX_MB . ' MB.</small></div>';
        default:
            return '<div class="evento-campo">' . $label
                . '<input type="text" id="' . $nome . '" name="' . $nome . '" maxlength="255" value="' . $v . '"' . $req . '></div>';
    }
}

/** O formulário precisa de enctype multipart se algum campo for de ficheiro. */
function eventoCamposTemFicheiro(array $campos): bool
{
    foreach ($campos as $c) if (eventoCampoTipo($c['tipo']) === 'ficheiro') return true;
    return false;
}

/**
 * Lê e valida as respostas aos campos extra. Os ficheiros só são gravados depois de
 * todas as outras respostas estarem certas; se algum falhar, apagam-se os já gravados.
 * Devolve [respostas (id => valor), erro].
 */
function eventoCamposRespostas(array $campos): array
{
    $respostas = [];
    foreach ($campos as $campo) {
        $tipo = eventoCampoTipo($campo['tipo']);
        if ($tipo === 'ficheiro') continue;
        $nome = 'campo_' . (int)$campo['id'];
        $rotulo = $campo['label'];

        if ($tipo === 'checkbox') {
            if ($campo['obrigatorio'] && empty($_POST[$nome])) return [[], "Marque a opção \"{$rotulo}\"."];
            $respostas[$campo['id']] = !empty($_POST[$nome]) ? 'Sim' : 'Não';
            continue;
        }

        $valor = is_string($_POST[$nome] ?? null) ? trim($_POST[$nome]) : '';
        if ($valor === '') {
            if ($campo['obrigatorio']) return [[], "Preencha o campo \"{$rotulo}\"."];
            $respostas[$campo['id']] = '';
            continue;
        }
        if ($tipo === 'numero') {
            $valor = str_replace(',', '.', $valor);
            if (!is_numeric($valor)) return [[], "Indique um número no campo \"{$rotulo}\"."];
        } elseif ($tipo === 'data') {
            $d = DateTime::createFromFormat('!Y-m-d', $valor);
            if (!$d || $d->format('Y-m-d') !== $valor) return [[], "Indique uma data válida no campo \"{$rotulo}\"."];
        } elseif ($tipo === 'escolha') {
            if (!in_array($valor, eventoCampoOpcoes($campo), true)) return [[], "Escolha uma das opções do campo \"{$rotulo}\"."];
        } else {
            $valor = mb_substr($valor, 0, $tipo === 'texto_longo' ? 2000 : 255);
        }
        $respostas[$campo['id']] = $valor;
    }

    $gravados = [];
    foreach ($campos as $campo) {
        if (eventoCampoTipo($campo['tipo']) !== 'ficheiro') continue;
        $f = $_FILES['campo_' . (int)$campo['id']] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($f['name'] ?? '') === '') {
            if ($campo['obrigatorio']) {
                eventoApagarFicheiros($gravados);
                return [[], "Junte o ficheiro pedido em \"{$campo['label']}\"."];
            }
            $respostas[$campo['id']] = '';
            continue;
        }
        [$ficheiro, $erro] = eventoGuardarFicheiro($f, $campo['label']);
        if ($erro) {
            eventoApagarFicheiros($gravados);
            return [[], $erro];
        }
        $respostas[$campo['id']] = $gravados[] = $ficheiro;
    }

    // Mantém a ordem dos campos (o JSON e a lista de inscritos leem-na assim).
    $ordenadas = [];
    foreach ($campos as $c) if (array_key_exists($c['id'], $respostas)) $ordenadas[$c['id']] = $respostas[$c['id']];
    return [$ordenadas, ''];
}

/** Grava um ficheiro enviado numa inscrição. Devolve [['ficheiro' => ..., 'nome' => ...] ou null, erro]. */
function eventoGuardarFicheiro(array $f, string $rotulo): array
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [null, "Não foi possível enviar o ficheiro de \"{$rotulo}\": " . erroUploadTexto((int)$f['error'])];
    }
    if (($f['size'] ?? 0) > EVENTO_FICHEIRO_MAX_MB * 1024 * 1024) {
        return [null, "O ficheiro de \"{$rotulo}\" tem mais de " . EVENTO_FICHEIRO_MAX_MB . " MB."];
    }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    $mime = function_exists('finfo_open') ? (string)finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']) : '';
    $mimesAceites = array_merge(array_values(EVENTO_FICHEIRO_TIPOS), ['image/heif']);
    if (!isset(EVENTO_FICHEIRO_TIPOS[$ext]) || ($mime !== '' && !in_array($mime, $mimesAceites, true))) {
        return [null, "O ficheiro de \"{$rotulo}\" tem de ser uma fotografia (JPG, PNG, WEBP, HEIC) ou um PDF."];
    }

    if (!is_dir(EVENTO_FICHEIROS_DIR) && !@mkdir(EVENTO_FICHEIROS_DIR, 0775, true) && !is_dir(EVENTO_FICHEIROS_DIR)) {
        return [null, "Não foi possível guardar o ficheiro (pasta de envios). Contacte a Junta."];
    }
    // Para o Apache não servir esta pasta diretamente; os nomes são também impossíveis de adivinhar.
    if (!is_file(EVENTO_FICHEIROS_DIR . '.htaccess')) @file_put_contents(EVENTO_FICHEIROS_DIR . '.htaccess', "Require all denied\n");
    if (!is_file(EVENTO_FICHEIROS_DIR . 'index.html')) @file_put_contents(EVENTO_FICHEIROS_DIR . 'index.html', '');

    $novo = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    if (!move_uploaded_file($f['tmp_name'], EVENTO_FICHEIROS_DIR . $novo)) {
        return [null, "Não foi possível guardar o ficheiro de \"{$rotulo}\". Tente de novo ou contacte a Junta."];
    }
    $original = mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', basename((string)$f['name'])), 0, 190);
    return [['ficheiro' => $novo, 'nome' => $original], ''];
}

/** Nome gravado de um ficheiro de inscrição, só se tiver o formato que geramos. */
function eventoFicheiroNome($valor): string
{
    $nome = is_array($valor) ? (string)($valor['ficheiro'] ?? '') : '';
    return preg_match('/^[a-f0-9]{32}\.[a-z]{3,4}$/', $nome) ? $nome : '';
}

/** Apaga os ficheiros de uma lista de respostas (ex.: ao cancelar uma inscrição). */
function eventoApagarFicheiros(array $valores): void
{
    foreach ($valores as $v) {
        $nome = eventoFicheiroNome($v);
        if ($nome !== '' && is_file(EVENTO_FICHEIROS_DIR . $nome)) @unlink(EVENTO_FICHEIROS_DIR . $nome);
    }
}

/** Resposta em texto, para a lista de inscritos, o CSV e o email à Junta. */
function eventoRespostaTexto($valor): string
{
    if (is_array($valor)) return (string)($valor['nome'] ?? 'ficheiro');
    $valor = (string)$valor;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m)) return "{$m[3]}/{$m[2]}/{$m[1]}";
    return $valor;
}
