<?php
$adminPageTitle = "Homepage";
$adminActive = "homepage";

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

$stmt = $pdo->query("SELECT * FROM homepage_config ORDER BY id ASC LIMIT 1");
$home = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$home) {
    $pdo->query("
        INSERT INTO homepage_config 
        (id, hero_titulo, hero_subtitulo, boasvindas_titulo, boasvindas_texto, cta_titulo, cta_texto, cta_botao_texto, cta_botao_link)
        VALUES
        (1, 'Atalaia e Alto Estanqueiro-Jardia mais próxima dos cidadãos', 'Informação, serviços, documentos e património num único espaço digital.', 'Bem-vindo', 'A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia pertence ao concelho do Montijo.', 'Atalaia e Alto Estanqueiro-Jardia mais próxima dos cidadãos', 'Informação, serviços, documentos e património num único espaço digital.', 'Contactar Junta', 'contactos.php')
    ");

    $stmt = $pdo->query("SELECT * FROM homepage_config ORDER BY id ASC LIMIT 1");
    $home = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $pdo->prepare("
        UPDATE homepage_config SET
            hero_titulo = ?,
            hero_subtitulo = ?,
            boasvindas_titulo = ?,
            boasvindas_texto = ?,
            cta_titulo = ?,
            cta_texto = ?,
            cta_botao_texto = ?,
            cta_botao_link = ?,
            mostrar_hero = ?,
            mostrar_boasvindas = ?,
            mostrar_pontos = ?,
            mostrar_mapa = ?,
            mostrar_noticias_eventos = ?,
            mostrar_servicos = ?,
            mostrar_cta_final = ?,
            mostrar_galeria = ?,
            presidente_titulo = ?,
            presidente_mensagem = ?,
            mostrar_mensagem_presidente = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['hero_titulo'] ?? '',
        $_POST['hero_subtitulo'] ?? '',
        $_POST['boasvindas_titulo'] ?? '',
        $_POST['boasvindas_texto'] ?? '',
        $_POST['cta_titulo'] ?? '',
        $_POST['cta_texto'] ?? '',
        $_POST['cta_botao_texto'] ?? '',
        $_POST['cta_botao_link'] ?? '',
        isset($_POST['mostrar_hero']) ? 1 : 0,
        isset($_POST['mostrar_boasvindas']) ? 1 : 0,
        isset($_POST['mostrar_pontos']) ? 1 : 0,
        isset($_POST['mostrar_mapa']) ? 1 : 0,
        isset($_POST['mostrar_noticias_eventos']) ? 1 : 0,
        isset($_POST['mostrar_servicos']) ? 1 : 0,
        isset($_POST['mostrar_cta_final']) ? 1 : 0,
        isset($_POST['mostrar_galeria']) ? 1 : 0,
        trim($_POST['presidente_titulo'] ?? ''),
        trim($_POST['presidente_mensagem'] ?? ''),
        isset($_POST['mostrar_mensagem_presidente']) ? 1 : 0,
        (int)($home['id'] ?? 1)
    ]);

    header("Location: homepage.php?ok=1");
    exit;
}

require_once __DIR__ . "/includes/header.php";
?>

<div class="admin-topbar">
    <h1>Homepage</h1>
    <p>Gerir textos, chamadas de ação e secções visíveis da página inicial.</p>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="alerta-sucesso">
        Homepage atualizada com sucesso.
    </div>
<?php endif; ?>

