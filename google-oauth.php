<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/GoogleCalendarService.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    GoogleCalendarService::prepareSchema($pdo);
    $service = new GoogleCalendarService($pdo, (int) $_SESSION['user_id']);
    if (isset($_GET['error'])) {
        throw new RuntimeException('A autorização Google foi cancelada ou não foi concedida.');
    }
    if (isset($_GET['code'])) {
        $state = (string) ($_GET['state'] ?? '');
        if (!isset($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
            throw new RuntimeException('A validação de segurança do OAuth falhou. Tente conectar novamente.');
        }
        unset($_SESSION['google_oauth_state']);
        $service->connect((string) $_GET['code']);
        header('Location: calendario.php?google=connected');
        exit;
    }
    $state = bin2hex(random_bytes(32));
    $_SESSION['google_oauth_state'] = $state;
    header('Location: ' . GoogleCalendarService::authorizationUrl($state));
    exit;
} catch (Throwable $error) {
    error_log('Google OAuth error: ' . $error->getMessage());
    $_SESSION['calendar_flash_error'] = $error->getMessage();
    header('Location: calendario.php?google=error');
    exit;
}
