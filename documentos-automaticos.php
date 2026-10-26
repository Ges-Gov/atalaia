<?php require_once "includes/header.php"; ?>


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

                <select name="tipo_documento" required>
                    <option value=""><?= te('documentos_automaticos.formulario.tipo') ?></option>
                    <option value="atestado_residencia"><?= te('documentos_automaticos.formulario.tipo_atestado') ?></option>
                    <option value="declaracao"><?= te('documentos_automaticos.formulario.tipo_declaracao') ?></option>
                    <option value="certidao"><?= te('documentos_automaticos.formulario.tipo_certidao') ?></option>
                    <option value="licenca"><?= te('documentos_automaticos.formulario.tipo_licenca') ?></option>
                </select>

                <input type="text" name="nome" placeholder="<?= te('documentos_automaticos.formulario.ph_nome') ?>" required>

                <input type="text" name="morada" placeholder="<?= te('documentos_automaticos.formulario.ph_morada') ?>" required>

                <input type="text" name="cc" placeholder="<?= te('documentos_automaticos.formulario.ph_cc') ?>">

                <input type="text" name="nif" placeholder="<?= te('documentos_automaticos.formulario.ph_nif') ?>">

                <input type="date" name="data_nascimento">

                <input type="text" name="assunto" placeholder="<?= te('documentos_automaticos.formulario.ph_assunto') ?>">

                <textarea name="observacoes" placeholder="<?= te('documentos_automaticos.formulario.ph_observacoes') ?>"></textarea>








<label><?= te('documentos_automaticos.formulario.cc_frente') ?></label>

<input 
type="file" 
name="cc_frente"
accept=".jpg,.jpeg,.png,.pdf,.webp"
required>

<label><?= te('documentos_automaticos.formulario.cc_verso') ?></label>

<input 
type="file" 
name="cc_verso"
accept=".jpg,.jpeg,.png,.pdf,.webp"
required>






                <button class="btn" type="submit">
                    <?= te('documentos_automaticos.formulario.botao') ?>
                </button>

            </form></div><aside class="docs-auto-info"><span class="section-kicker"><?= te('documentos_automaticos.sidebar.kicker') ?></span><h3><?= te('documentos_automaticos.sidebar.titulo') ?></h3><ul><li><?= te('documentos_automaticos.sidebar.item1') ?></li><li><?= te('documentos_automaticos.sidebar.item2') ?></li><li><?= te('documentos_automaticos.sidebar.item3') ?></li><li><?= te('documentos_automaticos.sidebar.item4') ?></li></ul><div class="docs-auto-badges"><span><?= te('documentos_automaticos.sidebar.selo1') ?></span><span><?= te('documentos_automaticos.sidebar.selo2') ?></span><span><?= te('documentos_automaticos.sidebar.selo3') ?></span></div></aside></div>
        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>