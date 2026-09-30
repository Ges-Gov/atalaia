<?php
/**
 * Newsletter mensal — CORE, idêntico em todos os sites. O texto compõe-se
 * sozinho a partir das notícias e eventos publicados no mês corrente; a
 * única personalização manual é o destaque do topo, ou substituir tudo
 * por texto escrito à mão (newsletter_config.conteudo_manual).
 *
 * HTML com estilos em linha de propósito (sem <style>/classes) — clientes
 * de email (Outlook, Gmail) não interpretam CSS externo de forma fiável.
 */

function newsletterConfig($pdo) {
    $config = $pdo->query("SELECT * FROM newsletter_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    return $config ?: [
        'auto_ativo' => 0, 'dia_envio' => 1, 'ultimo_envio' => null,
        'conteudo_manual' => null, 'destaque_tipo' => null, 'destaque_id' => null,
    ];
}

/**
 * Notícias e eventos do mês corrente, já ordenados por data. Cada item
 * fica com um campo 'tipo' ('noticia'|'evento') acrescentado, para a
 * escolha do destaque saber a que tabela voltar.
 */
function newsletterItensDoMes($pdo) {
    $inicioMes = date('Y-m-01 00:00:00');
    $fimMes = date('Y-m-t 23:59:59');

    $stmtNoticias = $pdo->prepare("
        SELECT * FROM noticias
        WHERE data BETWEEN ? AND ?
        ORDER BY data ASC
    ");
    $stmtNoticias->execute([$inicioMes, $fimMes]);
    $noticias = $stmtNoticias->fetchAll(PDO::FETCH_ASSOC);
    foreach ($noticias as &$n) { $n['tipo'] = 'noticia'; }
    unset($n);

    // Evento entra se o seu intervalo [data_evento, data_fim ?? data_evento]
    // cruzar o mês corrente (evento a decorrer ou a começar no mês).
    $stmtEventos = $pdo->prepare("
        SELECT * FROM eventos
        WHERE data_evento <= ?
        AND COALESCE(data_fim, data_evento) >= ?
        ORDER BY data_evento ASC
    ");
    $stmtEventos->execute([$fimMes, $inicioMes]);
    $eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
    foreach ($eventos as &$e) { $e['tipo'] = 'evento'; }
    unset($e);

    return ['noticias' => $noticias, 'eventos' => $eventos];
}

/**
 * Vai buscar o item de destaque escolhido em newsletter_config. Se não
 * houver escolha, ou o item escolhido já não estiver no período do mês,
 * cai para a 1ª notícia do mês (ou null, se não houver nenhuma).
 */
function newsletterDestaque($pdo, $itens, $config) {
    if (!empty($config['destaque_tipo']) && !empty($config['destaque_id'])) {
        $lista = $config['destaque_tipo'] === 'noticia' ? $itens['noticias'] : $itens['eventos'];
        foreach ($lista as $item) {
            if ((int) $item['id'] === (int) $config['destaque_id']) {
                return $item;
            }
        }
    }
    return $itens['noticias'][0] ?? null;
}

/**
 * URL absoluta do site — necessária para imagens no email (ao contrário
 * do browser, um cliente de email não tem "página atual" para resolver
 * caminhos relativos). Na pré-visualização do backoffice (há pedido web)
 * usa o próprio pedido; no envio real por cron (sem pedido web nenhum)
 * usa o domínio configurado em configuracoes_site.dominio.
 */
function newsletterBaseUrl() {
    if (!empty($_SERVER['HTTP_HOST'])) {
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocolo . '://' . $_SERVER['HTTP_HOST'];
    }
    return rtrim(siteConfig('dominio', ''), '/');
}

/*
 * Devolve o URL de uma versão pré-gerada e já quadrada do logo do site
 * (fundo branco, imagem centrada), em cache em assets/img/newsletter/.
 *
 * Existe porque o Outlook clássico (motor Word) ignora `object-fit` do CSS
 * e esmaga a imagem para caber exatamente na largura/altura pedidas — como
 * o logo normalmente é um brasão (não quadrado), ficava esticado só nesse
 * cliente. Gerando antecipadamente um PNG já quadrado não há nenhuma
 * distorção para o CSS corrigir, por isso funciona em qualquer cliente.
 */
function newsletterLogoEmailUrl() {
    $logoFicheiro = siteConfig('logo', '');
    if (!$logoFicheiro || !function_exists('imagecreatetruecolor')) {
        return null;
    }

    $origem = __DIR__ . '/../assets/img/' . $logoFicheiro;
    if (!is_file($origem)) {
        return null;
    }

    $pastaCache = __DIR__ . '/../assets/img/newsletter';
    if (!is_dir($pastaCache) && !mkdir($pastaCache, 0755, true) && !is_dir($pastaCache)) {
        return null;
    }

    // Nome com hash do ficheiro + data de modificação: se o logo for
    // trocado no backoffice, gera-se uma versão nova em vez de continuar a
    // servir a antiga em cache.
    $nomeCache = 'logo-email-' . md5($logoFicheiro . filemtime($origem)) . '.png';
    $destino = $pastaCache . '/' . $nomeCache;

    if (!is_file($destino) && !newsletterGerarLogoQuadrado($origem, $destino)) {
        return null;
    }

    $base = newsletterBaseUrl();
    return $base !== '' ? $base . '/assets/img/newsletter/' . $nomeCache : null;
}

function newsletterGerarLogoQuadrado($origem, $destino) {
    $extensao = strtolower(pathinfo($origem, PATHINFO_EXTENSION));
    $origemImg = match ($extensao) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($origem),
        'png' => @imagecreatefrompng($origem),
        'gif' => @imagecreatefromgif($origem),
        'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($origem) : false,
        default => false,
    };
    if (!$origemImg) {
        return false;
    }

    $largOrig = imagesx($origemImg);
    $altOrig = imagesy($origemImg);

    $tamanhoTela = 128; // maior que os 32px exibidos, para ecrãs retina.
    $margem = 12;
    $areaUtil = $tamanhoTela - ($margem * 2);
    $escala = min($areaUtil / $largOrig, $areaUtil / $altOrig);
    $largNova = (int) round($largOrig * $escala);
    $altNova = (int) round($altOrig * $escala);

    $tela = imagecreatetruecolor($tamanhoTela, $tamanhoTela);
    imagefill($tela, 0, 0, imagecolorallocate($tela, 255, 255, 255));
    imagealphablending($tela, true);

    imagecopyresampled(
        $tela, $origemImg,
        (int) (($tamanhoTela - $largNova) / 2), (int) (($tamanhoTela - $altNova) / 2),
        0, 0,
        $largNova, $altNova,
        $largOrig, $altOrig
    );

    $ok = imagepng($tela, $destino);
    imagedestroy($origemImg);
    imagedestroy($tela);
    return $ok;
}

function newsletterImagemUrl($item) {
    if (empty($item['imagem'])) {
        return null;
    }
    $base = newsletterBaseUrl();
    if ($base === '') {
        return null;
    }
    return $base . '/assets/img/' . rawurlencode($item['imagem']);
}

/**
 * Composição automática do HTML do mês corrente (usada tanto na
 * pré-visualização como no envio real, quando não há conteudo_manual).
 * Segue de perto o layout já usado na newsletter do gesgov.pt: frase de
 * introdução, destaque com imagem grande, secções agrupadas por categoria
 * com miniatura pequena ao lado do título (não uma imagem grande por
 * item — isso é só para o destaque).
 */
function newsletterComporMesAtual($pdo) {
    $config = newsletterConfig($pdo);
    $itens = newsletterItensDoMes($pdo);
    $destaque = newsletterDestaque($pdo, $itens, $config);

    $totalItens = count($itens['noticias']) + count($itens['eventos']);
    if ($totalItens === 0) {
        return '<p style="margin:0;font-size:14px;color:#3f4b5c;">Sem novidades este mês.</p>';
    }

    $html = '';

    if ($destaque) {
        $html .= newsletterBlocoDestaque($destaque);
    }

    $destaqueId = $destaque['id'] ?? null;
    $destaqueTipo = $destaque['tipo'] ?? null;

    $noticiasRestantes = array_values(array_filter($itens['noticias'], fn($n) => !($destaqueTipo === 'noticia' && (int) $n['id'] === (int) $destaqueId)));
    $eventosRestantes = array_values(array_filter($itens['eventos'], fn($e) => !($destaqueTipo === 'evento' && (int) $e['id'] === (int) $destaqueId)));

    if (!empty($noticiasRestantes)) {
        $html .= newsletterBlocoSeccao('Notícias deste mês', newsletterAgruparPorCategoria($noticiasRestantes, false));
    }

    if (!empty($eventosRestantes)) {
        $html .= newsletterBlocoSeccao('Eventos deste mês', newsletterAgruparPorCategoria($eventosRestantes, true));
    }

    $nomeSite = htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia'), ENT_QUOTES, 'UTF-8');
    $intro = '<p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#3f4b5c;">Aqui fica um resumo das principais novidades de ' . $nomeSite . ' este mês.</p>';

    return $intro . $html;
}

function newsletterBlocoDestaque($item) {
    $corPrincipal = htmlspecialchars(siteConfig('cor_principal', '#242A30'), ENT_QUOTES, 'UTF-8');
    $imgUrl = newsletterImagemUrl($item);

    $html = '<div style="background:#f8fafc;border-left:4px solid ' . $corPrincipal . ';border-radius:8px;padding:20px 24px;margin:0 0 28px;">';
    $html .= '<p style="margin:0 0 ' . ($imgUrl ? '14px' : '10px') . ';font-size:11px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;color:' . $corPrincipal . ';">Em destaque</p>';
    if ($imgUrl) {
        $html .= '<img src="' . $imgUrl . '" alt="" style="max-width:100%;height:auto;border-radius:8px;display:block;margin-bottom:14px;">';
    }
    $html .= '<p style="margin:0 0 8px;font-size:17px;font-weight:bold;line-height:1.4;color:#0f1720;">' . htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') . '</p>';
    $html .= '<p style="font-size:14px;line-height:1.5;margin:0;color:#374151;">' . nl2br(htmlspecialchars(mb_strimwidth($item['descricao'] ?? '', 0, 280, '…'), ENT_QUOTES, 'UTF-8')) . '</p>';
    $html .= '</div>';

    return $html;
}

function newsletterBlocoSeccao($titulo, $conteudo) {
    $corPrincipal = htmlspecialchars(siteConfig('cor_principal', '#242A30'), ENT_QUOTES, 'UTF-8');
    return '<h3 style="margin:32px 0 12px;padding-bottom:8px;border-bottom:2px solid ' . $corPrincipal . ';font-size:15px;font-weight:bold;color:' . $corPrincipal . ';text-transform:uppercase;letter-spacing:.03em;">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h3>' . $conteudo;
}

/**
 * Agrupa por categoria (o "tema" — mesmo campo já usado nos filtros das
 * notícias/eventos), com um cabeçalho por grupo. Os sem categoria ficam
 * soltos no fim, sem cabeçalho — tal como na newsletter do gesgov.pt.
 */
function newsletterAgruparPorCategoria($itens, $eEvento) {
    $comCategoria = [];
    $semCategoria = [];

    foreach ($itens as $item) {
        if (!empty($item['categoria'])) {
            $comCategoria[$item['categoria']][] = $item;
        } else {
            $semCategoria[] = $item;
        }
    }

    $html = '';
    foreach ($comCategoria as $categoria => $doGrupo) {
        $html .= '<h4 style="margin:18px 0 8px;font-size:12px;font-weight:bold;letter-spacing:.02em;text-transform:uppercase;color:#6b7280;">' . htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') . '</h4>';
        $html .= newsletterListaItens($doGrupo, $eEvento);
    }
    if (!empty($semCategoria)) {
        $html .= newsletterListaItens($semCategoria, $eEvento);
    }

    return $html;
}

function newsletterListaItens($itens, $eEvento) {
    $html = '<ul style="list-style:none;padding:0;margin:0 0 4px;">';
    foreach ($itens as $item) {
        $html .= '<li style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #eef1f4;font-size:14px;line-height:1.5;color:#0f1720;">' . newsletterLinhaItem($item, $eEvento) . '</li>';
    }
    $html .= '</ul>';
    return $html;
}

function newsletterLinhaItem($item, $eEvento) {
    $miniatura = newsletterMiniaturaHtml($item);
    $linha = $miniatura . '<strong>' . htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') . '</strong>';
    if ($eEvento && !empty($item['data_evento'])) {
        $linha .= ' <span style="font-size:12px;color:#6b7280;">— ' . date('d/m/Y', strtotime($item['data_evento'])) . '</span>';
    }
    return $linha;
}

/**
 * Miniatura pequena em linha antes do título (40x40) — devolve string
 * vazia se não houver imagem, para não deixar espaço a mais.
 */
function newsletterMiniaturaHtml($item) {
    $url = newsletterImagemUrl($item);
    if (!$url) {
        return '';
    }
    return '<img src="' . $url . '" alt="" width="40" height="40" style="width:40px;height:40px;object-fit:cover;border-radius:6px;vertical-align:middle;margin-right:8px;">';
}

/**
 * Envolve o conteúdo (automático ou manual) no template de email —
 * cabeçalho com a marca da freguesia, rodapé fixo. Usado tanto na
 * pré-visualização do backoffice como no email real enviado.
 */
function newsletterTemplateEmail($conteudoHtml) {
    $nomeSite = htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia'), ENT_QUOTES, 'UTF-8');
    $corPrincipal = htmlspecialchars(siteConfig('cor_principal', '#242A30'), ENT_QUOTES, 'UTF-8');

    $logoHtml = '';
    $logoUrl = newsletterLogoEmailUrl();
    if ($logoUrl) {
        // Sem object-fit aqui: o Outlook clássico ignora-o e esmagaria um
        // logo não-quadrado (ex: um brasão) para caber em 32x32. A imagem
        // servida já vem pré-gerada quadrada (ver newsletterLogoEmailUrl),
        // por isso não há nada para o CSS ter de corrigir.
        $logoHtml = '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="" width="48" height="48" style="width:48px;height:48px;vertical-align:middle;margin-right:16px;border-radius:10px;">';
    }

    return '
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
            <tr><td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;font-family:Arial,sans-serif;">
                    <tr><td style="background:' . $corPrincipal . ';padding:26px 28px;">
                        ' . $logoHtml . '<strong style="color:#ffffff;font-size:17px;vertical-align:middle;">' . $nomeSite . '</strong>
                    </td></tr>
                    <tr><td style="padding:24px;">' . $conteudoHtml . '</td></tr>
                    <tr><td style="padding:16px 24px;background:#f8fafc;font-size:11px;color:#94a3b8;text-align:center;">
                        Recebeu este email porque subscreveu a newsletter de ' . $nomeSite . '.
                    </td></tr>
                </table>
            </td></tr>
        </table>
    ';
}

// Nomes dos meses em português, sem depender da locale do servidor
// (setlocale() é frágil entre sistemas operativos diferentes).
function strftime_pt($mesNumero) {
    $meses = [1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho',
        7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'];
    return $meses[(int) $mesNumero] ?? '';
}

/**
 * Envia a newsletter (conteúdo automático, ou o manual se preenchido) a
 * uma lista de destinatários. $emails=null envia a TODOS os subscritores
 * ativos e conta como "o envio oficial do mês" (regista histórico e
 * atualiza ultimo_envio); uma lista explícita é um reenvio seletivo
 * (ex: corrigir um email e reenviar só a essa pessoa) e não mexe em
 * nenhum dos dois, para não bloquear o envio oficial nem falsear o
 * histórico. Devolve quantos emails foram efetivamente enviados.
 */
function newsletterEnviar($pdo, $emails = null) {
    require_once __DIR__ . '/mail_helper.php';

    $config = newsletterConfig($pdo);
    $conteudo = !empty($config['conteudo_manual']) ? $config['conteudo_manual'] : newsletterComporMesAtual($pdo);
    $htmlCompleto = newsletterTemplateEmail($conteudo);

    if ($emails === null) {
        $subscritores = $pdo->query("SELECT email FROM newsletter_subscribers WHERE ativo = 1")->fetchAll(PDO::FETCH_COLUMN);
        $envioOficial = true;
    } else {
        $placeholders = implode(',', array_fill(0, count($emails), '?'));
        $stmt = $pdo->prepare("SELECT email FROM newsletter_subscribers WHERE ativo = 1 AND email IN ($placeholders)");
        $stmt->execute($emails);
        $subscritores = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $envioOficial = false;
    }

    $replyTo = siteConfig('email', null);
    $assunto = 'Newsletter ' . siteConfig('nome_site', 'Junta de Freguesia') . ' — ' . ucfirst(strftime_pt(date('n'))) . ' ' . date('Y');

    $enviados = 0;
    foreach ($subscritores as $email) {
        if (enviarEmailSistema($email, $assunto, $htmlCompleto, $replyTo)) {
            $enviados++;
        }
    }

    // Só conta como "enviado este mês" se pelo menos um destinatário
    // recebeu mesmo o email — uma falha total (ex: SMTP mal configurado)
    // não deve gastar o envio do mês nem bloquear uma nova tentativa,
    // tanto no "Enviar agora" como no cron automático.
    if ($envioOficial && $enviados > 0) {
        $mesReferencia = date('Y-m');
        $stmt = $pdo->prepare("
            INSERT INTO newsletter_envios (mes_referencia, conteudo_html, total_destinatarios)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE conteudo_html = VALUES(conteudo_html), total_destinatarios = VALUES(total_destinatarios), enviado_em = NOW()
        ");
        $stmt->execute([$mesReferencia, $htmlCompleto, $enviados]);

        $pdo->prepare("UPDATE newsletter_config SET ultimo_envio = ?, conteudo_manual = NULL WHERE id = 1")
            ->execute([$mesReferencia]);
    }

    return $enviados;
}
