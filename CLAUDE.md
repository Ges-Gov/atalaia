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
