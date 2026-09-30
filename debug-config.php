<?php
require_once "includes/config.php";

echo "<pre>";
echo "NOME SITE: " . siteConfig('nome_site') . "\n";
echo "COR PRINCIPAL: " . siteConfig('cor_principal') . "\n";
echo "COR SECUNDARIA: " . siteConfig('cor_secundaria') . "\n";

echo "\nCONFIG COMPLETA:\n";
print_r($config);
echo "</pre>";