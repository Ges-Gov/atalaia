<?php require_once "includes/header.php"; ?>
<?php require_once __DIR__ . "/includes/mapa.php"; ?>

<?php
/*
 * Contactos (redesenho 10/2026):
 *   1. contactos rápidos (telefone, email, morada, horário)
 *   2. formulário de contacto — guarda em contactos_mensagens (migração 042) e tenta enviar
 *      por email para a Junta; lido no admin (Junta Virtual → Mensagens de Contacto)
 *   3. mapa interativo (Leaflet): limite da freguesia, sede da Junta, pontos de interesse e
 *      comércio com coordenadas, filtros e lista de locais
 *   4. contactos úteis em grelha, com filtro por categoria
 * O título/textos do topo continuam a vir de contactos_pagina_config (admin).
 */
$configContactos = [
    'hero_kicker' => 'Contactos',
    'hero_titulo' => 'Contactos',
    'hero_subtitulo' => 'Encontre aqui os principais contactos, serviços úteis e informações de atendimento da freguesia.',
    'bloco_titulo' => 'Junta de Freguesia',
    'bloco_texto' => 'Estamos disponíveis para apoiar os cidadãos, responder a pedidos e encaminhar informações úteis.',
    'mapa_embed' => '',
    'horario_titulo' => 'Horário de atendimento',
    'horario_texto' => 'Segunda a sexta-feira, em horário definido pela Junta de Freguesia.'
];
try {
    $row = $pdo->query("SELECT * FROM contactos_pagina_config WHERE ativo = 1 ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($row) $configContactos = array_merge($configContactos, array_filter($row, fn($v) => $v !== null));
} catch (Exception $e) {}

/* ---------- 2. Formulário: tratar o envio (padrão POST → redirect → GET) ---------- */
$assuntosContacto = ['Pedido de informação', 'Sugestão', 'Reclamação', 'Atendimento / marcação', 'Outro assunto'];
if (empty($_SESSION['csrf_contacto'])) $_SESSION['csrf_contacto'] = bin2hex(random_bytes(16));
$errosForm = [];
$dadosForm = ['nome' => '', 'email' => '', 'telefone' => '', 'assunto' => '', 'mensagem' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_contacto'])) {
    foreach ($dadosForm as $k => $v) $dadosForm[$k] = trim((string)($_POST[$k] ?? ''));

    $spam = ($_POST['website'] ?? '') !== ''                                   // campo-isco escondido
         || !hash_equals($_SESSION['csrf_contacto'], (string)($_POST['csrf'] ?? ''))
         || (time() - (int)($_POST['t'] ?? 0)) < 3;                              // preenchido em < 3 s = robô

    if (mb_strlen($dadosForm['nome']) < 2)                                  $errosForm['nome'] = 'Indique o seu nome.';
    if (!filter_var($dadosForm['email'], FILTER_VALIDATE_EMAIL))           $errosForm['email'] = 'Indique um email válido (ex.: nome@exemplo.pt).';
    if ($dadosForm['telefone'] !== '' && !preg_match('/^[\d\s+()-]{9,20}$/', $dadosForm['telefone'])) $errosForm['telefone'] = 'O telefone deve ter pelo menos 9 algarismos.';
    if (!in_array($dadosForm['assunto'], $assuntosContacto, true))         $errosForm['assunto'] = 'Escolha o motivo do contacto.';
    if (mb_strlen($dadosForm['mensagem']) < 10)                            $errosForm['mensagem'] = 'Explique o motivo do contacto (pelo menos 10 caracteres).';
    if (mb_strlen($dadosForm['mensagem']) > 4000)                          $errosForm['mensagem'] = 'A mensagem é demasiado longa (máximo 4000 caracteres).';
    if (empty($_POST['consentimento']))                                     $errosForm['consentimento'] = 'É preciso aceitar o tratamento dos dados para podermos responder.';

    $ultimo = $_SESSION['ultimo_contacto'] ?? 0;
    if (!$errosForm && !$spam && time() - $ultimo < 30) $errosForm['geral'] = 'Já enviou uma mensagem há instantes. Aguarde um pouco antes de enviar outra.';

    if (!$errosForm) {
        if (!$spam) {
            $_SESSION['ultimo_contacto'] = time();
            $enviado = 0;
            try {
                $pdo->prepare("INSERT INTO contactos_mensagens (nome, email, telefone, assunto, mensagem, ip_hash) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$dadosForm['nome'], $dadosForm['email'], $dadosForm['telefone'] ?: null, $dadosForm['assunto'], $dadosForm['mensagem'], hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'jf')]);
                $idMsg = (int)$pdo->lastInsertId();

                // email para a Junta (se o envio estiver configurado neste servidor)
                $destino = siteConfig('email_notificacoes', '') ?: siteConfig('email', '');
                if ($destino !== '' && is_file(__DIR__ . '/includes/mail_config.php')) {
                    require_once __DIR__ . '/includes/mail_helper.php';
                    $h = fn($s) => nl2br(htmlspecialchars($s));
                    $html = '<h2>Nova mensagem pelo site</h2>'
                          . '<p><strong>Motivo:</strong> ' . $h($dadosForm['assunto']) . '</p>'
                          . '<p><strong>Nome:</strong> ' . $h($dadosForm['nome']) . '<br><strong>Email:</strong> ' . $h($dadosForm['email'])
                          . ($dadosForm['telefone'] ? '<br><strong>Telefone:</strong> ' . $h($dadosForm['telefone']) : '') . '</p>'
                          . '<p>' . $h($dadosForm['mensagem']) . '</p>';
                    if (enviarEmailSistema($destino, 'Contacto pelo site: ' . $dadosForm['assunto'], $html, $dadosForm['email'])) {
                        $enviado = 1;
                        $pdo->prepare("UPDATE contactos_mensagens SET email_enviado = 1 WHERE id = ?")->execute([$idMsg]);
                    }
                }
            } catch (Throwable $e) {
                $errosForm['geral'] = 'Não foi possível enviar a mensagem agora. Tente novamente ou contacte-nos por telefone.';
            }
        }
        if (!$errosForm) {
            // robôs recebem a mesma resposta de sucesso, sem nada ser guardado
            $_SESSION['csrf_contacto'] = bin2hex(random_bytes(16));
            header("Location: contactos.php?enviado=1#formulario");
            exit;
        }
    }
}
$enviadoOk = isset($_GET['enviado']);

/* ---------- 3. Dados do mapa ---------- */
$junta = [
    'lat' => (float)temaConfig('junta_lat', '0'),
    'lng' => (float)temaConfig('junta_lng', '0'),
];
/* Categorias do mapa (12/10/2026): filtros no topo do mapa, cada um com subfiltros por tipo.
   [rótulo, cor, ícone] — as cores de marca vêm das variáveis; as outras duas só distinguem pinos. */
$tiposMapa = [
    'junta'    => ['Junta', 'var(--cor-secundaria)', 'bi-bank2'],
    'ponto'    => ['Pontos de interesse', 'var(--cor-principal)', 'bi-geo-alt-fill'],
    'comercio' => ['Economia Local', '#2563eb', 'bi-shop'],
    'assoc'    => ['Associações', '#b4436c', 'bi-people-fill'],
];
// Subcategorias: os pontos de interesse não têm categoria na BD — deduz-se pelo nome
$subPonto = function (string $n): string {
    if (preg_match('/igreja|capela|convento|ermida|santu[aá]rio/iu', $n)) return 'Património religioso';
    if (preg_match('/museu|pelourinho|pra[cç]a de touros|anta|castelo|fonte|monumento/iu', $n)) return 'Monumentos e museus';
    if (preg_match('/serra|jardim|parque|barragem|ribeira|miradouro|percurso/iu', $n)) return 'Natureza e lazer';
    return 'Equipamentos';
};
$subComercio = function (string $t): string {
    if (preg_match('/aloj|casa de campo|turismo|hosped|residencial|quinta|monte/iu', $t)) return 'Alojamento';
    if (preg_match('/restaur|churrasq|tasca|pizz/iu', $t)) return 'Restaurantes';
    if (preg_match('/caf[eé]|snack|bar|pastel/iu', $t)) return 'Cafés e pastelarias';
    return 'Lojas e serviços';
};
$locaisMapa = [];
if ($junta['lat'] && $junta['lng']) {
    $locaisMapa[] = ['tipo' => 'junta', 'sub' => 'Sede da Junta', 'nome' => siteConfig('nome_site', 'Junta de Freguesia'), 'morada' => siteConfig('morada', ''),
                     'lat' => $junta['lat'], 'lng' => $junta['lng'], 'img' => '', 'link' => '', 'extra' => siteConfig('telefone', '')];
}
try {
    foreach ($pdo->query("SELECT id, nome, localizacao, latitude, longitude, imagem FROM pontos_interesse WHERE latitude <> '' AND longitude <> '' AND latitude IS NOT NULL ORDER BY nome") as $p) {
        $locaisMapa[] = ['tipo' => 'ponto', 'sub' => $subPonto($p['nome']), 'nome' => $p['nome'], 'morada' => (string)$p['localizacao'], 'lat' => (float)$p['latitude'], 'lng' => (float)$p['longitude'],
                         'img' => $p['imagem'] ? '/assets/img/' . $p['imagem'] : '', 'link' => '/ponto.php?id=' . (int)$p['id'], 'extra' => 'Ponto de interesse'];
    }
} catch (Exception $e) {}
try {
    foreach ($pdo->query("SELECT nome, tipo, morada, latitude, longitude, imagem, telefone FROM comercio_local WHERE latitude <> '' AND longitude <> '' AND latitude IS NOT NULL ORDER BY nome") as $c) {
        $locaisMapa[] = ['tipo' => 'comercio', 'sub' => $subComercio((string)$c['tipo']), 'nome' => $c['nome'], 'morada' => (string)$c['morada'], 'lat' => (float)$c['latitude'], 'lng' => (float)$c['longitude'],
                         'img' => $c['imagem'] ? '/assets/img/' . $c['imagem'] : '', 'link' => '/comercio.php', 'extra' => $c['tipo'] ?: 'Economia local'];
    }
} catch (Exception $e) {}
try {
    foreach ($pdo->query("SELECT nome, categoria, morada, latitude, longitude, imagem FROM associacoes WHERE latitude <> '' AND longitude <> '' AND latitude IS NOT NULL ORDER BY nome") as $a) {
        $locaisMapa[] = ['tipo' => 'assoc', 'sub' => $a['categoria'] ?: 'Outras', 'nome' => $a['nome'], 'morada' => (string)$a['morada'], 'lat' => (float)$a['latitude'], 'lng' => (float)$a['longitude'],
                         'img' => $a['imagem'] ? '/assets/img/' . $a['imagem'] : '', 'link' => '/associacoes.php', 'extra' => 'Associação · ' . ($a['categoria'] ?: 'Outras')];
    }
} catch (Exception $e) {}
$centro = centroFreguesia();
$nTipo = array_count_values(array_column($locaisMapa, 'tipo'));
$subsMapa = [];   // [tipo => [subcategoria => n]], das mais frequentes para as menos
foreach ($locaisMapa as $l) $subsMapa[$l['tipo']][$l['sub']] = ($subsMapa[$l['tipo']][$l['sub']] ?? 0) + 1;
foreach ($subsMapa as &$s) arsort($s);
unset($s);

/* ---------- 4. Contactos úteis ---------- */
require_once __DIR__ . "/includes/utilidades.php";   // cartão e secção partilhados
$contactos = [];
try {
    $contactos = $pdo->query("SELECT * FROM contactos_uteis WHERE ativo = 1 ORDER BY destaque DESC, ordem ASC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try { $contactos = $pdo->query("SELECT * FROM contactos_uteis ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e2) {}
}
function txt($v){ return nl2br(htmlspecialchars($v ?? '')); }
function telLink($t){ return 'tel:' . preg_replace('/[^\d+]/', '', explode('/', (string)$t)[0]); }
$campoErro = fn($k) => isset($errosForm[$k]) ? ' aria-invalid="true" aria-describedby="erro-' . $k . '"' : '';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

<style>
/* ===== Contactos (10/2026) ===== */
.contact-hero{background:radial-gradient(circle at 15% 18%,rgb(var(--ouro-rgb, 240 180 41) / .24),transparent 30%),linear-gradient(135deg,#242A30,#11151B 65%,#071c2e);color:white;padding:92px 0 108px;position:relative;overflow:hidden}
.contact-hero .container{position:relative;z-index:2}
.contact-kicker{display:inline-flex;background:rgb(var(--ouro-rgb, 240 180 41) / .16);border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .38);color:var(--ouro-claro, #f8d77a);padding:9px 14px;border-radius:999px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;font-size:12px}
.contact-hero h1{margin:18px 0 14px;line-height:1.02;max-width:980px}
.contact-hero p{max-width:880px;color:#e2e8f0;font-size:19px;line-height:1.7;margin:0}
@media(max-width:700px){.contact-hero p{font-size:15px;line-height:1.5}}

.ct-wrap{position:relative;z-index:5;margin-top:-56px;padding-bottom:20px}
.ct-titulo{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin:0 0 20px}
.ct-titulo h2{margin:0;font-size:clamp(24px,1.2rem + 1vw,34px)}
.ct-titulo p{margin:6px 0 0;color:#64748b}
.ct-kicker{display:block;font-size:12.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--cor-principal);margin-bottom:6px}

/* 1. contactos rápidos */
.ct-rapidos{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;align-items:stretch}
.ct-rapido{display:flex;flex-direction:column;gap:6px;padding:22px;border-radius:22px;background:rgba(255,255,255,.86);backdrop-filter:blur(16px) saturate(1.4);-webkit-backdrop-filter:blur(16px) saturate(1.4);border:1px solid rgba(255,255,255,.7);box-shadow:0 18px 40px -22px rgba(15,23,42,.35);color:#1e293b;text-decoration:none;min-width:0;transition:transform var(--dur-3) var(--ease-spring),box-shadow var(--dur-3) var(--ease-out)}
a.ct-rapido:hover{transform:translateY(-4px);box-shadow:var(--sombra-cartao-hover)}
.ct-rapido__ico{width:46px;height:46px;border-radius:14px;display:grid;place-items:center;background:color-mix(in srgb,var(--cor-principal) 12%,transparent);color:var(--cor-principal);font-size:20px;margin-bottom:8px}
.ct-rapido__ico .bi{display:block;line-height:1}
.ct-rapido small{font-size:12.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.ct-rapido strong{font-size:17px;font-weight:600;line-height:1.35;overflow-wrap:anywhere}
.ct-rapido span.ct-rapido__acao{margin-top:auto;padding-top:8px;font-size:14px;font-weight:600;color:var(--cor-principal);display:inline-flex;align-items:center;gap:6px}

/* 2. formulário + lado */
.ct-form-sec{margin-top:56px}
.ct-form-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:22px;align-items:stretch}
.ct-form{position:relative;padding:30px;border-radius:28px;overflow:hidden;isolation:isolate;
  background:radial-gradient(80% 60% at 0% 0%,color-mix(in srgb,var(--cor-principal) 18%,transparent),transparent 70%),radial-gradient(70% 60% at 100% 100%,color-mix(in srgb,var(--cor-secundaria) 20%,transparent),transparent 70%),rgba(255,255,255,.7);
  backdrop-filter:blur(20px) saturate(1.5);-webkit-backdrop-filter:blur(20px) saturate(1.5);border:1px solid rgba(255,255,255,.75);box-shadow:0 30px 60px -30px rgba(15,23,42,.45)}
.ct-form h2{margin:0 0 6px;font-size:clamp(22px,1.1rem + .8vw,30px)}
.ct-form > p{margin:0 0 22px;color:#475569}
.ct-campos{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px}
.ct-campo{display:flex;flex-direction:column;gap:6px;min-width:0}
.ct-campo--total{grid-column:1/-1}
.ct-campo label{font-size:14px;font-weight:600;color:#1e293b}
.ct-campo label .opc{font-weight:400;color:#64748b}
.ct-campo input,.ct-campo select,.ct-campo textarea{width:100%;box-sizing:border-box;min-height:50px;padding:12px 16px;border-radius:14px;border:1px solid rgba(15,23,42,.14);background:rgba(255,255,255,.85);font:400 15.5px var(--fonte);color:#1e293b;transition:border-color var(--dur-2) var(--ease-out),box-shadow var(--dur-2) var(--ease-out),background-color var(--dur-2) var(--ease-out)}
.ct-campo textarea{min-height:150px;resize:vertical;line-height:1.6}
.ct-campo input:focus,.ct-campo select:focus,.ct-campo textarea:focus{outline:none;border-color:var(--cor-principal);background:#fff;box-shadow:0 0 0 4px color-mix(in srgb,var(--cor-principal) 18%,transparent)}
.ct-campo [aria-invalid="true"]{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.12)}
.ct-erro{font-size:13.5px;color:#b91c1c;font-weight:600}
.ct-contador{align-self:flex-end;font-size:12.5px;color:#64748b;font-variant-numeric:tabular-nums}
/* motivos como "pílulas" (radio) */
.ct-motivos{display:flex;flex-direction:row!important;flex-wrap:wrap;gap:8px;border:0;padding:0;margin:0;min-inline-size:0}
.ct-motivos legend{width:100%}
.ct-motivos legend{font-size:14px;font-weight:600;color:#1e293b;margin-bottom:8px;padding:0}
.ct-motivo{position:relative}
.ct-motivo input{position:absolute;opacity:0;inset:0;margin:0;cursor:pointer}
.ct-motivo span{display:inline-flex;align-items:center;gap:6px;min-height:42px;padding:0 16px;border-radius:999px;border:1px solid rgba(15,23,42,.14);background:rgba(255,255,255,.8);font-size:14.5px;font-weight:600;color:#334155;transition:all var(--dur-2) var(--ease-out)}
.ct-motivo input:checked + span{background:var(--cor-principal);border-color:var(--cor-principal);color:#fff;box-shadow:0 8px 18px -8px color-mix(in srgb,var(--cor-principal) 80%,transparent)}
.ct-motivo input:focus-visible + span{outline:3px solid var(--tema-acento-escuro,var(--ouro-texto, #856611));outline-offset:3px}
.ct-motivo:hover span{border-color:var(--cor-principal)}
.ct-consent{display:flex;gap:12px;align-items:flex-start;font-size:14px;color:#334155;line-height:1.5}
.ct-consent input{width:20px;height:20px;margin-top:1px;flex-shrink:0;accent-color:var(--cor-principal)}
.ct-consent a{color:var(--cor-principal);font-weight:600}
.ct-enviar{display:inline-flex;align-items:center;gap:10px;min-height:54px;padding:0 28px;border:0;border-radius:999px;background:var(--cor-principal);color:#fff;font:600 16px var(--fonte);cursor:pointer;box-shadow:0 14px 28px -12px color-mix(in srgb,var(--cor-principal) 80%,transparent);transition:transform var(--dur-2) var(--ease-spring),box-shadow var(--dur-2) var(--ease-out)}
.ct-enviar:hover{transform:translateY(-2px)}
.ct-enviar .bi{transition:transform var(--dur-3) var(--ease-spring)}
.ct-enviar:hover .bi{transform:translate(3px,-3px) rotate(8deg)}
.ct-acoes-form{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:6px}
.ct-acoes-form small{color:#64748b}
.ct-aviso{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:16px;margin-bottom:18px;font-size:15px;line-height:1.5}
.ct-aviso--erro{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.ct-sucesso{display:flex;flex-direction:column;align-items:flex-start;gap:12px;padding:10px 0}
.ct-sucesso__ico{width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:color-mix(in srgb,var(--cor-principal) 14%,transparent);color:var(--cor-principal);font-size:30px;animation:ct-pop .6s var(--ease-spring) both}
@keyframes ct-pop{from{transform:scale(.4);opacity:0}to{transform:none;opacity:1}}
.ct-sucesso h2{margin:0}
.ct-sucesso p{margin:0;color:#475569;max-width:52ch}
.ct-lado{display:flex;flex-direction:column;gap:16px}
.ct-cartao{padding:24px;border-radius:24px;background:#fff;border:1px solid #e8ecef;box-shadow:0 1px 2px rgba(15,23,42,.05),0 12px 30px -18px rgba(15,23,42,.3)}
.ct-cartao h3{margin:0 0 10px;font-size:18px;display:flex;align-items:center;gap:10px}
.ct-cartao h3 .bi{color:var(--cor-principal)}
.ct-cartao p{margin:0 0 12px;color:#475569;line-height:1.6}
.ct-cartao:not(.ct-cartao--escuro) p a{color:var(--cor-principal);font-weight:600}
.ct-cartao--escuro{background:radial-gradient(90% 70% at 100% 0%,color-mix(in srgb,var(--cor-secundaria) 26%,transparent),transparent 70%),linear-gradient(160deg,var(--cor-principal),color-mix(in srgb,var(--cor-principal) 55%,#0b0f0c));color:#fff;border-color:transparent}
.ct-cartao--escuro h3,.ct-cartao--escuro h3 .bi{color:#fff}
.ct-cartao--escuro p{color:rgba(255,255,255,.88)}
.ct-btn-sec{display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:0 18px;border-radius:999px;background:var(--cor-secundaria);color:#11151b;font-weight:600;text-decoration:none}
.ct-horario-lista{list-style:none;margin:0;padding:0;display:grid;gap:8px}
.ct-horario-lista li{display:flex;gap:10px;color:#334155;line-height:1.5}
.ct-horario-lista .bi{color:var(--cor-principal);margin-top:3px}
.ct-aberto{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;font-size:13px;font-weight:600;margin-bottom:12px}
.ct-aberto::before{content:"";width:8px;height:8px;border-radius:50%;background:currentColor}
.ct-aberto--sim{background:#dcfce7;color:#166534}
.ct-aberto--nao{background:#f1f5f9;color:#475569}

/* 3. mapa */
.ct-mapa-sec{margin-top:56px}
/* a secção tem id="mapa" (âncora dos links «ver no mapa»): anula as regras antigas de #mapa do style.css, feitas para
   o mapa da página Mapa (altura fixa, cantos e overflow:hidden, que cortava o título e o kicker) */
.ct-mapa-sec#mapa{height:auto;border-radius:0;overflow:visible;box-shadow:none}
.ct-mapa-shell{position:relative;display:grid;grid-template-columns:320px minmax(0,1fr);border-radius:28px;overflow:hidden;background:#fff;border:1px solid #e8ecef;box-shadow:0 30px 60px -34px rgba(15,23,42,.45);min-height:560px}
.ct-mapa-lado{display:flex;flex-direction:column;min-height:0;border-right:1px solid #eef2f5;background:linear-gradient(180deg,#fff,#fafbfa)}
/* Filtros no topo do mapa: CSS partilhado em assets/css/style.css (.ct-mfiltro…) */
.ct-lista{list-style:none;margin:0;padding:8px;overflow-y:auto;flex:1;max-height:470px}
.ct-lista button{width:100%;display:flex;align-items:center;gap:12px;padding:10px;border:0;border-radius:14px;background:transparent;text-align:left;cursor:pointer;font:inherit;color:#1e293b;transition:background-color var(--dur-1) var(--ease-out)}
.ct-lista button:hover,.ct-lista button.ativo{background:color-mix(in srgb,var(--cor-principal) 9%,transparent)}
.ct-lista .mini{flex-shrink:0;width:46px;height:46px;border-radius:12px;background:#e5e7eb center/cover;display:grid;place-items:center;color:#fff;font-size:18px}
.ct-lista strong{display:block;font-size:14.5px;font-weight:600;line-height:1.3}
.ct-lista small{display:block;font-size:12.5px;color:#64748b;line-height:1.35;margin-top:2px}
.ct-lista li[hidden]{display:none}
#ctMapa{width:100%;height:100%;min-height:560px;background:#e8eef0;z-index:1}
/* mosaicos OSM com um toque mais suave (dessaturados) e versão escura no tema escuro */
#ctMapa .leaflet-tile-pane{filter:saturate(.75) contrast(1.02)}
:root[data-tema="escuro"] #ctMapa .leaflet-tile-pane{filter:invert(1) hue-rotate(180deg) brightness(.85) contrast(.9) saturate(.6)}
:root[data-tema="escuro"] #ctMapa{background:#1b1f24}
.ct-mapa-acoes{position:absolute;right:14px;top:14px;z-index:500;display:flex;flex-direction:column;gap:8px}
.ct-mapa-acoes button{width:44px;height:44px;border-radius:14px;border:1px solid rgba(255,255,255,.7);background:rgba(255,255,255,.88);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);box-shadow:0 8px 20px -10px rgba(15,23,42,.45);color:#1e293b;font-size:17px;cursor:pointer;display:grid;place-items:center}
.ct-mapa-acoes button:hover{color:var(--cor-principal)}
/* marcadores */
.ct-pin{position:relative;width:40px;height:52px;filter:drop-shadow(0 6px 8px rgba(15,23,42,.35));transition:transform var(--dur-3) var(--ease-spring)}
.ct-pin svg{position:absolute;inset:0}
.ct-pin .bi{position:absolute;left:50%;top:15px;transform:translateX(-50%);color:#fff;font-size:15px;line-height:1}
.leaflet-marker-icon:hover .ct-pin,.ct-pin.ativo{transform:translateY(-4px) scale(1.12)}
.ct-pin--junta{width:48px;height:62px}
.ct-pin--junta .bi{top:17px;font-size:18px}
.ct-pin--junta::after{content:"";position:absolute;left:50%;bottom:-6px;width:56px;height:56px;margin-left:-28px;border-radius:50%;background:color-mix(in srgb,var(--cor-secundaria) 35%,transparent);z-index:-1;animation:ct-pulso 2.4s ease-out infinite}
@keyframes ct-pulso{0%{transform:scale(.3);opacity:.9}100%{transform:scale(1.4);opacity:0}}
/* popups */
.ct-popup .leaflet-popup-content-wrapper{border-radius:18px;padding:0;overflow:hidden;box-shadow:0 20px 40px -14px rgba(15,23,42,.45)}
.ct-popup .leaflet-popup-content{margin:0;width:260px!important;font-family:var(--fonte)}
.ct-pop-img{height:120px;background:#e5e7eb center/cover}
.ct-pop-corpo{padding:14px 16px 16px}
.ct-pop-tipo{font-size:11.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cor-principal)}
.ct-pop-corpo h4{margin:4px 0 6px;font-size:16px;font-weight:600;line-height:1.3;color:#11151b}
.ct-pop-corpo p{margin:0 0 12px;font-size:13px;color:#475569;line-height:1.45}
.ct-pop-acoes{display:flex;gap:8px;flex-wrap:wrap}
.ct-pop-acoes a{display:inline-flex;align-items:center;gap:6px;min-height:36px;padding:0 12px;border-radius:999px;font-size:13px;font-weight:600;text-decoration:none;background:var(--cor-principal);color:#fff!important}
.ct-pop-acoes a.sec{background:#f1f5f9;color:#1e293b!important}
.leaflet-container{font-family:var(--fonte)}
.ct-mapa-nota{margin:10px 2px 0;font-size:13px;color:#64748b}

/* 4. contactos úteis */

/* escuro */
:root[data-tema="escuro"] :is(.ct-rapido,.ct-cartao:not(.ct-cartao--escuro),.ct-mapa-shell,.ct-mapa-lado,.ct-mfiltro,.ct-msub,.ct-lista button){background:var(--escuro-superficie);border-color:var(--escuro-borda);color:var(--escuro-texto)}
:root[data-tema="escuro"] .ct-form{background:radial-gradient(80% 60% at 0% 0%,color-mix(in srgb,var(--cor-principal) 28%,transparent),transparent 70%),rgba(28,32,37,.85);border-color:rgba(255,255,255,.08)}
:root[data-tema="escuro"] :is(.ct-campo input,.ct-campo select,.ct-campo textarea,.ct-motivo span){background:var(--escuro-superficie-2);border-color:var(--escuro-borda);color:var(--escuro-texto)}
:root[data-tema="escuro"] :is(.ct-campo label,.ct-motivos legend,.ct-consent,.ct-horario-lista li,.ct-rapido strong){color:var(--escuro-texto)}
:root[data-tema="escuro"] :is(.ct-form > p,.ct-cartao p,.ct-rapido small,.ct-lista small,.ct-titulo p,.ct-contador){color:var(--escuro-texto-2)}
:root[data-tema="escuro"] :is(.ct-rapido__ico,.ct-rapido span.ct-rapido__acao,.ct-kicker,.ct-cartao h3 .bi){color:color-mix(in srgb,var(--cor-principal) 45%,#fff)}
:root[data-tema="escuro"] .ct-mapa-acoes button{background:rgba(28,32,37,.9);color:var(--escuro-texto);border-color:rgba(255,255,255,.1)}

@media(max-width:1100px){.ct-rapidos{grid-template-columns:repeat(2,minmax(0,1fr))}.ct-form-grid{grid-template-columns:1fr}}
@media(max-width:900px){.ct-mapa-shell{grid-template-columns:1fr}.ct-mapa-lado{border-right:0;border-bottom:1px solid #eef2f5;order:2}.ct-lista{max-height:260px}#ctMapa{min-height:420px}}
@media(max-width:600px){.ct-rapidos{grid-template-columns:1fr}.ct-campos{grid-template-columns:1fr}.ct-form{padding:22px}.ct-rapido{padding:18px}}
@media(prefers-reduced-motion:reduce){.ct-pin--junta::after{animation:none;opacity:.5}}
</style>

<section class="contact-hero">
    <div class="container">
        <span class="contact-kicker"><?= htmlspecialchars($configContactos['hero_kicker']) ?></span>
        <h1><?= htmlspecialchars($configContactos['hero_titulo']) ?></h1>
        <p><?= htmlspecialchars($configContactos['hero_subtitulo']) ?></p>
    </div>
</section>

<div class="ct-wrap">
    <div class="container">

        <!-- 1. Contactos rápidos -->
        <div class="ct-rapidos">
            <?php if (siteConfig('telefone', '')): ?>
                <a class="ct-rapido" href="<?= htmlspecialchars(telLink(siteConfig('telefone'))) ?>">
                    <span class="ct-rapido__ico" aria-hidden="true"><i class="bi bi-telephone-fill"></i></span>
                    <small><?= te('contactos.rapidos.telefone') ?></small>
                    <strong><?= htmlspecialchars(siteConfig('telefone')) ?></strong>
                    <span class="ct-rapido__acao"><?= te('contactos.rapidos.telefone_acao') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                </a>
            <?php endif; ?>
            <?php if (siteConfig('email', '')): ?>
                <a class="ct-rapido" href="mailto:<?= htmlspecialchars(siteConfig('email')) ?>">
                    <span class="ct-rapido__ico" aria-hidden="true"><i class="bi bi-envelope-fill"></i></span>
                    <small><?= te('contactos.rapidos.email') ?></small>
                    <strong><?= str_replace('@', '<wbr>@', htmlspecialchars(siteConfig('email'))) ?></strong>
                    <span class="ct-rapido__acao"><?= te('contactos.rapidos.email_acao') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                </a>
            <?php endif; ?>
            <?php if (siteConfig('morada', '')): ?>
                <a class="ct-rapido" href="#mapa">
                    <span class="ct-rapido__ico" aria-hidden="true"><i class="bi bi-geo-alt-fill"></i></span>
                    <small><?= te('contactos.rapidos.morada') ?></small>
                    <strong><?= htmlspecialchars(siteConfig('morada')) ?></strong>
                    <span class="ct-rapido__acao"><?= te('contactos.rapidos.morada_acao') ?> <i class="bi bi-arrow-down" aria-hidden="true"></i></span>
                </a>
            <?php endif; ?>
            <div class="ct-rapido">
                <span class="ct-rapido__ico" aria-hidden="true"><i class="bi bi-clock-fill"></i></span>
                <small><?= htmlspecialchars($configContactos['horario_titulo']) ?></small>
                <strong style="font-size:15px;font-weight:500;"><?= txt($configContactos['horario_texto']) ?></strong>
            </div>
        </div>

        <!-- 2. Formulário -->
        <section class="ct-form-sec" id="formulario" aria-labelledby="ctFormTitulo">
            <div class="ct-form-grid">
                <div class="ct-form">
                    <?php if ($enviadoOk): ?>
                        <div class="ct-sucesso" role="status" tabindex="-1" id="ctSucesso">
                            <span class="ct-sucesso__ico" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                            <h2 id="ctFormTitulo"><?= te('contactos.formulario.sucesso_titulo') ?></h2>
                            <p><?= te('contactos.formulario.sucesso_texto') ?>
                               Se o assunto for urgente, ligue-nos<?= siteConfig('telefone', '') ? ' para o ' . htmlspecialchars(siteConfig('telefone')) : '' ?>.</p>
                            <a class="ct-btn-sec" href="contactos.php#formulario"><i class="bi bi-pencil" aria-hidden="true"></i> <?= te('contactos.formulario.sucesso_botao') ?></a>
                        </div>
                    <?php else: ?>
                        <h2 id="ctFormTitulo"><?= te('contactos.formulario.titulo') ?></h2>
                        <p><?= te('contactos.formulario.intro') ?></p>

                        <?php if ($errosForm): ?>
                            <div class="ct-aviso ct-aviso--erro" role="alert" tabindex="-1" id="ctErros">
                                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                                <div><strong>Verifique o formulário:</strong> <?= htmlspecialchars($errosForm['geral'] ?? (count($errosForm) === 1 ? reset($errosForm) : 'há ' . count($errosForm) . ' campos por corrigir.')) ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="contactos.php#formulario" novalidate>
                            <input type="hidden" name="form_contacto" value="1">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_contacto']) ?>">
                            <input type="hidden" name="t" value="<?= time() ?>">
                            <div style="position:absolute;left:-9999px;" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                            <div class="ct-campos">
                                <div class="ct-campo">
                                    <label for="ctNome">Nome</label>
                                    <input id="ctNome" name="nome" type="text" required autocomplete="name" maxlength="150" value="<?= htmlspecialchars($dadosForm['nome']) ?>"<?= $campoErro('nome') ?>>
                                    <?php if (isset($errosForm['nome'])): ?><span class="ct-erro" id="erro-nome"><?= $errosForm['nome'] ?></span><?php endif; ?>
                                </div>
                                <div class="ct-campo">
                                    <label for="ctEmail">Email</label>
                                    <input id="ctEmail" name="email" type="email" required autocomplete="email" maxlength="190" value="<?= htmlspecialchars($dadosForm['email']) ?>"<?= $campoErro('email') ?>>
                                    <?php if (isset($errosForm['email'])): ?><span class="ct-erro" id="erro-email"><?= $errosForm['email'] ?></span><?php endif; ?>
                                </div>
                                <div class="ct-campo ct-campo--total">
                                    <label for="ctTelefone">Telefone <span class="opc">(opcional)</span></label>
                                    <input id="ctTelefone" name="telefone" type="tel" inputmode="tel" autocomplete="tel" maxlength="20" value="<?= htmlspecialchars($dadosForm['telefone']) ?>"<?= $campoErro('telefone') ?>>
                                    <?php if (isset($errosForm['telefone'])): ?><span class="ct-erro" id="erro-telefone"><?= $errosForm['telefone'] ?></span><?php endif; ?>
                                </div>
                                <fieldset class="ct-campo ct-campo--total ct-motivos"<?= isset($errosForm['assunto']) ? ' aria-describedby="erro-assunto"' : '' ?>>
                                    <legend><?= te('contactos.formulario.campo_motivo') ?></legend>
                                    <?php foreach ($assuntosContacto as $i => $a): ?>
                                        <label class="ct-motivo"><input type="radio" name="assunto" value="<?= htmlspecialchars($a) ?>" <?= $dadosForm['assunto'] === $a ? 'checked' : '' ?> required><span><?= htmlspecialchars($a) ?></span></label>
                                    <?php endforeach; ?>
                                    <?php if (isset($errosForm['assunto'])): ?><span class="ct-erro" id="erro-assunto" style="width:100%"><?= $errosForm['assunto'] ?></span><?php endif; ?>
                                </fieldset>
                                <div class="ct-campo ct-campo--total">
                                    <label for="ctMensagem">Mensagem</label>
                                    <textarea id="ctMensagem" name="mensagem" required maxlength="4000" placeholder="<?= te('contactos.formulario.ph_mensagem') ?>"<?= $campoErro('mensagem') ?>><?= htmlspecialchars($dadosForm['mensagem']) ?></textarea>
                                    <span class="ct-contador" aria-live="polite" data-contador-de="ctMensagem"></span>
                                    <?php if (isset($errosForm['mensagem'])): ?><span class="ct-erro" id="erro-mensagem"><?= $errosForm['mensagem'] ?></span><?php endif; ?>
                                </div>
                                <div class="ct-campo ct-campo--total">
                                    <label class="ct-consent">
                                        <input type="checkbox" name="consentimento" value="1" required <?= !empty($_POST['consentimento']) ? 'checked' : '' ?><?= $campoErro('consentimento') ?>>
                                        <span><?= te('contactos.formulario.consentimento') ?>
                                        <a href="/politica-privacidade.php" target="_blank" rel="noopener">Política de Privacidade</a>.</span>
                                    </label>
                                    <?php if (isset($errosForm['consentimento'])): ?><span class="ct-erro" id="erro-consentimento"><?= $errosForm['consentimento'] ?></span><?php endif; ?>
                                </div>
                            </div>
                            <div class="ct-acoes-form">
                                <button type="submit" class="ct-enviar"><?= te('contactos.formulario.botao') ?> <i class="bi bi-send-fill" aria-hidden="true"></i></button>
                                <small><?= te('contactos.formulario.nota') ?></small>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <aside class="ct-lado" aria-label="Outras formas de contacto">
                    <div class="ct-cartao">
                        <?php
                        // Aberto agora? Calculado a partir do horário publicado em Configurações (includes/hoje.php) —
                        // até 15/10/2026 as horas estavam escritas aqui no código e não acompanhavam o horário do admin
                        require_once __DIR__ . '/includes/hoje.php';
                        $horarioCt = horarioJunta();
                        $abertoAgora = $horarioCt ? estadoAtendimento($horarioCt)['aberto'] : false;
                        ?>
                        <h3><i class="bi bi-building" aria-hidden="true"></i> <?= htmlspecialchars($configContactos['bloco_titulo']) ?></h3>
                        <span class="ct-aberto <?= $abertoAgora ? 'ct-aberto--sim' : 'ct-aberto--nao' ?>"><?= $abertoAgora ? 'Aberto agora' : 'Fechado neste momento' ?></span>
                        <ul class="ct-horario-lista">
                            <li><i class="bi bi-clock" aria-hidden="true"></i> <span><?= txt($configContactos['horario_texto']) ?></span></li>
                            <?php if (siteConfig('morada', '')): ?><li><i class="bi bi-geo-alt" aria-hidden="true"></i> <span><?= htmlspecialchars(siteConfig('morada')) ?></span></li><?php endif; ?>
                        </ul>
                    </div>
                    <div class="ct-cartao ct-cartao--escuro">
                        <h3><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> <?= te('contactos.cartoes.ocorrencia_titulo') ?></h3>
                        <p><?= te('contactos.cartoes.ocorrencia_texto') ?></p>
                        <a class="ct-btn-sec" href="/pedidos.php"><?= te('contactos.cartoes.ocorrencia_botao') ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="ct-cartao">
                        <h3><i class="bi bi-shield-lock" aria-hidden="true"></i> <?= te('contactos.cartoes.denuncias_titulo') ?></h3>
                        <p>Para denúncias confidenciais use o <a href="/canal-denuncias.php">Canal de Denúncias</a>. Reclamações formais: <a href="/livro-reclamacoes.php">Livro de Reclamações</a>.</p>
                    </div>
                </aside>
            </div>
        </section>

        <!-- 3. Mapa -->
        <section class="ct-mapa-sec" id="mapa" aria-labelledby="ctMapaTitulo">
            <div class="ct-titulo">
                <div>
                    <span class="ct-kicker"><?= te('contactos.mapa.kicker') ?></span>
                    <h2 id="ctMapaTitulo"><?= te('contactos.mapa.titulo') ?></h2>
                    <p><?= te('contactos.mapa.intro') ?></p>
                </div>
            </div>
            <?php /* Filtros no topo do mapa (12/10/2026): cada categoria liga/desliga com um clique; a seta
               abre os subfiltros (tipos). Estado anunciado em texto («A mostrar N de M locais»). */ ?>
            <div class="ct-mfiltros" role="group" aria-label="Filtrar o mapa">
                <?php foreach ($tiposMapa as $t => [$rot, $cor, $ic]): if (empty($nTipo[$t])) continue; $subsT = $subsMapa[$t] ?? []; ?>
                <div class="ct-mfiltro" style="--c:<?= $cor ?>" data-grupo-mapa="<?= $t ?>">
                    <button type="button" class="ct-mfiltro__btn" data-filtro-mapa="<?= $t ?>" aria-pressed="true">
                        <span class="ct-mfiltro__icone" aria-hidden="true"><i class="bi <?= $ic ?>"></i></span>
                        <span><?= $rot ?></span> <b data-conta-mapa="<?= $t ?>"><?= $nTipo[$t] ?></b>
                    </button>
                    <?php if (count($subsT) > 1): ?>
                    <button type="button" class="ct-mfiltro__mais" aria-expanded="false" aria-controls="ctSub-<?= $t ?>" aria-label="Mais filtros de <?= $rot ?>"><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                    <div class="ct-msub" id="ctSub-<?= $t ?>" role="group" aria-label="Tipos de <?= mb_strtolower($rot) ?>" hidden>
                        <p class="ct-msub__titulo"><?= $rot ?></p>
                        <?php foreach ($subsT as $s => $q): ?>
                        <label class="ct-msub__op"><input type="checkbox" data-sub-mapa="<?= $t ?>" value="<?= htmlspecialchars($s) ?>" checked><span><?= htmlspecialchars($s) ?></span><b><?= $q ?></b></label>
                        <?php endforeach; ?>
                        <div class="ct-msub__acoes">
                            <button type="button" data-sub-todos="<?= $t ?>">Todos</button>
                            <button type="button" data-sub-nenhum="<?= $t ?>">Nenhum</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <button type="button" class="ct-mfiltros__repor" data-mapa-repor hidden><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Mostrar tudo</button>
            </div>
            <p class="ct-mfiltros__estado" data-mapa-estado aria-live="polite"></p>
            <div class="ct-mapa-shell">
                <div class="ct-mapa-lado">
                    <ul class="ct-lista" aria-label="Locais no mapa">
                        <?php foreach ($locaisMapa as $i => $l): [, $corT, $icT] = $tiposMapa[$l['tipo']]; ?>
                            <li data-tipo="<?= $l['tipo'] ?>" data-local-li="<?= $i ?>">
                                <button type="button" data-local="<?= $i ?>">
                                    <span class="mini" aria-hidden="true" style="<?= $l['img'] ? "background-image:url('" . htmlspecialchars($l['img'], ENT_QUOTES) . "')" : 'background:' . $corT ?>"><?= $l['img'] ? '' : '<i class="bi ' . $icT . '"></i>' ?></span>
                                    <span><strong><?= htmlspecialchars($l['nome']) ?></strong><small><?= htmlspecialchars($l['morada'] ?: $l['extra']) ?></small></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                        <li class="ct-lista__vazio" data-mapa-vazio hidden><?= te('contactos.mapa.vazio') ?> <button type="button" data-mapa-repor>Mostrar tudo</button></li>
                    </ul>
                </div>
                <div style="position:relative;min-width:0;">
                    <div id="ctMapa" role="region" aria-label="Mapa interativo da freguesia de <?= htmlspecialchars(nomeFreguesia()) ?>. A lista ao lado tem os mesmos locais."></div>
                    <div class="ct-mapa-acoes">
                        <button type="button" id="ctMapaTudo" aria-label="Ver a freguesia toda" title="Ver a freguesia toda"><i class="bi bi-arrows-angle-contract" aria-hidden="true"></i></button>
                        <button type="button" id="ctMapaJunta" aria-label="Centrar na sede da Junta" title="Centrar na sede da Junta"><i class="bi bi-bank2" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>
            <p class="ct-mapa-nota"><?= te('contactos.mapa.nota') ?></p>
        </section>

        <!-- 4. Contactos úteis (componente partilhado com utilidades.php: includes/utilidades.php) -->
        <?php seccaoContactosUteis($contactos, 'ctUteisTitulo'); ?>

    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var reduz = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* --- formulário: foco no resumo de erros / sucesso, contador de caracteres --- */
    var foco = document.getElementById('ctErros') || document.getElementById('ctSucesso');
    if (foco) foco.focus();
    var ta = document.getElementById('ctMensagem'), cont = document.querySelector('[data-contador-de="ctMensagem"]');
    if (ta && cont) { var upd = function () { cont.textContent = ta.value.length + ' / 4000'; }; ta.addEventListener('input', upd); upd(); }

    /* --- mapa --- */
    if (typeof L === 'undefined' || !document.getElementById('ctMapa')) return;
    var locais = <?= json_encode($locaisMapa, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
    var limite = <?= geojsonFreguesiaJs() ?>;
    var escuro = document.documentElement.getAttribute('data-tema') === 'escuro';

    var mapa = L.map('ctMapa', { scrollWheelZoom: false, zoomControl: false, attributionControl: true })
        .setView([<?= $centro['lat'] ?>, <?= $centro['lng'] ?>], <?= $centro['zoom'] ?>);
    L.control.zoom({ position: 'bottomright' }).addTo(mapa);
    // base: OpenStreetMap (livre, sem chave). No modo escuro o CSS escurece os mosaicos.
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(mapa);
    // o scroll só faz zoom depois de clicar no mapa (não "prende" quem está a descer a página)
    mapa.on('click', function () { mapa.scrollWheelZoom.enable(); });
    mapa.on('mouseout', function () { mapa.scrollWheelZoom.disable(); });

    // limite da freguesia: brilho por baixo + traço tracejado por cima
    var corP = getComputedStyle(document.documentElement).getPropertyValue('--cor-principal').trim() || '#355F3A';
    var limiteCamada = null;
    if (limite && limite.features && limite.features.length) {
        L.geoJSON(limite, { interactive: false, style: { color: corP, weight: 10, opacity: .14, fillOpacity: 0 } }).addTo(mapa);
        limiteCamada = L.geoJSON(limite, { interactive: false, style: { color: corP, weight: 2.5, dashArray: '8 6', fillColor: corP, fillOpacity: .06 } }).addTo(mapa);
    }

    // cores/ícones das categorias vêm do PHP ($tiposMapa); "var(--x)" resolve-se aqui para o SVG do pino
    var tipos = <?= json_encode($tiposMapa, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var cssRaiz = getComputedStyle(document.documentElement);
    var cores = {}, icones = {};
    Object.keys(tipos).forEach(function (t) {
        var c = tipos[t][1], v = /^var\((--[\w-]+)\)$/.exec(c);
        cores[t] = v ? (cssRaiz.getPropertyValue(v[1]).trim() || corP) : c;
        icones[t] = tipos[t][2];
    });
    function pin(tipo) {
        var cor = cores[tipo], g = tipo === 'junta';
        return L.divIcon({
            className: '', iconSize: g ? [48, 62] : [40, 52], iconAnchor: g ? [24, 60] : [20, 50], popupAnchor: [0, g ? -54 : -44],
            html: '<div class="ct-pin' + (g ? ' ct-pin--junta' : '') + '"><svg viewBox="0 0 40 52" width="100%" height="100%"><path d="M20 51s17-17.6 17-30.5C37 10 29.4 2 20 2S3 10 3 20.5C3 33.4 20 51 20 51z" fill="' + cor + '" stroke="#fff" stroke-width="2.5"/></svg><i class="bi ' + icones[tipo] + '"></i></div>'
        });
    }
    function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    var camada = L.layerGroup().addTo(mapa), marcadores = [];
    locais.forEach(function (l, i) {
        var dir = 'https://www.google.com/maps/dir/?api=1&destination=' + l.lat + ',' + l.lng;
        var html = (l.img ? '<div class="ct-pop-img" style="background-image:url(\'' + encodeURI(l.img) + '\')"></div>' : '') +
            '<div class="ct-pop-corpo"><span class="ct-pop-tipo">' + esc(l.tipo === 'ponto' ? l.sub : (l.tipo === 'junta' ? l.sub : l.extra)) + '</span><h4>' + esc(l.nome) + '</h4>' +
            (l.morada ? '<p>' + esc(l.morada) + '</p>' : '') +
            '<div class="ct-pop-acoes"><a href="' + dir + '" target="_blank" rel="noopener"><i class="bi bi-signpost-2-fill"></i> Como chegar</a>' +
            (l.link ? '<a class="sec" href="' + l.link + '">Saber mais</a>' : '') + '</div></div>';
        var m = L.marker([l.lat, l.lng], { icon: pin(l.tipo), title: l.nome, riseOnHover: true, zIndexOffset: l.tipo === 'junta' ? 1000 : 0 })
            .bindPopup(html, { className: 'ct-popup', maxWidth: 260, minWidth: 260 })
            .addTo(camada);
        m.on('popupopen', function () { marcarLista(i); });
        marcadores[i] = m;
    });

    function enquadrar() {
        var pts = marcadores.filter(function (m) { return camada.hasLayer(m); }).map(function (m) { return m.getLatLng(); });
        if (limiteCamada) mapa.flyToBounds(limiteCamada.getBounds(), { padding: [30, 30], duration: reduz ? 0 : .8 });
        else if (pts.length) mapa.flyToBounds(L.latLngBounds(pts), { padding: [50, 50], maxZoom: 16, duration: reduz ? 0 : .8 });
    }
    // vista inicial: todos os locais (a vila e a Serra de São Miguel), não a freguesia inteira
    var vila = locais.map(function (l) { return [l.lat, l.lng]; });
    if (vila.length) mapa.fitBounds(L.latLngBounds(vila), { padding: [50, 50], maxZoom: 15 });
    else if (limiteCamada && limiteCamada.getBounds().isValid()) mapa.fitBounds(limiteCamada.getBounds());

    function marcarLista(i) {
        document.querySelectorAll('.ct-lista button[data-local]').forEach(function (b) { b.classList.toggle('ativo', +b.dataset.local === i); });
        var b = document.querySelector('.ct-lista button[data-local="' + i + '"]');
        if (b) b.scrollIntoView({ block: 'nearest', behavior: reduz ? 'auto' : 'smooth' });
    }
    document.querySelectorAll('.ct-lista button[data-local]').forEach(function (b) {
        b.addEventListener('click', function () {
            var i = +b.dataset.local, m = marcadores[i];
            mapa.flyTo(m.getLatLng(), Math.max(mapa.getZoom(), 17), { duration: reduz ? 0 : .7 });
            setTimeout(function () { m.openPopup(); }, reduz ? 0 : 720);
        });
    });
    /* --- filtros: categoria (liga/desliga) + subfiltros (tipos) ---
       Um local aparece se a categoria estiver ligada E o seu tipo estiver marcado. */
    var ligado = {}, desmarcado = {};
    Object.keys(tipos).forEach(function (t) { ligado[t] = true; desmarcado[t] = {}; });
    var estadoEl = document.querySelector('[data-mapa-estado]');
    function visivel(i) { var l = locais[i]; return ligado[l.tipo] && !desmarcado[l.tipo][l.sub]; }
    function aplicar(enquadrarVisiveis) {
        var n = 0, total = locais.length, porTipo = {}, pts = [];
        marcadores.forEach(function (m, i) {
            var v = visivel(i), t = locais[i].tipo;
            if (v) { n++; porTipo[t] = (porTipo[t] || 0) + 1; pts.push(m.getLatLng()); }
            if (v && !camada.hasLayer(m)) camada.addLayer(m);
            else if (!v && camada.hasLayer(m)) { m.closePopup(); camada.removeLayer(m); }
            var li = document.querySelector('[data-local-li="' + i + '"]');
            if (li) li.hidden = !v;
        });
        document.querySelectorAll('[data-grupo-mapa]').forEach(function (g) {
            var t = g.dataset.grupoMapa, totalT = locais.filter(function (l) { return l.tipo === t; }).length, vis = porTipo[t] || 0;
            g.querySelector('[data-filtro-mapa]').setAttribute('aria-pressed', ligado[t] && vis ? 'true' : 'false');
            g.classList.toggle('ct-mfiltro--parcial', ligado[t] && vis > 0 && vis < totalT);
            g.querySelector('[data-conta-mapa]').textContent = ligado[t] && vis < totalT && vis > 0 ? vis + '/' + totalT : totalT;
        });
        document.querySelectorAll('[data-mapa-repor]').forEach(function (b) { b.hidden = n === total; });
        document.querySelector('[data-mapa-vazio]').hidden = n > 0;
        if (estadoEl) estadoEl.textContent = n === total ? '' : 'A mostrar ' + n + ' de ' + total + ' locais';
        // o mapa acompanha o filtro: enquadra os locais que ficaram visíveis
        if (enquadrarVisiveis && pts.length) mapa.flyToBounds(L.latLngBounds(pts), { padding: [60, 60], maxZoom: 16, duration: reduz ? 0 : .8 });
    }
    function marcarSubs(t, valor) {
        desmarcado[t] = {};
        document.querySelectorAll('[data-sub-mapa="' + t + '"]').forEach(function (c) { c.checked = valor; if (!valor) desmarcado[t][c.value] = true; });
    }
    document.querySelectorAll('[data-filtro-mapa]').forEach(function (b) {
        b.addEventListener('click', function () {
            var t = b.dataset.filtroMapa, on = b.getAttribute('aria-pressed') !== 'true';
            ligado[t] = on;
            // ligar uma categoria com todos os tipos desmarcados volta a marcá-los
            if (on && locais.every(function (l) { return l.tipo !== t || desmarcado[t][l.sub]; })) marcarSubs(t, true);
            aplicar(true);
        });
    });
    document.querySelectorAll('[data-sub-mapa]').forEach(function (c) {
        c.addEventListener('change', function () {
            var t = c.dataset.subMapa;
            if (c.checked) { delete desmarcado[t][c.value]; ligado[t] = true; } else desmarcado[t][c.value] = true;
            aplicar(true);
        });
    });
    document.querySelectorAll('[data-sub-todos], [data-sub-nenhum]').forEach(function (b) {
        b.addEventListener('click', function () {
            var t = b.dataset.subTodos || b.dataset.subNenhum, todos = !!b.dataset.subTodos;
            marcarSubs(t, todos);
            if (todos) ligado[t] = true;
            aplicar(true);
        });
    });
    document.querySelectorAll('[data-mapa-repor]').forEach(function (b) {
        b.addEventListener('click', function () {
            Object.keys(tipos).forEach(function (t) { ligado[t] = true; marcarSubs(t, true); });
            fecharSubs();
            aplicar(true);
        });
    });

    // painéis de subfiltros: um aberto de cada vez; fecham com Esc ou ao clicar fora
    function fecharSubs(exceto) {
        document.querySelectorAll('.ct-mfiltro__mais[aria-expanded="true"]').forEach(function (b) {
            if (b === exceto) return;
            b.setAttribute('aria-expanded', 'false');
            document.getElementById(b.getAttribute('aria-controls')).hidden = true;
        });
    }
    document.querySelectorAll('.ct-mfiltro__mais').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.stopPropagation();
            var painel = document.getElementById(b.getAttribute('aria-controls')), abrir = b.getAttribute('aria-expanded') !== 'true';
            fecharSubs(b);
            b.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            painel.hidden = !abrir;
            if (abrir) {
                // não deixar o painel sair do ecrã nos filtros das pontas (telemóvel)
                painel.style.marginLeft = '0px';
                var r = painel.getBoundingClientRect(), folga = 12;
                if (r.left < folga) painel.style.marginLeft = (folga - r.left) + 'px';
                else if (r.right > innerWidth - folga) painel.style.marginLeft = (innerWidth - folga - r.right) + 'px';
                var primeiro = painel.querySelector('input');
                if (primeiro) primeiro.focus();
            }
        });
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.ct-msub')) fecharSubs(); });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var aberto = document.querySelector('.ct-mfiltro__mais[aria-expanded="true"]');
        if (aberto) { fecharSubs(); aberto.focus(); }
    });
    aplicar(false);
    document.getElementById('ctMapaTudo').addEventListener('click', enquadrar);
    document.getElementById('ctMapaJunta').addEventListener('click', function () {
        var i = locais.findIndex(function (l) { return l.tipo === 'junta'; });
        if (i >= 0) { mapa.flyTo(marcadores[i].getLatLng(), 17, { duration: reduz ? 0 : .7 }); setTimeout(function () { marcadores[i].openPopup(); }, reduz ? 0 : 720); }
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
