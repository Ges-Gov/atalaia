<?php
// Acrescenta conteúdo a uma BD que já está em uso — nunca apaga nada.
//
// Uso (na pasta do site):  php deploy/acrescentar_conteudo.php deploy/<ficheiro>.json [--simular]
//
// - "atualizar": UPDATE de valores (cores, tema); cria a linha do tema se ainda não existir
//                (com "so_se_existir": true só atualiza — ex.: o partido de um membro já existente).
// - "trocar_texto": substitui um texto por outro nas colunas indicadas (ex.: corrigir um nome).
// - "inserir":   cada linha só entra se ainda não houver outra com o mesmo valor na coluna "chave"
//                (ex.: o mesmo título) — correr duas vezes não duplica nada.
// - Colunas que não existam nesta BD são ignoradas (avisa), em vez de falhar.
// - Tudo numa transação; com --simular faz rollback no fim e só mostra o que faria.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$ficheiro = $argv[1] ?? '';
$simular  = in_array('--simular', $argv, true);
if ($ficheiro === '' || !is_file($ficheiro)) {
    fwrite(STDERR, "uso: php deploy/acrescentar_conteudo.php <conteudo.json> [--simular]\n");
    exit(1);
}

$cfg = require __DIR__ . '/../includes/db_config.php';
$pdo = new PDO("mysql:host={$cfg['host']};dbname={$cfg['db']};charset=utf8mb4", $cfg['user'], $cfg['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$c = json_decode(file_get_contents($ficheiro), true, 512, JSON_THROW_ON_ERROR);

$esquema = [];
foreach ($pdo->query("SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = DATABASE()") as $r) {
    $esquema[$r[0]][$r[1]] = true;
}
$avisos = [];
$tab = function (string $t) use ($esquema) {
    if (!isset($esquema[$t])) throw new RuntimeException("tabela desconhecida: $t");
    return "`$t`";
};
$col = function (string $t, string $k) use ($esquema) {
    if (!isset($esquema[$t][$k])) throw new RuntimeException("coluna desconhecida: $t.$k");
    return "`$k`";
};
$filtrar = function (string $t, array $linha) use ($esquema, &$avisos) {
    foreach (array_keys($linha) as $k) {
        if (!isset($esquema[$t][$k])) {
            $avisos["$t.$k"] = true;
            unset($linha[$k]);
        }
    }
    return $linha;
};
$correr = function (string $sql, array $params = []) use ($pdo) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st;
};
$inserir = function (string $t, array $linha) use ($correr, $tab, $col, $pdo) {
    $cols = array_map(fn($k) => $col($t, $k), array_keys($linha));
    $correr("INSERT INTO " . $tab($t) . " (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_fill(0, count($linha), '?')) . ")",
        array_values($linha));
    return (int)$pdo->lastInsertId();
};

