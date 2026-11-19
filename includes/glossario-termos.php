<?php
/**
 * Termos do glossário (CORE), 19/11/2026 — fonte única para a página glossario.php e para as ligações automáticas nos
 * textos (app.js liga a primeira ocorrência de cada termo, no conteúdo da página, à sua definição).
 * Selo de Usabilidade e Acessibilidade, checklist «Conteúdo», requisito 1.2.
 *
 * id => [termo, definição, [formas que aparecem nos textos e se ligam automaticamente]]
 * As formas são procuradas como palavras inteiras e com as maiúsculas tal como estão. Sem formas, o termo só aparece na
 * página do glossário (palavras demasiado comuns para ligar sozinhas, como «ata» ou «executivo»).
 */
function glossarioTermos(): array
{
    return [
        'assembleia-de-freguesia' => ['Assembleia de Freguesia', 'Órgão deliberativo da freguesia, eleito diretamente pelos cidadãos recenseados. Aprova o orçamento e as opções do plano, os regulamentos e acompanha e fiscaliza a atividade da Junta.', []],
        'junta-de-freguesia' => ['Junta de Freguesia', 'Órgão executivo da freguesia. Põe em prática as decisões da Assembleia de Freguesia e presta os serviços do dia a dia à população.', []],
        'executivo' => ['Executivo', 'Os membros da Junta de Freguesia: o presidente, o secretário, o tesoureiro e, quando existem, os vogais.', []],
        'vogal' => ['Vogal', 'Membro da Junta de Freguesia ou da mesa da Assembleia sem um cargo específico (presidente, secretário ou tesoureiro).', []],
        'ata' => ['Ata', 'Registo escrito do que foi discutido e decidido numa reunião ou sessão. É aprovada pelos membros e passa a ser o documento oficial dessa reunião.', []],
        'edital' => ['Edital', 'Aviso oficial publicado para conhecimento de todos — por exemplo, a convocatória de uma sessão da Assembleia de Freguesia.', ['edital', 'editais', 'Edital', 'Editais']],
        'atestado' => ['Atestado', 'Documento emitido pela Junta de Freguesia que confirma um facto, como a morada de residência, com base nos elementos apresentados por quem o pede.', ['atestado de residência', 'Atestado de residência']],
        'requerimento' => ['Requerimento', 'Pedido escrito e formal dirigido à Junta de Freguesia, por exemplo para obter um documento ou uma autorização.', []],
        'orcamento' => ['Orçamento', 'Documento que prevê as receitas e as despesas da freguesia para um ano. É proposto pela Junta e aprovado pela Assembleia de Freguesia.', []],
        'opcoes-do-plano' => ['Opções do Plano', 'As linhas de atuação e os investimentos que a Junta se propõe fazer, aprovados com o orçamento.', ['Opções do Plano', 'opções do plano']],
        'prestacao-de-contas' => ['Prestação de contas', 'Conjunto de documentos que mostram como o orçamento de um ano foi executado: o que se recebeu, o que se gastou e em quê.', ['prestação de contas', 'Prestação de contas', 'Prestação de Contas']],
        'contratacao-publica' => ['Contratação pública', 'As regras que a freguesia tem de seguir para comprar bens, serviços ou obras, definidas no Código dos Contratos Públicos.', ['contratação pública', 'Contratação pública', 'Contratação Pública', 'Código dos Contratos Públicos']],
        'procedimento-concursal' => ['Procedimento concursal', 'Processo de seleção, com regras públicas, para recrutar trabalhadores para a Junta de Freguesia.', ['procedimento concursal', 'procedimentos concursais', 'Procedimento concursal', 'Procedimentos concursais']],
        'rgpd' => ['RGPD', 'Regulamento Geral sobre a Proteção de Dados — a lei europeia que define como os dados pessoais podem ser recolhidos, usados e guardados.', ['RGPD']],
        'epd' => ['EPD (Encarregado de Proteção de Dados)', 'Pessoa designada para garantir que a Junta cumpre o RGPD e a quem os cidadãos se podem dirigir sobre os seus dados pessoais.', ['Encarregado de Proteção de Dados', 'EPD']],
        'canal-de-denuncias' => ['Canal de denúncias', 'Meio seguro, que pode ser anónimo, para comunicar irregularidades ou infrações, com proteção de quem denuncia.', []],
        'livro-de-reclamacoes' => ['Livro de Reclamações', 'Meio oficial para apresentar uma reclamação, um pedido de informação, uma sugestão ou um elogio sobre um serviço, também disponível em formato eletrónico.', []],
        'declaracao-de-acessibilidade' => ['Declaração de acessibilidade', 'Documento em que a entidade indica até que ponto o seu sítio Web cumpre os requisitos de acessibilidade do Decreto-Lei n.º 83/2018.', []],
    ];
}

/** Dados para as ligações automáticas (só os termos com formas): [{id, def, formas}] */
function glossarioLigacoes(): array
{
    $out = [];
    foreach (glossarioTermos() as $id => [, $def, $formas]) {
        if ($formas) $out[] = ['id' => $id, 'def' => $def, 'formas' => $formas];
    }
    return $out;
}
