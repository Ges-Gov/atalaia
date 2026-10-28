<?php
/**
 * Menu lateral do backoffice (12/10/2026) — definido UMA vez aqui e desenhado por includes/header.php.
 *
 * Cada entrada:
 *   chave  → valor de $adminActive da página (marca a entrada como ativa)
 *   href   → página do backoffice
 *   icone  → classe do Bootstrap Icons
 *   dica   → explicação em linguagem simples (aparece como dica ao passar o rato, na pesquisa do
 *            menu e como subtítulo da página)
 *   rel    → outras páginas que pertencem a esta entrada (adicionar/editar/ver…), para a manter ativa
 *   site   → página pública correspondente (botão «Ver no site» no topo)
 *   badge  → número a assinalar (pedidos por tratar…)
 *   se     → false esconde a entrada (módulos que este site não tem)
 * Grupos: 'perm' = secção de permissões (podeAceder) ou null (visível a qualquer administrador).
 * Dentro de um grupo, ['sub' => 'Título'] cria um pequeno separador.
 *
 * Recebe do header: $badgePedidos, $badgeRequerimentos, $badgeFaturacao, $badgeMensagens, $badgeReservas.
 */

/**
 * Definição do menu (17/10/2026: passou a função — o auth.php usa-a para verificar no servidor as
 * permissões de cada página). $badges: ['badgePedidos' => n, …]. Devolve [menu do papel, menu completo].
 */
