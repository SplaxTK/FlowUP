<?php

define('JWT_SECRET', getenv('JWT_SECRET_KEY') ?: '');

function flowup_is_https_request(): bool
{
    $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
}

function flowup_auth_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => flowup_is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax'
    ];
}

function flowup_clear_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];
    $cookie = session_get_cookie_params();

    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => flowup_is_https_request(),
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax'
        ]);
    }

    session_destroy();
}

function gerarTokenAutenticacao($dadosUsuario) {
    if (JWT_SECRET === '') {
        throw new RuntimeException('A variável de ambiente JWT_SECRET_KEY não está configurada.');
    }

    $payload = json_encode([
        'user_id' => $dadosUsuario['id'],
        'email'   => $dadosUsuario['email'],
        'exp'     => time() + (3600 * 24 * 7) 
    ]);
    
    $payloadBase64 = base64_encode($payload);
    
    $assinatura = hash_hmac('sha256', $payloadBase64, JWT_SECRET);
    
    return $payloadBase64 . '.' . $assinatura;
}

function validarTokenAutenticacao($token) {
    if (!$token || JWT_SECRET === '') return false;
    
    $partes = explode('.', $token);
    if (count($partes) !== 2) return false;
    
    list($payloadBase64, $assinaturaFornecida) = $partes;
    
    $assinaturaCorreta = hash_hmac('sha256', $payloadBase64, JWT_SECRET);
    
    if (!hash_equals($assinaturaCorreta, $assinaturaFornecida)) {
        return false;
    }
    
    $payloadJson = base64_decode($payloadBase64, true);
    if ($payloadJson === false) return false;

    $payload = json_decode($payloadJson, true);
    if (
        !is_array($payload)
        || empty($payload['user_id'])
        || empty($payload['email'])
        || !isset($payload['exp'])
        || !is_numeric($payload['exp'])
        || time() > (int) $payload['exp']
    ) return false;
    
    return $payload; 
}
