<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/flowup.php';
require_once __DIR__ . '/../config/config.php';

$pontosUsuario = flowup_pontos($pdo, $_SESSION['user_email'] ?? null);

$siteUrl = getenv('APP_URL') ?: '/Hatsune/pi/FlowUp/site';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlowUp</title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($siteUrl); ?>/assets/img/flowUp.png">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($siteUrl); ?>/assets/css/style.css">
    <script src="<?php echo htmlspecialchars($siteUrl); ?>/assets/js/script.js" defer></script>
</head>
