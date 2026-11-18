<?php
/**
 * Formulários acessíveis — checklist «Transação» da AMA (Selo de Usabilidade e Acessibilidade, nível Prata), 18/11/2026.
 *
 * - etiqueta visível por cima de cada campo (o texto de exemplo desaparece quando se escreve, não serve de etiqueta);
 * - * nos obrigatórios (só visual: o campo tem `required`, que o leitor de ecrã anuncia) e «(opcional)» nos outros,
 *   com a nota «Os campos assinalados com * são obrigatórios.» no topo do formulário;
 * - erros junto ao campo de origem, com uma frase que diz o que fazer, e um resumo no topo com ligação a cada campo;
 *   a página abre no resumo (antes recarregava no topo e a mensagem ficava fora de vista).
 *
 * O aspeto (style.css, «Formulários acessíveis») é o modelo dos Contactos, que já cumpria.
 * Carregado pelo includes/header.php; as funções só são chamadas no HTML, depois do header.
 */

/** Texto de etiqueta a partir de um texto antigo de exemplo: «Nome completo *» → «Nome completo»,
 *  «Escolha a categoria *» → «Categoria», «Subcategoria (opcional)» → «Subcategoria». */
function rotuloTexto(string $t): string
{
    $t = preg_replace('/\s*\(opcional\)/iu', '', $t);
    $t = trim(preg_replace('/\s*\*\s*$/u', '', trim($t)));
    $t = preg_replace('/^Escolha (a|o) /iu', '', $t);
    return mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1);
}

/** <label> visível: o texto, e depois * (obrigatório) ou «(opcional)». $texto vem em texto simples (não escapado). */
function rotulo(string $id, string $texto, bool $obrigatorio, string $classe = ''): string
{
    return '<label class="campo-rotulo' . ($classe !== '' ? ' ' . $classe : '') . '" for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars(rotuloTexto($texto), ENT_QUOTES, 'UTF-8') . ' '
        . ($obrigatorio ? marcaObrigatorio() : '<span class="campo-opc">(opcional)</span>') . '</label>';
}

/** O * vermelho de «obrigatório» (escondido do leitor de ecrã, que já anuncia o `required`). */
function marcaObrigatorio(): string
{
    return '<span class="campo-ob" aria-hidden="true">*</span>';
}

/** Nota no topo do formulário. */
function notaObrigatorios(): string
{
    return '<p class="form-nota">Os campos assinalados com ' . marcaObrigatorio() . ' são obrigatórios.</p>';
}

/** Atributos do campo com erro: aria-invalid e ligação à mensagem (o leitor de ecrã lê-a ao chegar ao campo). */
function erroAttrs(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? ' aria-invalid="true" aria-describedby="erro-' . htmlspecialchars($campo, ENT_QUOTES, 'UTF-8') . '"' : '';
}

/** Mensagem de erro por baixo do campo. */
function erroCampo(array $erros, string $campo): string
{
    if (!isset($erros[$campo])) return '';
    return '<div class="campo-erro" id="erro-' . htmlspecialchars($campo, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($erros[$campo], ENT_QUOTES, 'UTF-8') . '</div>';
}

/**
 * Resumo dos erros no topo do formulário, com uma ligação a cada campo. $ids = ['campo' => 'id do elemento'] quando o
 * id não é o nome do campo. A página abre aqui: o resumo recebe o foco (e o leitor de ecrã lê-o, role="alert").
 */
function resumoErros(array $erros, array $ids = []): string
{
    if (!$erros) return '';
    $n = count($erros);
    $h = '<div class="form-resumo" id="resumo-erros" role="alert" tabindex="-1"><strong>'
        . ($n === 1 ? 'Há 1 campo por corrigir:' : "Há $n campos por corrigir:") . '</strong><ul>';
    foreach ($erros as $campo => $msg) {
        $alvo = $ids[$campo] ?? $campo;
        $h .= '<li><a href="#' . htmlspecialchars($alvo, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
    return $h . '</ul></div>'
        . '<script>document.addEventListener("DOMContentLoaded",function(){var r=document.getElementById("resumo-erros");if(!r)return;'
        . 'r.focus({preventScroll:true});r.scrollIntoView({block:"center"});'
        // a ligação leva ao campo e põe lá o cursor (só o salto de âncora não o faz em todos os browsers)
        . 'r.addEventListener("click",function(e){var a=e.target.closest("a");if(!a)return;var c=document.getElementById(a.getAttribute("href").slice(1));'
        . 'if(!c)return;e.preventDefault();c.scrollIntoView({block:"center"});c.focus({preventScroll:true});});});</script>';
}
