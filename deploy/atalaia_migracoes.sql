-- ============================================================
-- Atalaia e Alto Estanqueiro-Jardia — instalação completa da BD
-- ============================================================
-- Para um SITE NOVO no FastPanel: importar numa base de dados VAZIA
-- (phpMyAdmin → selecionar a BD → Importar). Cria todas as tabelas
-- (esquema + migrações 001–032 já aplicadas) e o conteúdo da freguesia.
--
-- ⚠️ Este ficheiro começa cada tabela com DROP TABLE IF EXISTS: NUNCA
-- importar numa BD que já tenha dados de outro site.
-- ⚠️ Confirmar antes o nome da BD no includes/db_config.php do servidor
-- e que é ESSA que está selecionada no topo do phpMyAdmin.
-- ============================================================

-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: atalaia
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_utilizadores`
--

DROP TABLE IF EXISTS `admin_utilizadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_utilizadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `nome` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `tipo` enum('admin','operador','vogal','presidente_assembleia','admin_denuncias') NOT NULL DEFAULT 'operador',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `perfil_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_utilizadores`
--

LOCK TABLES `admin_utilizadores` WRITE;
/*!40000 ALTER TABLE `admin_utilizadores` DISABLE KEYS */;
INSERT INTO `admin_utilizadores` VALUES
(12,'admin','Administrador','9881e5330518dfa90c0a00f48ecf6289','admin',1,NULL);
/*!40000 ALTER TABLE `admin_utilizadores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alertas`
--

DROP TABLE IF EXISTS `alertas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alertas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `tipo` varchar(50) DEFAULT 'info',
  `ativo` tinyint(1) DEFAULT 1,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alertas`
--

LOCK TABLES `alertas` WRITE;
/*!40000 ALTER TABLE `alertas` DISABLE KEYS */;
/*!40000 ALTER TABLE `alertas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_competencias_blocos`
--

DROP TABLE IF EXISTS `assembleia_competencias_blocos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_competencias_blocos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `conteudo` text DEFAULT NULL,
  `lista` text DEFAULT NULL,
  `icone` varchar(30) DEFAULT NULL,
  `tipo` enum('fiscalizacao','deliberativa','comunidade','transparencia','timeline','destaque') NOT NULL DEFAULT 'fiscalizacao',
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_competencias_blocos`
--

LOCK TABLES `assembleia_competencias_blocos` WRITE;
/*!40000 ALTER TABLE `assembleia_competencias_blocos` DISABLE KEYS */;
INSERT INTO `assembleia_competencias_blocos` VALUES
(5,'Competências de Apreciação e Fiscalização','Acompanhamento da atividade autárquica','Compete à Assembleia acompanhar e fiscalizar a atividade da Junta de Freguesia, garantindo transparência, rigor e responsabilidade na gestão local.','Eleger os membros da mesa da Assembleia e do executivo da Junta\nAprovar o regimento e os regulamentos internos\nAcompanhar e fiscalizar a atividade da Junta de Freguesia\nConstituir delegações para o estudo de problemas de interesse para a freguesia\nSolicitar informações sobre assuntos de interesse municipal\nDeliberar sobre a administração de bens e águas públicas da freguesia\nAceitar doações, legados e heranças a benefício de inventário\nApreciar os relatórios de auditoria\nReceber os relatórios de atividade do Presidente da Junta\nVotar moções de censura\nDeliberar sobre outras matérias de interesse para a freguesia','bi-search','fiscalizacao',1,1,'2026-06-26 10:53:22',NULL),
(6,'Competências Deliberativas','Decisões estruturantes para a freguesia','A Assembleia delibera sobre documentos e matérias fundamentais para o funcionamento democrático e institucional da freguesia.','Aprovar as opções do plano, a proposta de orçamento e as suas revisões\nApreciar os relatórios de atividade e as contas de gerência\nAutorizar a Junta a contrair empréstimos\nAutorizar a Junta a fixar taxas\nAutorizar a participação em empresas municipais\nAutorizar acordos de cooperação intermunicipal\nDeliberar sobre o regime de tempo de exercício de funções do Presidente da Junta\nAutorizar a aquisição e alienação de bens imóveis\nAprovar posturas e regulamentos\nRatificar atos praticados ao abrigo de delegação de competências da Câmara Municipal\nAprovar o mapa de pessoal\nAutorizar apoios financeiros a instituições culturais e desportivas\nRegulamentar o pastoreio de gado\nAprovar os símbolos heráldicos da freguesia (brasão, selo e bandeira)','bi-clipboard-check','deliberativa',2,1,'2026-06-26 10:53:22',NULL),
(7,'Competências Relacionadas com a Comunidade','Participação cívica e proximidade','A Assembleia é também um espaço de debate público, valorizando a participação dos cidadãos e a defesa dos interesses da comunidade.','Promover o debate democrático sobre temas locais\nValorizar a participação dos cidadãos nas sessões públicas\nAcompanhar projetos relevantes para o desenvolvimento da freguesia\nDefender os interesses da população junto dos órgãos competentes','bi-people','comunidade',3,1,'2026-06-26 10:53:22',NULL),
(8,'Transparência e Responsabilidade','Decisões registadas e divulgadas','A atuação da Assembleia assenta nos princípios da legalidade, transparência, responsabilidade e proximidade com os cidadãos.','As decisões são registadas em ata\nOs documentos relevantes são divulgados publicamente\nA informação reforça a confiança nas instituições locais','bi-journal-text','transparencia',4,1,'2026-06-26 10:53:22',NULL);
/*!40000 ALTER TABLE `assembleia_competencias_blocos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_competencias_config`
--

DROP TABLE IF EXISTS `assembleia_competencias_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_competencias_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_kicker` varchar(150) DEFAULT 'Atribuições e Competências',
  `hero_titulo` varchar(255) NOT NULL,
  `hero_subtitulo` text DEFAULT NULL,
  `intro_titulo` varchar(255) DEFAULT NULL,
  `intro_texto` text DEFAULT NULL,
  `destaque_1_titulo` varchar(150) DEFAULT NULL,
  `destaque_1_valor` varchar(100) DEFAULT NULL,
  `destaque_2_titulo` varchar(150) DEFAULT NULL,
  `destaque_2_valor` varchar(100) DEFAULT NULL,
  `destaque_3_titulo` varchar(150) DEFAULT NULL,
  `destaque_3_valor` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_competencias_config`
--

LOCK TABLES `assembleia_competencias_config` WRITE;
/*!40000 ALTER TABLE `assembleia_competencias_config` DISABLE KEYS */;
INSERT INTO `assembleia_competencias_config` VALUES
(1,'Atribuições e Competências','Atribuições e Competências','Conheça as principais competências da Assembleia de Freguesia: fiscalização, deliberação, participação democrática e acompanhamento da atividade autárquica.','Órgão deliberativo da freguesia','A Assembleia de Freguesia acompanha, aprecia e fiscaliza a atividade da Junta de Freguesia, deliberando sobre matérias fundamentais para a vida da comunidade local.','Fiscalização','Acompanhamento da Junta','Deliberação','Planos, orçamento e contas','Participação','Debate democrático local',1,NULL);
/*!40000 ALTER TABLE `assembleia_competencias_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_composicao`
--

DROP TABLE IF EXISTS `assembleia_composicao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_composicao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cargo` varchar(150) NOT NULL,
  `grupo` varchar(100) NOT NULL DEFAULT 'Vogais',
  `partido` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_composicao`
--

LOCK TABLES `assembleia_composicao` WRITE;
/*!40000 ALTER TABLE `assembleia_composicao` DISABLE KEYS */;
INSERT INTO `assembleia_composicao` VALUES
(62,'David Costa','Presidente da Assembleia','Mesa da Assembleia','CHEGA','david_costa.jpg',NULL,1,1,1,'2026-09-30 11:12:56',NULL),
(63,'Luís Pinho','1.º Secretário','Mesa da Assembleia','CHEGA','luis_pinho.jpg',NULL,2,0,1,'2026-09-30 11:12:56',NULL),
(64,'Carla Mestre','2.º Secretário','Mesa da Assembleia','CHEGA','carla_mestre.jpg',NULL,3,0,1,'2026-09-30 11:12:56',NULL),
(65,'Bruno Silva','Vogal','Vogais','PS','bruno_silva.jpg',NULL,4,0,1,'2026-09-30 11:12:56',NULL),
(66,'Adelino Silva','Vogal','Vogais','PS','adelino_silva.jpg',NULL,5,0,1,'2026-09-30 11:12:56',NULL),
(67,'Inga Oliveira','Vogal','Vogais','MVC',NULL,NULL,6,0,1,'2026-09-30 11:12:56',NULL),
(68,'Dora Horta','Vogal','Vogais','MVC','dora_horta.jpg',NULL,7,0,1,'2026-09-30 11:12:56',NULL),
(69,'Patrícia Machado','Vogal','Vogais','PSD','patricia_machado.jpg',NULL,8,0,1,'2026-09-30 11:12:56',NULL),
(70,'Rui Joaquim','Vogal','Vogais','IL','rui_joaquim.jpg',NULL,9,0,1,'2026-09-30 11:12:56',NULL);
/*!40000 ALTER TABLE `assembleia_composicao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_documentos`
--

DROP TABLE IF EXISTS `assembleia_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ficheiro` varchar(255) NOT NULL,
  `data_documento` date DEFAULT NULL,
  `ano` int(11) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_documentos`
--

LOCK TABLES `assembleia_documentos` WRITE;
/*!40000 ALTER TABLE `assembleia_documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `assembleia_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_funcionamento_blocos`
--

DROP TABLE IF EXISTS `assembleia_funcionamento_blocos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_funcionamento_blocos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `conteudo` text DEFAULT NULL,
  `icone` varchar(30) DEFAULT NULL,
  `tipo` enum('card','timeline','destaque') NOT NULL DEFAULT 'card',
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_funcionamento_blocos`
--

LOCK TABLES `assembleia_funcionamento_blocos` WRITE;
/*!40000 ALTER TABLE `assembleia_funcionamento_blocos` DISABLE KEYS */;
/*!40000 ALTER TABLE `assembleia_funcionamento_blocos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_funcionamento_config`
--

DROP TABLE IF EXISTS `assembleia_funcionamento_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_funcionamento_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_kicker` varchar(150) DEFAULT 'Funcionamento da Assembleia',
  `hero_titulo` varchar(255) NOT NULL,
  `hero_subtitulo` text DEFAULT NULL,
  `intro_titulo` varchar(255) DEFAULT NULL,
  `intro_texto` text DEFAULT NULL,
  `destaque_1_titulo` varchar(150) DEFAULT NULL,
  `destaque_1_valor` varchar(80) DEFAULT NULL,
  `destaque_2_titulo` varchar(150) DEFAULT NULL,
  `destaque_2_valor` varchar(80) DEFAULT NULL,
  `destaque_3_titulo` varchar(150) DEFAULT NULL,
  `destaque_3_valor` varchar(80) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_funcionamento_config`
--

LOCK TABLES `assembleia_funcionamento_config` WRITE;
/*!40000 ALTER TABLE `assembleia_funcionamento_config` DISABLE KEYS */;
INSERT INTO `assembleia_funcionamento_config` VALUES
(2,'Funcionamento da Assembleia','Funcionamento da Assembleia','Como funcionam as sessões da Assembleia de Freguesia: periodicidade, natureza pública e participação dos cidadãos.','Sessões ordinárias e extraordinárias','A Assembleia de Freguesia reúne regularmente ao longo do ano em sessões ordinárias, podendo ainda realizar sessões extraordinárias sempre que se justifique tratar de assuntos urgentes ou de especial importância para a freguesia. As reuniões são, regra geral, públicas, reforçando a transparência e a proximidade entre os órgãos do poder local e os munícipes. Os cidadãos podem participar ativamente nas sessões, apresentando sugestões, críticas e propostas, fortalecendo o envolvimento cívico e o escrutínio democrático. O Presidente da Junta de Freguesia participa nas sessões por inerência do cargo. A Assembleia aprova orçamentos, aprecia relatórios de atividade, fiscaliza o desempenho da Junta e delibera sobre regulamentos e demais assuntos submetidos pelo executivo.','Sessões ordinárias','Ao longo do ano','Sessões extraordinárias','Por convocação','Reuniões','Regra geral públicas',1,NULL);
/*!40000 ALTER TABLE `assembleia_funcionamento_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_sessao_anexos`
--

DROP TABLE IF EXISTS `assembleia_sessao_anexos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_sessao_anexos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessao_id` int(11) NOT NULL,
  `utilizador_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `ficheiro_original` varchar(255) NOT NULL,
  `ficheiro_guardado` varchar(255) NOT NULL,
  `extensao` varchar(20) DEFAULT NULL,
  `tamanho_bytes` int(11) DEFAULT NULL,
  `origem` enum('admin','presidente_assembleia','vogal') NOT NULL DEFAULT 'vogal',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sessao_id` (`sessao_id`),
  KEY `idx_utilizador_id` (`utilizador_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_sessao_anexos`
--

LOCK TABLES `assembleia_sessao_anexos` WRITE;
/*!40000 ALTER TABLE `assembleia_sessao_anexos` DISABLE KEYS */;
/*!40000 ALTER TABLE `assembleia_sessao_anexos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_sessao_documentos`
--

DROP TABLE IF EXISTS `assembleia_sessao_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_sessao_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessao_id` int(11) NOT NULL,
  `documento_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sessao_documento` (`sessao_id`,`documento_id`),
  KEY `idx_sessao_id` (`sessao_id`),
  KEY `idx_documento_id` (`documento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_sessao_documentos`
--

LOCK TABLES `assembleia_sessao_documentos` WRITE;
/*!40000 ALTER TABLE `assembleia_sessao_documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `assembleia_sessao_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_sessoes`
--

DROP TABLE IF EXISTS `assembleia_sessoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_sessoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `tipo` varchar(100) DEFAULT 'Sessão Ordinária',
  `descricao` text DEFAULT NULL,
  `local_sessao` varchar(255) DEFAULT NULL,
  `data_sessao` date NOT NULL,
  `hora_sessao` time NOT NULL,
  `estado` enum('agendada','realizada','cancelada') NOT NULL DEFAULT 'agendada',
  `ordem_trabalhos` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_sessoes`
--

LOCK TABLES `assembleia_sessoes` WRITE;
/*!40000 ALTER TABLE `assembleia_sessoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `assembleia_sessoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_votacoes`
--

DROP TABLE IF EXISTS `assembleia_votacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_votacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessao_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `estado` enum('rascunho','aberta','fechada') NOT NULL DEFAULT 'rascunho',
  `tipo_votacao` enum('publica','secreta') NOT NULL DEFAULT 'publica',
  `resultado_final` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_votacoes`
--

LOCK TABLES `assembleia_votacoes` WRITE;
/*!40000 ALTER TABLE `assembleia_votacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `assembleia_votacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assembleia_votos`
--

DROP TABLE IF EXISTS `assembleia_votos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembleia_votos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `votacao_id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `voto` enum('favor','contra','abstencao') NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_voto_utilizador` (`votacao_id`,`utilizador_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assembleia_votos`
--

LOCK TABLES `assembleia_votos` WRITE;
/*!40000 ALTER TABLE `assembleia_votos` DISABLE KEYS */;
/*!40000 ALTER TABLE `assembleia_votos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `associacoes`
--

DROP TABLE IF EXISTS `associacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `associacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `morada` text DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `outros_contactos` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `associacoes`
--

LOCK TABLES `associacoes` WRITE;
/*!40000 ALTER TABLE `associacoes` DISABLE KEYS */;
INSERT INTO `associacoes` VALUES
(81,'Sociedade Recreativa Atalaiense','Associação desportiva, cultural e recreativa fundada a 11 de outubro de 1946.',NULL,NULL,'sociedade_recreativa_atalaiense.jpg','Avenida 28 de Setembro, 2870-701 Atalaia','38.7065383','-8.9213201','https://atalaiense.pt/','https://www.facebook.com/sociedade.atalaiense',NULL,NULL),
(82,'Rancho Folclórico Juventude Atalaiense','Associação etnográfica, presença habitual nas Festas em honra de Nossa Senhora da Atalaia.',NULL,NULL,NULL,'Rua do Bairro Novo, Atalaia','38.7038851','-8.9255232',NULL,NULL,NULL,NULL),
(83,'Águias Negras Futebol Clube','Clube fundado a 1 de março de 1964, no Alto Estanqueiro, com um papel importante na dinamização desportiva, social e cultural da freguesia.',NULL,'212 301 826','noticia_aguias_negras_62.jpg','Estrada da Charnequinha, 2870-604 Alto Estanqueiro-Jardia','38.6779956','-8.9237494',NULL,NULL,NULL,NULL),
(84,'União Futebol Clube Jardiense','Clube de futebol da Jardia, fundado a 1 de maio de 1963.','uniaofcjardiense@gmail.com','917 752 975','ufc_jardiense.jpg','Rua União Clube Jardiense, 2870-684 Alto Estanqueiro-Jardia','38.6655363','-8.9256051',NULL,'https://www.facebook.com/formacaojardia/',NULL,NULL),
(85,'Academia Desportiva Infantil e Juvenil Bairro Miranda','Associação desportiva, recreativa e cultural fundada a 31 de março de 2003, dedicada sobretudo ao futsal jovem. Recebeu a Bandeira da Ética do IPDJ em 2020.',NULL,NULL,'academia_bairro_miranda.jpg','Rua das Águias, 85 – Bairro Miranda, 2870-682 Alto Estanqueiro-Jardia','38.6721345','-8.9258328',NULL,'https://www.facebook.com/academia.bairro.miranda/',NULL,NULL),
(86,'Associação Mansos e Vadios','Tertúlia e charanga da Atalaia, organizadora da Caminhada Solidária da Atalaia, integrada nas comemorações do 25 de Abril.',NULL,NULL,NULL,'Atalaia',NULL,NULL,NULL,NULL,NULL,NULL),
(87,'Centro Social e Paroquial de Nossa Senhora da Atalaia','Instituição Particular de Solidariedade Social que gere creche, centro de dia e serviço de apoio domiciliário. Atendimento das 9h30 às 12h30 e das 14h30 às 18h45.','geral.csatalaia@gmail.com','212 317 534 / 915 943 757',NULL,'Escadaria do Adro da Igreja, 2870-711 Atalaia',NULL,NULL,'https://www.cspatalaia.com/',NULL,NULL,NULL),
(88,'Cáritas Paroquial de Nossa Senhora da Atalaia','Apoio social às famílias da freguesia, ligada à paróquia de Nossa Senhora da Atalaia.','caritas.atalaia@gmail.com',NULL,NULL,'Atalaia',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `associacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cidadaos`
--

DROP TABLE IF EXISTS `cidadaos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cidadaos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expira` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cidadaos`
--

LOCK TABLES `cidadaos` WRITE;
/*!40000 ALTER TABLE `cidadaos` DISABLE KEYS */;
/*!40000 ALTER TABLE `cidadaos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comercio_local`
--

DROP TABLE IF EXISTS `comercio_local`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comercio_local` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `morada` text DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `outros_contactos` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comercio_local`
--

LOCK TABLES `comercio_local` WRITE;
/*!40000 ALTER TABLE `comercio_local` DISABLE KEYS */;
INSERT INTO `comercio_local` VALUES
(119,'O Ninho','Restaurante','212 318 988','o_ninho.jpg',NULL,'Avenida Dom Manuel I, 2870-736 Atalaia','38.7062978','-8.9230556','https://restauranteoninho.net/',NULL,NULL,'Grelhados no carvão, peixe e cozinha tradicional portuguesa.'),
(120,'Adega do Mocho','Restaurante','212 316 312 / 912 217 453','adega_do_mocho.jpg',NULL,'EN 4, n.º 41, Atalaia','38.7066864','-8.9287066',NULL,'https://www.facebook.com/pages/Adega-Mocho/175906119213164',NULL,'Cozinha simples e rústica, com destaque para a carne de porco preto.'),
(121,'Restaurante Churrasqueira O Carlos','Restaurante','212 316 760','o_carlos.jpg','restaurantecarlos52@gmail.com','Rua Círio de Aldegalega, 210, 2870-724 Atalaia','38.7095185','-8.9263794',NULL,'https://www.facebook.com/Restaurante-Churrasqueira-O-Carlos-639343232765601/',NULL,'Grelhados e cozinha tradicional.'),
(122,'O Tacho d\'Mãe','Restaurante',NULL,'o_tacho_d_mae.jpg',NULL,'Rua da Figueira, 68, 2870-738 Atalaia','38.7003828','-8.9297564',NULL,'https://www.facebook.com/tachodamae',NULL,'Cozinha tradicional alentejana.'),
(123,'A Rotunda','Restaurante','910 532 529',NULL,NULL,'Rua das Forças Armadas, 2870-712 Atalaia','38.7064795','-8.9276435',NULL,NULL,NULL,NULL),
(124,'Sinfonia dos Sabores','Restaurante / Marisqueira',NULL,NULL,NULL,'Rua das Forças Armadas, 2870-712 Atalaia','38.7041687','-8.9276785',NULL,'https://www.facebook.com/p/Sinfonia-dos-Sabores-Restaurante-Marisqueira-61581520914922/',NULL,'Marisqueira e grelhados.'),
(125,'O Típico','Restaurante','211 586 265 / 914 602 806',NULL,NULL,'EN 5, 2870-621 Alto Estanqueiro','38.6812278','-8.928787',NULL,NULL,NULL,NULL),
(126,'Marisqueira Sabores do Mar','Restaurante / Marisqueira',NULL,NULL,NULL,'Rua 1.º de Maio, 2870-626 Jardia',NULL,NULL,NULL,'https://www.facebook.com/p/Restaurante-Marisqueira-Sabores-do-Mar-100067801087481/',NULL,'Antigo «Mercado do Peixe».'),
(127,'O Pardal','Restaurante',NULL,NULL,NULL,'Rua dos Tractores, 506 – Parque Industrial da Jardia','38.6720755','-8.9367860',NULL,'https://www.facebook.com/opardal.montijo/',NULL,'Almoços de segunda a sexta-feira.'),
(128,'Apeadeiro Café','Café',NULL,NULL,NULL,'Rua do Operário, 10, 2870-609 Alto Estanqueiro-Jardia',NULL,NULL,NULL,NULL,NULL,NULL),
(129,'Padaria da Atalaia','Padaria','212 474 228',NULL,NULL,'Rua do Mercado, 31, 2870-751 Atalaia','38.7052472','-8.9226277',NULL,NULL,NULL,NULL),
(130,'Farmácia Cravidão','Farmácia',NULL,NULL,NULL,'Avenida Dom Manuel I, 2870-736 Atalaia','38.7064108','-8.9228921',NULL,NULL,NULL,NULL),
(131,'Provari','Comércio agrícola e ferragens','212 318 904',NULL,NULL,'Rua 25 de Abril, 25, 2870-709 Atalaia','38.7061291','-8.9222211',NULL,NULL,NULL,'Comércio agrícola, agropecuária e ferragens.'),
(132,'Rolizoo','Loja de animais','212 384 731',NULL,NULL,'EN 252, gaveto com a Rua Gil Fernandes, 2, Alto Estanqueiro','38.6804598','-8.9387858','https://www.rolizoo.com/',NULL,NULL,NULL),
(133,'Stand Ricarauto','Comércio automóvel','964 604 547',NULL,NULL,'EN 252, 2870-660 Alto Estanqueiro','38.6810634','-8.9393324','https://www.standricarauto.pt/',NULL,NULL,NULL),
(134,'RP Auto','Oficina automóvel',NULL,NULL,NULL,'EN 4, 2870-700 Atalaia','38.7066243','-8.9285662',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `comercio_local` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracoes_site`
--

DROP TABLE IF EXISTS `configuracoes_site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracoes_site` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_site` varchar(255) DEFAULT NULL,
  `municipio` varchar(255) DEFAULT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `dominio` varchar(255) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `morada` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `cor_principal` varchar(20) DEFAULT '#0d3b66',
  `cor_secundaria` varchar(20) DEFAULT '#f0b429',
  `footer` text DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `horario` text DEFAULT NULL,
  `email_notificacoes` varchar(255) DEFAULT NULL,
  `alertas_ativos` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracoes_site`
--

LOCK TABLES `configuracoes_site` WRITE;
/*!40000 ALTER TABLE `configuracoes_site` DISABLE KEYS */;
INSERT INTO `configuracoes_site` VALUES
(2,'Junta de Freguesia de Atalaia e Alto Estanqueiro-Jardia','Montijo',NULL,'geral@juf.aaej.pt',NULL,'212 320 480','Av. 28 de Setembro, n.º 56, 2870-701 Atalaia','logo-aaej.png','#312783','#F9B233',NULL,'https://www.facebook.com/jfaaej/',NULL,'Segunda a sexta-feira, das 9h00 às 12h30 e das 14h00 às 17h30','geral@juf.aaej.pt',1);
/*!40000 ALTER TABLE `configuracoes_site` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contactos_pagina_config`
--

DROP TABLE IF EXISTS `contactos_pagina_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contactos_pagina_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_kicker` varchar(150) DEFAULT 'Contactos',
  `hero_titulo` varchar(255) NOT NULL DEFAULT 'Contactos',
  `hero_subtitulo` text DEFAULT NULL,
  `bloco_titulo` varchar(255) DEFAULT NULL,
  `bloco_texto` text DEFAULT NULL,
  `mapa_embed` text DEFAULT NULL,
  `horario_titulo` varchar(255) DEFAULT NULL,
  `horario_texto` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contactos_pagina_config`
--

LOCK TABLES `contactos_pagina_config` WRITE;
/*!40000 ALTER TABLE `contactos_pagina_config` DISABLE KEYS */;
INSERT INTO `contactos_pagina_config` VALUES
(1,'Contactos','Contactos','Estamos ao seu dispor na sede, na Atalaia, e na dependência do Alto Estanqueiro.','Junta de Freguesia de Atalaia e Alto Estanqueiro-Jardia','Sede: Av. 28 de Setembro, n.º 56, 2870-701 Atalaia · Tel. 212 320 480 · Tlm. 910 697 305 / 961 826 278 · geral@juf.aaej.pt\nDependência: Rua dos Russos – Quinta das Tílias, 2870-624 Alto Estanqueiro-Jardia · Tel. 212 301 076',NULL,'Horário de atendimento','Segunda a sexta-feira, das 9h00 às 12h30 e das 14h00 às 17h30. Posto CTT: segunda a sexta-feira, das 9h00 às 12h30.',1,NULL);
/*!40000 ALTER TABLE `contactos_pagina_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contactos_uteis`
--

DROP TABLE IF EXISTS `contactos_uteis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contactos_uteis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `morada` varchar(255) DEFAULT NULL,
  `horario` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contactos_uteis`
--

LOCK TABLES `contactos_uteis` WRITE;
/*!40000 ALTER TABLE `contactos_uteis` DISABLE KEYS */;
INSERT INTO `contactos_uteis` VALUES
(45,'Junta de Freguesia — Sede','Junta de Freguesia','212 320 480 / 910 697 305 / 961 826 278','geral@juf.aaej.pt','Av. 28 de Setembro, n.º 56, 2870-701 Atalaia','Segunda a sexta, 9h00–12h30 e 14h00–17h30',NULL,NULL,1,1,1,NULL),
(46,'Junta de Freguesia — Dependência','Junta de Freguesia','212 301 076',NULL,'Rua dos Russos – Quinta das Tílias, 2870-624 Alto Estanqueiro-Jardia',NULL,NULL,NULL,2,1,1,NULL),
(47,'Posto CTT (na sede da Junta)','Serviços','212 320 480',NULL,'Av. 28 de Setembro, n.º 56, 2870-701 Atalaia','Segunda a sexta, 9h00–12h30',NULL,NULL,3,0,1,NULL),
(48,'Escola Básica de Novos Trilhos','Educação','212 312 623',NULL,'Rua 28 de Setembro, Atalaia',NULL,NULL,NULL,4,0,1,NULL),
(49,'Escola Básica do Alto Estanqueiro','Educação','212 318 521',NULL,'Rua Gomes Martins de Lemos, Alto Estanqueiro',NULL,NULL,NULL,5,0,1,NULL),
(50,'Escola Básica de Jardia','Educação','212 361 576',NULL,'Jardia',NULL,NULL,NULL,6,0,1,NULL),
(51,'Jardim de Infância de Alto Estanqueiro-Jardia','Educação',NULL,NULL,'Alto Estanqueiro',NULL,NULL,NULL,7,0,1,NULL);
/*!40000 ALTER TABLE `contactos_uteis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contratacao_publica`
--

DROP TABLE IF EXISTS `contratacao_publica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contratacao_publica` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `numero_procedimento` varchar(100) DEFAULT NULL,
  `carreira` varchar(100) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `area_funcional` varchar(150) DEFAULT NULL,
  `vagas` smallint(5) unsigned DEFAULT NULL,
  `tipo_vinculo` varchar(100) DEFAULT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `valor` decimal(12,2) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `requisitos` text DEFAULT NULL,
  `ficheiro` varchar(255) DEFAULT NULL,
  `data_publicacao` date DEFAULT NULL,
  `inicio_candidaturas` date DEFAULT NULL,
  `fim_candidaturas` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contratacao_publica`
--

LOCK TABLES `contratacao_publica` WRITE;
/*!40000 ALTER TABLE `contratacao_publica` DISABLE KEYS */;
/*!40000 ALTER TABLE `contratacao_publica` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contratacao_publica_anexos`
--

DROP TABLE IF EXISTS `contratacao_publica_anexos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contratacao_publica_anexos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `procedimento_id` int(11) NOT NULL,
  `ficheiro` varchar(255) NOT NULL,
  `ficheiro_original` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contratacao_publica_anexos`
--

LOCK TABLES `contratacao_publica_anexos` WRITE;
/*!40000 ALTER TABLE `contratacao_publica_anexos` DISABLE KEYS */;
/*!40000 ALTER TABLE `contratacao_publica_anexos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contratacao_publica_pagina`
--

DROP TABLE IF EXISTS `contratacao_publica_pagina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contratacao_publica_pagina` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hero_kicker` varchar(150) NOT NULL DEFAULT 'Transparência e Gestão Pública',
  `hero_titulo` varchar(255) NOT NULL DEFAULT 'Contratação Pública',
  `texto` text DEFAULT NULL,
  `botao_texto` varchar(150) NOT NULL DEFAULT 'Ver no Portal BASE',
  `botao_link` varchar(500) DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contratacao_publica_pagina`
--

LOCK TABLES `contratacao_publica_pagina` WRITE;
/*!40000 ALTER TABLE `contratacao_publica_pagina` DISABLE KEYS */;
INSERT INTO `contratacao_publica_pagina` VALUES
(1,'Transparência e Gestão Pública','Contratação Pública','Os procedimentos de contratação pública desta freguesia são publicados no Portal BASE, a plataforma oficial e obrigatória para a divulgação de contratos públicos em Portugal.','Ver no Portal BASE',NULL,'2026-09-30 08:53:59');
/*!40000 ALTER TABLE `contratacao_publica_pagina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `denuncias`
--

DROP TABLE IF EXISTS `denuncias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `denuncias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `tipo` varchar(120) NOT NULL,
  `categoria` varchar(120) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `data_ocorrencia` date DEFAULT NULL,
  `local_ocorrencia` varchar(255) DEFAULT NULL,
  `descricao` text NOT NULL,
  `anonima` tinyint(1) DEFAULT 1,
  `nome` varchar(180) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'recebida',
  `prioridade` varchar(50) DEFAULT 'normal',
  `observacoes_admin` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `denuncias`
--

LOCK TABLES `denuncias` WRITE;
/*!40000 ALTER TABLE `denuncias` DISABLE KEYS */;
/*!40000 ALTER TABLE `denuncias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `denuncias_anexos`
--

DROP TABLE IF EXISTS `denuncias_anexos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `denuncias_anexos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `denuncia_id` int(11) NOT NULL,
  `ficheiro` varchar(255) NOT NULL,
  `ficheiro_original` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `denuncias_anexos`
--

LOCK TABLES `denuncias_anexos` WRITE;
/*!40000 ALTER TABLE `denuncias_anexos` DISABLE KEYS */;
/*!40000 ALTER TABLE `denuncias_anexos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `denuncias_mensagens`
--

DROP TABLE IF EXISTS `denuncias_mensagens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `denuncias_mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `denuncia_id` int(11) NOT NULL,
  `origem` enum('admin','denunciante') DEFAULT 'admin',
  `mensagem` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `denuncias_mensagens`
--

LOCK TABLES `denuncias_mensagens` WRITE;
/*!40000 ALTER TABLE `denuncias_mensagens` DISABLE KEYS */;
/*!40000 ALTER TABLE `denuncias_mensagens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `denuncias_utilizadores`
--

DROP TABLE IF EXISTS `denuncias_utilizadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `denuncias_utilizadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `denuncias_utilizadores`
--

LOCK TABLES `denuncias_utilizadores` WRITE;
/*!40000 ALTER TABLE `denuncias_utilizadores` DISABLE KEYS */;
/*!40000 ALTER TABLE `denuncias_utilizadores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `categoria` varchar(100) NOT NULL,
  `ficheiro` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_documento` date DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `area` varchar(100) DEFAULT 'assembleia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos`
--

LOCK TABLES `documentos` WRITE;
/*!40000 ALTER TABLE `documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dpo_mensagens`
--

DROP TABLE IF EXISTS `dpo_mensagens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dpo_mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `origem` enum('dpo','cidadao') DEFAULT 'dpo',
  `mensagem` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dpo_mensagens`
--

LOCK TABLES `dpo_mensagens` WRITE;
/*!40000 ALTER TABLE `dpo_mensagens` DISABLE KEYS */;
/*!40000 ALTER TABLE `dpo_mensagens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dpo_pedidos`
--

DROP TABLE IF EXISTS `dpo_pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dpo_pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nome` varchar(180) NOT NULL,
  `email` varchar(180) NOT NULL,
  `mensagem` text NOT NULL,
  `aceita_politica` tinyint(1) DEFAULT 0,
  `autoriza_resposta` tinyint(1) DEFAULT 0,
  `estado` varchar(50) DEFAULT 'recebido',
  `observacoes_admin` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dpo_pedidos`
--

LOCK TABLES `dpo_pedidos` WRITE;
/*!40000 ALTER TABLE `dpo_pedidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `dpo_pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dpo_utilizadores`
--

DROP TABLE IF EXISTS `dpo_utilizadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dpo_utilizadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dpo_utilizadores`
--

LOCK TABLES `dpo_utilizadores` WRITE;
/*!40000 ALTER TABLE `dpo_utilizadores` DISABLE KEYS */;
/*!40000 ALTER TABLE `dpo_utilizadores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eventos`
--

DROP TABLE IF EXISTS `eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `data_evento` datetime DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `local` varchar(255) DEFAULT NULL,
  `data_fim` datetime DEFAULT NULL,
  `imagem_foco_x` tinyint(3) unsigned NOT NULL DEFAULT 50,
  `imagem_foco_y` tinyint(3) unsigned NOT NULL DEFAULT 50,
  `categoria` varchar(60) DEFAULT NULL,
  `inscricoes_ativas` tinyint(1) NOT NULL DEFAULT 0,
  `inscricoes_vagas` int(10) unsigned DEFAULT NULL,
  `inscricoes_ate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos`
--

LOCK TABLES `eventos` WRITE;
/*!40000 ALTER TABLE `eventos` DISABLE KEYS */;
/*!40000 ALTER TABLE `eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eventos_campos_extra`
--

DROP TABLE IF EXISTS `eventos_campos_extra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventos_campos_extra` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `evento_id` int(10) unsigned NOT NULL,
  `label` varchar(190) NOT NULL,
  `tipo` enum('texto','checkbox') NOT NULL DEFAULT 'texto',
  `obrigatorio` tinyint(1) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_eventos_campos_extra_evento` (`evento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos_campos_extra`
--

LOCK TABLES `eventos_campos_extra` WRITE;
/*!40000 ALTER TABLE `eventos_campos_extra` DISABLE KEYS */;
/*!40000 ALTER TABLE `eventos_campos_extra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eventos_inscricoes`
--

DROP TABLE IF EXISTS `eventos_inscricoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventos_inscricoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `evento_id` int(10) unsigned NOT NULL,
  `nome` varchar(190) NOT NULL,
  `email` varchar(190) NOT NULL,
  `telefone` varchar(40) DEFAULT NULL,
  `num_pessoas` smallint(5) unsigned NOT NULL DEFAULT 1,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `campos_extra` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eventos_inscricoes_evento` (`evento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos_inscricoes`
--

LOCK TABLES `eventos_inscricoes` WRITE;
/*!40000 ALTER TABLE `eventos_inscricoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `eventos_inscricoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `executivo_membros`
--

DROP TABLE IF EXISTS `executivo_membros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `executivo_membros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cargo` varchar(150) NOT NULL,
  `pelouros` text DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `executivo_membros`
--

LOCK TABLES `executivo_membros` WRITE;
/*!40000 ALTER TABLE `executivo_membros` DISABLE KEYS */;
INSERT INTO `executivo_membros` VALUES
(31,'Pedro Miguel Guerreiro da Franca Araújo','Presidente','Gestão Financeira\nRecursos Humanos\nPatrimónio\nExpediente\nDesporto e Associativismo',NULL,NULL,'pedro_araujo.jpg',1,1,'2026-09-30 11:12:56'),
(32,'Vanessa Sofia Leite de Castro','Secretária','Ação Social\nCultura\nEducação e Ensino\nCertificação de Atas\nSubscrição de Atestados',NULL,NULL,'vanessa_castro.jpg',2,1,'2026-09-30 11:12:56'),
(33,'Augusto Marques Cardoso','Tesoureiro','Higiene e Limpeza Urbana\nMercados e Feiras\nObras\nArrecadação de Receitas\nSubscrição de Despesas autorizadas',NULL,NULL,'augusto_cardoso.jpg',3,1,'2026-09-30 11:12:56');
/*!40000 ALTER TABLE `executivo_membros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pergunta` varchar(255) NOT NULL,
  `resposta` text NOT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES
(1,'Como posso contactar a Junta de Freguesia de Atalaia e Alto Estanqueiro-Jardia?','Pode contactar-nos por telefone, email ou presencialmente, através dos dados disponíveis na página de Contactos. Também pode usar a secção \"Pedidos à Junta\" para comunicar diretamente pelo site.',1,1,'2026-06-23 12:04:30'),
(2,'Como faço uma denúncia através do Canal de Denúncias?','Na página \"Canal de Denúncias\" encontra um formulário onde pode submeter a sua denúncia, com ou sem identificação. Após o envio, recebe um código único para acompanhar o estado da denúncia em \"Acompanhar Denúncia\".',2,1,'2026-06-23 12:04:30'),
(3,'Como posso solicitar um documento ou requerimento à Junta?','Através da página \"Requerimentos\", disponível na Junta Virtual, pode submeter o seu pedido online. Acompanhe o estado e a resposta na sua área pessoal.',3,1,'2026-06-23 12:04:30'),
(4,'Como consulto atas, editais e outros documentos públicos?','Na página \"Documentos\" da Freguesia tem acesso a atas, editais, convocatórias, regulamentos e outros documentos, organizados por categoria.',4,1,'2026-06-23 12:04:30'),
(5,'Como posso reportar uma ocorrência (ex.: iluminação, limpeza, via pública)?','Use a página \"Pedidos à Junta\" para indicar, inclusive no mapa, o local exato da ocorrência e descrever a situação.',5,1,'2026-06-23 12:04:30'),
(6,'Onde posso ver notícias e eventos da freguesia?','As secções \"Notícias\" e \"Eventos\" do site são atualizadas regularmente com a informação e atividades da freguesia de Atalaia e Alto Estanqueiro-Jardia.',6,1,'2026-06-23 12:04:30'),
(7,'O que é o SIAC e é obrigatório fazer o registo do meu animal de companhia?','O SIAC (Sistema de Informação de Animais de Companhia) promove a identificação de animais de companhia de forma simplificada numa única plataforma. A aplicação de microchip e o registo na base de dados do SIAC são obrigatórios para cães e gatos. O registo é feito na Junta de Freguesia, com licença anual a obter entre os meses de março e junho, mediante apresentação de certificado de vacinação antirrábica, cartão de cidadão e comprovativo de registo no SIAC. A não realização do registo pode dar origem a um processo de contraordenação.',7,1,'2026-06-30 14:54:37'),
(8,'Que documentos preciso para pedir um atestado de residência?','Para emitir um atestado de residência deve apresentar documento de identificação (cartão de cidadão com número de identificação fiscal, ou passaporte/título de residência válido). Cidadãos estrangeiros necessitam ainda do testemunho de dois residentes recenseados na freguesia.',8,1,'2026-06-30 14:54:37'),
(9,'Que documentos preciso para um atestado de situação económica?','É necessário apresentar documento de identificação, declarações de IRS, recibos de vencimento recentes, comprovativos de pensão (quando aplicável) e declaração de composição do agregado familiar emitida pela Segurança Social.',9,1,'2026-06-30 14:54:37'),
(10,'Como faço uma declaração de união de facto?','A declaração e a dissolução de união de facto exigem documento de identificação, certidão de nascimento e o testemunho de dois residentes recenseados na freguesia.',10,1,'2026-06-30 14:54:37');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `galeria_albuns`
--

DROP TABLE IF EXISTS `galeria_albuns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `galeria_albuns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `capa` varchar(255) DEFAULT NULL,
  `origem` enum('manual','evento','ponto') NOT NULL DEFAULT 'manual',
  `origem_id` int(11) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_origem` (`origem`,`origem_id`)
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `galeria_albuns`
--

LOCK TABLES `galeria_albuns` WRITE;
/*!40000 ALTER TABLE `galeria_albuns` DISABLE KEYS */;
INSERT INTO `galeria_albuns` VALUES
(138,'Igreja de Nossa Senhora da Atalaia',NULL,'igreja_atalaia.jpg','ponto',112,112,1,'2026-09-30 11:12:56'),
(139,'Cruzeiro Mor',NULL,'cruzeiro_mor.jpg','ponto',113,113,1,'2026-09-30 11:12:56'),
(140,'Cruzeiro de Alcochete',NULL,'cruzeiro_alcochete.jpg','ponto',114,114,1,'2026-09-30 11:12:56'),
(141,'Cruzeiro das Esmolas',NULL,'cruzeiro_esmolas.jpg','ponto',115,115,1,'2026-09-30 11:12:56'),
(142,'Museu Agrícola da Atalaia',NULL,'museu_agricola_atalaia.jpg','ponto',116,116,1,'2026-09-30 11:12:56'),
(143,'Flor da Liberdade — Homenagem à Floricultura',NULL,'monumento_floricultura.jpg','ponto',117,117,1,'2026-09-30 11:12:56'),
(144,'Monumento a Álvaro Tavares Mora',NULL,'monumento_alvaro_tavares_mora.jpg','ponto',118,118,1,'2026-09-30 11:12:56'),
(145,'Cruzeiro de Granito',NULL,'cruzeiro_granito.jpg','ponto',119,119,1,'2026-09-30 11:12:56');
/*!40000 ALTER TABLE `galeria_albuns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `galeria_imagens`
--

DROP TABLE IF EXISTS `galeria_imagens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `galeria_imagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `album_id` int(11) NOT NULL,
  `ficheiro` varchar(255) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_album` (`album_id`),
  CONSTRAINT `fk_galeria_imagens_album` FOREIGN KEY (`album_id`) REFERENCES `galeria_albuns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=181 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `galeria_imagens`
--

LOCK TABLES `galeria_imagens` WRITE;
/*!40000 ALTER TABLE `galeria_imagens` DISABLE KEYS */;
INSERT INTO `galeria_imagens` VALUES
(159,138,'igreja_atalaia.jpg','Igreja de Nossa Senhora da Atalaia',1,1,'2026-09-30 11:12:56'),
(160,139,'cruzeiro_mor.jpg','Cruzeiro Mor',1,1,'2026-09-30 11:12:56'),
(161,140,'cruzeiro_alcochete.jpg','Cruzeiro de Alcochete',1,1,'2026-09-30 11:12:56'),
(162,141,'cruzeiro_esmolas.jpg','Cruzeiro das Esmolas',1,1,'2026-09-30 11:12:56'),
(163,142,'museu_agricola_atalaia.jpg','Museu Agrícola da Atalaia',1,1,'2026-09-30 11:12:56'),
(164,143,'monumento_floricultura.jpg','Flor da Liberdade — Homenagem à Floricultura',1,1,'2026-09-30 11:12:56'),
(165,144,'monumento_alvaro_tavares_mora.jpg','Monumento a Álvaro Tavares Mora',1,1,'2026-09-30 11:12:56'),
(166,145,'cruzeiro_granito.jpg','Cruzeiro de Granito',1,1,'2026-09-30 11:12:56'),
(174,138,'igreja_atalaia_interior.jpg','Interior e retábulo do altar-mor',2,1,'2026-09-30 11:12:56'),
(175,138,'igreja_atalaia_escadaria.jpg','A escadaria do Santuário',3,1,'2026-09-30 11:12:56'),
(176,139,'cruzeiro_mor_2.jpg','Cruzeiro Mor',2,1,'2026-09-30 11:12:56'),
(177,139,'cruzeiro_mor_noite.jpg','Cruzeiro Mor à noite',3,1,'2026-09-30 11:12:56'),
(178,142,'museu_agricola_lagar.jpg','Mós do lagar de azeite',2,1,'2026-09-30 11:12:56'),
(179,142,'museu_agricola_quinta_nova.jpg','Quinta Nova da Atalaia',3,1,'2026-09-30 11:12:56'),
(180,144,'monumento_alvaro_tavares_mora_2.jpg','Busto de Álvaro Tavares Mora',2,1,'2026-09-30 11:12:56');
/*!40000 ALTER TABLE `galeria_imagens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `heraldica_elementos`
--

DROP TABLE IF EXISTS `heraldica_elementos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `heraldica_elementos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `icone` varchar(30) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `heraldica_elementos`
--

LOCK TABLES `heraldica_elementos` WRITE;
/*!40000 ALTER TABLE `heraldica_elementos` DISABLE KEYS */;
INSERT INTO `heraldica_elementos` VALUES
(44,'Brasão da Atalaia','Escudo de prata, cruzeiro de púrpura assente num monte de negro, movente da ponta e entre uma flor-de-lis de azul, à dextra, e uma espiga de milho de ouro, folhada de verde, à sinistra. Coroa mural de prata de três torres. Listel branco, com a legenda a negro: «Atalaia – Montijo».','bi-shield',NULL,1,1,'2026-09-30 11:12:56',NULL),
(45,'Bandeira da Atalaia','De azul. Cordão e borlas de prata e azul. Haste e lança de ouro.','bi-flag',NULL,2,1,'2026-09-30 11:12:56',NULL),
(46,'Brasão do Alto Estanqueiro-Jardia','Escudo de prata, uma cruz da Ordem de Santiago, de vermelho, uma roda dentada de azul, uma espiga de milho de ouro, folhada de verde, e um pinheiro arrancado de verde, frutado de ouro, as quatro figuras dispostas em cruz. Coroa mural de três torres de prata. Listel branco, com a legenda a negro: «Alto Estanqueiro–Jardia».','bi-shield',NULL,3,1,'2026-09-30 11:12:56',NULL),
(47,'Bandeira do Alto Estanqueiro-Jardia','De vermelho. Cordão e borlas de prata e vermelho. Haste e lança de ouro.','bi-flag',NULL,4,1,'2026-09-30 11:12:56',NULL);
/*!40000 ALTER TABLE `heraldica_elementos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `heraldica_pagina`
--

DROP TABLE IF EXISTS `heraldica_pagina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `heraldica_pagina` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_kicker` varchar(150) DEFAULT 'Heráldica',
  `hero_titulo` varchar(255) NOT NULL DEFAULT 'Heráldica',
  `hero_subtitulo` text DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `texto_intro` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `fonte_texto` varchar(255) DEFAULT NULL,
  `fonte_url` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `heraldica_pagina`
--

LOCK TABLES `heraldica_pagina` WRITE;
/*!40000 ALTER TABLE `heraldica_pagina` DISABLE KEYS */;
INSERT INTO `heraldica_pagina` VALUES
(1,'Heráldica','Símbolos de Atalaia e Alto Estanqueiro-Jardia','Logótipo institucional da União das Freguesias e brasões das antigas freguesias.','Símbolos da União das Freguesias','A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia ainda não tem ordenação heráldica própria publicada em Diário da República. Até lá, apresentam-se os brasões e bandeiras das antigas freguesias da Atalaia e do Alto Estanqueiro-Jardia. O logótipo institucional, adotado em dezembro de 2025, estiliza o Cruzeiro Mor da Atalaia.','logo-aaej-oficial.png','Câmara Municipal do Montijo — Descrição Heráldica','https://www.mun-montijo.pt/municipio/freguesias/uniao-das-freguesias-de-atalaia-e-alto-estanqueiro-jardia',1,NULL);
/*!40000 ALTER TABLE `heraldica_pagina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homepage_config`
--

DROP TABLE IF EXISTS `homepage_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homepage_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_titulo` varchar(255) DEFAULT NULL,
  `hero_subtitulo` text DEFAULT NULL,
  `boasvindas_titulo` varchar(255) DEFAULT NULL,
  `boasvindas_texto` text DEFAULT NULL,
  `cta_titulo` varchar(255) DEFAULT NULL,
  `cta_texto` text DEFAULT NULL,
  `cta_botao_texto` varchar(120) DEFAULT NULL,
  `cta_botao_link` varchar(255) DEFAULT NULL,
  `mostrar_hero` tinyint(1) DEFAULT 1,
  `mostrar_boasvindas` tinyint(1) DEFAULT 1,
  `mostrar_pontos` tinyint(1) DEFAULT 1,
  `mostrar_mapa` tinyint(1) DEFAULT 1,
  `mostrar_noticias_eventos` tinyint(1) DEFAULT 1,
  `mostrar_servicos` tinyint(1) DEFAULT 1,
  `mostrar_cta_final` tinyint(1) DEFAULT 1,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `galeria_kicker` varchar(150) DEFAULT 'Granho em imagens',
  `galeria_titulo` varchar(255) DEFAULT 'Uma freguesia com identidade',
  `mostrar_galeria` tinyint(1) DEFAULT 1,
  `presidente_titulo` varchar(255) DEFAULT 'Mensagem do Presidente',
  `presidente_mensagem` text DEFAULT NULL,
  `presidente_nome` varchar(255) DEFAULT NULL,
  `presidente_cargo` varchar(150) DEFAULT 'Presidente da Junta de Freguesia',
  `presidente_foto` varchar(255) DEFAULT NULL,
  `mostrar_mensagem_presidente` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homepage_config`
--

LOCK TABLES `homepage_config` WRITE;
/*!40000 ALTER TABLE `homepage_config` DISABLE KEYS */;
INSERT INTO `homepage_config` VALUES
(2,'Atalaia e Alto Estanqueiro-Jardia','Informação, serviços, documentos e património da freguesia, no concelho do Montijo.','Bem-vindo a Atalaia e Alto Estanqueiro-Jardia','A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia reúne o Santuário de Nossa Senhora da Atalaia, lugar de peregrinação desde o século XVI, e as localidades do Alto Estanqueiro e da Jardia, no concelho do Montijo.',NULL,NULL,'Contactar Junta','contactos.php',1,1,1,1,1,1,1,'2026-09-30 10:51:09','A freguesia em imagens','Atalaia e Alto Estanqueiro-Jardia em imagens',1,'Mensagem do Presidente','É com um profundo e enorme sentido de responsabilidade que cumprimento e saúdo todos os fregueses e a comunidade em geral.\n\nComo Presidente da União de Freguesias da Atalaia, Alto do Estanqueiro e Jardia, é para mim uma elevada honra assumir este compromisso com a causa pública, dedicando-me diariamente ao serviço de todos.\n\nIniciamos um novo ciclo, de novembro de 2025 a 2029, um ciclo marcado por proximidade, transparência e resultados concretos.\n\nA nossa Junta existe para servir, e esse é o eixo central de todo o trabalho que assumo com o restante executivo.','Pedro Miguel Guerreiro da Franca Araújo','Presidente da Junta de Freguesia','pedro_araujo.jpg',1);
/*!40000 ALTER TABLE `homepage_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homepage_destaque`
--

DROP TABLE IF EXISTS `homepage_destaque`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homepage_destaque` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('slider','video') DEFAULT 'slider',
  `titulo` varchar(255) DEFAULT NULL,
  `subtitulo` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `video_ficheiro` varchar(255) DEFAULT NULL,
  `botao_texto` varchar(100) DEFAULT NULL,
  `botao_link` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homepage_destaque`
--

LOCK TABLES `homepage_destaque` WRITE;
/*!40000 ALTER TABLE `homepage_destaque` DISABLE KEYS */;
/*!40000 ALTER TABLE `homepage_destaque` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `homepage_galeria`
--

DROP TABLE IF EXISTS `homepage_galeria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homepage_galeria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `imagem` varchar(255) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homepage_galeria`
--

LOCK TABLES `homepage_galeria` WRITE;
/*!40000 ALTER TABLE `homepage_galeria` DISABLE KEYS */;
/*!40000 ALTER TABLE `homepage_galeria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_operacionais`
--

DROP TABLE IF EXISTS `logs_operacionais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_operacionais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_operacionais`
--

LOCK TABLES `logs_operacionais` WRITE;
/*!40000 ALTER TABLE `logs_operacionais` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs_operacionais` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marcacoes_atendimento`
--

DROP TABLE IF EXISTS `marcacoes_atendimento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marcacoes_atendimento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cidadao_id` int(11) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `assunto` varchar(150) NOT NULL,
  `data_marcacao` date NOT NULL,
  `hora_marcacao` time NOT NULL,
  `mensagem` text DEFAULT NULL,
  `estado` enum('pendente','confirmada','cancelada','concluida') DEFAULT 'pendente',
  `resposta` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL,
  `responsavel` varchar(150) DEFAULT NULL,
  `observacoes_admin` text DEFAULT NULL,
  `tipo_atendimento` enum('presencial','virtual') NOT NULL DEFAULT 'presencial',
  `link_reuniao` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marcacoes_atendimento`
--

LOCK TABLES `marcacoes_atendimento` WRITE;
/*!40000 ALTER TABLE `marcacoes_atendimento` DISABLE KEYS */;
/*!40000 ALTER TABLE `marcacoes_atendimento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migracoes`
--

DROP TABLE IF EXISTS `migracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ficheiro` varchar(190) NOT NULL,
  `aplicada_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ficheiro` (`ficheiro`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migracoes`
--

LOCK TABLES `migracoes` WRITE;
/*!40000 ALTER TABLE `migracoes` DISABLE KEYS */;
INSERT INTO `migracoes` VALUES
(1,'001_tema_config.sql','2026-07-13 10:43:35'),
(2,'001_tema_config_seed.sql','2026-07-13 10:43:35'),
(3,'002_separadores_fundo.sql','2026-07-13 10:43:35'),
(4,'003_marca_logo.sql','2026-07-13 10:47:30'),
(5,'003_marca_logo_seed.sql','2026-07-13 10:47:30'),
(6,'004_faqs.sql','2026-07-13 10:56:20'),
(7,'005_galeria_albuns.sql','2026-07-13 11:11:33'),
(8,'006_evento_foco.sql','2026-07-13 11:29:30'),
(9,'007_perfis.sql','2026-07-13 14:49:37'),
(10,'008_tema_admin.sql','2026-07-13 14:55:50'),
(11,'008_tema_admin_seed.sql','2026-07-13 14:55:50'),
(12,'009_mensagem_presidente.sql','2026-07-13 15:25:57'),
(13,'010_alinhar_colunas.sql','2026-07-13 15:37:12'),
(14,'011_album_freguesia.sql','2026-07-13 20:35:28'),
(15,'012_albuns_pontos_existentes.sql','2026-07-13 20:56:25'),
(16,'013_mostrar_galeria.sql','2026-07-13 21:05:22'),
(17,'014_categorias.sql','2026-09-30 08:53:59'),
(18,'015_heraldica_elementos_imagem.sql','2026-09-30 08:53:59'),
(19,'016_eventos_inscricoes.sql','2026-09-30 08:53:59'),
(20,'017_eventos_campos_extra.sql','2026-09-30 08:53:59'),
(21,'018_contratacao_publica_pagina.sql','2026-09-30 08:53:59'),
(22,'019_procedimentos_concursais_campos.sql','2026-09-30 08:53:59'),
(23,'020_ocorrencias_avancado.sql','2026-09-30 08:53:59'),
(24,'021_ocorrencias_origem_estado_interno.sql','2026-09-30 08:53:59'),
(25,'022_ocorrencias_entidades_prioridades_estados_acoes.sql','2026-09-30 08:53:59'),
(26,'023_ocorrencias_tags_ordem.sql','2026-09-30 08:53:59'),
(27,'031_newsletter.sql','2026-09-30 08:53:59'),
(28,'032_eventos_coordenadas.sql','2026-09-30 08:53:59');
/*!40000 ALTER TABLE `migracoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modelos_requerimentos`
--

DROP TABLE IF EXISTS `modelos_requerimentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modelos_requerimentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `tipo` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ficheiro` varchar(255) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modelos_requerimentos`
--

LOCK TABLES `modelos_requerimentos` WRITE;
/*!40000 ALTER TABLE `modelos_requerimentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `modelos_requerimentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_config`
--

DROP TABLE IF EXISTS `newsletter_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_config` (
  `id` int(11) NOT NULL DEFAULT 1,
  `auto_ativo` tinyint(1) NOT NULL DEFAULT 0,
  `dia_envio` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `ultimo_envio` varchar(7) DEFAULT NULL,
  `conteudo_manual` longtext DEFAULT NULL,
  `destaque_tipo` enum('noticia','evento') DEFAULT NULL,
  `destaque_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_config`
--

LOCK TABLES `newsletter_config` WRITE;
/*!40000 ALTER TABLE `newsletter_config` DISABLE KEYS */;
INSERT INTO `newsletter_config` VALUES
(1,0,1,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `newsletter_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_envios`
--

DROP TABLE IF EXISTS `newsletter_envios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_envios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mes_referencia` varchar(7) NOT NULL,
  `conteudo_html` longtext NOT NULL,
  `total_destinatarios` int(11) NOT NULL DEFAULT 0,
  `enviado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mes` (`mes_referencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_envios`
--

LOCK TABLES `newsletter_envios` WRITE;
/*!40000 ALTER TABLE `newsletter_envios` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_envios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `origem` enum('site','manual') NOT NULL DEFAULT 'manual',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `noticias`
--

DROP TABLE IF EXISTS `noticias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `noticias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `data` datetime DEFAULT current_timestamp(),
  `imagem_foco_x` tinyint(3) unsigned NOT NULL DEFAULT 50,
  `imagem_foco_y` tinyint(3) unsigned NOT NULL DEFAULT 50,
  `categoria` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `noticias`
--

LOCK TABLES `noticias` WRITE;
/*!40000 ALTER TABLE `noticias` DISABLE KEYS */;
INSERT INTO `noticias` VALUES
(79,'Montijo, 41 anos de cidade','No dia 14 de agosto de 1985, o Montijo foi elevado à categoria de cidade.\n\nHoje, 41 anos depois, celebramos não apenas uma data, mas uma história construída por gerações de Montijenses e por todas as freguesias do concelho, que ao longo dos anos contribuíram para o seu desenvolvimento e afirmação.\n\nO crescimento do Montijo fez-se também a partir das suas freguesias, através do trabalho das suas populações, da atividade económica, da agricultura, do comércio, das associações, da cultura, das tradições e da vida comunitária.\n\nA União das Freguesias de Atalaia e Alto Estanqueiro-Jardia associa-se a esta celebração, deixando uma palavra de reconhecimento a todos aqueles que, ao longo dos anos, contribuíram e continuam a contribuir para o crescimento e desenvolvimento do nosso concelho.\n\nParabéns, Montijo.','noticia_montijo_41_anos.jpg','2026-08-14 10:00:00',50,50,'Freguesia'),
(80,'Entrega do donativo angariado à Cáritas Diocesana','No dia 22 de maio foi entregue à Cáritas Diocesana o donativo angariado na 3.ª Caminhada Solidária da Atalaia, iniciativa promovida pela Associação Mansos e Vadios e integrada nas comemorações do 25 de Abril, com o apoio da Junta da União das Freguesias de Atalaia e Alto Estanqueiro-Jardia.\n\nGraças à participação de todos, foi possível angariar 306 €, valor que reverteu integralmente para esta instituição, contribuindo para apoiar quem mais precisa na nossa comunidade.\n\nA todos os que participaram e contribuíram, o nosso sincero obrigado. Juntos, continuamos a construir uma união de freguesias mais solidária, unida e próxima da comunidade.','noticia_caritas.jpg','2026-05-22 10:00:00',50,50,'Social'),
(81,'3.ª Caminhada Solidária da Atalaia','A Junta da União das Freguesias de Atalaia e Alto Estanqueiro-Jardia marcou presença na 3.ª Caminhada Solidária da Atalaia.\n\nEsta iniciativa, promovida pela Associação Mansos e Vadios e integrada nas comemorações do 25 de Abril, voltou a reunir fregueses, munícipes, famílias e visitantes num momento de convívio, partilha e solidariedade. Com um percurso acessível, a caminhada teve como principal objetivo apoiar uma instituição de solidariedade do concelho.\n\nO valor total angariado através das inscrições foi de 306 €, doado à Cáritas Diocesana da Atalaia. A Junta felicita a Associação Mansos e Vadios pela excelente organização desta 3.ª edição e todos os participantes, e agradece ao Restaurante «O Ninho» o apoio prestado a esta causa.','noticia_caminhada_2026.jpg','2026-04-25 10:00:00',50,50,'Eventos'),
(82,'Mensagem de Páscoa','Estimados fregueses da Atalaia, Alto Estanqueiro e Jardia.\n\nNesta época de celebração e partilha, dirijo-me a cada um de vós para desejar uma Santa Páscoa, repleta de harmonia e paz. A Páscoa é, acima de tudo, um tempo de renovação e de esperança — valores que guiam o nosso trabalho diário na União de Freguesias.\n\nQue este período seja vivido com serenidade junto das vossas famílias e que o espírito de união que carateriza a nossa terra se fortaleça ainda mais.\n\nUm abraço fraterno a todos.\nPedro Araújo — O Presidente','noticia_pascoa_2026.jpg','2026-04-03 15:00:00',50,50,'Freguesia'),
(83,'Apresentação do livro «Um Pouco de Tudo», de José Martinho','No passado dia 28 de março, a dependência da Junta no Alto Estanqueiro encheu-se de poesia, emoção e partilha com a apresentação do livro «Um Pouco de Tudo», de José Martinho, um autor da nossa terra.\n\nCom um percurso marcante no futebol — como jogador, treinador e árbitro ao mais alto nível —, José Martinho traz agora para a escrita o mesmo rigor, sensibilidade e olhar atento sobre a vida, dando continuidade a obras como «Histórias Rimadas» e «Amor e Humor em Poesia».\n\nA sessão, conduzida por Inga Oliveira, locutora e também membro da assembleia de freguesia, contou com momentos verdadeiramente especiais: enquanto a poesia era declamada, o Sr. Sérgio Pastor acompanhava ao acordeão. No final, houve ainda uma sessão de autógrafos e um momento de convívio com o autor.','noticia_livro_jose_martinho.jpg','2026-03-28 18:00:00',50,50,'Cultura'),
(84,'62.º aniversário do Águias Negras Futebol Clube','No dia 1 de março de 2026, o Senhor Presidente e o Senhor Tesoureiro da Junta estiveram presentes no almoço comemorativo do 62.º aniversário do Águias Negras Futebol Clube, que teve lugar na sua sede, no Alto Estanqueiro.\n\nA iniciativa reuniu sócios, familiares e amigos desta coletividade, num momento de convívio e celebração, assinalado com um almoço tradicional de feijoada caramela.\n\nA Junta associa-se a esta data, felicitando o Águias Negras Futebol Clube pelos seus 62 anos de existência e destacando o seu importante papel na dinamização desportiva, social e cultural da freguesia.','noticia_aguias_negras_62.jpg','2026-03-01 13:00:00',50,50,'Associativismo'),
(85,'Ajuda solidária a Alcácer do Sal','As recentes cheias em Alcácer do Sal afetaram várias famílias, que neste momento precisam do apoio de todos. A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia está a promover uma angariação de bens alimentares e produtos de higiene, que serão entregues diretamente no local com a carrinha da Junta.\n\nO que pode doar: alimentos não perecíveis (arroz, massa, enlatados, leite, óleo, bolachas) e produtos de higiene pessoal (gel de banho, champô, pasta e escova de dentes, fraldas, pensos higiénicos, papel higiénico).\n\nPontos de recolha: Sede (Av. 28 de Setembro, n.º 56, Atalaia) e Dependência (Rua dos Russos – Quinta das Tílias, Alto Estanqueiro-Jardia), das 9h00 às 12h30 e das 14h00 às 17h30, até terça-feira, dia 10 de fevereiro.','noticia_alcacer_do_sal.jpg','2026-02-05 10:00:00',50,50,'Social');
/*!40000 ALTER TABLE `noticias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `noticias_imagens`
--

DROP TABLE IF EXISTS `noticias_imagens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `noticias_imagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `noticia_id` int(11) NOT NULL,
  `ficheiro` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `noticia_id` (`noticia_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `noticias_imagens`
--

LOCK TABLES `noticias_imagens` WRITE;
/*!40000 ALTER TABLE `noticias_imagens` DISABLE KEYS */;
/*!40000 ALTER TABLE `noticias_imagens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificacoes`
--

DROP TABLE IF EXISTS `notificacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `tipo` enum('geral','urgente','evento','servico','documento') DEFAULT 'geral',
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificacoes`
--

LOCK TABLES `notificacoes` WRITE;
/*!40000 ALTER TABLE `notificacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificacoes_lidas`
--

DROP TABLE IF EXISTS `notificacoes_lidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacoes_lidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notificacao_id` int(11) NOT NULL,
  `cidadao_id` int(11) NOT NULL,
  `lida_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unica_leitura` (`notificacao_id`,`cidadao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificacoes_lidas`
--

LOCK TABLES `notificacoes_lidas` WRITE;
/*!40000 ALTER TABLE `notificacoes_lidas` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificacoes_lidas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ocorrencias_acoes`
--

DROP TABLE IF EXISTS `ocorrencias_acoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ocorrencias_acoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designacao` varchar(100) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acao` (`designacao`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ocorrencias_acoes`
--

LOCK TABLES `ocorrencias_acoes` WRITE;
/*!40000 ALTER TABLE `ocorrencias_acoes` DISABLE KEYS */;
INSERT INTO `ocorrencias_acoes` VALUES
(1,'Enviar por E-mail',10,1,'2026-09-30 08:53:59'),
(2,'Enviar por Ofício',20,1,'2026-09-30 08:53:59'),
(3,'Agendar Visita',30,1,'2026-09-30 08:53:59'),
(4,'Resolver Internamente',40,1,'2026-09-30 08:53:59'),
(5,'Arquivar sem Ação',50,1,'2026-09-30 08:53:59');
/*!40000 ALTER TABLE `ocorrencias_acoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ocorrencias_assuntos`
--

DROP TABLE IF EXISTS `ocorrencias_assuntos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ocorrencias_assuntos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) NOT NULL,
  `designacao` varchar(150) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`categoria_id`),
  CONSTRAINT `fk_ocorrencias_assuntos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `ocorrencias_categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ocorrencias_assuntos`
--

LOCK TABLES `ocorrencias_assuntos` WRITE;
/*!40000 ALTER TABLE `ocorrencias_assuntos` DISABLE KEYS */;
INSERT INTO `ocorrencias_assuntos` VALUES
(1,3,'Árvore/ramo em risco',20,1,'2026-09-30 08:53:59'),
(2,3,'Corte de relva/mato',10,1,'2026-09-30 08:53:59'),
(3,2,'Poste danificado',20,1,'2026-09-30 08:53:59'),
(4,2,'Candeeiro fundido',10,1,'2026-09-30 08:53:59'),
(5,1,'Dejetos de animais',30,1,'2026-09-30 08:53:59'),
(6,1,'Contentor danificado',20,1,'2026-09-30 08:53:59'),
(7,1,'Lixo acumulado',10,1,'2026-09-30 08:53:59'),
(8,4,'Passeio danificado',30,1,'2026-09-30 08:53:59'),
(9,4,'Sinalização em falta/danificada',20,1,'2026-09-30 08:53:59'),
(10,4,'Buraco na via',10,1,'2026-09-30 08:53:59');
/*!40000 ALTER TABLE `ocorrencias_assuntos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ocorrencias_categorias`
--

DROP TABLE IF EXISTS `ocorrencias_categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ocorrencias_categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designacao` varchar(100) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categoria` (`designacao`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ocorrencias_categorias`
--

LOCK TABLES `ocorrencias_categorias` WRITE;
/*!40000 ALTER TABLE `ocorrencias_categorias` DISABLE KEYS */;
INSERT INTO `ocorrencias_categorias` VALUES
(1,'Limpeza urbana',10,1,'2026-09-30 08:53:59'),
(2,'Iluminação pública',20,1,'2026-09-30 08:53:59'),
(3,'Espaços verdes',30,1,'2026-09-30 08:53:59'),
(4,'Vias e passeios',40,1,'2026-09-30 08:53:59'),
(5,'Sugestão',50,1,'2026-09-30 08:53:59'),
(6,'Outro',60,1,'2026-09-30 08:53:59');
/*!40000 ALTER TABLE `ocorrencias_categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ocorrencias_entidades_externas`
--

DROP TABLE IF EXISTS `ocorrencias_entidades_externas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ocorrencias_entidades_externas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designacao` varchar(150) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_entidade` (`designacao`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ocorrencias_entidades_externas`
--

LOCK TABLES `ocorrencias_entidades_externas` WRITE;
/*!40000 ALTER TABLE `ocorrencias_entidades_externas` DISABLE KEYS */;
INSERT INTO `ocorrencias_entidades_externas` VALUES
(1,'Junta de Freguesia',NULL,10,1,'2026-09-30 08:53:59'),
(2,'Câmara Municipal',NULL,20,1,'2026-09-30 08:53:59'),
(3,'GNR - Guarda Nacional Republicana',NULL,30,1,'2026-09-30 08:53:59'),
(4,'EDP/E-REDES',NULL,40,1,'2026-09-30 08:53:59'),
(5,'Serviços Municipalizados de Água e Saneamento',NULL,50,1,'2026-09-30 08:53:59');
/*!40000 ALTER TABLE `ocorrencias_entidades_externas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ocorrencias_estados`
--

DROP TABLE IF EXISTS `ocorrencias_estados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ocorrencias_estados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(30) NOT NULL,
  `designacao` varchar(60) NOT NULL,
  `cor` varchar(20) NOT NULL DEFAULT '#495057',
  `valor_percentual` int(11) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_estado_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ocorrencias_estados`
--

LOCK TABLES `ocorrencias_estados` WRITE;
/*!40000 ALTER TABLE `ocorrencias_estados` DISABLE KEYS */;
INSERT INTO `ocorrencias_estados` VALUES
(1,'pendente','Pendente','#f59f00',15,10,1,'2026-09-30 08:53:59'),
(2,'em_analise','Em análise','#2563eb',55,20,1,'2026-09-30 08:53:59'),
(3,'resolvido','Resolvido','#2b8a3e',100,30,1,'2026-09-30 08:53:59'),
(4,'arquivado','Arquivado','#6b7280',100,40,1,'2026-09-30 08:53:59');
/*!40000 ALTER TABLE `ocorrencias_estados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ocorrencias_prioridades`
--

DROP TABLE IF EXISTS `ocorrencias_prioridades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ocorrencias_prioridades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(30) NOT NULL,
  `designacao` varchar(60) NOT NULL,
  `cor` varchar(20) NOT NULL DEFAULT '#495057',
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prioridade_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ocorrencias_prioridades`
--

LOCK TABLES `ocorrencias_prioridades` WRITE;
/*!40000 ALTER TABLE `ocorrencias_prioridades` DISABLE KEYS */;
INSERT INTO `ocorrencias_prioridades` VALUES
(1,'critico','Crítico','#c92a2a',10,1,'2026-09-30 08:53:59'),
(2,'urgente','Urgente','#e8590c',20,1,'2026-09-30 08:53:59'),
(3,'normal','Normal','#495057',30,1,'2026-09-30 08:53:59'),
(4,'baixo','Baixo','#adb5bd',40,1,'2026-09-30 08:53:59');
/*!40000 ALTER TABLE `ocorrencias_prioridades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ocorrencias_tags`
--

DROP TABLE IF EXISTS `ocorrencias_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ocorrencias_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designacao` varchar(100) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tag` (`designacao`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ocorrencias_tags`
--

LOCK TABLES `ocorrencias_tags` WRITE;
/*!40000 ALTER TABLE `ocorrencias_tags` DISABLE KEYS */;
INSERT INTO `ocorrencias_tags` VALUES
(1,'Urgente',0,1,'2026-09-30 08:53:59'),
(2,'Reincidente',0,1,'2026-09-30 08:53:59'),
(3,'Via pública',0,1,'2026-09-30 08:53:59'),
(4,'Zona escolar',0,1,'2026-09-30 08:53:59'),
(5,'Zona residencial',0,1,'2026-09-30 08:53:59');
/*!40000 ALTER TABLE `ocorrencias_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagina_freguesia`
--

DROP TABLE IF EXISTS `pagina_freguesia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagina_freguesia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_kicker` varchar(120) DEFAULT 'A Freguesia',
  `hero_titulo` varchar(255) DEFAULT 'Granho',
  `hero_subtitulo` text DEFAULT NULL,
  `hero_imagem` varchar(255) DEFAULT NULL,
  `intro_titulo` varchar(255) DEFAULT NULL,
  `intro_texto` longtext DEFAULT NULL,
  `historia_titulo` varchar(255) DEFAULT NULL,
  `historia_texto` longtext DEFAULT NULL,
  `identidade_titulo` varchar(255) DEFAULT NULL,
  `identidade_texto` longtext DEFAULT NULL,
  `patrimonio_titulo` varchar(255) DEFAULT NULL,
  `patrimonio_texto` longtext DEFAULT NULL,
  `localidades_titulo` varchar(255) DEFAULT NULL,
  `localidades_texto` text DEFAULT NULL,
  `galeria_titulo` varchar(255) DEFAULT NULL,
  `botao1_texto` varchar(120) DEFAULT NULL,
  `botao1_link` varchar(255) DEFAULT NULL,
  `botao2_texto` varchar(120) DEFAULT NULL,
  `botao2_link` varchar(255) DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagina_freguesia`
--

LOCK TABLES `pagina_freguesia` WRITE;
/*!40000 ALTER TABLE `pagina_freguesia` DISABLE KEYS */;
INSERT INTO `pagina_freguesia` VALUES
(1,'Concelho do Montijo','Atalaia e Alto Estanqueiro-Jardia','Num monte sobranceiro ao estuário do Tejo, entre o Santuário da Atalaia e os campos do Alto Estanqueiro e da Jardia.','santuario_atalaia.jpg','Uma freguesia do Montijo','A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia pertence ao concelho do Montijo, distrito de Setúbal. Tem 13,65 km² e 5379 habitantes (Censos 2021). Foi constituída pela Lei n.º 11-A/2013, de 28 de janeiro, que agregou as freguesias da Atalaia e do Alto Estanqueiro-Jardia.','História e memória','A cerca de quatro quilómetros da sede do município, a Atalaia beneficiou desde sempre da proximidade da Estrada Real que ligava Lisboa a Badajoz, por Aldeia Galega, e viu passar monarcas e outras personagens ilustres a caminho da fronteira e do sul do país. Já no início do século XVI, as populações locais e dos arredores vinham aqui em peregrinação.','Identidade','O culto de Nossa Senhora da Atalaia, vivido por romeiros e festeiros, é o grande traço de identidade da freguesia. Alguns monarcas foram particularmente devotos da Senhora, como D. João V; a última visita régia foi a da rainha D. Maria II, a 5 de outubro de 1843.','Património','A Igreja de Nossa Senhora da Atalaia e os seus três cruzeiros — o Cruzeiro Mor (1551), o Cruzeiro de Alcochete (1669) e o Cruzeiro das Esmolas — foram classificados em 2009 como Imóveis de Interesse Público. Junto à escadaria do Santuário fica o Museu Agrícola da Atalaia, na Quinta Nova da Atalaia.','Localidades da freguesia','Atalaia|Sede da freguesia, junto ao Santuário de Nossa Senhora da Atalaia\nAlto Estanqueiro|Onde fica a dependência da Junta, na Quinta das Tílias\nJardia|Lugar já referido em 1866, de tradição hortícola','A freguesia em imagens','Ver pontos de interesse','/pontos.php','Explorar no mapa','/mapa.php','2026-09-30 11:12:56');
/*!40000 ALTER TABLE `pagina_freguesia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagina_historia`
--

DROP TABLE IF EXISTS `pagina_historia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagina_historia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_kicker` varchar(120) DEFAULT 'História',
  `hero_titulo` varchar(255) DEFAULT 'História da Freguesia',
  `hero_subtitulo` text DEFAULT NULL,
  `hero_imagem` varchar(255) DEFAULT NULL,
  `intro_titulo` varchar(255) DEFAULT NULL,
  `intro_texto` longtext DEFAULT NULL,
  `bloco1_titulo` varchar(255) DEFAULT NULL,
  `bloco1_texto` longtext DEFAULT NULL,
  `bloco2_titulo` varchar(255) DEFAULT NULL,
  `bloco2_texto` longtext DEFAULT NULL,
  `timeline_titulo` varchar(255) DEFAULT NULL,
  `timeline_texto` longtext DEFAULT NULL,
  `galeria_titulo` varchar(255) DEFAULT NULL,
  `botao1_texto` varchar(120) DEFAULT NULL,
  `botao1_link` varchar(255) DEFAULT NULL,
  `botao2_texto` varchar(120) DEFAULT NULL,
  `botao2_link` varchar(255) DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagina_historia`
--

LOCK TABLES `pagina_historia` WRITE;
/*!40000 ALTER TABLE `pagina_historia` DISABLE KEYS */;
INSERT INTO `pagina_historia` VALUES
(1,'História','História de Atalaia e Alto Estanqueiro-Jardia','Do Santuário da Atalaia aos campos hortícolas do Alto Estanqueiro e da Jardia.','cruzeiro_mor.jpg','A Atalaia','Assente num monte sobranceiro ao estuário do Tejo, a povoação da Atalaia cresceu à volta do seu Santuário. A proximidade da Estrada Real, que ligava Lisboa a Badajoz via Aldeia Galega, trouxe-lhe passagem constante de viajantes — e a fé trouxe-lhe peregrinos desde o início do século XVI. Por volta de 1507, os funcionários da Alfândega de Lisboa vieram aqui em promessa a Nossa Senhora da Atalaia por ocasião de uma peste.','Tempos difíceis','Em 1808, as invasões francesas levaram ao saque da Igreja da Atalaia pelos exércitos de Napoleão. Mais tarde, com a implantação da República e o anticlericalismo que a acompanhou, o Cruzeiro Mor ficou com as imagens decapitadas e a coroa das armas reais do retábulo partida; em 1912, depois de um comício em Aldeia Galega, populares assaltaram a igreja. Ainda assim, o culto da Senhora da Atalaia manteve-se vivo até aos nossos dias.','Alto Estanqueiro e Jardia','Nascida da junção de dois lugares, a antiga freguesia do Alto Estanqueiro-Jardia pertenceu à jurisdição da Ordem de Santiago, sediada em Palmela. O topónimo «Estanqueiro» liga-se provavelmente ao comércio em regime de monopólio (tabaco, pólvora, palha); «Jardia» à járdia, a charneca de rosmaninho e alecrim. Até meados do século XX o território era de fazendas e terrenos agrícolas que abasteciam o concelho de produtos hortícolas; o crescimento urbano veio na segunda metade do século.','Principais datas','c. 1507|Os funcionários da Alfândega de Lisboa vêm em promessa a Nossa Senhora da Atalaia, por ocasião de uma peste.\n1551|A Confraria de Lisboa manda construir o Cruzeiro Mor.\n1669|Uma família de Alcochete manda construir o Cruzeiro de Alcochete.\n1808|Saque da Igreja da Atalaia durante as invasões francesas.\n1843|A rainha D. Maria II visita a Igreja da Atalaia, a 5 de outubro.\n1866|A Jardia é referida como lugar da freguesia do Divino Espírito Santo do Montijo.\n1985|A Lei n.º 82/85, de 4 de outubro, cria a freguesia de Alto Estanqueiro-Jardia.\n1997|Abre ao público o Museu Agrícola da Atalaia, na Quinta Nova da Atalaia.\n2009|A Igreja de Nossa Senhora da Atalaia e os três cruzeiros são classificados como Imóveis de Interesse Público.\n2013|Lei n.º 11-A/2013: constituição da União das Freguesias de Atalaia e Alto Estanqueiro-Jardia.','A freguesia em imagens',NULL,NULL,NULL,NULL,'2026-09-30 11:12:56');
/*!40000 ALTER TABLE `pagina_historia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_mensagens`
--

DROP TABLE IF EXISTS `pedido_mensagens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `cidadao_id` int(11) DEFAULT NULL,
  `autor_tipo` enum('cidadao','admin') NOT NULL,
  `autor_nome` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_mensagens`
--

LOCK TABLES `pedido_mensagens` WRITE;
/*!40000 ALTER TABLE `pedido_mensagens` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedido_mensagens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_timeline`
--

DROP TABLE IF EXISTS `pedido_timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_timeline`
--

LOCK TABLES `pedido_timeline` WRITE;
/*!40000 ALTER TABLE `pedido_timeline` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedido_timeline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_typing`
--

DROP TABLE IF EXISTS `pedido_typing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_typing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `tipo` enum('cidadao','admin') NOT NULL,
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pedido_tipo_unique` (`pedido_id`,`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_typing`
--

LOCK TABLES `pedido_typing` WRITE;
/*!40000 ALTER TABLE `pedido_typing` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedido_typing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos_emails_externos`
--

DROP TABLE IF EXISTS `pedidos_emails_externos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos_emails_externos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `destinatario` varchar(255) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `corpo` text NOT NULL,
  `enviado_por_nome` varchar(150) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pedido` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos_emails_externos`
--

LOCK TABLES `pedidos_emails_externos` WRITE;
/*!40000 ALTER TABLE `pedidos_emails_externos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos_emails_externos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos_funcionarios`
--

DROP TABLE IF EXISTS `pedidos_funcionarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos_funcionarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pedido_funcionario` (`pedido_id`,`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos_funcionarios`
--

LOCK TABLES `pedidos_funcionarios` WRITE;
/*!40000 ALTER TABLE `pedidos_funcionarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos_funcionarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos_imagens`
--

DROP TABLE IF EXISTS `pedidos_imagens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos_imagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `imagem` varchar(255) NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos_imagens`
--

LOCK TABLES `pedidos_imagens` WRITE;
/*!40000 ALTER TABLE `pedidos_imagens` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos_imagens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos_junta`
--

DROP TABLE IF EXISTS `pedidos_junta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos_junta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) DEFAULT NULL,
  `cidadao_id` int(11) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `categoria` varchar(100) NOT NULL,
  `subcategoria` varchar(150) DEFAULT NULL,
  `assunto` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `localizacao` varchar(255) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'pendente',
  `resposta` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `publico` tinyint(1) DEFAULT 1,
  `operador_id` int(11) DEFAULT NULL,
  `prioridade` varchar(30) NOT NULL DEFAULT 'normal',
  `competencia` varchar(120) DEFAULT 'Junta de Freguesia',
  `origem` varchar(30) NOT NULL DEFAULT 'website',
  `estado_interno` varchar(30) NOT NULL DEFAULT 'por_tratar',
  `nome_original` varchar(255) DEFAULT NULL,
  `email_original` varchar(255) DEFAULT NULL,
  `telefone_original` varchar(50) DEFAULT NULL,
  `categoria_original` varchar(100) DEFAULT NULL,
  `subcategoria_original` varchar(150) DEFAULT NULL,
  `assunto_original` varchar(255) DEFAULT NULL,
  `mensagem_original` text DEFAULT NULL,
  `localizacao_original` varchar(255) DEFAULT NULL,
  `acao` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos_junta`
--

LOCK TABLES `pedidos_junta` WRITE;
/*!40000 ALTER TABLE `pedidos_junta` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos_junta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos_notas_internas`
--

DROP TABLE IF EXISTS `pedidos_notas_internas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos_notas_internas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `autor_nome` varchar(150) NOT NULL,
  `nota` text NOT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pedido` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos_notas_internas`
--

LOCK TABLES `pedidos_notas_internas` WRITE;
/*!40000 ALTER TABLE `pedidos_notas_internas` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos_notas_internas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos_tags`
--

DROP TABLE IF EXISTS `pedidos_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pedido_tag` (`pedido_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos_tags`
--

LOCK TABLES `pedidos_tags` WRITE;
/*!40000 ALTER TABLE `pedidos_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos_tratamentos`
--

DROP TABLE IF EXISTS `pedidos_tratamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos_tratamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `autor_nome` varchar(150) NOT NULL,
  `nota` text NOT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pedido` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos_tratamentos`
--

LOCK TABLES `pedidos_tratamentos` WRITE;
/*!40000 ALTER TABLE `pedidos_tratamentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos_tratamentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfis`
--

DROP TABLE IF EXISTS `perfis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `permissoes` text DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfis`
--

LOCK TABLES `perfis` WRITE;
/*!40000 ALTER TABLE `perfis` DISABLE KEYS */;
INSERT INTO `perfis` VALUES
(1,'Secretaria','Conteúdos do site e atendimento ao munícipe.','freguesia,home,virtual',1,'2026-07-13 14:49:37'),
(2,'Assembleia','Apenas a área da Assembleia de Freguesia.','assembleia',1,'2026-07-13 14:49:37'),
(3,'Conteúdos','Só notícias, eventos e restantes conteúdos.','freguesia,home',1,'2026-07-13 14:49:37');
/*!40000 ALTER TABLE `perfis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pontos_interesse`
--

DROP TABLE IF EXISTS `pontos_interesse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pontos_interesse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `localizacao` varchar(255) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pontos_interesse`
--

LOCK TABLES `pontos_interesse` WRITE;
/*!40000 ALTER TABLE `pontos_interesse` DISABLE KEYS */;
INSERT INTO `pontos_interesse` VALUES
(112,'Igreja de Nossa Senhora da Atalaia','Igreja-santuário edificada no século XVI e reedificada no século XVIII, classificada como Imóvel de Interesse Público em 2009, com os três cruzeiros. Antecedida por um alpendre de três arcos, tem uma só nave, púlpito de mármore da Arrábida e altar-mor com retábulo setecentista de madeira do Brasil. As paredes estão forradas de azulejos azuis e brancos do século XVIII com cenas da vida da Virgem. Numa dependência anexa guardam-se os ex-votos populares; nas traseiras, a Fonte da Senhora, onde, segundo a lenda, terá aparecido a imagem de Nossa Senhora da Atalaia.','igreja_atalaia.jpg','Atalaia','38.7075836','-8.9220027'),
(113,'Cruzeiro Mor','Com as imagens esculpidas de Jesus Cristo e de Nossa Senhora da Piedade cobertas por uma cúpula sustida por quatro colunas, foi mandado construir pela Confraria de Lisboa em 1551 e reconstruído em 2001. As imagens continuam decapitadas, marca do anticlericalismo da República. Imóvel de Interesse Público (2009).','cruzeiro_mor.jpg','Atalaia','38.7070004','-8.9245699'),
(114,'Cruzeiro de Alcochete','Cruzeiro de pedra lioz, à direita da igreja, junto à linha limite do concelho, mandado construir por uma família de Alcochete em 1669. Imóvel de Interesse Público (2009).','cruzeiro_alcochete.jpg','Atalaia','38.7082449','-8.9224782'),
(115,'Cruzeiro das Esmolas','Também chamado Cruzeiro da Estrada, junto à Estrada Nacional n.º 4, a cerca de 150 metros da igreja. É o mais simples dos três; desconhece-se o ano da sua construção e foi reconstruído no início deste século. Imóvel de Interesse Público (2009).','cruzeiro_esmolas.jpg','Atalaia','38.7063537','-8.9221678'),
(116,'Museu Agrícola da Atalaia','Desde 1997, a Quinta Nova da Atalaia, junto à escadaria do Santuário, é o núcleo museológico do concelho dedicado à temática agrícola. Requalificado em 2009, preserva o lagar de azeite (com moinho de duas galgas e prensas), a adega e as práticas agrícolas tradicionais ligadas ao azeite, ao vinho e à fruta. Entrada gratuita.','museu_agricola_atalaia.jpg','Rua da Atalaia, Atalaia','38.7082512','-8.9231138'),
(117,'Flor da Liberdade — Homenagem à Floricultura','Escultura do italiano Tony Cassanelli, com o escultor Jaime Carvalho, em homenagem à floricultura montijense, inaugurada a 25 de abril de 2024 na rotunda do Apeadeiro de Sarilhos. A flor e as folhas são evocadas em geometrias abstratas e reflexos metálicos, numa obra pensada para ser vista em movimento, por quem circula na rotunda.','monumento_floricultura.jpg','Rotunda do Apeadeiro de Sarilhos, EN 5, Alto Estanqueiro','38.6864969','-8.944169'),
(118,'Monumento a Álvaro Tavares Mora','Busto em bronze e calcário moleano de Laureano Ribatua, inaugurado a 24 de agosto de 2001. É uma homenagem da população da Atalaia a Álvaro Tavares Mora, autarca da Câmara Municipal do Montijo e benemérito que, em 1947, mandou construir dois chafarizes, resolvendo o problema do abastecimento de água à população.','monumento_alvaro_tavares_mora.jpg','Praça dos Operários, Atalaia','38.7069401','-8.9220795'),
(119,'Cruzeiro de Granito','Cruzeiro em granito mandado colocar pela Junta de Freguesia em 2005, na rotunda da Atalaia, em homenagem aos círios que ainda hoje fazem romagem à Atalaia.','cruzeiro_granito.jpg','Estrada Nacional 4, Atalaia',NULL,NULL);
/*!40000 ALTER TABLE `pontos_interesse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recursos_humanos_config`
--

DROP TABLE IF EXISTS `recursos_humanos_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recursos_humanos_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_kicker` varchar(150) DEFAULT 'Recursos Humanos',
  `hero_titulo` varchar(255) NOT NULL DEFAULT 'Recursos Humanos',
  `hero_subtitulo` text DEFAULT NULL,
  `intro_titulo` varchar(255) DEFAULT NULL,
  `intro_texto` text DEFAULT NULL,
  `destaque_1_titulo` varchar(150) DEFAULT NULL,
  `destaque_1_valor` varchar(100) DEFAULT NULL,
  `destaque_2_titulo` varchar(150) DEFAULT NULL,
  `destaque_2_valor` varchar(100) DEFAULT NULL,
  `destaque_3_titulo` varchar(150) DEFAULT NULL,
  `destaque_3_valor` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recursos_humanos_config`
--

LOCK TABLES `recursos_humanos_config` WRITE;
/*!40000 ALTER TABLE `recursos_humanos_config` DISABLE KEYS */;
INSERT INTO `recursos_humanos_config` VALUES
(2,'Recursos Humanos','Recursos Humanos','Transparência, mérito e valorização das pessoas ao serviço da freguesia.','Pessoas ao serviço da comunidade','Os trabalhadores da Junta de Freguesia de Atalaia e Alto Estanqueiro-Jardia são o principal motor da prestação de serviços de proximidade à população. A gestão de recursos humanos assenta num ambiente de trabalho colaborativo, baseado na responsabilidade, ética, respeito e dedicação ao serviço público, na formação contínua dos colaboradores para manter o conhecimento atualizado e melhorar a qualidade do serviço, no recrutamento e seleção por mérito, numa comunicação interna clara e alinhada com os objetivos estratégicos, e em medidas de bem-estar e equilíbrio entre vida profissional e pessoal.','Concursos a decorrer','Sem concursos a decorrer','Recrutamento','Seleção por mérito','Portais de emprego público','BEP, Portal do Emprego Público, DRE, GovGo',1,NULL);
/*!40000 ALTER TABLE `recursos_humanos_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recursos_humanos_documentos`
--

DROP TABLE IF EXISTS `recursos_humanos_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recursos_humanos_documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `categoria` enum('mapa_pessoal','recrutamento_concursos','avaliacao_siadap','plano_formacao','organigrama','transparencia_legislacao','outros') NOT NULL DEFAULT 'outros',
  `estado` enum('ativo','aberto','encerrado','arquivo') NOT NULL DEFAULT 'ativo',
  `descricao` text DEFAULT NULL,
  `ficheiro_original` varchar(255) DEFAULT NULL,
  `ficheiro_guardado` varchar(255) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `data_documento` date DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_estado` (`estado`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recursos_humanos_documentos`
--

LOCK TABLES `recursos_humanos_documentos` WRITE;
/*!40000 ALTER TABLE `recursos_humanos_documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `recursos_humanos_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requerimentos`
--

DROP TABLE IF EXISTS `requerimentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `requerimentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cidadao_id` int(11) DEFAULT NULL,
  `codigo` varchar(50) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `tipo` varchar(150) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `mensagem` text DEFAULT NULL,
  `estado` enum('pendente','em_analise','deferido','indeferido','concluido') DEFAULT 'pendente',
  `resposta` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL,
  `documento_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requerimentos`
--

LOCK TABLES `requerimentos` WRITE;
/*!40000 ALTER TABLE `requerimentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `requerimentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requerimentos_ficheiros`
--

DROP TABLE IF EXISTS `requerimentos_ficheiros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `requerimentos_ficheiros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requerimento_id` int(11) NOT NULL,
  `ficheiro` varchar(255) NOT NULL,
  `nome_original` varchar(255) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requerimentos_ficheiros`
--

LOCK TABLES `requerimentos_ficheiros` WRITE;
/*!40000 ALTER TABLE `requerimentos_ficheiros` DISABLE KEYS */;
/*!40000 ALTER TABLE `requerimentos_ficheiros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seccoes_homepage`
--

DROP TABLE IF EXISTS `seccoes_homepage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seccoes_homepage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `conteudo` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seccoes_homepage`
--

LOCK TABLES `seccoes_homepage` WRITE;
/*!40000 ALTER TABLE `seccoes_homepage` DISABLE KEYS */;
/*!40000 ALTER TABLE `seccoes_homepage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `separadores_fundo`
--

DROP TABLE IF EXISTS `separadores_fundo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `separadores_fundo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(60) NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `separadores_fundo`
--

LOCK TABLES `separadores_fundo` WRITE;
/*!40000 ALTER TABLE `separadores_fundo` DISABLE KEYS */;
INSERT INTO `separadores_fundo` VALUES
(153,'freguesia','sep-igreja.jpg','2026-09-30 11:12:56'),
(154,'historia','sep-cruzeiro.jpg','2026-09-30 11:12:56'),
(155,'heraldica','sep-cruzeiro.jpg','2026-09-30 11:12:56'),
(156,'pontos','sep-museu.jpg','2026-09-30 11:12:56'),
(157,'galeria','sep-museu.jpg','2026-09-30 11:12:56'),
(158,'noticias','sep-igreja.jpg','2026-09-30 11:12:56'),
(159,'eventos','sep-igreja.jpg','2026-09-30 11:12:56'),
(160,'contactos','sep-igreja.jpg','2026-09-30 11:12:56'),
(161,'executivo','sep-cruzeiro.jpg','2026-09-30 11:12:56'),
(162,'associacoes','sep-museu.jpg','2026-09-30 11:12:56');
/*!40000 ALTER TABLE `separadores_fundo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slides_homepage`
--

DROP TABLE IF EXISTS `slides_homepage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `slides_homepage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) DEFAULT NULL,
  `subtitulo` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `link_destino` varchar(255) DEFAULT NULL,
  `ativo` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slides_homepage`
--

LOCK TABLES `slides_homepage` WRITE;
/*!40000 ALTER TABLE `slides_homepage` DISABLE KEYS */;
INSERT INTO `slides_homepage` VALUES
(41,'Atalaia e Alto Estanqueiro-Jardia','Um santuário de peregrinação desde o século XVI, no concelho do Montijo.','santuario_atalaia.jpg','freguesia.php',1),
(42,'Cruzeiro Mor','Imóvel de Interesse Público, mandado construir em 1551.','cruzeiro_mor.jpg','pontos.php',1),
(43,'Museu Agrícola da Atalaia','O lagar, a adega e as tradições agrícolas do concelho.','museu_agricola_atalaia.jpg','mapa.php',1);
/*!40000 ALTER TABLE `slides_homepage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tema_config`
--

DROP TABLE IF EXISTS `tema_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tema_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(60) NOT NULL,
  `valor` varchar(120) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tema_config`
--

LOCK TABLES `tema_config` WRITE;
/*!40000 ALTER TABLE `tema_config` DISABLE KEYS */;
INSERT INTO `tema_config` VALUES
(1,'fundo','#F7F8F5','Cor de fundo do site (body)','2026-09-30 08:59:09'),
(2,'topbar_bg','var(--cor-secundaria)','Fundo da barra de topo','2026-07-13 10:32:32'),
(3,'topbar_texto','#242A30','Texto da barra de topo','2026-07-13 10:32:32'),
(4,'topbar_borda','var(--cor-principal)','Bordo inferior da barra de topo','2026-09-30 09:37:25'),
(5,'hero_1','#F7F8F5','Gradiente do herói — cor inicial','2026-09-30 08:59:09'),
(6,'hero_2','#E7E5F2','Gradiente do herói — cor final','2026-09-30 10:51:09'),
(7,'hero_texto','#242A30','Texto dos heróis (sem imagem de fundo)','2026-07-13 10:32:32'),
(8,'acento','#F9B233','Cor de acento (bordos, kicker, badges)','2026-09-30 10:51:09'),
(9,'acento_escuro','#8A5A00','Acento escuro (texto do kicker, títulos do rodapé)','2026-09-30 10:51:09'),
(10,'footer_bg','#F2F4F1','Fundo do rodapé','2026-09-30 08:59:09'),
(11,'footer_texto','#3a3f47','Texto do rodapé','2026-07-13 10:32:32'),
(12,'kicker_img_texto','#FCE9B8','Texto do kicker quando o herói tem imagem de fundo','2026-07-13 10:32:32'),
(13,'tema_camada','1','1=aplica a camada de tema; 0=mantém o tema original das páginas','2026-07-13 10:39:24'),
(26,'logo_iniciais','AAEJ','Iniciais da freguesia (fallback do logótipo no rodapé)','2026-09-30 10:51:09'),
(27,'favicon','logo-aaej.png','Ficheiro do favicon em assets/img/ (vazio = usa o logo)','2026-09-30 10:51:09'),
(30,'admin_escuro','#11151B','Tom escuro do backoffice (títulos, sidebar)','2026-07-13 14:55:50');
/*!40000 ALTER TABLE `tema_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'atalaia'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
