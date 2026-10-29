# CLAUDE.md — Atalaia e Alto Estanqueiro-Jardia

> **Lê primeiro o `c:\laragon\www\CLAUDE.md`** (há uma cópia neste repositório em `CLAUDE_GERAL.md`).
> Este ficheiro tem só o que é **específico desta freguesia**.

## Identidade

| | |
|---|---|
| **Freguesia** | União das Freguesias de Atalaia e Alto Estanqueiro-Jardia |
| **Concelho** | Montijo |
| **Distrito** | Setúbal |
| **BD local** | `atalaia` |
| **Site oficial (fonte)** | juf.aaej.pt (o antigo jfaaej.pt já não tem conteúdo) |
| **Prefixo de denúncias/pedidos** | `AAEJ-` (a sigla que a própria Junta usa) |
| **Cor principal** | `#312783` (azul-violeta — Manual de Normas Gráficas da Junta, dez. 2025) |
| **Cor secundária** | `#F9B233` (amarelo do mesmo manual) |
| **Iniciais do logótipo** | AAEJ |
| **Logótipo** | `assets/img/logo-aaej.png` (símbolo) e `uploads/heraldica/logo-aaej-oficial.png` (completo) |

## Como foi criado (30/09/2026, sessão cloud)

1. Código copiado do **`caia`** (o site mais recente) com `git archive` e **todo o conteúdo do
   Caia apagado** (fotos, documentos, uploads, deploy). Sweep: "Caia, São Pedro e Alcáçova" →
   "Atalaia e Alto Estanqueiro-Jardia", `CSPA` → `AAEJ`, "concelho de Elvas" → "concelho do
   Montijo", "Portalegre" → "Setúbal". Textos de fallback em `freguesia.php`, `admin/freguesia.php`
   e `admin/homepage.php` reescritos com conteúdo real. `grep` final: zero ocorrências de
   Caia/Elvas/Alcáçova/CSPA.
2. BD `atalaia` = dump do Caia (esquema + migrações 001–032) + `deploy/atalaia_conteudo.sql`, que
   apaga o conteúdo do Caia e semeia o da Atalaia. **Lê esse ficheiro: tem as fontes em comentário.**
3. `assets/geo/freguesia.geojson`: relação OSM **6444914** (admin_level 8, `border_type=freguesia`,
   população 5379 — bate com os Censos 2021), 221 pontos.

## Marca

- **Não há brasão da união**: a ordenação heráldica ainda não foi publicada em Diário da República
  (confirmado na página da CM Montijo). A Junta adotou em **dezembro de 2025 um logótipo** com
  manual de normas gráficas (PDF em juf.aaej.pt › Manual Logotipo). O símbolo estiliza o Cruzeiro
  Mor. Recortado do PDF vetorial a 400 dpi, bordas verificadas (alfa 0).
- Cores do manual: `#312783` (principal) e `#F9B233` (secundária). O manual tem mais cores
  (`#BE1622`, `#1D71B8`, `#006633`, `#008D36`, `#F39200`, `#7993CB`, `#00A659`) — não usadas.
- **Heráldica**: imagem principal = logótipo oficial; elementos = brasões e bandeiras das antigas
  freguesias (texto oficial da CM Montijo, "Descrição Heráldica"). A página de Heráldica só mostra
  ícones nos elementos (não imagens) — as imagens das bandeiras antigas existem na CM Montijo
  (`Atalaia_1_2500_2500.png`, `Estanqueiro_1_2500_2500.png`, 199×237) mas não foram usadas.
- ⚠️ **heraldicacivica.pt está comprometido**: em 30/09/2026 devolvia uma página de casinos online.
  Não usar como fonte nem como link — e **o site do Caia tem esse link** em
  `heraldica_pagina.fonte_url` (a corrigir lá).

## Conteúdo — fontes

