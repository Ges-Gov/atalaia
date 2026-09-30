<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$pagina = $pdo->query("SELECT * FROM pagina_historia ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$ok = isset($_GET["ok"]);

function uploadHistoriaHero($campo, $atual = "") {

    if (empty($_FILES[$campo]["name"])) {
        return $atual;
    }

    $permitidas = ["jpg","jpeg","png","webp"];
    $ext = strtolower(pathinfo($_FILES[$campo]["name"], PATHINFO_EXTENSION));

    if (!in_array($ext, $permitidas)) {
        return $atual;
    }

    if (!is_dir("../assets/img")) {
        mkdir("../assets/img", 0777, true);
    }

    $nome = "historia-" . time() . "." . $ext;

    move_uploaded_file(
        $_FILES[$campo]["tmp_name"],
        "../assets/img/" . $nome
    );

    return $nome;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $heroImagem = uploadHistoriaHero(
        "hero_imagem",
        $pagina["hero_imagem"] ?? ""
    );

    $stmt = $pdo->prepare("
        UPDATE pagina_historia SET
            hero_kicker = ?,
            hero_titulo = ?,
            hero_subtitulo = ?,
            hero_imagem = ?,
            intro_titulo = ?,
            intro_texto = ?,
            bloco1_titulo = ?,
            bloco1_texto = ?,
            bloco2_titulo = ?,
            bloco2_texto = ?,
            timeline_titulo = ?,
            timeline_texto = ?,
            galeria_titulo = ?,
            botao1_texto = ?,
            botao1_link = ?,
            botao2_texto = ?,
            botao2_link = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST["hero_kicker"] ?? "",
        $_POST["hero_titulo"] ?? "",
        $_POST["hero_subtitulo"] ?? "",
        $heroImagem,
        $_POST["intro_titulo"] ?? "",
        $_POST["intro_texto"] ?? "",
        $_POST["bloco1_titulo"] ?? "",
        $_POST["bloco1_texto"] ?? "",
        $_POST["bloco2_titulo"] ?? "",
        $_POST["bloco2_texto"] ?? "",
        $_POST["timeline_titulo"] ?? "",
        $_POST["timeline_texto"] ?? "",
        $_POST["galeria_titulo"] ?? "",
        $_POST["botao1_texto"] ?? "",
        $_POST["botao1_link"] ?? "",
        $_POST["botao2_texto"] ?? "",
        $_POST["botao2_link"] ?? "",
        (int)$pagina["id"]
    ]);

    header("Location: historia.php?ok=1");
    exit;
}

require_once "includes/header.php";
?>

<style>

.historia-admin{
    padding:28px;
}

