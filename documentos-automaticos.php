<?php require_once "includes/header.php"; ?>
<?php
// Se o envio falhou (gerar-submeter-documento.php), volta com os erros junto a cada campo e o que já estava escrito
// (acessibilidade, 18/11/2026). As fotos do Cartão de Cidadão têm de ser escolhidas outra vez: o browser não as guarda.
$errosCampo = $_SESSION['docauto_erros'] ?? [];
$valDoc = $_SESSION['docauto_valores'] ?? [];
unset($_SESSION['docauto_erros'], $_SESSION['docauto_valores']);
$vd = fn(string $k): string => htmlspecialchars((string)($valDoc[$k] ?? ''), ENT_QUOTES, 'UTF-8');
?>


<style>
.docs-auto-hero{
background:linear-gradient(135deg,#242A30,#11151B);
padding:90px 0;
color:white;
position:relative;
overflow:hidden;
}
.docs-auto-hero:after{
content:"";
position:absolute;
width:520px;
height:520px;
border-radius:50%;
background:rgba(255,255,255,.05);
right:-180px;
top:-180px;
}
.docs-auto-hero .container{
position:relative;
z-index:2;
}
.docs-auto-hero h1{
font-size:58px;
margin:0 0 16px;
}
.docs-auto-hero p{
font-size:20px;
max-width:760px;
color:#dbeafe;
line-height:1.8;
}
.docs-auto-box{
background:white;
border-radius:28px;
padding:34px;
box-shadow:0 22px 55px rgba(0,0,0,.10);
border:1px solid #eef2f7;
margin-top:-60px;
position:relative;
z-index:5;
}
.docs-auto-box h2{
font-size:36px;
color:#11151B;
margin-top:10px;
}
.docs-auto-grid{
display:grid;
grid-template-columns:1.2fr .8fr;
gap:30px;
align-items:start;
}
.docs-auto-info{
background:linear-gradient(135deg,#f8fafc,#ffffff);
border:1px solid #e5e7eb;
border-radius:24px;
padding:26px;
}
.docs-auto-info ul{
padding-left:18px;
line-height:2;
color:#475569;
font-weight:700;
}
.docs-auto-form{
background:#f8fafc;
padding:24px;
border-radius:24px;
border:1px solid #e5e7eb;
}
.docs-auto-form input,
.docs-auto-form select,
.docs-auto-form textarea{
width:100%;
min-height:50px;
border-radius:14px;
border:1px solid #dbe3ea;
padding:0 14px;
background:white;
margin-bottom:14px;
box-sizing:border-box;
}
.docs-auto-form textarea{
padding:14px;
min-height:130px;
}
.docs-auto-form input:focus,
.docs-auto-form select:focus,
.docs-auto-form textarea:focus{
outline:none;
border-color:#242A30;
box-shadow:0 0 0 4px rgba(36,42,50,.10);
}
.docs-auto-badges{
display:flex;
gap:10px;
flex-wrap:wrap;
margin-top:20px;
}
.docs-auto-badges span{
background:#eff6ff;
color:#242A30;
padding:10px 14px;
border-radius:999px;
font-weight:800;
font-size:13px;
}
@media(max-width:900px){
.docs-auto-grid{
grid-template-columns:1fr;
}
.docs-auto-hero h1{
font-size:38px;
}
.docs-auto-box{
padding:22px;
}
}
</style>

<section class="docs-auto-hero">
    <div class="container">
        <h1><?= te('documentos_automaticos.hero.titulo') ?></h1>
        <p><?= te('documentos_automaticos.hero.texto') ?></p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:900px;">

        <div class="docs-auto-box"><div class="docs-auto-grid"><div>
            <span class="section-kicker"><?= te('documentos_automaticos.formulario.kicker') ?></span>
            <h2><?= te('documentos_automaticos.formulario.titulo') ?></h2>

            <form method="POST" action="gerar-submeter-documento.php" class="form-publico docs-auto-form" enctype="multipart/form-data">

                <?php /* etiqueta visível por cima de cada campo, * nos obrigatórios, erros junto ao campo e larguras à
                         medida dos dados: NIF, Cartão de Cidadão e data (acessibilidade, 18/11/2026) */ ?>
                <?= resumoErros($errosCampo, ['tipo_documento' => 'docTipo', 'nome' => 'docNome', 'morada' => 'docMorada', 'cc_frente' => 'docCcFrente', 'cc_verso' => 'docCcVerso']) ?>
                <?= notaObrigatorios() ?>

                <div class="campo">
                    <?= rotulo('docTipo', tx('documentos_automaticos.formulario.tipo'), true) ?>
                    <select id="docTipo" name="tipo_documento" required<?= erroAttrs($errosCampo, 'tipo_documento') ?>>
                        <option value="">Escolha…</option>
                        <?php foreach (['atestado_residencia' => 'tipo_atestado', 'declaracao' => 'tipo_declaracao', 'certidao' => 'tipo_certidao', 'licenca' => 'tipo_licenca'] as $v => $chave): ?>
                            <option value="<?= $v ?>"<?= ($valDoc['tipo_documento'] ?? '') === $v ? ' selected' : '' ?>><?= te('documentos_automaticos.formulario.' . $chave) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= erroCampo($errosCampo, 'tipo_documento') ?>
                </div>

                <div class="campo">
                    <?= rotulo('docNome', tx('documentos_automaticos.formulario.ph_nome'), true) ?>
                    <input type="text" id="docNome" name="nome" autocomplete="name" value="<?= $vd('nome') ?>" required<?= erroAttrs($errosCampo, 'nome') ?>>
                    <?= erroCampo($errosCampo, 'nome') ?>
                </div>

                <div class="campo">
                    <?= rotulo('docMorada', tx('documentos_automaticos.formulario.ph_morada'), true) ?>
                    <input type="text" id="docMorada" name="morada" autocomplete="street-address" value="<?= $vd('morada') ?>" required<?= erroAttrs($errosCampo, 'morada') ?>>
                    <?= erroCampo($errosCampo, 'morada') ?>
                </div>

                <div class="campo campo--cc">
                    <?= rotulo('docCc', tx('documentos_automaticos.formulario.ph_cc'), false) ?>
                    <input type="text" id="docCc" name="cc" maxlength="20" value="<?= $vd('cc') ?>">
                </div>

                <div class="campo campo--nif">
                    <?= rotulo('docNif', tx('documentos_automaticos.formulario.ph_nif'), false) ?>
                    <input type="text" id="docNif" name="nif" inputmode="numeric" maxlength="9" value="<?= $vd('nif') ?>">
                </div>

                <div class="campo campo--data">
                    <?= rotulo('docNascimento', 'Data de nascimento', false) ?>
                    <input type="date" id="docNascimento" name="data_nascimento" autocomplete="bday" value="<?= $vd('data_nascimento') ?>">
                </div>

                <div class="campo">
                    <?= rotulo('docAssunto', tx('documentos_automaticos.formulario.ph_assunto'), false) ?>
                    <input type="text" id="docAssunto" name="assunto" value="<?= $vd('assunto') ?>">
                </div>

                <div class="campo">
                    <?= rotulo('docObservacoes', tx('documentos_automaticos.formulario.ph_observacoes'), false) ?>
                    <textarea id="docObservacoes" name="observacoes"><?= $vd('observacoes') ?></textarea>
                </div>

<label for="docCcFrente"><?= te('documentos_automaticos.formulario.cc_frente') ?> <?= marcaObrigatorio() ?></label>

<input
type="file"
id="docCcFrente"
name="cc_frente"
accept=".jpg,.jpeg,.png,.pdf,.webp"
required<?= erroAttrs($errosCampo, 'cc_frente') ?>>
<?= erroCampo($errosCampo, 'cc_frente') ?>

<label for="docCcVerso"><?= te('documentos_automaticos.formulario.cc_verso') ?> <?= marcaObrigatorio() ?></label>

<input
type="file"
id="docCcVerso"
name="cc_verso"
accept=".jpg,.jpeg,.png,.pdf,.webp"
required<?= erroAttrs($errosCampo, 'cc_verso') ?>>
<?= erroCampo($errosCampo, 'cc_verso') ?>






                <button class="btn" type="submit">
                    <?= te('documentos_automaticos.formulario.botao') ?>
                </button>

            </form></div><div class="docs-auto-info"><span class="section-kicker"><?= te('documentos_automaticos.sidebar.kicker') ?></span><h3><?= te('documentos_automaticos.sidebar.titulo') ?></h3><ul><li><?= te('documentos_automaticos.sidebar.item1') ?></li><li><?= te('documentos_automaticos.sidebar.item2') ?></li><li><?= te('documentos_automaticos.sidebar.item3') ?></li><li><?= te('documentos_automaticos.sidebar.item4') ?></li></ul><div class="docs-auto-badges"><span><?= te('documentos_automaticos.sidebar.selo1') ?></span><span><?= te('documentos_automaticos.sidebar.selo2') ?></span><span><?= te('documentos_automaticos.sidebar.selo3') ?></span></div></div></div>
        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>