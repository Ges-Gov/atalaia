<?php
/* Sinalética digital — tempo em JSON (includes/tempo.php). Só GET, só dados públicos. */
require_once __DIR__ . "/../../includes/tempo.php";
signageResponder(["tempo" => signageTempo()]);
