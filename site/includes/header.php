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
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');

$projectPath = '';

if ($documentRoot !== '' && $scriptFile !== '') {
    $projectPath = str_replace($documentRoot, '', dirname($scriptFile));
} elseif ($scriptName !== '') {
    $projectPath = dirname($scriptName);
}

$projectPath = str_replace('\\', '/', $projectPath);
$projectPath = rtrim($projectPath, '/');

if ($projectPath === '/' || $projectPath === '\\') {
    $projectPath = '';
}

$siteUrl = getenv('APP_URL');
if (!$siteUrl) {
    $siteUrl = $projectPath !== '' ? $scheme . '://' . $host . $projectPath : '';
}
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
