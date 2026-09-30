<?php
// Configuração do assistente de IA (chatbot do site).
//
// Este ficheiro é só um EXEMPLO — nunca é lido diretamente pelo código.
// No servidor, copiar para includes/ia_config.php e preencher a chave.
// includes/ia_config.php NUNCA deve ir no zip de deploy nem ser partilhado,
// tal como o includes/db_config.php (guarda uma credencial).
//
// Chave: Google AI Studio -> https://aistudio.google.com/apikey
// (gratuita, sem cartão de crédito — nível gratuito com limite diário)

define('IA_API_KEY', '');
define('IA_MODELO', 'gemini-flash-lite-latest');

// Limite de perguntas por sessão do visitante, por hora — protege contra
// abuso/custos inesperados num assistente público.
define('IA_LIMITE_POR_HORA', 20);