function menuAdminDefinicao(array $badges = []): array
{
    $badgePedidos = $badgeRequerimentos = $badgeFaturacao = $badgeMensagens = $badgeReservas = 0;
    $badgeSugestoes = null;
    extract($badges);

    $menuAdmin = [
        ['chave' => 'dashboard', 'rotulo' => 'Painel', 'href' => 'dashboard.php', 'icone' => 'bi-speedometer2',
         'dica' => 'Resumo do dia: pedidos por tratar, reservas, requerimentos e atividade do site'],

        ['grupo' => 'home', 'rotulo' => 'Página Inicial', 'icone' => 'bi-house-heart', 'perm' => 'home',
         'dica' => 'Tudo o que aparece na página de entrada do site', 'itens' => [
            ['chave' => 'slides', 'rotulo' => 'Slides do topo', 'href' => 'slides.php', 'icone' => 'bi-images', 'site' => '/',
             'rel' => ['adicionar_slide.php', 'editar_slide.php'], 'dica' => 'As fotografias grandes que passam no topo da página inicial, com título e botão'],
            ['chave' => 'homepage_destaque', 'rotulo' => 'Vídeo / destaque do topo', 'href' => 'homepage_destaque.php', 'icone' => 'bi-camera-reels', 'site' => '/',
             'dica' => 'Escolher se o topo mostra os slides ou um vídeo em destaque'],
            ['chave' => 'cartoes_site', 'rotulo' => 'Cartões e atalhos', 'href' => 'cartoes-site.php', 'icone' => 'bi-grid-3x2-gap', 'site' => '/',
             'dica' => 'Atalhos do topo, Serviços, «Perto de quem precisa», «Descobrir» e destaques do Bem-vindo: criar, mudar, ordenar e esconder'],
            ['chave' => 'homepage', 'rotulo' => 'Textos da página inicial', 'href' => 'homepage.php', 'icone' => 'bi-fonts', 'site' => '/',
             'dica' => 'Boas-vindas, mensagem do Presidente, chamada final e outros textos da página inicial'],
            ['chave' => 'galeria-albuns', 'rotulo' => 'Galeria de fotos', 'href' => 'galeria-albuns.php', 'icone' => 'bi-images', 'site' => '/galeria.php',
             'rel' => ['galeria-album.php'], 'dica' => 'Álbuns de fotografias (a apresentação da página inicial usa estes álbuns)'],
            ['chave' => 'links_uteis', 'rotulo' => 'Links úteis', 'href' => 'links-uteis.php', 'icone' => 'bi-link-45deg', 'site' => '/',
             'dica' => 'Atalhos para sites externos (Câmara, Finanças, Segurança Social…)'],
        ]],

        ['grupo' => 'freguesia', 'rotulo' => 'A Freguesia', 'icone' => 'bi-bank', 'perm' => 'freguesia',
         'dica' => 'Conteúdos do site: notícias, eventos, locais, documentos e páginas institucionais', 'itens' => [
            ['sub' => 'Atualidade'],
            ['chave' => 'noticias', 'rotulo' => 'Notícias', 'href' => 'noticias.php', 'icone' => 'bi-newspaper', 'site' => '/noticias.php',
             'rel' => ['adicionar_noticia.php', 'editar_noticia.php'], 'dica' => 'Escrever, editar e publicar notícias'],
            ['chave' => 'eventos', 'rotulo' => 'Eventos e agenda', 'href' => 'eventos.php', 'icone' => 'bi-calendar-event', 'site' => '/eventos.php',
             'rel' => ['adicionar_evento.php', 'editar_evento.php', 'evento_campos.php', 'evento_inscricoes.php'], 'dica' => 'Eventos da agenda, com inscrições online se quiser'],
            ['chave' => 'farmacias', 'rotulo' => 'Farmácias de serviço', 'href' => 'farmacias-servico.php', 'icone' => 'bi-capsule', 'site' => '/',
             'dica' => 'Escala da farmácia de serviço que aparece na página inicial'],
            ['sub' => 'Quem somos'],
            ['chave' => 'executivo', 'rotulo' => 'Executivo', 'href' => 'executivo.php', 'icone' => 'bi-people', 'site' => '/executivo.php',
             'dica' => 'Presidente, secretário, tesoureira: fotos, cargos, pelouros e contactos'],
            ['chave' => 'freguesia_pagina', 'rotulo' => 'Página «A Freguesia»', 'href' => 'freguesia.php', 'icone' => 'bi-geo', 'site' => '/freguesia.php',
             'dica' => 'Apresentação da freguesia, blocos informativos e localidades'],
            ['chave' => 'galeria-homepage', 'rotulo' => 'Galeria de «A Freguesia»', 'href' => 'galeria-homepage.php', 'icone' => 'bi-images', 'site' => '/freguesia.php',
             'rel' => ['homepage_galeria.php'], 'dica' => 'Fotografias da secção Galeria da página «A Freguesia»'],
            ['chave' => 'historia', 'rotulo' => 'História', 'href' => 'historia.php', 'icone' => 'bi-journal-text', 'site' => '/historia.php',
             'dica' => 'Texto da história da freguesia e linha temporal'],
            ['chave' => 'heraldica', 'rotulo' => 'Heráldica', 'href' => 'heraldica.php', 'icone' => 'bi-shield', 'site' => '/heraldica.php',
             'dica' => 'Brasão, bandeira e selo, com a descrição de cada elemento'],
            ['chave' => 'memoria', 'rotulo' => 'Memória da Freguesia', 'href' => 'memoria-freguesia.php', 'icone' => 'bi-hourglass-split', 'site' => '/freguesia.php',
             'dica' => 'Fotografias antigas, lendas, tradições e marcos históricos'],
            ['sub' => 'Descobrir'],
            ['chave' => 'pontos', 'rotulo' => 'Pontos de interesse', 'href' => 'pontos.php', 'icone' => 'bi-geo-alt', 'site' => '/pontos.php',
             'rel' => ['adicionar_ponto.php', 'editar_ponto.php'], 'dica' => 'Património e locais a visitar, com fotografia e posição no mapa'],
            ['chave' => 'associacoes', 'rotulo' => 'Associações', 'href' => 'associacoes.php', 'icone' => 'bi-people-fill', 'site' => '/associacoes.php',
             'rel' => ['adicionar_associacao.php', 'editar_associacao.php'], 'dica' => 'Associações culturais e desportivas, contactos e posição no mapa'],
            ['chave' => 'comercio', 'rotulo' => 'Economia local', 'href' => 'comercio.php', 'icone' => 'bi-shop', 'site' => '/comercio.php',
             'rel' => ['adicionar_comercio.php', 'editar_comercio.php'], 'dica' => 'Restaurantes, cafés, alojamento e lojas da freguesia'],
            ['sub' => 'Informação'],
            ['chave' => 'documentos', 'rotulo' => 'Documentos públicos', 'href' => 'documentos.php', 'icone' => 'bi-file-earmark-text', 'site' => '/freguesia-documentos.php',
             'rel' => ['adicionar_documento.php', 'editar_documento.php'], 'dica' => 'Orçamento, contas, regulamentos e outros documentos em PDF'],
            ['chave' => 'faqs', 'rotulo' => 'Perguntas frequentes', 'href' => 'faqs.php', 'icone' => 'bi-question-circle', 'site' => '/faq.php',
             'rel' => ['adicionar_faq.php', 'editar_faq.php'], 'dica' => 'Perguntas e respostas da página de ajuda'],
        ]],

        ['grupo' => 'virtual', 'rotulo' => 'Junta Virtual', 'icone' => 'bi-laptop', 'perm' => 'virtual',
         'dica' => 'Pedidos dos cidadãos e serviços online', 'itens' => [
            ['sub' => 'Atendimento'],
            ['chave' => 'pedidos', 'rotulo' => 'Ocorrências', 'href' => 'pedidos.php', 'icone' => 'bi-megaphone', 'site' => '/pedidos.php', 'badge' => $badgePedidos,
             'rel' => ['ver_pedido.php', 'criar_pedido.php', 'ocorrencias_config.php'], 'dica' => 'Problemas comunicados pelos cidadãos (buracos, iluminação, limpeza…)'],
            ['chave' => 'mensagens_contacto', 'rotulo' => 'Mensagens de contacto', 'href' => 'mensagens-contacto.php', 'icone' => 'bi-chat-left-text', 'badge' => $badgeMensagens,
             'dica' => 'Mensagens enviadas pelo formulário da página Contactos'],
            ...($badgeSugestoes !== null ? [['chave' => 'sugestoes', 'rotulo' => 'Sugestões', 'href' => 'sugestoes.php', 'icone' => 'bi-lightbulb', 'site' => '/sugestoes.php',
             'badge' => $badgeSugestoes, 'dica' => 'Sugestões enviadas pelo formulário do site']] : []),
            ['chave' => 'requerimentos', 'rotulo' => 'Requerimentos', 'href' => 'requerimentos.php', 'icone' => 'bi-file-earmark-check', 'site' => '/requerimentos.php', 'badge' => $badgeRequerimentos,
             'rel' => ['ver_requerimento.php'], 'dica' => 'Pedidos de documentos feitos online (Balcão Virtual)'],
            ['chave' => 'marcacoes', 'rotulo' => 'Marcações', 'href' => 'marcacoes.php', 'icone' => 'bi-calendar-check',
             'rel' => ['ver_marcacao.php'], 'dica' => 'Marcações de atendimento presencial'],
            ['chave' => 'reservas', 'rotulo' => 'Reservas de espaços', 'href' => 'reservas.php', 'icone' => 'bi-calendar2-check', 'site' => '/reservas.php', 'badge' => $badgeReservas,
             'dica' => 'Pedidos de reserva de salas e espaços, e os espaços disponíveis'],
            ['chave' => 'relatorio_pdf', 'rotulo' => 'Relatório (PDF)', 'href' => 'relatorio-pdf.php', 'icone' => 'bi-file-earmark-pdf',
             'dica' => 'Descarregar um relatório em PDF com os números de ocorrências e requerimentos'],
            ['sub' => 'Balcão'],
            ['chave' => 'modelos_requerimentos', 'rotulo' => 'Modelos de requerimento', 'href' => 'modelos-requerimentos.php', 'icone' => 'bi-folder2-open',
             'dica' => 'Formulários em PDF que os cidadãos podem descarregar'],
            ['chave' => 'balcao_documentos', 'rotulo' => 'Documentos do Balcão', 'href' => 'balcao-documentos.php', 'icone' => 'bi-journal-check',
             'se' => is_file(__DIR__ . '/../balcao-documentos.php'), 'dica' => 'Catálogo de documentos pedidos no Balcão Virtual e prazos'],
            ['chave' => 'faturacao', 'rotulo' => 'Faturação', 'href' => 'faturacao.php', 'icone' => 'bi-receipt', 'badge' => $badgeFaturacao,
             'se' => is_file(__DIR__ . '/../faturacao.php'), 'dica' => 'Pagamentos dos requerimentos'],
            ['sub' => 'Transparência'],
            ['chave' => 'recursos_humanos', 'rotulo' => 'Recursos humanos', 'href' => 'recursos-humanos.php', 'icone' => 'bi-person-vcard', 'site' => '/recursos-humanos.php',
             'dica' => 'Mapa de pessoal e documentos de recursos humanos'],
            ['chave' => 'procedimentos_concursais', 'rotulo' => 'Procedimentos concursais', 'href' => 'procedimentos-concursais.php', 'icone' => 'bi-clipboard-check',
             'dica' => 'Concursos de recrutamento abertos e concluídos'],
            ['chave' => 'contratacao_publica', 'rotulo' => 'Contratação pública', 'href' => 'contratacao-publica.php', 'icone' => 'bi-briefcase', 'site' => '/contratacao-publica.php',
             'dica' => 'Contratos e procedimentos de contratação pública'],
            ['sub' => 'Avisos'],
            ['chave' => 'alertas', 'rotulo' => 'Alertas no site', 'href' => 'alertas.php', 'icone' => 'bi-exclamation-octagon', 'site' => '/',
             'rel' => ['adicionar_alerta.php', 'editar_alerta.php'], 'dica' => 'Faixa de aviso no topo do site (ex.: corte de água, mau tempo)'],
            ['chave' => 'notificacoes', 'rotulo' => 'Notificações', 'href' => 'notificacoes.php', 'icone' => 'bi-bell',
             'dica' => 'Enviar notificações aos cidadãos registados'],
        ]],

        ['grupo' => 'assembleia', 'rotulo' => 'Assembleia de Freguesia', 'icone' => 'bi-bank2', 'perm' => 'assembleia',
         'dica' => 'Páginas e documentos da Assembleia de Freguesia', 'itens' => [
            ['chave' => 'assembleia_composicao', 'rotulo' => 'Composição', 'href' => 'assembleia-composicao.php', 'icone' => 'bi-people', 'site' => '/assembleia-composicao.php',
             'dica' => 'Mesa da Assembleia e membros eleitos'],
            ['chave' => 'assembleia_funcionamento', 'rotulo' => 'Funcionamento', 'href' => 'assembleia-funcionamento.php', 'icone' => 'bi-gear', 'site' => '/assembleia-funcionamento.php',
             'dica' => 'Como funcionam as sessões, mandato e regras'],
            ['chave' => 'assembleia_competencias', 'rotulo' => 'Competências', 'href' => 'assembleia-competencias.php', 'icone' => 'bi-card-checklist', 'site' => '/assembleia-competencias.php',
             'dica' => 'Atribuições e competências da Assembleia'],
            ['chave' => 'assembleia_documentos', 'rotulo' => 'Documentos', 'href' => 'assembleia-documentos.php', 'icone' => 'bi-folder2-open', 'site' => '/assembleia-documentos.php',
             'dica' => 'Atas, convocatórias e editais da Assembleia'],
            ['chave' => 'assembleia_sessoes', 'rotulo' => 'Sessões', 'href' => 'assembleia-sessoes.php', 'icone' => 'bi-calendar3', 'site' => '/assembleia-sessoes.php',
             'dica' => 'Calendário e ordem de trabalhos das sessões'],
            ['chave' => 'assembleia_votacoes', 'rotulo' => 'Votações', 'href' => 'assembleia-votacoes.php', 'icone' => 'bi-ui-checks',
             'dica' => 'Votações online dos membros da Assembleia'],
        ]],

        ['chave' => 'contactos', 'rotulo' => 'Contactos', 'href' => 'contactos.php', 'icone' => 'bi-telephone', 'site' => '/contactos.php',
         'rel' => ['adicionar_contacto.php', 'editar_contacto.php'], 'dica' => 'Página Contactos: textos e contactos úteis (GNR, Bombeiros, Centro de Saúde…)'],

        ['grupo' => 'newsletter', 'rotulo' => 'Newsletter', 'icone' => 'bi-envelope-paper', 'perm' => null,
         'dica' => 'Enviar novidades por email a quem subscreveu', 'itens' => [
            ['chave' => 'newsletter', 'rotulo' => 'Enviar newsletter', 'href' => 'newsletter.php', 'icone' => 'bi-send',
             'rel' => ['ver_newsletter_envio.php'], 'dica' => 'Escrever e enviar uma newsletter, e ver os envios anteriores'],
            ['chave' => 'newsletter_subscritores', 'rotulo' => 'Subscritores', 'href' => 'newsletter-subscritores.php', 'icone' => 'bi-people',
             'dica' => 'Pessoas inscritas para receber a newsletter'],
        ]],

        ['grupo' => 'sistema', 'rotulo' => 'Sistema', 'icone' => 'bi-gear-wide-connected', 'perm' => 'sistema',
         'dica' => 'Configurações gerais, aspeto do site e utilizadores', 'itens' => [
            ['chave' => 'configuracoes', 'rotulo' => 'Configurações gerais', 'href' => 'configuracoes.php', 'icone' => 'bi-sliders',
             'dica' => 'Nome, logótipo, contactos, redes sociais, cores e rodapé do site'],
            ['chave' => 'menu_site', 'rotulo' => 'Menus do site', 'href' => 'menu-site.php', 'icone' => 'bi-list-nested', 'site' => '/',
             'dica' => 'Menu do topo (com submenus), barra do telemóvel e ligações do rodapé'],
            ['chave' => 'aparencia', 'rotulo' => 'Aparência e mapa', 'href' => 'aparencia.php', 'icone' => 'bi-palette', 'site' => '/',
             'dica' => 'Ícone do separador, iniciais, posição da sede no mapa, foto do rodapé, cores do site e estatísticas'],
            ['chave' => 'separadores-fundo', 'rotulo' => 'Fundos das páginas', 'href' => 'separadores-fundo.php', 'icone' => 'bi-image',
             'dica' => 'Fotografia de fundo do topo de cada página do site'],
            ['chave' => 'utilizadores', 'rotulo' => 'Utilizadores', 'href' => 'utilizadores.php', 'icone' => 'bi-person-badge',
             'dica' => 'Quem pode entrar no backoffice e com que permissões'],
            ['chave' => 'seguranca', 'rotulo' => 'Segurança', 'href' => 'seguranca.php', 'icone' => 'bi-shield-check',
             'dica' => 'Estado da proteção do site, CAPTCHA e registo de auditoria (entradas, bloqueios, consultas de dados pessoais)'],
        ['chave' => 'perfis', 'rotulo' => 'Perfis', 'href' => 'perfis.php', 'icone' => 'bi-shield-lock', 'se' => isGesGov(),
             'dica' => 'Conjuntos de permissões (reservado à GesGov)'],
            ['chave' => 'estatisticas', 'rotulo' => 'Estatísticas', 'href' => 'estatisticas.php', 'icone' => 'bi-bar-chart',
             'se' => is_file(__DIR__ . '/../estatisticas.php'), 'dica' => 'Visitas e utilização do site'],
        ]],
    ];

    $menuAdminCompleto = $menuAdmin;

    /* Perfis com acesso limitado: menu curto, sem grupos */
    if (isOperador()) {
        $menuAdmin = [
            ['chave' => 'pedidos', 'rotulo' => 'Ocorrências', 'href' => 'pedidos.php', 'icone' => 'bi-megaphone', 'badge' => $badgePedidos,
             'rel' => ['ver_pedido.php', 'criar_pedido.php'], 'dica' => 'Problemas comunicados pelos cidadãos'],
        ];
    } elseif (isVogal()) {
        $menuAdmin = [
            ['chave' => 'area_vogais', 'rotulo' => 'Área dos Vogais', 'href' => '../area-vogais.php', 'icone' => 'bi-check2-square', 'dica' => 'Votações e documentos para os vogais'],
        ];
    } elseif (isPresidenteAssembleia()) {
        foreach ($menuAdmin as $e) if (($e['grupo'] ?? '') === 'assembleia') $menuAdmin = $e['itens'];
    } elseif (isAdminDenuncias()) {
        $menuAdmin = [
            ['chave' => 'denuncias', 'rotulo' => 'Canal de Denúncias', 'href' => 'denuncias.php', 'icone' => 'bi-shield-exclamation',
             'rel' => ['ver_denuncia.php'], 'dica' => 'Denúncias confidenciais recebidas (Lei 93/2021)'],
        ];
    }

    return [$menuAdmin, $menuAdminCompleto];
}

