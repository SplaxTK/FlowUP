<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/senha_helper.php';
require_once __DIR__ . '/flowup.php';

function flowup_responder_alteracao_senha(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flowup_responder_alteracao_senha(405, ['ok' => false, 'mensagem' => 'Método não permitido.']);
}

$tokenCookie = (string) ($_COOKIE['auth_token'] ?? '');
$csrfHeader = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$usuarioLogado = validarTokenAutenticacao($tokenCookie);
$csrfEsperado = flowup_csrf_senha($tokenCookie);

if (!is_array($usuarioLogado)) {
    flowup_responder_alteracao_senha(401, ['ok' => false, 'mensagem' => 'Você precisa estar logado.']);
}

$usuarioId = flowup_user_id();
if (!$usuarioId) {
    flowup_responder_alteracao_senha(401, ['ok' => false, 'mensagem' => 'Você precisa estar logado.']);
}

if ($csrfEsperado === '' || !hash_equals($csrfEsperado, $csrfHeader)) {
    flowup_responder_alteracao_senha(403, ['ok' => false, 'mensagem' => 'Sessão expirada. Recarregue a página e tente novamente.']);
}

$raw = file_get_contents('php://input');
$dados = json_decode($raw ?: '', true);

if (!is_array($dados)) {
    flowup_responder_alteracao_senha(400, ['ok' => false, 'mensagem' => 'Dados inválidos.']);
}

$senhaAtual = (string) ($dados['senha_atual'] ?? '');
$novaSenha = (string) ($dados['nova_senha'] ?? '');
$confirmarSenha = (string) ($dados['confirmar_senha'] ?? '');

if ($senhaAtual === '') {
    flowup_responder_alteracao_senha(422, ['ok' => false, 'mensagem' => 'Informe a sua senha atual.']);
}

$erroSenha = flowup_validar_nova_senha($novaSenha, $confirmarSenha);
if ($erroSenha !== null) {
    flowup_responder_alteracao_senha(422, ['ok' => false, 'mensagem' => $erroSenha]);
}

if (hash_equals($senhaAtual, $novaSenha)) {
    flowup_responder_alteracao_senha(422, ['ok' => false, 'mensagem' => 'A nova senha deve ser diferente da atual.']);
}

$stmt = $pdo->prepare('SELECT senha FROM usuarios WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $usuarioId]);
$linha = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$linha) {
    flowup_responder_alteracao_senha(404, ['ok' => false, 'mensagem' => 'Usuário não encontrado.']);
}

if (!password_verify($senhaAtual, (string) $linha['senha'])) {
    usleep(300_000);
    flowup_responder_alteracao_senha(401, ['ok' => false, 'mensagem' => 'Senha atual incorreta.']);
}

$novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
$pdo->beginTransaction();
try {
    $upd = $pdo->prepare('UPDATE usuarios SET senha = :hash WHERE id = :id');
    $upd->execute([':hash' => $novoHash, ':id' => $usuarioId]);

    $tokens = $pdo->prepare(
        'UPDATE alteracao_senha_tokens
         SET usado_em = NOW()
         WHERE usuario_id = :id AND usado_em IS NULL'
    );
    $tokens->execute(['id' => $usuarioId]);
    $pdo->commit();
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

flowup_responder_alteracao_senha(200, ['ok' => true, 'mensagem' => 'Senha alterada com sucesso.']);