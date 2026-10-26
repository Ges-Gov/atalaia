<?php
$adminPageTitle = "Homepage";
$adminActive = "homepage";

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/config.php";   // db + nomeFreguesia()

$stmt = $pdo->query("SELECT * FROM homepage_config ORDER BY id ASC LIMIT 1");
$home = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$home) {
    // linha inicial neutra (13/10/2026)
    $pdo->prepare("
        INSERT INTO homepage_config
        (id, hero_titulo, hero_subtitulo, boasvindas_titulo, boasvindas_texto, cta_titulo, cta_texto, cta_botao_texto, cta_botao_link)
        VALUES (1, ?, ?, 'Bem-vindo', '', ?, ?, 'Contactar Junta', 'contactos.php')
    ")->execute([
        nomeFreguesia() . ' mais próxima dos cidadãos', 'Informação, serviços, documentos e património num único espaço digital.',
        nomeFreguesia() . ' mais próxima dos cidadãos', 'Informação, serviços, documentos e património num único espaço digital.',
    ]);

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
            mostrar_transparencia = ?,
            mostrar_proximidade = ?,
            mostrar_apoios = ?,
            mostrar_descobrir = ?,
            presidente_titulo = ?,
            presidente_mensagem = ?,
            mostrar_mensagem_presidente = ?,
            galeria_kicker = ?,
            galeria_titulo = ?
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
        isset($_POST['mostrar_transparencia']) ? 1 : 0,
        isset($_POST['mostrar_proximidade']) ? 1 : 0,
        isset($_POST['mostrar_apoios']) ? 1 : 0,
        isset($_POST['mostrar_descobrir']) ? 1 : 0,
        trim($_POST['presidente_titulo'] ?? ''),
        trim($_POST['presidente_mensagem'] ?? ''),
        isset($_POST['mostrar_mensagem_presidente']) ? 1 : 0,
        trim($_POST['galeria_kicker'] ?? '') ?: null,
        trim($_POST['galeria_titulo'] ?? '') ?: null,
        (int)($home['id'] ?? 1)
    ]);

    header("Location: homepage.php?ok=1");
    exit;
}