- **Executivo (3/3, com foto)** e **Assembleia (9/9, 8 com foto)**: juf.aaej.pt › Órgãos, mandato
  2025–2029 (CHEGA preside à Junta e à Assembleia). Nomes completos do executivo: mun-montijo.pt.
  Inga Oliveira (MVC) não tem foto na fonte (só um marcador branco).
- **Mensagem do presidente**: texto real de juf.aaej.pt, ligada (`mostrar_mensagem_presidente=1`).
- **História**: juf.aaej.pt (Igreja e Cruzeiros) + CM Montijo (resenhas da Atalaia e do Alto
  Estanqueiro-Jardia). Datas da cronologia todas das duas fontes.
- **Pontos de interesse (8)**: Igreja de N.ª Sr.ª da Atalaia, Cruzeiro Mor, Cruzeiro de Alcochete,
  Cruzeiro das Esmolas, Museu Agrícola da Atalaia, Flor da Liberdade (homenagem à
  floricultura, Tony Cassanelli, inaugurada a 25/04/2024), Monumento a Álvaro Tavares Mora e Cruzeiro de Granito (estes dois da "Rota da Atalaia" e da
  "Arte Pública" da CM Montijo). Coordenadas: nós OSM dentro do polígono (o Cruzeiro de Granito
  não tem nó — sem coordenadas). Fotos: Wikimedia Commons (CC BY-SA — "Igreja da Nossa Senhora
  da Atalaia.jpg", "EUROPA - PORTUGAL - SETUBAL - MONTIJO - ATALAIA 01.jpg", "Cruzeiro da Atalaia
  - Portugal (50929638332).jpg", autor Vitor Oliveira) e CM Montijo (restantes, incl. 7 fotos
  extra nos álbuns da galeria). Cruzeiro das Esmolas: foto Commons com a placa «Cruzeiro da
  Estrada ou das Esmolas»; Cruzeiro de Alcochete: o terceiro cruzeiro da categoria Commons dos três
  cruzeiros (por eliminação — sem GPS na foto). Flor da Liberdade: foto da inauguração, Diário do
  Distrito. **Todos os 8 pontos têm foto.**
  ⚠️ **A Fonte da Senhora fica no concelho de Alcochete** (nota da própria CM Montijo) — não
  incluída. As "Chaminés" da Rota da Atalaia ficam na estrada velha para o Montijo — freguesia não
  confirmada, não incluídas.
- **Associações (8)**: as 6 da lista oficial + Centro Social e Paroquial de N.ª Sr.ª da Atalaia
  (IPSS, Escadaria do Adro da Igreja) + Cáritas Paroquial. Coordenadas: SRA = nó OSM; Rancho,
  Águias Negras, Jardiense e Academia = ponto da rua (aproximado). Imagens: SRA, Academia,
  Jardiense (Facebook) e Águias Negras (imagem oficial do 62.º aniversário, da notícia da Junta).
