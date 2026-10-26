<?php
$adminPageTitle = "Configurações Gerais";
$adminActive = "configuracoes";

require_once "../includes/db.php";

$stmt = $pdo->query("SELECT * FROM configuracoes_site LIMIT 1");
$configuracoes = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$configuracoes) {
    $pdo->query("
        INSERT INTO configuracoes_site 
        (nome_site, municipio, slogan, email, telefone, morada, cor_principal, cor_secundaria, footer, alertas_ativos) 
        VALUES 
        ('Junta de Freguesia', 'Município', 'Site oficial da freguesia', '', '', '', '#242A30', '#C8A02E', '© " . date('Y') . " Junta de Freguesia', 1)
    ");

    $stmt = $pdo->query("SELECT * FROM configuracoes_site LIMIT 1");
    $configuracoes = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cor_principal = $_POST['cor_principal'] ?? '#242A30';
    $cor_secundaria = $_POST['cor_secundaria'] ?? '#C8A02E';





















    $cor_principal = '#' . ltrim($cor_principal, '#');
    $cor_secundaria = '#' . ltrim($cor_secundaria, '#');

    $logo = $configuracoes['logo'] ?? null;

    if (!empty($_FILES['logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $logo = "logo_" . time() . "." . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], "../assets/img/" . $logo);
    }

    $stmt = $pdo->prepare("
        UPDATE configuracoes_site 
        SET 
            nome_site = ?,
            municipio = ?,
            slogan = ?,
            email = ?,
            telefone = ?,
            morada = ?,
            horario = ?,
            facebook = ?,
            instagram = ?,
            youtube = ?,
            x_twitter = ?,
            tiktok = ?,
            linkedin = ?,
            whatsapp = ?,
            email_notificacoes = ?,
            logo = ?,
            cor_principal = ?,
            cor_secundaria = ?,
            footer = ?,
            alertas_ativos = ?,
            dominio = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['nome_site'] ?? '',
        $_POST['municipio'] ?? '',
        $_POST['slogan'] ?? '',
        $_POST['email'] ?? '',
        $_POST['telefone'] ?? '',
        $_POST['morada'] ?? '',
        $_POST['horario'] ?? '',
        $_POST['facebook'] ?? '',
        $_POST['instagram'] ?? '',
        $_POST['email_notificacoes'] ?? '',
        $logo,
        $cor_principal,
        $cor_secundaria,
        $_POST['footer'] ?? '',
        isset($_POST['alertas_ativos']) ? 1 : 0,
        $configuracoes['id']
    ]);

    // Mais redes sociais (migração 041, redesign 10/2026). À parte, para não falhar o resto num site sem a 041.
    try {
        $pdo->prepare("UPDATE configuracoes_site SET youtube = ?, x_twitter = ?, tiktok = ?, linkedin = ?, whatsapp = ? WHERE id = ?")
            ->execute([trim($_POST['youtube'] ?? ''), trim($_POST['x_twitter'] ?? ''), trim($_POST['tiktok'] ?? ''),
                       trim($_POST['linkedin'] ?? ''), trim($_POST['whatsapp'] ?? ''), $configuracoes['id']]);
    } catch (Throwable $e) { /* migração 041 ainda não corrida */ }

    header("Location: configuracoes.php?ok=1");
    exit;
}

require_once "includes/header.php";
?>










<form method="POST" enctype="multipart/form-data">

    <h2>Identidade do Site</h2>

    <input type="text" name="nome_site" placeholder="Nome do site" value="<?= htmlspecialchars($configuracoes['nome_site'] ?? '') ?>" required>

    <input type="text" name="municipio" placeholder="Município" value="<?= htmlspecialchars($configuracoes['municipio'] ?? '') ?>">

    <input type="text" name="slogan" placeholder="Slogan" value="<?= htmlspecialchars($configuracoes['slogan'] ?? '') ?>">

    <label for="cfgDominio">Endereço oficial do site</label>
    <input type="url" id="cfgDominio" name="dominio" placeholder="https://www.jf-exemplo.pt" value="<?= htmlspecialchars($configuracoes['dominio'] ?? '') ?>">
    <small>Usado nos links partilhados nas redes sociais, na newsletter, no calendário de eventos e nos motores de pesquisa. Vazio = usa o endereço pelo qual o site é aberto.</small>

    <?php if (!empty($configuracoes['logo'])): ?>
        <p><strong>Logo atual:</strong></p>
        <img src="../assets/img/<?= htmlspecialchars($configuracoes['logo']) ?>" style="max-width:160px;border-radius:12px;margin-bottom:15px;">
    <?php endif; ?>

    <label>Logo</label>
    <input type="file" name="logo" accept="image/*">

    <hr>

    <h2>Contactos</h2>

    <input type="email" name="email" placeholder="Email público" value="<?= htmlspecialchars($configuracoes['email'] ?? '') ?>">

    <input type="text" name="telefone" placeholder="Telefone" value="<?= htmlspecialchars($configuracoes['telefone'] ?? '') ?>">

    <textarea name="morada" placeholder="Morada"><?= htmlspecialchars($configuracoes['morada'] ?? '') ?></textarea>

    <textarea name="horario" placeholder="Horário de funcionamento"><?= htmlspecialchars($configuracoes['horario'] ?? '') ?></textarea>

    <input type="email" name="email_notificacoes" placeholder="Email para notificações internas" value="<?= htmlspecialchars($configuracoes['email_notificacoes'] ?? '') ?>">

    <hr>

    <h2>Redes Sociais</h2>
    <p style="color:#64748b;margin:-4px 0 12px;">Cole o endereço de cada perfil (ex.: https://www.facebook.com/…). Os ícones aparecem no menu e no rodapé do site só para as redes preenchidas.</p>

    <label for="cfgFacebook">Facebook</label>
    <input id="cfgFacebook" type="url" name="facebook" placeholder="https://www.facebook.com/…" value="<?= htmlspecialchars($configuracoes['facebook'] ?? '') ?>">

    <label for="cfgInstagram">Instagram</label>
    <input id="cfgInstagram" type="url" name="instagram" placeholder="https://www.instagram.com/…" value="<?= htmlspecialchars($configuracoes['instagram'] ?? '') ?>">

    <label for="cfgYoutube">YouTube</label>
    <input id="cfgYoutube" type="url" name="youtube" placeholder="https://www.youtube.com/@…" value="<?= htmlspecialchars($configuracoes['youtube'] ?? '') ?>">

    <label for="cfgX">X (Twitter)</label>
    <input id="cfgX" type="url" name="x_twitter" placeholder="https://x.com/…" value="<?= htmlspecialchars($configuracoes['x_twitter'] ?? '') ?>">

    <label for="cfgTiktok">TikTok</label>
    <input id="cfgTiktok" type="url" name="tiktok" placeholder="https://www.tiktok.com/@…" value="<?= htmlspecialchars($configuracoes['tiktok'] ?? '') ?>">

    <label for="cfgLinkedin">LinkedIn</label>
    <input id="cfgLinkedin" type="url" name="linkedin" placeholder="https://www.linkedin.com/company/…" value="<?= htmlspecialchars($configuracoes['linkedin'] ?? '') ?>">

    <label for="cfgWhatsapp">WhatsApp</label>
    <input id="cfgWhatsapp" type="text" name="whatsapp" placeholder="912 345 678 ou https://wa.me/…" value="<?= htmlspecialchars($configuracoes['whatsapp'] ?? '') ?>">

    <hr>

    <h2>Cores e Rodapé</h2>

    

















<label>Cor principal</label>
<input type="color" name="cor_principal" value="<?= htmlspecialchars(!empty($configuracoes['cor_principal']) ? '#' . ltrim($configuracoes['cor_principal'], '#') : '#242A30') ?>">

<label>Cor secundária</label>
<input type="color" name="cor_secundaria" value="<?= htmlspecialchars(!empty($configuracoes['cor_secundaria']) ? '#' . ltrim($configuracoes['cor_secundaria'], '#') : '#C8A02E') ?>">






    <label>Texto do rodapé</label>
<textarea 
    name="footer" 
    placeholder="Ex: © <?= date('Y') ?> <?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?> • Todos os direitos reservados"
><?= htmlspecialchars($configuracoes['footer'] ?? '') ?></textarea>


    

    <hr>

    <h2>Sistema</h2>

    <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
        <input type="checkbox" name="alertas_ativos" value="1" <?= !empty($configuracoes['alertas_ativos']) ? 'checked' : '' ?> style="width:auto;margin:0;">
        Mostrar alertas no site público
    </label>

    <button class="btn">Guardar configurações</button>

</form>

<?php require_once "includes/footer.php"; ?>