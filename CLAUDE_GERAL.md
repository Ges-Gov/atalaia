# CLAUDE.md — Sites das Juntas de Freguesia (GesGov)

> Este ficheiro está na raiz `c:\laragon\www\` e é lido automaticamente quando se abre
> qualquer um dos sites. **Lê-o primeiro.** Cada site tem depois o seu próprio `CLAUDE.md`
> com a identidade dessa freguesia (BD, cores, domínio, prefixo).
>
> Última atualização: **29/09/2026** — acrescentado `assuncaoassi` (União das Freguesias de
> Assunção, Ajuda, Salvador e Santo Ildefonso, concelho de Elvas) — 23º site, copiado a partir do
> `arraiolos`. Coincide, em larga medida, com o centro histórico de Elvas (Património Mundial da
> UNESCO desde 2012). Conteúdo real de jf-assuncaoassi.pt (CMS próprio, sem armadilhas de SPA) +
> cm-elvas.pt (confirmação independente do presidente/morada) + pesquisa web (morada da sede, não
> publicada no site oficial): história/dados demográficos, heráldica (PDF oficial completo,
> brasão recortado em vetor sem perda de qualidade), executivo (5/5 com foto real), assembleia
> (13/13 com foto real), **20 pontos de interesse** com coordenadas reais (6 com foto real do
> Wikimedia Commons), **30 associações** (de ~45 identificadas), **7 farmácias**, **8 notícias
> reais** (2 com foto) e **8 documentos oficiais** descarregados. Sem secção de eventos/agenda no
> site oficial — documentado como tal, não inventado. Cores amostradas a pixel do PDF vetorial de
> heráldica: `#C70000` / `#FFDD00`. **Achado importante, a aplicar em todos os próximos sites**:
> `pagina_freguesia`, `pagina_historia`, `heraldica_pagina` e `contactos_pagina_config` nascem
> **vazias** (só ganham a primeira linha ao gravar pela primeira vez no backoffice) — um `UPDATE
> ... WHERE id=1` sobre uma tabela sem nenhuma linha não faz nada (0 registos afetados, sem
> erro), o que fez o site mostrar por baixo do panos o brasão antigo `heraldica-granho.webp`
> (fallback hardcoded no código, herdado do jfgranho) até se confirmar com `curl` e corrigir para
> `INSERT`. **Confirmar sempre com `SELECT COUNT(*)` antes de decidir entre `UPDATE`/`INSERT` ao
> popular um site novo — nunca assumir que a tabela já tem uma linha.** Ver `assuncaoassi/CLAUDE.md`
> para o processo completo e todas as fontes. Site só local, sem domínio nem deploy.
>
> Atualização anterior (25/09/2026) — migrados para o FastPanel os **10 sites que faltavam da
> lista "Prioritários"** (`sousel`, `sarilhosgrandes`, `vimieiro`, `avis`, `pavia`, `arraiolos`,
> `casabranca`, `cano`, `comporta`, `carvalhal`), todos em domínio `nip.io`, com utilizador
> `JF<Nome>` próprio da Junta criado em cada um. E, mais importante para o processo de
> trabalho: **todos os ~20 sites (os 10 novos + os 10 já reais em produção) passaram a ter
> repositório próprio no GitHub** (`github.com/Ges-Gov/<site>`, um por freguesia, sem
> monorepo) — 6 deles (`sousel`, `sarilhosgrandes`, `vimieiro`, `avis`, `pavia`, `arraiolos`)
> nunca tinham sequer sido criados no GitHub antes desta data. **As pastas de cada site no
> servidor (VPS) deixaram de ser só ficheiros soltos de um zip e passaram a ser clones git a
> sério**, ligados ao respetivo repositório via **chave SSH** associada à conta `Ges-Gov`
> (`git@github.com:Ges-Gov/<site>.git` — sem passwords/tokens). Confirmado, sem perdas: os
> ficheiros de credenciais (`db_config.php`/`mail_config.php`/`ia_config.php`) e os uploads
> reais de cada site continuam intocados (o `.gitignore` já os excluía; ficheiros só-servidor
> nunca são apagados por um `git checkout`/`pull`). Foi feito backup de ficheiros (não da BD,
> não fazia falta) antes de mexer nos 10 sites reais, em `/root/backups_pre_git/` no próprio VPS.
>
> **Isto muda o processo de propagar uma alteração a vários sites** (secção 5 tem o resto do
> processo de deploy, que continua válido para sites *novos*): para código já em produção, o
> caminho passa a ser `git push` a partir do dev local + `git pull` por SSH em cada pasta do
> servidor, em vez de gerar zip e extrair manualmente pelo File Manager em cada um. Para
> alterações de esquema de BD, `migrations/run.php` (ver secção 4) já corre por CLI
> (`php migrations/run.php`, idempotente) e, como as pastas agora têm git, chega lá sozinho via
> `git pull` — não precisa de ser colado à mão no phpMyAdmin. Ainda não existe o script final
> que percorre os ~20 sites de uma vez (nem no `git pull` nem no `migrations/run.php`); fica para
> montar da próxima vez que houver mesmo uma alteração para propagar.
>
> Atualização anterior (24/09/2026) — revistos os **6 sites mais antigos que nunca tinham sido
> vistos pelo utilizador** (`casabranca`, `cano`, `comporta`, `carvalhal`, `brotas`, `cabecao`),
> a pedido explícito: *"tenho uns sites na pasta que ainda nem sequer os vi... antes de os ver
> individualmente, gostava que fosses a cada um... e melhorasses"*. Encontrado e corrigido nos
> **6** um bug sistémico grave, presente desde a criação de cada site: `configuracoes_site`
> tinha ficado com os valores genéricos de scaffold (`nome_site = "Junta de Freguesia"`,
> `cor_principal`/`cor_secundaria` = `#242A30`/`#C8A02E`, literalmente as cores do `jfgranho` de
> origem) — nunca efetivamente atualizada, apesar do `tema_config` (acento, favicon,
> logo_iniciais) já estar correto desde a criação. **Ou seja, todos os 6 sites estiveram sempre
> a mostrar a marca errada**, apesar da documentação de cada um dizer o contrário. Corrigido nos
> 6 para os valores já documentados no `CLAUDE.md` de cada site (só o `casabranca` mudou de
> facto de cor: nunca se tinha chegado a amostrar o brasão real — agora `#005090`/`#C09020`,
> amostrado do brasão encontrado na Wikipédia). Encontrado também um segundo bug em todos os 6:
> `configuracoes_site.footer` fixo no texto genérico "© 2026 Junta de Freguesia", sobrepondo-se
> ao `nome_site` já corrigido (`footer.php` só usa `nome_site` como *fallback* quando `footer` é
> `NULL`) — corrigido (posto a `NULL`) nos 6. **Terceiro achado, em 3 dos 6** (`comporta`,
> `brotas`, `cabecao`): fotografias reais já descarregadas para `assets/img/`/
> `assets/img/galeria/` desde a criação do site, mas **nunca associadas** a nenhuma linha de
> `pontos_interesse`/`comercio_local` — ligadas as que correspondiam genuinamente ao que a foto
> mostra (visualização de cada foto antes de qualquer associação, nunca por nome de ficheiro),
> pesquisado conteúdo novo via Wikimedia Commons/Câmara Municipal do concelho para o resto. No
> `carvalhal`, o mesmo tipo de fotos "genéricas" já existentes (placa de entrada, campo de
> futebol, parque infantil) foram **explicitamente recusadas** para os pontos específicos
> (Ruínas Romanas de Tróia, praias) por seriam legenda falsa — substituídas por fotos específicas
> do Wikimedia Commons. `separadores_fundo` e `faqs` estavam vazias nos 6 — populadas. Ver o
> `CLAUDE.md` de cada site para o detalhe completo (fontes exatas, o que ficou por confirmar).
> Todos os 6 continuam só locais, sem domínio nem deploy.
>
> Atualização anterior (24/09/2026) — acrescentado `arraiolos` (concelho de Arraiolos, Évora) —
> 23º site, e **último da lista "Prioritários"** — copiado a partir do `pavia`. Conteúdo real de
> jf-arraiolos.pt: história (uma das 7 freguesias do concelho de Arraiolos, constituída por
> Arraiolos/Ilhas/Santana do Campo, 146 km², 3146 habitantes, tagline oficial «Terra de Tapetes,
> saberes e tradições»), heráldica, executivo (1/3 com foto real — os outros 2 usam uma imagem de
> perfil genérica partilhada na própria fonte, publicados sem foto para mostrar o fallback de
> iniciais), **14 pontos de interesse** e **15 associações** (de 19 identificadas), todos com foto
> real, e **6 notícias reais**. Cor principal — 1ª proposta `#96281B` (vermelho do `expectacao`,
> escolhido diretamente sem sequer tentar amostrar o brasão, aplicando a lição do Pavia); o
> utilizador não gostou e pediu explicitamente o vermelho do `sjbaptista` (o mesmo já usado no
> Pavia) — ficou `#B3001E`. **Lição**: quando o utilizador já validou um vermelho específico numa
> sessão anterior, essa é a referência a propor primeiro, não variar por iniciativa própria. Duas
> coisas resolvidas sem fabricar conteúdo: a
> "mensagem da presidente" do site oficial é de uma presidente anterior (Helena Espadaneira, fim
> de mandato) e contradiz o executivo atual (Carlos Cartaxo) — o widget foi desligado em vez de
> atribuir as palavras à pessoa errada; e a agenda de eventos do site oficial estava genuinamente
> vazia, documentado como tal em vez de forçar um evento.
>
> ⚠️ **Correção importante, no mesmo dia**: a primeira entrega tinha "0 comércio local" — o
> utilizador estranhou, com razão, e valeu a pena insistir. A pesquisa tinha parado na
> página-índice do `visitarraiolos.pt` (uma SPA renderizada em JS, ilegível a `curl`) sem testar
> se as páginas individuais de cada restaurante eram server-rendered — eram. Descoberto o padrão
> `visitarraiolos.pt/<id-numérico>` (sem prefixo `/menu/`), que devolve HTML já preenchido com
> caminhos de imagem reais mesmo quando a listagem que aponta para lá é só JS. Resultado: **7
> restaurantes reais com foto e telefone** (0→7) e, pelo mesmo mecanismo, o `sitemap.xml` do site
> revelou uma página de "Monumentos" com **10 pontos de interesse adicionais** (14→24) que a
> pesquisa original nunca tinha visto. **Lição para todos os sites futuros**: quando uma fonte
> parece "vazia" só porque a página principal é uma SPA ilegível a `curl`, tentar (1) uma página
> de detalhe individual isolada (se se souber ou adivinhar um ID) e (2) o `sitemap.xml` do
> domínio, antes de concluir que não há conteúdo. Ver `arraiolos/CLAUDE.md` para o processo
> exato. Site só local, sem domínio nem deploy. **Não há mais sites por construir da lista
> "Prioritários"** — os próximos passos dependem de novo pedido do utilizador.
>
> Atualização anterior (23/09/2026) — acrescentado `pavia` (concelho de Mora, Évora) — 22º site,
> copiado a partir do `avis`, aplicando já desde o início a lição do episódio do próprio avis
> (pesquisa exaustiva sem esperar o utilizador insistir). Conteúdo real de jf-pavia.pt (mesma
> família de template Joomla) + Câmara Municipal de Mora para comércio local: história (sede de
> concelho entre 1278 e 1838, foral de D. Dinis, imigrantes italianos, Fernando Namora, Manuel
> Ribeiro de Pavia), heráldica (escudo de vermelho, anta de prata, abelhas e trigo em ouro),
> executivo completo (3/3 com foto), **15 pontos de interesse** com foto real (a galeria
> "FotosGenericasFreg" do próprio site oficial já tinha tudo — não foi preciso fonte externa),
> **9 associações** com foto real (de 12 identificadas), **3 restaurantes** com foto real
> (cm-mora.pt, já que a página "visitar" do site oficial não tinha listagens próprias), **6
> notícias reais** e **1 evento já futuro** (12/12/2026). Cor secundária dourado institucional
> `#C9A227` (não amostrado do brasão — os elementos dourados eram pequenos demais para amostragem
> fiável). ⚠️ A cor principal foi amostrada do brasão (`#E71727`) mas o utilizador achou-a "super
> viva, até encandeia" — pediu para usar o vermelho já validado do `sjbaptista`. Ficou
> `#B3001E` (ver `pavia/CLAUDE.md` para a saga completa, incluindo uma correção a mais que ficou
> escura demais antes de acertar). **Lição**: um vermelho amostrado diretamente do brasão pode sair
> saturado demais para uso de UI — os vermelhos já validados no portefólio (`sjbaptista` `#B3001E`,
> `expectacao` `#96281B`) são a referência segura a propor primeiro. Zero rondas extra de
> pesquisa de conteúdo precisas desta vez. Duas armadilhas evitadas por terem sido aprendidas no `avis`: (1)
> sweep de nome com fronteiras de palavra (`\bAvis\b`, não substring simples — evita corromper
> "Aviso"→"Pavioso" etc.) e (2) "concelho de Avis"→"concelho de Mora" tratado à parte do sweep
> genérico, porque ao contrário do Avis, o concelho de Pavia não tem o mesmo nome da freguesia.
> `rm -rf .git` corrido logo a seguir ao `cp -r`, como já ficou registado na checklist. Ver
> `pavia/CLAUDE.md`. Site só local, sem domínio nem deploy. Resta por construir, pela ordem da
> lista "Prioritários": Arraiolos.
>
> Atualização anterior (23/09/2026) — acrescentado `avis` (concelho de Avis, Portalegre) — 21º
> site, copiado a partir do `vimieiro`. Conteúdo real de jf-avis.pt: história e heráldica da
> Ordem Militar de Avis (fundada em 1211), executivo completo (3/3 com foto), 6 associações com
> foto real, e a composição completa da Assembleia (9 membros, `grupo='Mesa da Assembleia'`
> correto desde o início). Cores por contraste WCAG: azul `#005090` (principal, só passa com
> texto branco) / dourado `#C09020` (secundária, só passa com texto escuro) — ver
> `avis/CLAUDE.md`. ⚠️ **Primeira ronda de pesquisa ficou-se pela página "Visitar" do site
> oficial e nem chegou a pesquisar comércio local** (só 4 pontos de interesse, 0 comércio) — o
> utilizador apontou, com razão, que isso era preguiça, não falta de conteúdo disponível. Uma
> segunda ronda, desta vez também pela **Câmara Municipal de Avis** (cm-avis.pt), encontrou
> **9 pontos de interesse adicionais com foto real** (Castelo de Avis e Torre da Rainha, Convento
> de São Bento, Igreja do Convento, Museu do Campo Alentejano, Pelourinho, Cisterna Medieval,
> Capela d'Entre Águas, Centro de Arqueologia, Fundação Abreu Callado — total 13) e **5
> restaurantes com foto e contacto** (Café Jardim, Mestre de Avis, Restaurante 180º, À da Maria,
> O Cantinho do Luca — total 5, todos confirmados dentro dos limites da freguesia de Avis,
> excluindo os de Ervedal/Alcórrego/Aroeira, que são outras freguesias do mesmo concelho).
> **Lição a aplicar já no próximo site**: quando o site oficial da Junta for pobre nalguma secção,
> tentar sempre a Câmara Municipal do mesmo concelho como segunda fonte antes de declarar "não
> encontrado" — tem quase sempre uma página de "Locais"/"Turismo" mais completa e com fotos
> próprias. Descoberto também um **quarto caminho de resolução de imagem**:
> `separadores_fundo.imagem` lê de `uploads/separadores/`, não de `assets/img/` como as outras
> tabelas de conteúdo. Corrigido também um erro de sintaxe PHP idêntico ao do `sarilhosgrandes`/
> `vimieiro` (aspas retas dentro de string SQL em aspas duplas — `"Aviz"` → `«Aviz»`) e uma frase
> hardcoded fora da BD ("história desde o foral", copiada literalmente do Vimieiro, sem sentido
> para Avis) em `index.php`. **Lição nova**: `cp -r <origem> <novo-site>` copia também o `.git`
> da origem inteiro (histórico incluído) — invisível num `ls` normal; um primeiro `git init`
> dentro dessa pasta não limpa esse histórico, só reinicializa a configuração. É preciso
> `rm -rf .git` explícito logo a seguir ao `cp -r`, antes de mais nada. Comércio de bens
> (mercearias, supermercados) continua sem nenhum resultado nomeado/fotografado em qualquer fonte
> pesquisada — lacuna genuína de fonte pública. Site só local, sem domínio nem deploy. Restam por
> construir, pela ordem da lista "Prioritários": Pavia, Arraiolos.
>
> Atualização anterior (22/09/2026) — acrescentado `vimieiro` (Arraiolos) — 20º site, copiado a
> partir do `sousel` (não do `sarilhosgrandes`, que entretanto ficou com desvios só corretos
> para secundária escura). Desta vez o brasão real tinha vermelho **e** dourado genuínos —
> cor_principal/cor_secundaria decididas por cálculo de contraste WCAG antes de escolher, não
> depois: vermelho só passa com texto branco, dourado só com texto escuro, logo vermelho =
> principal, dourado = secundária. Zero problemas de contraste. Todas as lições do
> `sarilhosgrandes` aplicadas desde o início (HTML em bruto para imagens, PDO com parâmetros para
> texto acentuado, `grupo='Mesa da Assembleia'` correto de início, kicker dourado ajustado ao
> tom exato da marca) — ver `vimieiro/CLAUDE.md`. Site oficial (jfvimieiro.pt) excecionalmente
> completo: 12 pontos de interesse, 5 associações e executivo completo, todos com foto/emblema
> real. Por confirmar: comércio local (nada encontrado ainda) e documentos da assembleia.
>
> Atualização anterior (22/09/2026) — acrescentado `sarilhosgrandes` (Montijo) — 19º site,
> copiado a partir do `sousel`. Conteúdo real do site oficial (freguesiadesarilhosgrandes.pt) +
> Wikimedia Commons (fotos licenciadas) + Nominatim/OSM — ver `sarilhosgrandes/CLAUDE.md` para
> as fontes exatas e o que ficou por confirmar (associações e vogais sem foto, documentos ainda
> não publicados na fonte oficial). Corrigido um bug de dados **também presente no `sousel`**:
> `assembleia-composicao.php` espera `grupo='Mesa da Assembleia'` na tabela
> `assembleia_composicao`, não `'Mesa'` — com o valor errado, a Mesa aparecia na secção "Outros
> elementos" em vez da sua própria secção. Corrigido nos dois sites; vale a pena confirmar os
> restantes antes do próximo deploy que toque nessa tabela.
>
> ⚠️ **Lição sobre amostragem de cores por pixel**: a cor secundária do `sarilhosgrandes` foi
> inicialmente amostrada do verde dos molhos de trigo do brasão (`#285945`), com a principal a
> vermelho (`#CC2F2F`) — mas **toda a folha de estilos partilhada (CORE) assume principal escura
> + secundária clara** (botões e badges usam sempre texto escuro sobre `var(--cor-secundaria)`,
> ver `.historia-btn.primary` em `historia.php`). Com as duas cores escuras trocadas, o
> contraste ficou ilegível em vários sítios (barra de topo, botões). Corrigido: `#285945` passou
> a principal (é escura, funciona bem com texto branco), e a secundária passou a um dourado
> `#C9962E` — **não amostrado do brasão** (não há elemento dourado nele), mas escolhido para
> respeitar a convenção dourada já usada em todos os outros sites do portefólio. Também trocado
> o `footer_bg`/`fundo` genéricos (`#F2EAD6`/`#FAF6EC`, herdados da seed e descritos como "amarelo
> xixi") por tons neutros mais limpos (`#F2F4F1`/`#F7F8F5`). **Ao criar o próximo site**: depois
> de amostrar cores do brasão, confirma sempre que a mais escura fica em `cor_principal` e a
> mais clara em `cor_secundaria` — e se nenhuma das duas for suficientemente clara para texto
> escuro em cima, usa um dourado institucional em vez de forçar uma cor do brasão que não serve.
>
> ⚠️ Achado maior, ao trocar depois o dourado por vermelho a pedido do utilizador: o "kicker"
> dourado (`#C8A02E` / `#f8d77a` / `#856611` / `rgba(240,180,41,...)`) está **hardcoded em CSS
> inline em quase todas as páginas**, públicas e do backoffice — não usa `var(--tema-acento)`.
> Confirmado no `jfgranho`: são exatamente os mesmos literais, não só no `sarilhosgrandes`. Passa
> despercebido em sites cuja `cor_secundaria` já é dourada por coincidência (a maioria), mas é
> uma violação da disciplina CORE/MARCA — a cor devia vir do tema, não estar escrita no código.
> Só corrigido no `sarilhosgrandes` (65 ficheiros, substituição literal, não a variável). Por
> considerar no futuro: migrar estes kickers para `var(--tema-acento)`, propagável aos 19 sites.
>
> Atualização anterior (16/09/2026) — ⚠️ achado importante: `admin/pedidos.php` e
> `admin/assembleia-documentos.php` **não são CORE idêntico** como seria suposto — têm cores da
> freguesia escritas diretamente no código (ex.: `#4B4F54` no torrão) em vez de
> `var(--cor-principal)`, resquício de antes da disciplina CORE/MARCA estar montada. Confirmado
> por `md5sum`: só 8 dos 16 sites partilhavam a mesma versão de `pedidos.php`, os outros 8 tinham
> cada um a sua (`admin/assembleia-documentos.php` só diverge em 3: riomoinhos, vilaboim,
> valedeagua). **Antes de propagar uma correção nestes 2 ficheiros, confirma sempre por
> `md5sum` se o destino é mesmo igual à origem — não presumas.** Nesta data foi aplicado um novo
> layout à tabela de Ocorrências (`.tabela-moderna` em `admin/assets/admin.css`, esse sim 100%
> idêntico nos 16) e corrigidos os filtros de "Documentos da Assembleia" (grelha rígida a cortar
> texto), preservando a cor própria de cada site — ver `relatorio_estagio.txt`, Dia 61, para o
> processo usado (substituição validada por contagem exata, falha em vez de aplicar parcial).
>
> Atualização anterior (18/08/2026): acrescentados `comporta` (Alcácer do Sal), `carvalhal`
> (Grândola), `brotas` e `cabecao` (ambos de Mora) — 15º a 18º sites, copiados a partir do
> `jfgranho`, já com todas as funcionalidades mais recentes. Todos os 4 têm site oficial próprio
> usado como fonte de conteúdo real (investigação feita por subagentes de pesquisa, com fontes
> citadas). Ficam só locais, sem domínio nem deploy. Nesta ronda encontrou-se e corrigiu-se (só
> nestes 4 sites, não propagado ao resto do portefólio) um bug real herdado do `jfgranho`: o
> texto "GR" hardcoded como marca de água/placeholder em 6 sítios do código, em vez de ler
> `tema_config.logo_iniciais` — ver `comporta/CLAUDE.md` para a lista exata dos ficheiros.
> Encontrou-se também um terceiro caminho de resolução de imagem (`heraldica_pagina.imagem` lê de
> `uploads/heraldica/`, diferente dos outros dois já documentados). Antes disso (17/08/2026),
> acrescentados `casabranca` e `cano` (13º e 14º sites, réplicas do concelho de Sousel) — ver os
> `CLAUDE.md` de cada um para lacunas de conteúdo conhecidas (sobretudo o `casabranca`, sem site
> oficial próprio nem fotos reais). Antes disso (07/08/2026), pacotes de deploy
> gerados/regenerados para os 3 sites de então (`sjbaptista`, `expectacao`, `valedeagua`), com
> sugestão de domínio `nip.io` no `LEIA-ME.md` de cada um. O `crato` (8º site) foi acrescentado a
> 24/07/2026. A uniformização original dos 7 sites (secção anterior desta nota) foi concluída a
> 14/07/2026.

---

## 1. O que é este projeto

**24 sites** de Juntas de Freguesia, em `c:\laragon\www\`. Dez são reais e já em produção; treze
(`casabranca`, `cano`, `comporta`, `carvalhal`, `brotas`, `cabecao`, `sousel`, `sarilhosgrandes`,
`vimieiro`, `avis`, `pavia`, `arraiolos`, `assuncaoassi`) são réplicas novas, só locais, sem
domínio nem deploy ainda; um é ambiente de testes.

> **Atualização 22/09/2026:** acrescentado `sousel` — 18º site (Sousel, Portalegre), um dos
> "Prioritários" da lista de sites GesGov. Cores amostradas a pixel do brasão real. Conteúdo real
> recolhido do site oficial (jf-sousel.pt) + pesquisa complementar (Nominatim/OSM para os pontos
> de interesse) + confirmação direta do utilizador para o comércio local (é natural de Sousel) —
> ver `sousel/CLAUDE.md` para as fontes exatas e o que ficou por confirmar. Existia uma pasta
> antiga `jfsousel` (especificação nunca construída para uma versão em Laravel deste site, sem
> nenhuma relação com o site atual) — renomeada para `jfsousel(teste)` para não se confundir.
>
> **Atualização 10/08/2026:** o utilizador confirmou que `crato`, `sjbaptista`, `expectacao` e
> `valedeagua` já estão em produção (BD igual à dos outros 6). A nota anterior desta secção
> ("ainda por acabar de povoar e sem deploy" / "crato nunca foi para produção") ficou desatualizada
> — não verificado de forma independente (sem acesso ao FastPanel), mas passa a tratar-se estes 4
> como sites reais para efeitos de deploy incremental (SQL de migração, não dump completo), tal
> como os outros 6.

| Pasta | Freguesia | BD local | Prefixo | Cores (principal / secundária) |
|---|---|---|---|---|
| `riomoinhos` | Rio de Moinhos (Borba) | `riomoinhos` | `RM-` | `#0d3b66` / `#f0b429` |
| `jfgranho` | Granho (Salvaterra de Magos) | `jfgranho` | `GR-` | `#242A30` / `#C8A02E` |
| `saodomingos` | São Domingos (Santiago do Cacém) | `saodomingos` | `SD-` | `#242A30` / `#C8A02E` |
| `torrao` | Torrão (Alcácer do Sal) | `torrao` | `TR-` | `#4B4F54` / `#A4231D` |
| `terrugem` | Terrugem (Elvas) | `terrugem` | `TG-` | `#1c3f6e` / `#c8a02e` |
| `vilaboim` | Vila Boim (Elvas) | `vilaboim` | `VB-` | `#006B3F` / `#C8A02E` |
| `crato` | União das Freguesias de Crato e Mártires, Flor da Rosa e Vale do Peso (Crato) | `crato` | `CR-` | `#B22325` / `#C8A02E` |
| `sjbaptista` | São João Baptista (Campo Maior) | `sjbaptista` | `SJ-` | `#7A2331` / `#C9A227` |
| `expectacao` | Nossa Senhora da Expectação (Campo Maior) | `expectacao` | `NE-` | `#96281B` / `#C8A02E` |
| `valedeagua` | Vale de Água (Santiago do Cacém) | `valedeagua` | `VA-` | `#A31C1C` / `#C8A02E` |
| `casabranca` | Casa Branca (Sousel) | `casabranca` | `CB-` | `#005090` / `#C09020` |
| `cano` | Cano (Sousel) | `cano` | `CN-` | `#2F5D3A` / `#D4A72C` |
| `comporta` | Comporta (Alcácer do Sal) | `comporta` | `CP-` | `#1B6B45` / `#D4A72C` |
| `carvalhal` | Carvalhal (Grândola) | `carvalhal` | `CV-` | `#55672E` / `#C9A227` |
| `brotas` | Brotas (Mora) | `brotas` | `BT-` | `#1F4E79` / `#C8A02E` |
| `cabecao` | Cabeção (Mora) | `cabecao` | `CC-` | `#5B2A5E` / `#D4A72C` |
| `sousel` | Sousel (Sousel) | `sousel` | `SOU-` | `#355F3A` / `#B79420` |
| `sarilhosgrandes` | Sarilhos Grandes (Montijo) | `sarilhosgrandes` | `SG-` | `#285945` / `#B33A2E` |
| `vimieiro` | Vimieiro (Arraiolos) | `vimieiro` | `VM-` | `#A61C1C` / `#D4AA00` |
| `avis` | Avis (Avis) | `avis` | `AV-` | `#005090` / `#C09020` |
| `pavia` | Pavia (Mora) | `pavia` | `PV-` | `#B3001E` / `#C9A227` |
| `arraiolos` | Arraiolos (Arraiolos) | `arraiolos` | `AR-` | `#B3001E` / `#C9A227` |
| `assuncaoassi` | Assunção, Ajuda, Salvador e Santo Ildefonso (Elvas) | `assuncaoassi` | `ASSI-` | `#96281B` / `#FFDD00` |
| `riomoinhos-test` | *(ambiente de testes)* | `riomoinhos_test` | `RM-` | `#0d3b66` / `#f0b429` |

O `riomoinhos-test` é o único que tem **Concursos** (candidaturas a concursos públicos).
É deliberado: é uma funcionalidade que pode vir a ser implementada, e vive só ali.

**Utilizador:** Filipe Dórdio, estagiário de Engenharia Informática (ramo Engenharia de Software,
ESTSetúbal/IPS) na GesGov. **Pedro Prates é o Diretor Geral da GesGov** e supervisor de estágio
(`pedro.prates@gesgov.pt`) — não é o estagiário; é "a chefia"/"o patrão" referido no relatório de estágio.
Escreve em português de Portugal; responde-lhe em pt-PT.

---

## 2. Stack

- **PHP 8.3 procedural**, *page-per-file*, sem framework e sem router. Cada página é um `.php`
  na raiz (site público) ou em `admin/` (backoffice).
- **MySQL/MariaDB** via **PDO**, `utf8mb4`, `ERRMODE_EXCEPTION`.
- **Sem build step.** CSS à mão, ícones Bootstrap Icons via CDN.
- Passwords do admin em **MD5** (inseguro, herdado — **não refatorar sem pedir**).
- **Local:** Laragon. PHP em `/c/laragon/bin/php/php-8.3.30-*/php.exe`,
  MySQL em `/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe` (root / `pinguluro_2004`).
- **Produção:** VPS **Contabo** (IP `185.205.244.198`) com painel **FastPanel**.
  Domínios reais: `jfriodemoinhos.pt`, `jf-granho.pt`. Os restantes em `nip.io`
  (ex.: `terrugem.185.205.244.198.nip.io`) enquanto não há domínio próprio.

---

## 3. A arquitetura que interessa: CORE vs MARCA

**Este é o conceito central do projeto. Se só leres uma secção, lê esta.**

- **CORE** = o código. É **idêntico, byte a byte, nos 7 sites**. Uma correção feita uma vez
  serve todos. Verifica-se com `md5sum`.
- **MARCA** = cores, logótipo, iniciais, nome da freguesia, limite geográfico.
  Vive **na base de dados** (ou em ficheiros de dados próprios de cada site), **nunca no código**.

### Porque é que isto existe

Antes, a identidade de cada freguesia estava *dentro* dos ficheiros partilhados — em especial
um bloco `:root{--cor-principal:#XXXXXX !important}` no fim do `includes/footer.php`, que
**sobrepunha até os valores da base de dados**. Copiar o `footer.php` de um site para outro
trocava-lhe a identidade visual. Como esse ficheiro é carregado por todas as páginas, **nenhuma
correção podia ser portada com segurança**. Era esta a causa-raiz de tudo.

### Regra prática

> **Nunca metas uma cor, um nome de freguesia ou um brasão dentro de um ficheiro core.**
> Se precisas de uma cor, usa `var(--cor-principal)` / `var(--tema-*)`.
> Se precisas de um tom derivado, usa `color-mix(in srgb, var(--cor-principal) 55%, #000)`.

### Peças

- `includes/tema.php` — `temaConfig($chave, $default)`, `temaVariaveisCss()`.
  Lê a tabela `tema_config` (chave/valor) e emite variáveis CSS no `:root`.
- `includes/header.php` — emite o `:root` com `--cor-principal`, `--cor-secundaria` e os tokens do tema.
- `includes/footer.php` — a "camada de tema" clara, que **consome** os tokens. Ligada/desligada
  pelo interruptor `tema_camada` em `tema_config`.
  **`tema_camada = 0` no `riomoinhos` e no `riomoinhos-test`** (têm tema escuro próprio);
  **`= 1` nos outros 5**.
- `includes/galeria.php` — álbuns, upload seguro, `imagemGaleriaUrl()` (resolve ficheiros
  legados em `assets/img/` **e** novos em `assets/img/galeria/`, sem mover nada em produção).
- `includes/membros.php` — fotos dos membros, `fotoMembroUrl()` (mesma lógica de caminhos legados).
- `includes/mapa.php` — `centroFreguesia()` (calcula centro+zoom a partir do bounding box do
  geojson) e `geojsonFreguesiaJs()` (injeta o geojson como literal JS).
  O limite vive em **`assets/geo/freguesia.geojson`** — um ficheiro por site, mesmo nome.
- `admin/includes/auth.php` — `isGesGov()`, `podeAceder($seccao)`, perfis de permissões.

---

## 4. Migrações

Pasta `migrations/` em cada site: ficheiros `.sql` numerados + `run.php` (executor).
Uma tabela `migracoes` regista o que já correu. **São idempotentes**: só criam e acrescentam,
nunca apagam. Podem correr sobre bases de dados de produção com dados reais.

Existem **16**: `001_tema_config` · `001_tema_config_seed` · `002_separadores_fundo` ·
`003_marca_logo` · `003_marca_logo_seed` · `004_faqs` · `005_galeria_albuns` · `006_evento_foco` ·
`007_perfis` · `008_tema_admin` · `008_tema_admin_seed` · `009_mensagem_presidente` ·
`010_alinhar_colunas` · `011_album_freguesia` · `012_albuns_pontos_existentes` · `013_mostrar_galeria`

Os ficheiros `*_seed.sql` são os **únicos** que diferem entre sites — carregam a marca de cada um.
Todo o resto do DDL é byte a byte igual.

### Armadilhas ao escrever migrações

1. **Nunca uses `CREATE PROCEDURE`.** O `;` dentro do corpo parte o `$pdo->exec()` do ficheiro
   inteiro. Usa `SET @sql := IF(...)` + `PREPARE`/`EXECUTE`.
2. **Não ponhas `DEFAULT CHARSET` no `CREATE TABLE`.** Deixa herdar o da BD. Caso contrário
   as tabelas novas ficam `utf8mb4_0900_ai_ci` e as antigas `utf8mb4_general_ci` →
   *"Illegal mix of collations"* ao comparar strings.
3. Ao comparar strings entre tabelas, força a collation dos **dois** lados:
   `CONVERT(x USING utf8mb4) COLLATE utf8mb4_bin = CONVERT(y USING utf8mb4) COLLATE utf8mb4_bin`.

---

## 5. Deploy

Cada site tem `deploy/` com:

- `<site>_deploy.zip` — o código;
- `<site>_migracoes.sql` — as alterações de BD (**não é um dump**: não apaga nada);
- `LEIA-ME.md` — instruções.

**O ZIP nunca leva:** `includes/db_config.php` (credenciais), `uploads/`, `assets/img/*`,
`assets/docs/`, `vendor/`, `dompdf/`, `.md`, `.zip`, `.sql` soltos.
São dados/credenciais de produção, ou dependências que já existem no servidor.

### Ordem (importa)

1. **Backup**: BD (exportar do phpMyAdmin) **e** ficheiros.
2. **SQL primeiro**, código depois. O código novo precisa das tabelas novas; o código antigo
   convive bem com elas. Ao contrário, o site fica partido no intervalo.
3. **ZIP** → File Manager → raiz do site → *Extract*.
4. **Permissões** (ver abaixo).

### ⚠️ A armadilha que já rebentou o jf-granho.pt

**Confirma sempre o nome da base de dados antes de importar.** O Granho tem **duas** BDs no
servidor (`granho_` e `jfgranho`, resquício de um transplante). O SQL foi importado na `granho_`,
correu **sem um único erro** — e o site (que lê a `jfgranho`) ficou com **HTTP 500**, porque
as tabelas foram criadas na casa ao lado.

> **Abre sempre o `includes/db_config.php` do site no servidor**, vê o nome da BD, e confirma
> que é **esse** que aparece no topo do phpMyAdmin.

### Cores em produção

Cada `_migracoes.sql` termina com um `UPDATE configuracoes_site SET cor_principal=..., cor_secundaria=...`
**Isto é obrigatório.** Como o `footer.php` já não força as cores no código, passam a vir da BD —
e a BD de produção do Granho tinha `#540303` (vermelho escuro), que não é a cor do site.
Sem este `UPDATE`, o jf-granho.pt ficaria vermelho.

---

## 6. Permissões no servidor (FastPanel)

**Regra: pastas a `755`, ficheiros a `644`.** Sem exceções.

Numa **pasta**, o bit de execução não significa "executar" — significa **"poder entrar"**.
Uma pasta a `644` está trancada: nem o dono lá entra, e o PHP não consegue escrever.
Foi esta a causa das falhas de upload (documentos, anexos, fotos dos membros).

O **dono** também tem de estar certo (`<site>_usr`, não `root`).

Pastas onde o código escreve: `uploads/` (e subpastas), `assets/img/` (+ `galeria/`, `membros/`),
`assets/docs/`.

```bash
find uploads assets -type d -exec chmod 755 {} \;
find uploads assets -type f -exec chmod 644 {} \;
```

**Anomalia conhecida:** partes de `vendor/` e `dompdf/` pertencem ao `root` e o utilizador do site
nem as consegue ler — o que faz falhar o `tar` do FastPanel ao criar backups. Contorna-se
excluindo essas pastas do backup (o deploy também não lhes toca).

---

## 7. Armadilhas / lições (LER antes de mexer)

1. **`ob_start()` incondicional no topo dos dois headers** (público e admin). É preciso porque há
   `header()` de redirect depois de já haver output. Não usar `if(!ob_get_level())`.
2. **`admin_utilizadores.tipo` é um ENUM.** Para um tipo novo é preciso `ALTER TABLE ... MODIFY`
   **antes** do INSERT, senão o INSERT falha em silêncio.
3. **`php -l` NÃO valida CSS.** E um HTTP 200 só diz que o ficheiro existe, não que está bem formado.
   Já aconteceu cortar a chaveta que fechava um `@media` e engolir ~1000 regras — os 7 sites
   apareceram sem formatação nenhuma. **Valida o aninhamento do CSS com um parser**, no ficheiro
   de origem **e dentro do ZIP**, e serve o site a partir do ZIP para confirmar.
4. **Nunca apagues ficheiros por data de modificação.** Já se apagou por engano a folha de estilos
   de um site assim. Apaga por nome/padrão explícito.
5. **Ao mudar CSS, sobe a versão de cache** (`style.css?v=AAAAMMDD` no `includes/header.php`),
   senão o browser serve o antigo e "a correção não aparece".
6. **`admin/homepage.php` faz `isset($_POST['x']) ? 1 : 0`.** Um POST parcial (ex.: por `curl`)
   **desliga todos os interruptores** `mostrar_*`. Já aconteceu.
7. **Não há botão de apagar denúncias, por design.** Só via BD, removendo primeiro
   `denuncias_anexos` e `denuncias_mensagens`.
8. **Utilizadores ocultos**: `$utilizadoresOcultos = ['admin', 'admin@denuncias']` em
   `admin/utilizadores.php`. Existem e fazem login, mas não aparecem na lista. São os da GesGov —
   só eles podem criar/atribuir **perfis** (`isGesGov()`).
9. **Encoding no git-bash:** `export LC_ALL=C.UTF-8` antes de fazer `grep` com acentos.
10. **O `Compress-Archive` do PowerShell gera ZIPs com barras invertidas.** Gera-os antes com
    `System.IO.Compression.ZipArchive`, criando as entradas com `/`.

---

## 8. Estado atual (14/07/2026)

### Feito

- Os **7 sites uniformizados**: core idêntico (`md5sum` igual), marca na BD.
- **16 migrações** aplicadas nos 7 (local e produção).
- **Galeria por álbuns**: uma só galeria. Os pontos de interesse e os eventos criam
  automaticamente um álbum com o nome do ponto / título do evento. A galeria aparece na
  **última secção da página inicial**, antes do rodapé (interruptor `mostrar_galeria`,
  em *Página Inicial → Textos Homepage*).
- **Perfis de permissões** no backoffice (só o utilizador oculto da GesGov os gere).
- **Mapas**: limite real do OpenStreetMap em `assets/geo/freguesia.geojson`, centro calculado.
- **Backoffice igual em todos** — mesmas funcionalidades e mesmo layout; muda só a cor e o brasão.
- **Deploy feito em produção nos 6 sites reais.**
- Corrigidos: fundo dos separadores, checkboxes desalinhadas, vulnerabilidade no upload de slides
  (aceitava `.php`), mensagem do presidente sem campo próprio, calendário de eventos não clicável,
  cartões dos pontos a azul/roxo, foto do ponto cortada (`object-fit:cover`).

### Por fazer / por confirmar

- **Brasão do Terrugem**: `assets/img/brasao-terrugem.png` ainda não foi colocado (cai no
  fallback das iniciais "TG").
- **SMTP**: `includes/mail_helper.php` — falta configurar conta real + App Password.
- **Executivo do Terrugem**: só o presidente é conhecido.
- Conteúdos por preencher em vários sites (fotos, associações, comércio, FAQs, contactos úteis).
- A BD `granho_` ficou com tabelas a mais (importação enganada). Inofensivo — ninguém a lê.
  Pode ser limpa com calma.

### ⏳ Por propagar / por fazer deploy (24/07/2026)

Trabalho feito **só localmente** nesta sessão (a partir da pasta `crato`, mas os ficheiros
foram tocados diretamente nas 8 pastas — não precisa de ser reescrito, só de deploy):

- **`historia.php` — cartão `.historia-side` redesenhado** (glow radial + "bolha" decorativa
  na cor de destaque do site via `color-mix()`, ícone com gradiente/sombra, kicker em
  maiúsculas). Tirou-se a abreviatura grande da freguesia (`CR`/`GR`/`RM`/`SD`/`TR`/`TG`/`VB`)
  que aparecia como marca de água — já não existe em nenhum site. Aplicado **já** aos 8:
  `riomoinhos`, `jfgranho`, `saodomingos`, `torrao`, `terrugem`, `vilaboim`, `crato`,
  `riomoinhos-test`. **Ainda não foi feito deploy** nos 6 que estão em produção (todos
  menos `crato`, que nunca teve deploy, e `riomoinhos-test`, que é só teste).
- De caminho corrigiu-se um bug de cópia: o `terrugem` tinha "TR" (do torrão) em vez de "TG"
  — irrelevante agora que a abreviatura foi removida, mas fica registado.
- **Chatbot de IA (Gemini)** — ver `ajax/chat_ia.php` e `includes/footer.php` — só existe
  em `jfgranho` e `crato`. Duas correções feitas nos dois (contexto com as páginas fixas do
  site + links `[Nome](/pagina.php)` clicáveis a abrir em nova aba + histórico do chat
  persistido em `sessionStorage`): aplicadas em ambos, mas **sem deploy** — o `jfgranho` está
  em produção e ainda não recebeu isto.
- Migração nova `015_heraldica_elementos_imagem.sql` (coluna `imagem` opcional em
  `heraldica_elementos`, core, idempotente) — corrida localmente em `crato` e `jfgranho`.

**Se abrires uma sessão numa destas pastas para preparar deploy**: o código já está pronto,
falta só gerar `<site>_deploy.zip` + `<site>_migracoes.sql` (a migração 015, só para
jfgranho) seguindo o processo da secção 5. Não é preciso voltar a editar `historia.php`.

---

## 9. Como trabalhar aqui

- **Verifica antes de afirmar.** Serve o site, faz `curl`, lê a BD. Não assumas que uma alteração
  funcionou só porque o ficheiro foi escrito.
- **Não regridas os sites.** Antes de alterar, confirma que está a funcionar.
- **Se tiveres dúvidas, pergunta.** Foi pedido explicitamente.
- **Uma correção num ficheiro core aplica-se aos 7** — propaga com `cp` e confirma com `md5sum`.
- Documentos relacionados: `relatorio_estagio.txt` (relatório de estágio, por dias),
  `ARQUITETURA_E_DEPLOY.md` e `PLANO_DENUNCIAS_CENTRAL.md` (em alguns sites).
