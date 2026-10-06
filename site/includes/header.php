<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/flowup.php';
require_once __DIR__ . '/../config/config.php';

$pontosUsuario = flowup_pontos($pdo, $_SESSION['user_email'] ?? null);

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$sitePath = str_replace('\\', '/', dirname($scriptName));
$sitePath = preg_replace('~/login$~', '', $sitePath);
$sitePath = rtrim($sitePath, '/');

$siteUrl = getenv('APP_URL');
if (!$siteUrl) {
    $siteUrl = $scheme . '://' . $host . $sitePath;
}
$siteUrl = rtrim($siteUrl, '/');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlowUp</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($siteUrl); ?>/assets/img/flowUp.png">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($siteUrl); ?>/assets/css/style.css">
    <script src="<?php echo htmlspecialchars($siteUrl); ?>/assets/js/script.js" defer></script>
</head>
