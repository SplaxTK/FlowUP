<?php

define('JWT_SECRET', getenv('JWT_SECRET_KEY'));

function gerarTokenAutenticacao($dadosUsuario) {
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
    if (!$token) return false;
    
    $partes = explode('.', $token);
    if (count($partes) !== 2) return false;
    
    list($payloadBase64, $assinaturaFornecida) = $partes;
    
    $assinaturaCorreta = hash_hmac('sha256', $payloadBase64, JWT_SECRET);
    
    if (!hash_equals($assinaturaCorreta, $assinaturaFornecida)) {
        return false;
    }
    
    $payload = json_decode(base64_decode($payloadBase64), true);
    
    // Verifica se o token já expirou
    if (time() > $payload['exp']) {
        return false; 
    }
    
    return $payload; 
}