require_once __DIR__ . "/includes/header.php";
?>


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

        <h2>Galeria de fotos</h2>

        <label>Etiqueta por cima do título</label>
        <input type="text" name="galeria_kicker" value="<?= htmlspecialchars($home['galeria_kicker'] ?? '') ?>" placeholder="<?= htmlspecialchars(nomeFreguesia()) ?> em imagens">

        <label>Título da secção</label>
        <input type="text" name="galeria_titulo" value="<?= htmlspecialchars($home['galeria_titulo'] ?? '') ?>" placeholder="Uma freguesia com identidade">
        <small>As fotografias vêm dos álbuns da Galeria de fotos. Vazio = usa o texto de exemplo.</small>

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
        <p class="ajuda" style="margin:-4px 0 14px;color:#64748b">Pela ordem em que aparecem na página. A pesquisa e os atalhos aparecem sempre; a Mensagem do Presidente liga-se na secção dela, acima. Uma página inicial curta lê-se melhor — as secções desligadas continuam no site pelo menu.</p>

        <label>
            <input type="checkbox" name="mostrar_hero" <?= !empty($home['mostrar_hero']) ? 'checked' : '' ?>>
            Topo — slideshow / vídeo
        </label>

        <label>
            <input type="checkbox" name="mostrar_noticias_eventos" <?= !empty($home['mostrar_noticias_eventos']) ? 'checked' : '' ?>>
            Notícias e Agenda
        </label>

        <label>
            <input type="checkbox" name="mostrar_proximidade" <?= !empty($home['mostrar_proximidade']) ? 'checked' : '' ?>>
            «Juntos por <?= htmlspecialchars(nomeFreguesia()) ?>» — números, «O que precisa de fazer hoje?» e Voz do Freguês <small style="color:#64748b">(repete os atalhos do topo)</small>
        </label>

        <label>
            <input type="checkbox" name="mostrar_apoios" <?= !empty($home['mostrar_apoios']) ? 'checked' : '' ?>>
            «Perto de quem precisa» — apoios, saúde, ação social e educação
        </label>

        <label>
            <input type="checkbox" name="mostrar_boasvindas" <?= !empty($home['mostrar_boasvindas']) ? 'checked' : '' ?>>
            «Bem-vindo» com o painel de números <small style="color:#64748b">(desligado, as ligações dele aparecem no cartão do Presidente)</small>
        </label>

        <label>
            <input type="checkbox" name="mostrar_pontos" <?= !empty($home['mostrar_pontos']) ? 'checked' : '' ?>>
            Pontos de interesse
        </label>

        <label>
            <input type="checkbox" name="mostrar_descobrir" <?= !empty($home['mostrar_descobrir']) ? 'checked' : '' ?>>
            «Descobrir» — cartões de turismo (um fim de semana em …)
        </label>

        <label>
            <input type="checkbox" name="mostrar_mapa" <?= !empty($home['mostrar_mapa']) ? 'checked' : '' ?>>
            Faixa do mapa interativo <small style="color:#64748b">(desligado, o mapa fica como ligação no cabeçalho dos Pontos de interesse)</small>
        </label>

        <label>
            <input type="checkbox" name="mostrar_servicos" <?= !empty($home['mostrar_servicos']) ? 'checked' : '' ?>>
            «Serviços e informação útil» <small style="color:#64748b">(repete os atalhos do topo)</small>
        </label>

        <label>
            <input type="checkbox" name="mostrar_galeria" <?= !empty($home['mostrar_galeria']) ? 'checked' : '' ?>>
            Galeria de fotos (carrossel) <small style="color:#64748b">(desligado, a galeria fica como ligação no cabeçalho dos Pontos de interesse)</small>
        </label>

        <label>
            <input type="checkbox" name="mostrar_transparencia" <?= !empty($home['mostrar_transparencia']) ? 'checked' : '' ?>>
            «Contas à vista» — documentos de transparência <small style="color:#64748b">(já estão no Portal da Transparência)</small>
        </label>

        <label>
            <input type="checkbox" name="mostrar_cta_final" <?= !empty($home['mostrar_cta_final']) ? 'checked' : '' ?>>
            Chamada final «Precisa de tratar de um assunto…» <small style="color:#64748b">(repete os atalhos do topo)</small>
        </label>
        <br><br>

        <button type="submit" class="btn">
            Guardar homepage
        </button>

    </form>

</div>

<?php
require_once __DIR__ . "/includes/textos_bloco.php";
textosBloco('Títulos das secções da página inicial', [
    "Topo da página" => ['inicio.topo.etiqueta', 'inicio.topo.video_etiqueta', 'inicio.topo.video_ver', 'inicio.topo.video_qualidade', 'inicio.pesquisa.texto'],
    "Notícias" => ['inicio.noticias.kicker', 'inicio.noticias.titulo'],
    "Agenda" => ['inicio.agenda.kicker', 'inicio.agenda.titulo'],
    "Perto de quem precisa" => ['inicio.apoios.kicker', 'inicio.apoios.titulo', 'inicio.apoios.texto'],
    "Pontos de interesse" => ['inicio.pontos.kicker', 'inicio.pontos.titulo'],
    "Descobrir" => ['inicio.descobrir.kicker', 'inicio.descobrir.titulo', 'inicio.descobrir.texto'],
    "Mapa" => ['inicio.mapa.kicker', 'inicio.mapa.titulo', 'inicio.mapa.texto'],
    "Serviços" => ['inicio.servicos.kicker', 'inicio.servicos.titulo'],
    "Transparência" => ['inicio.transparencia.kicker', 'inicio.transparencia.titulo'],
    "Mensagem do Presidente" => ['inicio.presidente.kicker'],
    "Proximidade" => ['inicio.proximidade.kicker'],
], '/');
?>

<?php require_once __DIR__ . "/includes/footer.php"; ?>