.historia-admin-hero{
    background:linear-gradient(135deg,#242A30,#11151B);
    color:white;
    border-radius:30px;
    padding:34px;
    margin-bottom:24px;
    box-shadow:0 20px 45px rgba(0,0,0,.15);
}

.historia-admin-hero span{
    color:#D4AA00;
    font-size:13px;
    font-weight:900;
    text-transform:uppercase;
}

.historia-admin-hero h1{
    margin:10px 0;
    font-size:38px;
}

.historia-admin-card{
    background:white;
    border-radius:24px;
    padding:28px;
    margin-bottom:22px;
    box-shadow:0 16px 40px rgba(0,0,0,.08);
}

.historia-admin-card h2{
    margin:0 0 18px;
    color:#11151B;
}

.historia-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

.historia-admin label{
    display:block;
    margin:14px 0 7px;
    font-weight:900;
    color:#11151B;
}

.historia-admin input,
.historia-admin textarea{
    width:100%;
    border:1px solid #dbe3ea;
    border-radius:14px;
    padding:13px 14px;
    background:#f8fafc;
    box-sizing:border-box;
}

.historia-admin textarea{
    min-height:160px;
    resize:vertical;
}

.historia-full{
    grid-column:span 2;
}

.historia-preview{
    width:100%;
    max-height:240px;
    object-fit:cover;
    border-radius:20px;
    margin-top:14px;
}

.historia-save{
    position:sticky;
    bottom:18px;
    background:rgba(255,255,255,.95);
    border:1px solid #e5e7eb;
    border-radius:20px;
    padding:16px;
    box-shadow:0 18px 40px rgba(0,0,0,.12);
    display:flex;
    justify-content:space-between;
    align-items:center;
    z-index:20;
}

.historia-btn{
    background:#242A30;
    color:white;
    border:0;
    padding:14px 22px;
    border-radius:14px;
    font-weight:900;
    cursor:pointer;
}

.historia-alert{
    background:#dcfce7;
    color:#166534;
    padding:14px 16px;
    border-radius:14px;
    margin-bottom:18px;
    font-weight:900;
}

@media(max-width:900px){

    .historia-grid{
        grid-template-columns:1fr;
    }

    .historia-full{
        grid-column:span 1;
    }

    .historia-save{
        flex-direction:column;
        gap:14px;
    }
}

</style>

<div class="historia-admin">

    <div class="historia-admin-hero">

        <span>Página pública</span>

        <h1>Editar História</h1>

        <p>Gerir os conteúdos da página História da freguesia.</p>

    </div>

    <?php if ($ok): ?>
        <div class="historia-alert">
            Página atualizada com sucesso.
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="historia-admin-card">

            <h2>Hero premium</h2>

            <div class="historia-grid">

                <div>
                    <label>Kicker</label>
                    <input type="text" name="hero_kicker" value="<?= htmlspecialchars($pagina["hero_kicker"] ?? "") ?>">
                </div>

                <div>
                    <label>Título principal</label>
                    <input type="text" name="hero_titulo" value="<?= htmlspecialchars($pagina["hero_titulo"] ?? "") ?>">
                </div>

                <div class="historia-full">
                    <label>Subtítulo</label>
                    <textarea name="hero_subtitulo"><?= htmlspecialchars($pagina["hero_subtitulo"] ?? "") ?></textarea>
                </div>

                <div class="historia-full">
                    <label>Imagem do hero</label>
                    <input type="file" name="hero_imagem">

                    <?php if (!empty($pagina["hero_imagem"])): ?>
                        <img src="../assets/img/<?= htmlspecialchars($pagina["hero_imagem"]) ?>" class="historia-preview">
                    <?php endif; ?>
                </div>

            </div>

        </div>

        <div class="historia-admin-card">

            <h2>Introdução</h2>

            <label>Título</label>
            <input type="text" name="intro_titulo" value="<?= htmlspecialchars($pagina["intro_titulo"] ?? "") ?>">

            <label>Texto</label>
            <textarea name="intro_texto"><?= htmlspecialchars($pagina["intro_texto"] ?? "") ?></textarea>

        </div>

        <div class="historia-admin-card">

            <h2>Blocos de conteúdo</h2>

            <div class="historia-grid">

                <div>
                    <label>Título bloco 1</label>
                    <input type="text" name="bloco1_titulo" value="<?= htmlspecialchars($pagina["bloco1_titulo"] ?? "") ?>">

                    <label>Texto bloco 1</label>
                    <textarea name="bloco1_texto"><?= htmlspecialchars($pagina["bloco1_texto"] ?? "") ?></textarea>
                </div>

                <div>
                    <label>Título bloco 2</label>
                    <input type="text" name="bloco2_titulo" value="<?= htmlspecialchars($pagina["bloco2_titulo"] ?? "") ?>">

                    <label>Texto bloco 2</label>
                    <textarea name="bloco2_texto"><?= htmlspecialchars($pagina["bloco2_texto"] ?? "") ?></textarea>
                </div>

            </div>

        </div>

        <div class="historia-admin-card">

            <h2>Linha temporal</h2>

            <label>Título timeline</label>
            <input type="text" name="timeline_titulo" value="<?= htmlspecialchars($pagina["timeline_titulo"] ?? "") ?>">

            <label>Timeline</label>
            <textarea name="timeline_texto"><?= htmlspecialchars($pagina["timeline_texto"] ?? "") ?></textarea>

        </div>

        <div class="historia-admin-card">

            <h2>Botões</h2>

            <div class="historia-grid">

                <div>
                    <label>Botão 1 texto</label>
                    <input type="text" name="botao1_texto" value="<?= htmlspecialchars($pagina["botao1_texto"] ?? "") ?>">
                </div>

                <div>
                    <label>Botão 1 link</label>
                    <input type="text" name="botao1_link" value="<?= htmlspecialchars($pagina["botao1_link"] ?? "") ?>">
                </div>

                <div>
                    <label>Botão 2 texto</label>
                    <input type="text" name="botao2_texto" value="<?= htmlspecialchars($pagina["botao2_texto"] ?? "") ?>">
                </div>

                <div>
                    <label>Botão 2 link</label>
                    <input type="text" name="botao2_link" value="<?= htmlspecialchars($pagina["botao2_link"] ?? "") ?>">
                </div>

            </div>

        </div>

        <div class="historia-save">

            <strong>Guardar alterações da página História</strong>

            <button type="submit" class="historia-btn">
                Guardar página
            </button>

        </div>

    </form>

</div>

<?php require_once "includes/footer.php"; ?>