<div class="table-box">

    <form method="POST">

        <h2>Hero / Destaque inicial</h2>

        <label>Título principal</label>
        <input type="text" name="hero_titulo" value="<?= htmlspecialchars($home['hero_titulo'] ?? '') ?>">

        <label>Subtítulo</label>
        <textarea name="hero_subtitulo"><?= htmlspecialchars($home['hero_subtitulo'] ?? '') ?></textarea>

        <hr>

        <h2>Boas-vindas</h2>

        <label>Título</label>
        <input type="text" name="boasvindas_titulo" value="<?= htmlspecialchars($home['boasvindas_titulo'] ?? '') ?>">

        <label>Texto</label>
        <textarea name="boasvindas_texto"><?= htmlspecialchars($home['boasvindas_texto'] ?? '') ?></textarea>


        <hr>

        <h2>Mensagem do Presidente</h2>

        <p style="color:#64748b;font-weight:700;margin:-4px 0 14px;">
            Aparece na página inicial, a seguir às Notícias e Eventos. A fotografia, o nome e o
            cargo vêm da secção <strong>Executivo</strong> (do membro com cargo de Presidente).
        </p>

        <label>Título da secção</label>
        <input type="text" name="presidente_titulo"
               value="<?= htmlspecialchars($home['presidente_titulo'] ?? '') ?>"
               placeholder="Mensagem do Presidente">

        <label>Mensagem</label>
        <textarea name="presidente_mensagem"
                  placeholder="Escreva aqui a mensagem do Presidente aos fregueses."><?= htmlspecialchars($home['presidente_mensagem'] ?? '') ?></textarea>
        <p style="color:#64748b;font-weight:700;margin:-10px 0 14px;font-size:13px;">
            A página inicial mostra os primeiros 450 caracteres. Se deixar em branco, é usada a
            biografia do Presidente (era o que acontecia antes de este campo existir).
        </p>

        <label>
            <input type="checkbox" name="mostrar_mensagem_presidente"
                   <?= !empty($home['mostrar_mensagem_presidente']) ? 'checked' : '' ?>>
            Mostrar a Mensagem do Presidente na página inicial
        </label>

        <hr>

        <h2>CTA Final</h2>

        <label>Título CTA</label>
        <input type="text" name="cta_titulo" value="<?= htmlspecialchars($home['cta_titulo'] ?? '') ?>">

        <label>Texto CTA</label>
        <textarea name="cta_texto"><?= htmlspecialchars($home['cta_texto'] ?? '') ?></textarea>

        <label>Texto do botão</label>
        <input type="text" name="cta_botao_texto" value="<?= htmlspecialchars($home['cta_botao_texto'] ?? '') ?>">

        <label>Link do botão</label>
        <input type="text" name="cta_botao_link" value="<?= htmlspecialchars($home['cta_botao_link'] ?? '') ?>">

        <hr>

        <h2>Secções visíveis</h2>

        <label>
            <input type="checkbox" name="mostrar_hero" <?= !empty($home['mostrar_hero']) ? 'checked' : '' ?>>
            Mostrar hero
        </label>

        <label>
            <input type="checkbox" name="mostrar_boasvindas" <?= !empty($home['mostrar_boasvindas']) ? 'checked' : '' ?>>
            Mostrar boas-vindas
        </label>

        <label>
            <input type="checkbox" name="mostrar_pontos" <?= !empty($home['mostrar_pontos']) ? 'checked' : '' ?>>
            Mostrar pontos de interesse
        </label>

        <label>
            <input type="checkbox" name="mostrar_mapa" <?= !empty($home['mostrar_mapa']) ? 'checked' : '' ?>>
            Mostrar mapa
        </label>

        <label>
            <input type="checkbox" name="mostrar_noticias_eventos" <?= !empty($home['mostrar_noticias_eventos']) ? 'checked' : '' ?>>
            Mostrar notícias e eventos
        </label>

        <label>
            <input type="checkbox" name="mostrar_servicos" <?= !empty($home['mostrar_servicos']) ? 'checked' : '' ?>>
            Mostrar serviços rápidos
        </label>

        <label>
            <input type="checkbox" name="mostrar_cta_final" <?= !empty($home['mostrar_cta_final']) ? 'checked' : '' ?>>
            Mostrar CTA final
        </label>

        <label>
            <input type="checkbox" name="mostrar_galeria" <?= !empty($home['mostrar_galeria']) ? 'checked' : '' ?>>
            Mostrar Galeria de Fotos (última secção da página inicial)
        </label>

        <br><br>

        <button type="submit" class="btn">
            Guardar homepage
        </button>

    </form>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>