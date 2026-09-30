<?php require_once "includes/header.php"; ?>

<style>
/* ATESTADO RESIDÊNCIA PREMIUM - apenas visual */
.atestado-hero-premium{
    position:relative;
    overflow:hidden;
    background:
        radial-gradient(circle at top right, rgba(212,170,0,.24), transparent 34%),
        linear-gradient(135deg, var(--cor-principal), #11151B 72%);
    color:white;
    padding:92px 0 76px;
}

.atestado-hero-premium::before{
    content:"";
    position:absolute;
    width:520px;
    height:520px;
    border-radius:50%;
    background:rgba(255,255,255,.055);
    right:-190px;
    bottom:-285px;
}

.atestado-hero-premium::after{
    content:"";
    position:absolute;
    inset:auto 0 0 0;
    height:1px;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,.28), transparent);
}

.atestado-hero-inner{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:1.15fr .85fr;
    gap:34px;
    align-items:center;
}

.atestado-eyebrow{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:var(--cor-secundaria);
    background:rgba(212,170,0,.13);
    border:1px solid rgba(212,170,0,.34);
    border-radius:999px;
    padding:8px 13px;
    font-size:13px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.8px;
    margin-bottom:18px;
}

.atestado-hero-premium h1{
    font-size:clamp(42px, 6vw, 68px);
    line-height:1.02;
    margin:0 0 18px;
    letter-spacing:-1.4px;
}

.atestado-hero-premium p{
    max-width:760px;
    margin:0;
    color:#dbeafe;
    font-size:20px;
    line-height:1.75;
}

.atestado-hero-card{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.18);
    border-radius:30px;
    padding:28px;
    box-shadow:0 24px 70px rgba(0,0,0,.22);
    backdrop-filter:blur(12px);
}

.atestado-hero-card span{
    width:66px;
    height:66px;
    border-radius:22px;
    background:var(--cor-secundaria);
    color:#11151B;
    display:grid;
    place-items:center;
    font-size:34px;
    margin-bottom:18px;
    box-shadow:0 18px 40px rgba(0,0,0,.20);
}

.atestado-hero-card strong{
    display:block;
    font-size:24px;
    margin-bottom:8px;
}

.atestado-hero-card small{
    display:block;
    color:#dbeafe;
    font-weight:700;
    line-height:1.6;
}

.atestado-section-premium{
    background:linear-gradient(180deg, #f8fafc, #f7f4ef);
    padding:70px 0;
}

.atestado-layout{
    display:grid;
    grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);
    gap:30px;
    align-items:start;
}

.atestado-form-card,
.atestado-side-card{
    background:white;
    border:1px solid #eef2f7;
    border-radius:32px;
    box-shadow:0 24px 70px rgba(15,23,42,.10);
}

.atestado-form-card{
    padding:34px;
    position:relative;
    overflow:hidden;
}

.atestado-form-card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    right:0;
    height:6px;
    background:linear-gradient(90deg, var(--cor-principal), var(--cor-secundaria));
}

.atestado-form-head{
    display:flex;
    justify-content:space-between;
    gap:24px;
    align-items:flex-start;
    margin-bottom:26px;
}

.atestado-form-head h2{
    margin:8px 0 10px;
    color:#11151B;
    font-size:34px;
    line-height:1.12;
}

.atestado-form-head p{
    margin:0;
    color:#64748b;
    line-height:1.7;
}

.atestado-badge{
    flex-shrink:0;
    background:#ecfdf5;
    color:#166534;
    border:1px solid #bbf7d0;
    border-radius:999px;
    padding:9px 13px;
    font-weight:900;
    font-size:13px;
    white-space:nowrap;
}

.atestado-form-premium.form-publico{
    display:grid;
    grid-template-columns:repeat(2, minmax(0,1fr));
    gap:16px 22px !important;
    background:transparent;
    box-shadow:none;
    padding:0;
}

.atestado-field{
    position:relative;
}

.atestado-field.full{
    grid-column:span 2;
}

.atestado-field input{
    width:100%;
    min-height:54px;
    border:1px solid #dbe4ee;
    border-radius:16px;
    padding:18px 15px 8px;
    background:#f8fafc;
    color:#11151B;
    font-size:15px;
    font-weight:700;
    outline:none;
    transition:.22s ease;
    box-sizing:border-box;
}

