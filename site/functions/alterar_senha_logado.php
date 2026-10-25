<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function responder(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- método ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'mensagem' => 'Método não permitido.']);
}

// --- CSRF ---
$tokenCookie = (string) ($_COOKIE['auth_token'] ?? '');
$csrfHeader  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$csrfEsperado = flowup_csrf_senha($tokenCookie); // mesma função usada no HTML

if (!hash_equals($csrfEsperado, (string) $csrfHeader)) {
    responder(403, ['ok' => false, 'mensagem' => 'Sessão expirada. Recarregue a página e tente novamente.']);
}

// --- usuário logado (ajuste para sua função de auth) ---
$usuarioId = flowup_usuario_logado_id(); // retorne null se não logado
if (!$usuarioId) {
    responder(401, ['ok' => false, 'mensagem' => 'Você precisa estar logado.']);
}

// --- payload ---
$raw = file_get_contents('php://input');
$dados = json_decode($raw ?: '', true);

if (!is_array($dados)) {
    responder(400, ['ok' => false, 'mensagem' => 'Dados inválidos.']);
}

$senhaAtual      = (string) ($dados['senha_atual'] ?? '');
$novaSenha       = (string) ($dados['nova_senha'] ?? '');
$confirmarSenha  = (string) ($dados['confirmar_senha'] ?? '');

// --- validações básicas ---
if ($senhaAtual === '' || $novaSenha === '' || $confirmarSenha === '') {
    responder(422, ['ok' => false, 'mensagem' => 'Preencha todos os campos.']);
}
if (mb_strlen($novaSenha) < 8) {
    responder(422, ['ok' => false, 'mensagem' => 'A nova senha deve ter ao menos 8 caracteres.']);
}
if (mb_strlen($novaSenha) > 1024) {
    responder(422, ['ok' => false, 'mensagem' => 'Nova senha muito longa.']);
}
if ($novaSenha !== $confirmarSenha) {
    responder(422, ['ok' => false, 'mensagem' => 'A confirmação não confere com a nova senha.']);
}
if (hash_equals($senhaAtual, $novaSenha)) {
    responder(422, ['ok' => false, 'mensagem' => 'A nova senha deve ser diferente da atual.']);
}

// --- busca hash atual ---
$stmt = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $usuarioId]);
$linha = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$linha) {
    responder(404, ['ok' => false, 'mensagem' => 'Usuário não encontrado.']);
}

// --- confere senha atual ---
if (!password_verify($senhaAtual, (string) $linha['senha_hash'])) {
    usleep(300_000);
    responder(401, ['ok' => false, 'mensagem' => 'Senha atual incorreta.']);
}

// --- grava nova senha ---
$novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);

$upd = $pdo->prepare('UPDATE usuarios SET senha_hash = :hash, senha_alterada_em = NOW() WHERE id = :id');
$upd->execute([':hash' => $novoHash, ':id' => $usuarioId]);

// opcional: invalidar outros tokens/sessões
// $pdo->prepare('DELETE FROM sessoes WHERE usuario_id = ?')->execute([$usuarioId]);

responder(200, ['ok' => true, 'mensagem' => 'Senha alterada com sucesso.']);