# Deploy — Atalaia e Alto Estanqueiro-Jardia (site novo no FastPanel)

Pacote para a **primeira instalação** do site no servidor (VPS Contabo `185.205.244.198`, FastPanel).
Não é uma atualização: o ZIP leva o site inteiro e o SQL cria a base de dados completa.

| Ficheiro | O que é |
|---|---|
| `atalaia_deploy.zip` | O site completo: código, `vendor/`, `dompdf/`, imagens, documentos e uploads. **Sem** credenciais (`db_config.php`, `mail_config.php`, `ia_config.php`) e sem ficheiros de desenvolvimento (`.md`, `debug-config.php`). |
| `atalaia_migracoes.sql` | A base de dados completa: esquema + migrações 001–032 + conteúdo da freguesia. Para importar numa **BD vazia**. |
| `atalaia.sql` | O mesmo dump, sem o cabeçalho de aviso. É o que se importa no Laragon. |
| `atalaia_conteudo.sql` | Só para referência: o script que gerou o conteúdo (fontes documentadas em comentário). **Não importar no servidor.** |

Testado antes de entregar: o ZIP foi extraído numa pasta limpa, o SQL importado numa BD vazia e o
site servido a partir daí. Todas as páginas públicas, o login do backoffice, o CSS (chavetas
equilibradas), as imagens, os uploads e os PDFs responderam 200, sem erros PHP.

## Passos

### 1. Criar o site no FastPanel

- Novo site, por exemplo `atalaia.185.205.244.198.nip.io` (convenção `nip.io` dos outros sites sem
  domínio próprio), PHP 8.3.
- Criar a base de dados **`atalaia`** (utf8mb4) e um utilizador próprio para ela.
- Criar, como nos outros sites, o utilizador `JF<Nome>` da Junta.

### 2. Base de dados

1. phpMyAdmin → **seleciona a BD `atalaia`** e confirma que é esse o nome que aparece no topo.
2. Importar → `atalaia_migracoes.sql` → Executar.
3. Deve ficar com 82 tabelas.

⚠️ O SQL começa cada tabela com `DROP TABLE IF EXISTS`. **Nunca o importes numa BD que já tenha
dados de outro site.** É a armadilha do jf-granho.pt: um SQL na BD errada corre sem erro nenhum e
parte o site.

### 3. Código

1. File Manager → raiz do site → carregar `atalaia_deploy.zip` → **Extract**.
2. Criar à mão os dois ficheiros de credenciais (nunca vão no ZIP):

`includes/db_config.php`
```php
<?php
return ['host' => 'localhost', 'db' => 'atalaia', 'user' => '<utilizador_da_bd>', 'pass' => '<password>'];
```

`includes/mail_config.php`
```php
<?php
return ['pass' => '<app password do email>'];
```
Sem o `mail_config.php`, as páginas de pedidos e requerimentos dão **HTTP 500**. Com
`'pass' => ''` o site funciona, só não envia emails.

O chatbot de IA só aparece se existir `includes/ia_config.php` (ver `includes/ia_config.example.php`).
Sem ele, o site funciona normalmente, sem chatbot.

### 4. Permissões

Pastas a **755**, ficheiros a **644**, e o dono deve ser o utilizador do site (não `root`):
```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
```
O ZIP já traz as pastas de upload vazias (`uploads/denuncias`, `uploads/assembleia`,
`assets/img/galeria`, `assets/img/membros`…), para que os uploads funcionem logo.

### 5. Verificar

- Abrir o site e percorrer: início, A Freguesia, História, Heráldica, Executivo, Assembleia,
  Pontos de interesse, Mapa, Comércio, Associações, Documentos, Contactos.
- Backoffice em `/admin/`, com o utilizador `admin` (o mesmo dos outros sites; veio do modelo).
- Confirmar que o logótipo aparece no cabeçalho, na Heráldica e no favicon.

## Alternativa: git

Desde 25/09/2026 as pastas dos sites no servidor são clones git (ver `CLAUDE_GERAL.md`). Em vez do
ZIP, pode fazer-se `git clone git@github.com:Ges-Gov/atalaia.git` na pasta do site. Nesse caso, criar
também à mão as credenciais (passo 3) e importar o SQL (passo 2) na mesma.