.atestado-field label{
    position:absolute;
    top:7px;
    left:15px;
    margin:0;
    color:#64748b;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.5px;
    pointer-events:none;
}

.atestado-field input:hover{
    border-color:#b6c2cf;
}

.atestado-field input:focus{
    background:white;
    border-color:var(--cor-principal);
    box-shadow:0 0 0 4px rgba(36,42,50,.10);
}

.atestado-submit-row{
    grid-column:span 2;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    margin-top:8px;
    padding-top:18px;
    border-top:1px solid #eef2f7;
}

.atestado-submit-row small{
    color:#64748b;
    line-height:1.5;
    font-weight:700;
}

.atestado-submit-row .btn{
    min-height:52px;
    border-radius:16px;
    padding:0 22px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    box-shadow:0 16px 35px rgba(36,42,50,.18);
    white-space:nowrap;
}

.atestado-side-card{
    padding:28px;
    position:sticky;
    top:120px;
}

.atestado-side-title{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:18px;
}

.atestado-side-title span{
    width:48px;
    height:48px;
    border-radius:16px;
    background:linear-gradient(135deg, var(--cor-principal), #11151B);
    color:white;
    display:grid;
    place-items:center;
    font-size:23px;
}

.atestado-side-title h3{
    margin:0;
    color:#11151B;
    font-size:24px;
}

.atestado-processo{
    display:grid;
    gap:14px;
    margin:22px 0;
}

.atestado-step{
    display:grid;
    grid-template-columns:42px 1fr;
    gap:13px;
    align-items:flex-start;
    background:#f8fafc;
    border:1px solid #eef2f7;
    border-radius:18px;
    padding:14px;
    transition:.22s ease;
}

.atestado-step:hover{
    transform:translateY(-3px);
    background:white;
    box-shadow:0 14px 30px rgba(15,23,42,.07);
}

.atestado-step strong:first-child{
    width:42px;
    height:42px;
    border-radius:14px;
    background:var(--cor-principal);
    color:white;
    display:grid;
    place-items:center;
}

.atestado-step b{
    display:block;
    color:#11151B;
    margin-bottom:4px;
}

.atestado-step p{
    margin:0;
    color:#64748b;
    line-height:1.55;
    font-size:14px;
}

.atestado-note{
    background:#fff7e0;
    border:1px solid #ffe08a;
    border-left:5px solid var(--cor-secundaria);
    border-radius:18px;
    padding:16px;
    color:#73510b;
    line-height:1.6;
    font-weight:700;
}

.atestado-mini-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:14px;
    margin-top:28px;
}

.atestado-mini-card{
    background:white;
    border:1px solid #eef2f7;
    border-radius:22px;
    padding:20px;
    box-shadow:0 14px 34px rgba(15,23,42,.07);
    transition:.22s ease;
}

.atestado-mini-card:hover{
    transform:translateY(-5px);
}

.atestado-mini-card span{
    font-size:30px;
    display:block;
    margin-bottom:10px;
}

.atestado-mini-card strong{
    display:block;
    color:#11151B;
    margin-bottom:6px;
}

.atestado-mini-card small{
    color:#64748b;
    line-height:1.5;
    font-weight:700;
}

@media(max-width:1000px){
    .atestado-hero-inner,
    .atestado-layout{
        grid-template-columns:1fr;
    }

    .atestado-side-card{
        position:static;
    }
}

@media(max-width:760px){
    .atestado-hero-premium{
        padding:68px 0 56px;
    }

    .atestado-form-card,
    .atestado-side-card{
        border-radius:24px;
        padding:24px;
    }

    .atestado-form-head,
    .atestado-submit-row{
        flex-direction:column;
        align-items:flex-start;
    }

    .atestado-form-premium.form-publico,
    .atestado-mini-grid{
        grid-template-columns:1fr;
    }

    .atestado-field.full,
    .atestado-submit-row{
        grid-column:span 1;
    }

    .atestado-submit-row .btn{
        width:100%;
    }
}
</style>