- **Comércio local (16)**: o site oficial não tem lista. Nós OSM dentro do polígono (confirmados
  pelo Nominatim) + restaurantes de pesquisa web com código postal da freguesia e rua
  geocodificada (O Carlos, O Tacho d'Mãe, Sinfonia dos Sabores, O Pardal — coordenadas da rua).
  Sem coordenadas: Sabores do Mar, Apeadeiro Café. Imagens: O Ninho, Adega do Mocho, O Carlos,
  O Tacho d'Mãe.
- **Notícias (7)**: juf.aaej.pt, 2026, texto integral. Todas com imagem; a de "Montijo, 41 anos
  de cidade" usa o cartaz oficial da CM Montijo das comemorações. Datas pelo texto; a da "Ajuda solidária a
  Alcácer do Sal" é aproximada (recolha até 10/02/2026).
- **Documentos**: nenhum — o site oficial não publica documentos.
- **Contactos úteis**: sede, dependência, posto CTT (na sede) e 4 escolas (nós OSM).

## Por fazer / por confirmar

- Documentos oficiais (orçamento, atas) — pedir à Junta.
- Eventos (Festas de N.ª Sr.ª da Atalaia, agosto; Festas do Alto Estanqueiro) — o "Programa de
  Festas" do site oficial é um iframe; não preenchido.
- Fotos: 12 comércios e 4 associações sem imagem.
- Sem deploy — site só local.

## Deploy

Pacote de primeira instalação em `deploy/` (30/09/2026): `atalaia_deploy.zip` (site completo,
sem credenciais nem `debug-config.php`), `atalaia_migracoes.sql` (BD completa para uma BD vazia) e
`LEIA-ME.md` com os passos. Testado a partir do próprio ZIP numa pasta e BD limpas (34 páginas e
ficheiros, 0 falhas). Ainda **sem deploy feito** — site só local.

## Produção (instalado a 01/10/2026)

## ⚠️ Desde 15/10/2026 o site de produção está no SERVIDOR DA JUNTA

| | |
|---|---|
| **Endereço** | https://new.aaej.pt (provisório; o informático vai mudar o nome da pasta para `juf.aaej.pt` quando a Junta aprovar) |
| **Servidor** | alojamento cPanel da Junta, `185.12.116.247` (`cpanel903.dnscpanel.com`, DNS gerida pela Flesk). O email da Junta (`geral@juf.aaej.pt`) está no mesmo servidor. |
| **Acesso** | só FTP: `gesgov@juf.aaej.pt` em `ftp.jfaaej.pt:21` (conta recriada pelo informático a 27/10/2026; antes era `gesgov@new.aaej.pt`), FTP explícito sobre TLS. O certificado não é para esse nome, por isso tem de se aceitar. A password fica com o utilizador e não é guardada aqui. |
| **PHP / BD** | PHP 8.4 (ea-php84), MariaDB 10.6. BD `jfatalai_atalaia`, utilizador `jfatalai_GesGov`. Porta 3306 fechada ao exterior. |
| **Atualizar** | **O `pull_sites2.sh` não chega lá.** Fazer push para `Ges-Gov/atalaia` e depois enviar por FTP só os ficheiros alterados desde o último envio (`git diff --name-only <último>..HEAD`; último enviado: `7acaad4`, a 29/10/2026 — Sugestões, Ocorrências no rodapé, Marcações e requisições, validação; antes `0bc432b`). **Nunca enviar a `.htaccess` da raiz**, porque a deles tem diretivas do cPanel. Migrações: script temporário que dá sessão de admin e faz `require migrations/run.php`, chamado uma vez e apagado logo a seguir. |
| **Cuidados** | O alojamento limita pedidos seguidos (erro 429 e bloqueio temporário do IP no FTP): espaçar os pedidos. Antes de substituir ficheiros, confirmar que ninguém os alterou lá. |
| **Credenciais nossas lá** | `mail_config.php` (conta `newsletter@gesgov.pt`) e o hash do `admin` oculto, comum ao portefólio. Proposto: SMTP próprio e password única do `admin` no Atalaia. Chave Gemini própria do site, posta pelo utilizador. |
| **Cron** | A pedir ao informático, no cPanel → Cron Jobs: diário às 8h, `/usr/local/bin/php /home/jfatalai/<pasta final>/cron/newsletter_enviar_mensal.php` |

A cópia `atalaia.185.205.244.198.nip.io` no nosso VPS (abaixo) ficou **desatualizada e não deve ser usada**: o
backoffice real é `new.aaej.pt/admin`. Em 15/10/2026 o código foi também atualizado para o modelo antigo atual
(ciborro `81efc4f`), porque vinha do Caia, de antes de várias correções. O commit é `8c062bb`, com a migração 033.

### Instalação anterior no nosso VPS (já não é a produção)

| | |
|---|---|
| **Endereço** | https://atalaia.185.205.244.198.nip.io (**provisório** — `nip.io` até haver domínio próprio) |
| **Servidor** | VPS `185.205.244.198`, FastPanel. Dono `atalaia_185__usr`, pasta `/var/www/atalaia_185__usr/data/www/atalaia.185.205.244.198.nip.io` — clone git (`git@github.com:Ges-Gov/atalaia.git`, ramo `main`) |
| **BD produção** | `atalaia_185_` (utilizador `atalaia_185_`) |
| **SSL** | Let's Encrypt só para o domínio principal (sem `www.`) |
| **Credenciais** (só no servidor) | `includes/db_config.php`; `includes/mail_config.php` e `includes/ia_config.php` copiados do `jf-granho.pt` (conta `newsletter@gesgov.pt` e chave Gemini partilhadas) |
| **Cron** | `0 8 * * *` no crontab de `atalaia_185__usr` → `cron/newsletter_enviar_mensal.php` (log em `cron/newsletter.log`) |

- **Atualizar:** `git -c safe.directory=$PWD pull --ff-only origin main` na pasta do site.
- `deploy/*.zip` apagado no servidor (o nginx servia-o diretamente); o `.sql` dá 403.
- **Newsletter automática desligada de propósito** e `configuracoes_site.dominio` vazio: os links e
  imagens dos emails ficariam presos ao `nip.io`. Quando houver domínio definitivo: alias + SSL no
  FastPanel, `UPDATE configuracoes_site SET dominio='https://…'` e ligar a newsletter no backoffice.
- Por fazer: mudar a password do `admin` (herdada dos outros sites).

## Modelo novo (26/10/2026) — à espera do FTP

- Código passado para o modelo novo (o do Sousel) no ramo `redesign`/`main`; conteúdo em `deploy/atalaia_redesign.json`
  (gerado por `gera_atal_redesign.py` no scratchpad da sessão). **Só acrescenta**: a Junta publica notícias no backoffice (32 em
  26/10, contra 7 na BD local) — o carregador não toca em notícias, eventos, pontos, associações nem comércio; as atualizações de
  pessoas usam `so_se_existir`.
- Corrige o que a Junta reportou: fotos repetidas no carrossel das fichas (`fotosSemRepetidas()` em `includes/galeria.php`),
  formulário de requerimentos em duas colunas apertadas no telemóvel, e a mensagem do Presidente (aparece no Executivo e na página inicial).
- Resultados de 2025 (MAI `organId=6`, territoryId 2563): CH 3 · PS 2 · MVC 2 · PPD/PSD.IL 2 · CDU 0. As listas oficiais
  (edital) não foram encontradas online — os membros batem com as notícias da tomada de posse e dos pelouros.
- ⚠️ Envio: a conta FTP `gesgov@new.aaej.pt` dá «421 Home directory not available» desde que a pasta passou a `juf.aaej.pt`
  — o informático tem de a apontar para a pasta nova. Depois: ficheiros alterados desde `5362908` (nunca a `.htaccess` da raiz),
  migrações e carregador por script temporário (o servidor não tem SSH).

## Modelo novo — ENVIADO a 27/10/2026

- Cópia da BD antes do envio (feita por script temporário, que devolve o SQL sem o gravar no servidor):
  `atal_bd_antes_20261027.sql` no scratchpad da sessão. Foi testada a reposição: 82 tabelas e 32 notícias.
- Ordem do envio:
  1. As 16 migrações primeiro, com o executor temporário. O código antigo funciona com as tabelas novas.
  2. Depois os 219 ficheiros do código, nunca a `.htaccess` da raiz.
  3. Por fim `deploy/atalaia_redesign.json`, com o carregador temporário (simular → real).
  4. Os scripts temporários e o JSON foram apagados logo a seguir.
- `freguesia.php` e `admin/freguesia.php` apareciam como «mexidos no servidor». Só diferiam nas terminações de linha (CRLF), por isso foram substituídos.
- Inventário antes e depois (`atal_inventario_antes/depois_20261027.json`): 32 notícias com os mesmos títulos, 1 evento, 6 pontos, as mesmas associações e o mesmo comércio.
- Ferramenta: `atalaia_fase.py backup|migracoes|conteudo` no scratchpad, com a password em FTP_PASS.