/**
 * Filtra o que este utilizador pode ver e descobre a entrada ativa.
 * Devolve [menu filtrado, entrada ativa ou null, rótulo do grupo da entrada ativa ou null].
 */
function menuAdminPreparar(array $menu, string $ativo): array
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $eAtivo = function (array $i) use ($ativo, $script): bool {
        return ($ativo !== '' && ($i['chave'] ?? '') === $ativo) || $script === basename($i['href'] ?? '')
            || in_array($script, $i['rel'] ?? [], true);
    };
    $saida = []; $atual = null; $grupoAtual = null;
    foreach ($menu as $e) {
        if (isset($e['itens'])) {
            if ($e['perm'] !== null && !podeAceder($e['perm'])) continue;
            $itens = []; $temSub = null;
            foreach ($e['itens'] as $i) {
                if (isset($i['sub'])) { $temSub = $i; continue; }
                if (($i['se'] ?? true) === false) continue;
                if (!menuAdminPaginaExiste($i['href'] ?? '')) continue;   // módulos que este site ainda não tem
                if ($temSub) { $itens[] = $temSub; $temSub = null; }   // separador só se tiver itens a seguir
                $i['ativo'] = $eAtivo($i);
                if ($i['ativo']) { $atual = $i; $grupoAtual = $e['rotulo']; }
                $itens[] = $i;
            }
            if (!$itens) continue;
            $e['itens'] = $itens;
            $e['aberto'] = (bool)array_filter($itens, fn($i) => !empty($i['ativo']));
            $e['badge'] = array_sum(array_map(fn($i) => (int)($i['badge'] ?? 0), $itens));
            $saida[] = $e;
        } else {
            if (($e['se'] ?? true) === false) continue;
            if (!menuAdminPaginaExiste($e['href'] ?? '')) continue;
            if (!empty($e['perm']) && !podeAceder($e['perm'])) continue;
            $e['ativo'] = $eAtivo($e);
            if ($e['ativo']) $atual = $e;
            $saida[] = $e;
        }
    }
    return [$saida, $atual, $grupoAtual];
}

/** A página do backoffice existe neste site? (redesign 10/2026: o mesmo menu serve sites com e sem cada módulo) */
function menuAdminPaginaExiste(string $href): bool
{
    if ($href === '' || strpos($href, '../') === 0) return true;
    return is_file(__DIR__ . '/../' . strtok($href, '?#'));
}