<section class="atestado-hero-premium">
    <div class="container atestado-hero-inner">
        <div>
            <span class="atestado-eyebrow"><i class="bi bi-file-earmark-text"></i> Balcão Virtual</span>
            <h1>Atestado de Residência</h1>
            <p>Geração automática de documento através do Balcão Virtual, com preenchimento simples, rápido e preparado para emissão em PDF.</p>
        </div>

        <div class="atestado-hero-card">
            <span><i class="bi bi-house-door"></i></span>
            <strong>Documento digital imediato</strong>
            <small>Preencha os dados necessários e gere automaticamente o atestado em formato PDF, mantendo o processo simples para o cidadão.</small>
        </div>
    </div>
</section>

<section class="atestado-section-premium">
    <div class="container">

        <div class="atestado-layout">

            <div class="atestado-form-card">
                <div class="atestado-form-head">
                    <div>
                        <span class="section-kicker">Balcão Virtual</span>
                        <h2>Gerar atestado</h2>
                        <p>Introduza os dados do requerente para criar o documento automaticamente.</p>
                    </div>
                    <span class="atestado-badge"><i class="bi bi-check-lg"></i> PDF automático</span>
                </div>

                <form method="POST" action="gerar-atestado.php" class="form-publico atestado-form-premium">

                    <div class="atestado-field full">
                        <label>Nome completo *</label>
                        <input type="text" name="nome" placeholder="Nome completo *" required>
                    </div>

                    <div class="atestado-field full">
                        <label>Morada completa *</label>
                        <input type="text" name="morada" placeholder="Morada completa *" required>
                    </div>

                    <div class="atestado-field">
                        <label>Cartão de Cidadão</label>
                        <input type="text" name="cc" placeholder="Cartão de Cidadão">
                    </div>

                    <div class="atestado-field">
                        <label>NIF</label>
                        <input type="text" name="nif" placeholder="NIF">
                    </div>

                    <div class="atestado-field full">
                        <label>Data de nascimento</label>
                        <input type="date" name="data_nascimento">
                    </div>

                    <div class="atestado-submit-row">
                        <small>Os campos assinalados com * são obrigatórios para gerar o documento.</small>
                        <button class="btn" type="submit">
                            <i class="bi bi-download"></i> Gerar PDF automaticamente
                        </button>
                    </div>

                </form>
            </div>

            <aside class="atestado-side-card">
                <div class="atestado-side-title">
                    <span><i class="bi bi-lightning-charge"></i></span>
                    <h3>Como funciona</h3>
                </div>

                <div class="atestado-processo">
                    <div class="atestado-step">
                        <strong>1</strong>
                        <div>
                            <b>Preencher dados</b>
                            <p>Insira a identificação e a morada completa do requerente.</p>
                        </div>
                    </div>

                    <div class="atestado-step">
                        <strong>2</strong>
                        <div>
                            <b>Gerar documento</b>
                            <p>O sistema cria automaticamente o atestado com os dados submetidos.</p>
                        </div>
                    </div>

                    <div class="atestado-step">
                        <strong>3</strong>
                        <div>
                            <b>Guardar PDF</b>
                            <p>O documento fica pronto para impressão, arquivo ou validação interna.</p>
                        </div>
                    </div>
                </div>

                <div class="atestado-note">
                    <i class="bi bi-lightbulb"></i> Confirme sempre os dados antes de gerar o PDF para evitar erros no documento final.
                </div>
            </aside>

        </div>

        <div class="atestado-mini-grid">
            <div class="atestado-mini-card">
                <span><i class="bi bi-lock"></i></span>
                <strong>Dados protegidos</strong>
                <small>Processo simples e orientado à emissão do documento.</small>
            </div>

            <div class="atestado-mini-card">
                <span><i class="bi bi-printer"></i></span>
                <strong>Pronto a imprimir</strong>
                <small>Documento gerado em PDF para utilização administrativa.</small>
            </div>

            <div class="atestado-mini-card">
                <span><i class="bi bi-bank"></i></span>
                <strong>Balcão digital</strong>
                <small>Mais rapidez e modernização dos serviços da freguesia.</small>
            </div>
        </div>

    </div>
</section>

<?php require_once "includes/footer.php"; ?>