$contagem = function () use ($correr, $esquema) {
    $out = [];
    foreach (['noticias', 'eventos', 'pontos_interesse', 'associacoes', 'comercio_local', 'contactos_uteis', 'galeria_albuns', 'galeria_imagens'] as $t) {
        if (isset($esquema[$t])) $out[$t] = (int)$correr("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    }
    return $out;
};
$antes = $contagem();
$novos = [];
$saltados = [];
$semPonto = [];

$pdo->beginTransaction();
try {
    foreach ($c['atualizar'] ?? [] as $op) {
        $t = $op['tabela'];
        $valores = $filtrar($t, $op['valores']);
        if (!$valores) continue;
        $sets = implode(', ', array_map(fn($k) => $col($t, $k) . " = ?", array_keys($valores)));
        $params = array_values($valores);
        $sql = "UPDATE " . $tab($t) . " SET $sets";
        if (!empty($op['onde'])) {
            [$k, $v] = $op['onde'];
            $existe = $correr("SELECT COUNT(*) FROM " . $tab($t) . " WHERE " . $col($t, $k) . " = ?", [$v])->fetchColumn();
            if (!$existe) {
                if (empty($op['so_se_existir'])) $inserir($t, $valores + [$k => $v]);   // "so_se_existir": nunca cria a linha
                continue;
            }
            $sql .= " WHERE " . $col($t, $k) . " = ?";
            $params[] = $v;
        }
        $correr($sql, $params);
    }

    // "trocar_texto": REPLACE(de → para) nas colunas indicadas (correções de nomes, ficheiros de imagem).
    foreach ($c['trocar_texto'] ?? [] as $op) {
        $t = $op['tabela'];
        if (!isset($esquema[$t])) continue;
        foreach ($op['colunas'] as $k) {
            if (!isset($esquema[$t][$k])) { $avisos["$t.$k"] = true; continue; }
            $n = $correr("UPDATE " . $tab($t) . " SET `$k` = REPLACE(`$k`, ?, ?) WHERE `$k` LIKE ?",
                [$op['de'], $op['para'], '%' . $op['de'] . '%'])->rowCount();
            if ($n) $novos["$t.$k («{$op['de']}» → «{$op['para']}»)"] = array_fill(0, $n, 0);
        }
    }

    foreach ($c['inserir'] ?? [] as $op) {
        $t = $op['tabela'];
        $chave = $op['chave'];
        $col($t, $chave);
        foreach ($op['linhas'] as $linha) {
            $extras = $linha['_imagens'] ?? [];
            unset($linha['_imagens']);
            // {"_id_de": ["tabela", "coluna", "valor"]} → id dessa linha (ex.: a eleição de um mandato).
            // Não se fixam ids no JSON: uma simulação (--simular) gasta números de AUTO_INCREMENT.
            foreach ($linha as $k => $v) {
                if (is_array($v) && isset($v['_id_de'])) {
                    [$rt, $rc, $rv] = $v['_id_de'];
                    $linha[$k] = $correr("SELECT id FROM " . $tab($rt) . " WHERE " . $col($rt, $rc) . " = ? ORDER BY id LIMIT 1", [$rv])->fetchColumn() ?: null;
                    if ($linha[$k] === null) throw new RuntimeException("$t: não encontrei $rt.$rc = «{$rv}»");
                }
            }
            $existe = $correr("SELECT COUNT(*) FROM " . $tab($t) . " WHERE LOWER(TRIM(`$chave`)) = LOWER(TRIM(?))", [$linha[$chave]])->fetchColumn();
            if ($existe) {
                $saltados[$t][] = $linha[$chave];
                continue;
            }
            $id = $inserir($t, $filtrar($t, $linha));
            $novos[$t][] = $id;
            if ($t === 'noticias' && $extras && isset($esquema['noticias_imagens'])) {
                foreach (array_values($extras) as $i => $f) {
                    $inserir('noticias_imagens', ['noticia_id' => $id, 'ficheiro' => $f, 'ordem' => $i + 1]);
                }
            }
        }
    }

    // Um álbum na galeria por cada ponto/evento novo com foto (o backoffice fá-lo sozinho ao gravar;
    // aqui os registos entram por script, por isso é preciso criá-lo).
    foreach (['pontos_interesse' => ['ponto', 'nome'], 'eventos' => ['evento', 'titulo']] as $t => [$origem, $campoNome]) {
        foreach ($novos[$t] ?? [] as $id) {
            $r = $correr("SELECT `$campoNome` AS nome, imagem FROM `$t` WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
            if (!$r || empty($r['imagem'])) continue;
            $ja = $correr("SELECT COUNT(*) FROM galeria_albuns WHERE origem = ? AND origem_id = ?", [$origem, $id])->fetchColumn();
            if ($ja) continue;
            $album = $inserir('galeria_albuns', ['nome' => $r['nome'], 'capa' => $r['imagem'], 'origem' => $origem,
                'origem_id' => $id, 'ordem' => 100 + $id, 'ativo' => 1]);
            $inserir('galeria_imagens', ['album_id' => $album, 'ficheiro' => $r['imagem'], 'titulo' => $r['nome'], 'ordem' => 1, 'ativo' => 1]);
        }
    }

    // "albuns_em_falta": o mesmo que a migração 012, para os pontos/eventos que entraram depois dela
    // sem passar pelo backoffice (carregados por script) e por isso não aparecem na Galeria.
    if (!empty($c['albuns_em_falta'])) {
        foreach (['ponto' => ['pontos_interesse', 'nome'], 'evento' => ['eventos', 'titulo']] as $origem => [$t, $campoNome]) {
            $correr("INSERT INTO galeria_albuns (nome, descricao, origem, origem_id, ordem, ativo)
                     SELECT x.`$campoNome`, NULL, ?, x.id, 0, 1 FROM `$t` x
                     WHERE x.imagem IS NOT NULL AND TRIM(x.imagem) <> ''
                       AND NOT EXISTS (SELECT 1 FROM galeria_albuns a WHERE a.origem = ? AND a.origem_id = x.id)", [$origem, $origem]);
            $correr("INSERT INTO galeria_imagens (album_id, ficheiro, titulo, ordem, ativo)
                     SELECT a.id, x.imagem, x.`$campoNome`, 1, 1 FROM galeria_albuns a JOIN `$t` x ON x.id = a.origem_id
                     WHERE a.origem = ? AND x.imagem IS NOT NULL AND TRIM(x.imagem) <> ''
                       AND NOT EXISTS (SELECT 1 FROM galeria_imagens gi WHERE gi.album_id = a.id)", [$origem]);
        }
        $correr("UPDATE galeria_albuns a SET a.capa = (SELECT gi.ficheiro FROM galeria_imagens gi WHERE gi.album_id = a.id
                 ORDER BY gi.ordem ASC, gi.id ASC LIMIT 1)
                 WHERE (a.capa IS NULL OR a.capa = '') AND EXISTS (SELECT 1 FROM galeria_imagens gi WHERE gi.album_id = a.id)");
    }

    // "fotos_album": [{"ponto": "<nome do ponto>"} ou {"album": "<nome>", "descricao": "..."}, "fotos": [{"ficheiro","titulo"}]]
    // Fotos extra para a Galeria (ficheiros em assets/img/galeria/). Com "ponto", vão para o álbum desse ponto
    // (criado se não existir); com "album", para um álbum manual com esse nome. Nunca repete um ficheiro no mesmo álbum.
    foreach ($c['fotos_album'] ?? [] as $op) {
        $albumId = null;
        if (!empty($op['ponto'])) {
            $p = $correr("SELECT id, nome, imagem FROM pontos_interesse WHERE LOWER(TRIM(nome)) = LOWER(TRIM(?)) LIMIT 1", [$op['ponto']])->fetch(PDO::FETCH_ASSOC);
            if (!$p) { $semPonto[] = $op['ponto']; continue; }
            $albumId = $correr("SELECT id FROM galeria_albuns WHERE origem = 'ponto' AND origem_id = ?", [$p['id']])->fetchColumn();
            if (!$albumId) {
                $albumId = $inserir('galeria_albuns', ['nome' => $p['nome'], 'capa' => $p['imagem'] ?: null, 'origem' => 'ponto',
                    'origem_id' => $p['id'], 'ordem' => 100 + $p['id'], 'ativo' => 1]);
                if (!empty($p['imagem'])) {
                    $inserir('galeria_imagens', ['album_id' => $albumId, 'ficheiro' => $p['imagem'], 'titulo' => $p['nome'], 'ordem' => 1, 'ativo' => 1]);
                }
            }
        } else {
            $albumId = $correr("SELECT id FROM galeria_albuns WHERE origem = 'manual' AND LOWER(TRIM(nome)) = LOWER(TRIM(?)) LIMIT 1", [$op['album']])->fetchColumn();
            if (!$albumId) {
                $albumId = $inserir('galeria_albuns', ['nome' => $op['album'], 'descricao' => $op['descricao'] ?? null,
                    'capa' => $op['fotos'][0]['ficheiro'] ?? null, 'origem' => 'manual', 'ordem' => 50, 'ativo' => 1]);
            }
        }
        $ordem = (int)$correr("SELECT COALESCE(MAX(ordem), 0) FROM galeria_imagens WHERE album_id = ?", [$albumId])->fetchColumn();
        foreach ($op['fotos'] as $f) {
            $ja = $correr("SELECT COUNT(*) FROM galeria_imagens WHERE album_id = ? AND ficheiro = ?", [$albumId, $f['ficheiro']])->fetchColumn();
            if ($ja) continue;
            $novos['galeria_imagens'][] = $inserir('galeria_imagens', ['album_id' => $albumId, 'ficheiro' => $f['ficheiro'],
                'titulo' => $f['titulo'] ?? null, 'ordem' => ++$ordem, 'ativo' => 1]);
        }
    }

    $depois = $contagem();
    $simular ? $pdo->rollBack() : $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ERRO (nada foi gravado): " . $e->getMessage() . "\n");
    exit(1);
}

echo $simular ? "SIMULAÇÃO — nada foi gravado\n\n" : "Gravado.\n\n";
printf("%-20s %6s %6s\n", 'tabela', 'antes', 'depois');
foreach ($antes as $t => $n) {
    printf("%-20s %6d %6d\n", $t, $n, $depois[$t]);
}
foreach ($novos as $t => $lista) {
    if (str_contains($t, '→')) echo "Corrigido: $t em " . count($lista) . " linha(s)\n";
}
foreach ($saltados as $t => $lista) {
    echo "\nJá existiam em $t (não duplicados): " . implode('; ', $lista) . "\n";
}
if ($semPonto) {
    echo "\nfotos_album — ponto não encontrado (fotos não juntadas): " . implode('; ', $semPonto) . "\n";
}
if ($avisos) {
    echo "\nColunas ignoradas (não existem nesta BD): " . implode(', ', array_keys($avisos)) . "\n";
}
