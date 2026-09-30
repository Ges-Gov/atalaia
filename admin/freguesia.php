<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$erro = "";
$ok = isset($_GET["ok"]) ? "Conteúdo da página Freguesia atualizado com sucesso." : "";

$pagina = $pdo->query("SELECT * FROM pagina_freguesia ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$pagina) {
    $pdo->exec("
        INSERT INTO pagina_freguesia (
            hero_kicker,
            hero_titulo,
            hero_subtitulo,
            intro_titulo,
            intro_texto,
            historia_titulo,
            historia_texto,
            identidade_titulo,
            identidade_texto,
            patrimonio_titulo,
            patrimonio_texto,
            localidades_titulo,
            localidades_texto,
            galeria_titulo,
            botao1_texto,
            botao1_link,
            botao2_texto,
            botao2_link
        ) VALUES (
            'A Freguesia',
            'Atalaia e Alto Estanqueiro-Jardia',
            'Num monte sobranceiro ao estuário do Tejo, entre o Santuário da Atalaia e os campos do Alto Estanqueiro e da Jardia.',
            'Uma freguesia do Montijo',
            'A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia pertence ao concelho do Montijo, distrito de Setúbal. Tem 13,65 km² e 5379 habitantes (Censos 2021). Foi constituída pela Lei n.º 11-A/2013, de 28 de janeiro, que agregou as freguesias da Atalaia e do Alto Estanqueiro-Jardia.',
            'História e memória',
            'A cerca de quatro quilómetros da sede do município, a Atalaia beneficiou desde sempre da proximidade da Estrada Real que ligava Lisboa a Badajoz, por Aldeia Galega. Já no início do século XVI, as populações locais e dos arredores vinham aqui em peregrinação.',
            'Identidade',
            'O culto de Nossa Senhora da Atalaia, vivido por romeiros e festeiros, é o grande traço de identidade da freguesia.',
            'Património',
            'A Igreja de Nossa Senhora da Atalaia e os seus três cruzeiros foram classificados em 2009 como Imóveis de Interesse Público. Junto à escadaria do Santuário fica o Museu Agrícola da Atalaia.',
            'Localidades da freguesia',
            'Atalaia|Sede da freguesia, junto ao Santuário de Nossa Senhora da Atalaia\nAlto Estanqueiro|Onde fica a dependência da Junta\nJardia|Lugar de tradição hortícola',
            'A freguesia em imagens',
            'Ver pontos de interesse',
            '/pontos.php',
            'Explorar no mapa',
            '/mapa.php'
        )
    ");

    $pagina = $pdo->query("SELECT * FROM pagina_freguesia ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

function uploadHeroFreguesia($campo, $imagemAtual = "") {
    if (empty($_FILES[$campo]["name"])) {
        return $imagemAtual;
    }

    $permitidas = ["jpg", "jpeg", "png", "webp"];
    $ext = strtolower(pathinfo($_FILES[$campo]["name"], PATHINFO_EXTENSION));

    if (!in_array($ext, $permitidas)) {
        return $imagemAtual;
    }

    if (!is_dir("../assets/img")) {
        mkdir("../assets/img", 0777, true);
    }

    $base = pathinfo($_FILES[$campo]["name"], PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $base);
    $base = trim($base, "-");
    if ($base === "") {
        $base = "freguesia-hero";
    }

    $nome = $base . "-" . time() . "." . $ext;
    move_uploaded_file($_FILES[$campo]["tmp_name"], "../assets/img/" . $nome);

    return $nome;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $heroImagem = uploadHeroFreguesia("hero_imagem", $pagina["hero_imagem"] ?? "");

    $campos = [
        "hero_kicker",
        "hero_titulo",
        "hero_subtitulo",
        "intro_titulo",
        "intro_texto",
        "historia_titulo",
        "historia_texto",
        "identidade_titulo",
        "identidade_texto",
        "patrimonio_titulo",
        "patrimonio_texto",
        "localidades_titulo",
        "localidades_texto",
        "galeria_titulo",
        "botao1_texto",
        "botao1_link",
        "botao2_texto",
        "botao2_link"
    ];

    $dados = [];
    foreach ($campos as $campo) {
        $dados[$campo] = $_POST[$campo] ?? "";
    }

    $stmt = $pdo->prepare("
        UPDATE pagina_freguesia SET
            hero_kicker = ?,
            hero_titulo = ?,
            hero_subtitulo = ?,
            hero_imagem = ?,
            intro_titulo = ?,
            intro_texto = ?,
            historia_titulo = ?,
            historia_texto = ?,
            identidade_titulo = ?,
            identidade_texto = ?,
            patrimonio_titulo = ?,
            patrimonio_texto = ?,
            localidades_titulo = ?,
            localidades_texto = ?,
            galeria_titulo = ?,
            botao1_texto = ?,
            botao1_link = ?,
            botao2_texto = ?,
            botao2_link = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $dados["hero_kicker"],
        $dados["hero_titulo"],
        $dados["hero_subtitulo"],
        $heroImagem,
        $dados["intro_titulo"],
        $dados["intro_texto"],
        $dados["historia_titulo"],
        $dados["historia_texto"],
        $dados["identidade_titulo"],
        $dados["identidade_texto"],
        $dados["patrimonio_titulo"],
        $dados["patrimonio_texto"],
        $dados["localidades_titulo"],
        $dados["localidades_texto"],
        $dados["galeria_titulo"],
        $dados["botao1_texto"],
        $dados["botao1_link"],
        $dados["botao2_texto"],
        $dados["botao2_link"],
        (int)$pagina["id"]
    ]);

    header("Location: freguesia.php?ok=1");
    exit;
}

require_once "includes/header.php";
?>

<style>
.freguesia-admin-page{padding:28px;}
.freguesia-admin-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:28px;padding:32px;margin-bottom:24px;box-shadow:0 20px 45px rgba(0,0,0,.14);display:flex;justify-content:space-between;gap:24px;align-items:center;overflow:hidden;position:relative;}
.freguesia-admin-hero::after{content:"";position:absolute;width:420px;height:420px;border-radius:50%;right:-150px;top:-200px;background:rgba(255,255,255,.06);}
.freguesia-admin-hero>*{position:relative;z-index:2;}
.freguesia-admin-hero span{color:#D4AA00;text-transform:uppercase;font-size:13px;font-weight:900;letter-spacing:.8px;}
.freguesia-admin-hero h1{margin:8px 0;font-size:36px;}
.freguesia-admin-hero p{margin:0;color:#dbeafe;}
.freguesia-admin-card{background:white;border-radius:24px;padding:26px;box-shadow:0 16px 40px rgba(0,0,0,.08);border:1px solid #eef2f7;margin-bottom:22px;}
.freguesia-admin-card h2{margin:0 0 18px;color:#11151B;font-size:24px;}
.freguesia-admin-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.freguesia-admin-form label{display:block;font-weight:900;color:#11151B;margin:14px 0 7px;}
.freguesia-admin-form input,.freguesia-admin-form textarea{width:100%;border:1px solid #dbe3ea;border-radius:14px;padding:12px 14px;box-sizing:border-box;background:#f8fafc;font-size:15px;}
.freguesia-admin-form input{min-height:48px;}
.freguesia-admin-form textarea{min-height:145px;resize:vertical;}
.freguesia-admin-form input:focus,.freguesia-admin-form textarea:focus{outline:none;border-color:#242A30;background:white;box-shadow:0 0 0 4px rgba(36,42,50,.10);}
.freguesia-full{grid-column:span 2;}
.freguesia-preview{width:100%;max-height:260px;object-fit:cover;border-radius:20px;margin-top:12px;box-shadow:0 14px 34px rgba(0,0,0,.12);}
.freguesia-help{background:#f8fafc;border-left:5px solid #242A30;border-radius:16px;padding:16px;color:#475569;line-height:1.7;margin-top:12px;}
.freguesia-save-bar{position:sticky;bottom:18px;background:rgba(255,255,255,.92);backdrop-filter:blur(14px);border:1px solid #e5e7eb;border-radius:20px;padding:16px;box-shadow:0 18px 45px rgba(0,0,0,.14);display:flex;justify-content:space-between;gap:14px;align-items:center;z-index:20;}
.freguesia-btn-admin{border:0;background:#242A30;color:white;padding:14px 22px;border-radius:14px;font-weight:900;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:.25s;}
.freguesia-btn-admin:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(36,42,50,.25);}
.freguesia-btn-admin.secondary{background:#64748b;}
.freguesia-alert{padding:14px 16px;border-radius:14px;margin-bottom:18px;font-weight:900;background:#dcfce7;color:#166534;}
@media(max-width:900px){.freguesia-admin-grid{grid-template-columns:1fr}.freguesia-full{grid-column:span 1}.freguesia-admin-hero,.freguesia-save-bar{flex-direction:column;align-items:flex-start}}
</style>

<div class="freguesia-admin-page">
    <div class="freguesia-admin-hero">
        <div>
            <span>Página pública</span>
            <h1>Editar Freguesia</h1>
            <p>Gerir o conteúdo da página pública da freguesia.</p>
        </div>

        <a href="../freguesia.php" target="_blank" class="freguesia-btn-admin secondary">Ver página</a>
    </div>

    <?php if ($ok): ?>
        <div class="freguesia-alert"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="freguesia-admin-form">

        <div class="freguesia-admin-card">
            <h2>Hero premium</h2>

            <div class="freguesia-admin-grid">
                <div>
                    <label>Kicker</label>
                    <input type="text" name="hero_kicker" value="<?= htmlspecialchars($pagina["hero_kicker"] ?? "") ?>">
                </div>

                <div>
                    <label>Título principal</label>
                    <input type="text" name="hero_titulo" value="<?= htmlspecialchars($pagina["hero_titulo"] ?? "") ?>">
                </div>

                <div class="freguesia-full">
                    <label>Subtítulo</label>
                    <textarea name="hero_subtitulo"><?= htmlspecialchars($pagina["hero_subtitulo"] ?? "") ?></textarea>
                </div>

                <div class="freguesia-full">
                    <label>Imagem de fundo do hero</label>
                    <input type="file" name="hero_imagem" accept="image/*">

                    <?php if (!empty($pagina["hero_imagem"])): ?>
                        <img class="freguesia-preview" src="../assets/img/<?= htmlspecialchars($pagina["hero_imagem"]) ?>" alt="">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="freguesia-admin-card">
            <h2>Texto de apresentação</h2>

            <label>Título</label>
            <input type="text" name="intro_titulo" value="<?= htmlspecialchars($pagina["intro_titulo"] ?? "") ?>">

            <label>Texto</label>
            <textarea name="intro_texto"><?= htmlspecialchars($pagina["intro_texto"] ?? "") ?></textarea>
        </div>

        <div class="freguesia-admin-card">
            <h2>Blocos informativos</h2>

            <div class="freguesia-admin-grid">
                <div>
                    <label>Título História</label>
                    <input type="text" name="historia_titulo" value="<?= htmlspecialchars($pagina["historia_titulo"] ?? "") ?>">

                    <label>Texto História</label>
                    <textarea name="historia_texto"><?= htmlspecialchars($pagina["historia_texto"] ?? "") ?></textarea>
                </div>

                <div>
                    <label>Título Identidade rural</label>
                    <input type="text" name="identidade_titulo" value="<?= htmlspecialchars($pagina["identidade_titulo"] ?? "") ?>">

                    <label>Texto Identidade rural</label>
                    <textarea name="identidade_texto"><?= htmlspecialchars($pagina["identidade_texto"] ?? "") ?></textarea>
                </div>

                <div class="freguesia-full">
                    <label>Título Património e natureza</label>
                    <input type="text" name="patrimonio_titulo" value="<?= htmlspecialchars($pagina["patrimonio_titulo"] ?? "") ?>">

                    <label>Texto Património e natureza</label>
                    <textarea name="patrimonio_texto"><?= htmlspecialchars($pagina["patrimonio_texto"] ?? "") ?></textarea>
                </div>
            </div>
        </div>

        <div class="freguesia-admin-card">
            <h2>Localidades</h2>

            <label>Título da secção</label>
            <input type="text" name="localidades_titulo" value="<?= htmlspecialchars($pagina["localidades_titulo"] ?? "") ?>">

            <label>Localidades</label>
            <textarea name="localidades_texto" style="min-height:220px;"><?= htmlspecialchars($pagina["localidades_texto"] ?? "") ?></textarea>

            <div class="freguesia-help">
                Escreve uma localidade por linha neste formato:<br>
                <strong>Nome da localidade|Descrição da localidade</strong>
            </div>
        </div>

        <div class="freguesia-admin-card">
            <h2>Galeria e botões</h2>

            <div class="freguesia-admin-grid">
                <div class="freguesia-full">
                    <label>Título da galeria</label>
                    <input type="text" name="galeria_titulo" value="<?= htmlspecialchars($pagina["galeria_titulo"] ?? "") ?>">
                </div>

                <div>
                    <label>Botão 1 - Texto</label>
                    <input type="text" name="botao1_texto" value="<?= htmlspecialchars($pagina["botao1_texto"] ?? "") ?>">
                </div>

                <div>
                    <label>Botão 1 - Link</label>
                    <input type="text" name="botao1_link" value="<?= htmlspecialchars($pagina["botao1_link"] ?? "") ?>">
                </div>

                <div>
                    <label>Botão 2 - Texto</label>
                    <input type="text" name="botao2_texto" value="<?= htmlspecialchars($pagina["botao2_texto"] ?? "") ?>">
                </div>

                <div>
                    <label>Botão 2 - Link</label>
                    <input type="text" name="botao2_link" value="<?= htmlspecialchars($pagina["botao2_link"] ?? "") ?>">
                </div>
            </div>
        </div>

        <div class="freguesia-save-bar">
            <strong>Guardar alterações da página Freguesia</strong>

            <div>
                <a href="../freguesia.php" target="_blank" class="freguesia-btn-admin secondary">Pré-visualizar</a>
                <button type="submit" class="freguesia-btn-admin">Guardar página</button>
            </div>
        </div>

    </form>
</div>

<?php require_once "includes/footer.php"; ?>
