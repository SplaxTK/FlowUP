<?php
/**
 * Funções compartilhadas da alteração de senha por confirmação de e-mail.
 *
 * Usado por:
 *  - site/configuracoes.php                      (gera o token CSRF do botão)
 *  - site/login/esqueci_senha.php                 (pede o e-mail)
 *  - site/login/redefinir_senha.php               (confirma a nova senha)
 *
 * IMPORTANTE: carregue site/helpers/auth_helper.php ANTES deste arquivo
 * (ele define a constante JWT_SECRET usada no token CSRF).
 */

const FLOWUP_SENHA_TOKEN_MINUTOS = 30;      // validade do link
const FLOWUP_SENHA_MIN_CARACTERES = 6;      // tamanho mínimo da nova senha
const FLOWUP_SENHA_MAX_BYTES = 72;          // limite do bcrypt (PASSWORD_DEFAULT)
const FLOWUP_SENHA_MAX_PEDIDOS_HORA = 3;    // e-mails por hora, por usuário
const FLOWUP_SENHA_INTERVALO_SEGUNDOS = 60; // espera entre dois pedidos
const FLOWUP_SENHA_MAX_FALHAS = 5;          // senhas atuais erradas...
const FLOWUP_SENHA_JANELA_FALHAS_MIN = 15;  // ...dentro desta janela

/**
 * Lê uma variável de ambiente.
 * Na Vercel ela vem do ambiente; localmente cai para o arquivo .env da raiz.
 */
function flowup_env(string $nome): ?string
{
    $valor = getenv($nome);
    if ($valor !== false && $valor !== '') {
        return $valor;
    }

    foreach ([$_ENV, $_SERVER] as $origem) {
        if (isset($origem[$nome]) && is_string($origem[$nome]) && $origem[$nome] !== '') {
            return $origem[$nome];
        }
    }

    static $arquivo = null;
    if ($arquivo === null) {
        $arquivo = [];
        $caminho = dirname(__DIR__, 2) . '/.env'; // site/helpers -> site -> raiz do projeto

        if (is_file($caminho) && is_readable($caminho)) {
            foreach (file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linha) {
                $linha = trim($linha);
                if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) {
                    continue;
                }
                [$chave, $v] = explode('=', $linha, 2);
                $v = trim($v);
                if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                    $v = substr($v, 1, -1);
                }
                $arquivo[trim($chave)] = $v;
            }
        }
    }

    return (isset($arquivo[$nome]) && $arquivo[$nome] !== '') ? $arquivo[$nome] : null;
}

/**
 * URL base confiável do site (variável APP_URL), nunca vinda de cabeçalhos HTTP.
 * Exige HTTPS, exceto em localhost. Retorna null se estiver ausente/inválida.
 */
function flowup_url_base(): ?string
{
    $url = flowup_env('APP_URL');
    if ($url === null) {
        return null;
    }

    $url = rtrim(trim($url), '/');
    $partes = parse_url($url);
    if (!is_array($partes) || empty($partes['scheme']) || empty($partes['host'])) {
        return null;
    }

    $local = in_array(strtolower($partes['host']), ['localhost', '127.0.0.1'], true);
    $esquema = strtolower($partes['scheme']);

    if ($esquema !== 'https' && !($esquema === 'http' && $local)) {
        return null;
    }

    return $url;
}

/**
 * Token CSRF sem sessão (a Vercel não garante arquivos de sessão persistentes).
 * É um HMAC ligado ao cookie de autenticação do usuário: sem o cookie
 * (HttpOnly) ninguém consegue calculá-lo.
 */
function flowup_csrf_senha(string $authToken): string
{
    if ($authToken === '' || !defined('JWT_SECRET') || JWT_SECRET === '') {
        return '';
    }

    return hash_hmac('sha256', 'flowup-alterar-senha|' . $authToken, JWT_SECRET);
}

/** Gera [token_em_claro, hash_sha256]. Só o hash vai para o banco. */
function flowup_gerar_token_senha(): array
{
    $token = bin2hex(random_bytes(32)); // 256 bits de entropia
    return [$token, hash('sha256', $token)];
}

function flowup_hash_token_senha(string $token): string
{
    return hash('sha256', $token);
}

function flowup_token_senha_formato_valido(string $token): bool
{
    return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
}

/** Retorna a mensagem de erro, ou null se a nova senha for válida. */
function flowup_validar_nova_senha(string $senha, string $confirmacao): ?string
{
    if ($senha === '' || $confirmacao === '') {
        return 'Preencha os dois campos.';
    }

    $tamanho = function_exists('mb_strlen') ? mb_strlen($senha, 'UTF-8') : strlen($senha);
    if ($tamanho < FLOWUP_SENHA_MIN_CARACTERES) {
        return 'A nova senha deve ter pelo menos ' . FLOWUP_SENHA_MIN_CARACTERES . ' caracteres.';
    }

    if (strlen($senha) > FLOWUP_SENHA_MAX_BYTES) {
        return 'A nova senha é muito longa (máximo de ' . FLOWUP_SENHA_MAX_BYTES . ' caracteres).';
    }

    if (!hash_equals($senha, $confirmacao)) {
        return 'As senhas não coincidem.';
    }

    return null;
}

function flowup_mascarar_email(string $email): string
{
    $partes = explode('@', $email, 2);
    if (count($partes) !== 2 || $partes[0] === '') {
        return '***';
    }

    $inicial = function_exists('mb_substr') ? mb_substr($partes[0], 0, 1, 'UTF-8') : substr($partes[0], 0, 1);
    return $inicial . '***@' . $partes[1];
}

/**
 * Busca um token ainda válido (não usado e não expirado) pelo hash.
 * Com $travar = true usa FOR UPDATE (precisa estar dentro de uma transação).
 */
function flowup_buscar_token_senha(PDO $pdo, string $tokenHash, bool $travar = false): ?array
{
    $sql = 'SELECT t.id, t.usuario_id, u.senha AS senha_hash
            FROM alteracao_senha_tokens t
            INNER JOIN usuarios u ON u.id = t.usuario_id
            WHERE t.token_hash = :hash AND t.usado_em IS NULL AND t.expira_em > NOW()
            LIMIT 1' . ($travar ? ' FOR UPDATE' : '');

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['hash' => $tokenHash]);
    $registro = $stmt->fetch();

    return $registro ?: null;
}

/**
 * Troca a senha usando o token, de forma atômica.
 * O token é "consumido" na mesma transação: duas requisições simultâneas com
 * o mesmo link nunca conseguem as duas trocar a senha.
 *
 * @return bool true se trocou; false se o token já não era válido.
 */
function flowup_trocar_senha_com_token(PDO $pdo, string $tokenHash, string $novoHashSenha): bool
{
    $pdo->beginTransaction();

    try {
        $registro = flowup_buscar_token_senha($pdo, $tokenHash, true);
        if (!$registro) {
            $pdo->rollBack();
            return false;
        }

        // 1) consome este token (só um vencedor chega a rowCount() === 1)
        $stmt = $pdo->prepare('UPDATE alteracao_senha_tokens SET usado_em = NOW() WHERE id = :id AND usado_em IS NULL');
        $stmt->execute(['id' => $registro['id']]);
        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return false;
        }

        // 2) troca a senha SOMENTE do usuário dono do token
        $stmt = $pdo->prepare('UPDATE usuarios SET senha = :senha WHERE id = :id');
        $stmt->execute(['senha' => $novoHashSenha, 'id' => $registro['usuario_id']]);
        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return false;
        }

        // 3) invalida qualquer outro pedido pendente do mesmo usuário
        $stmt = $pdo->prepare('UPDATE alteracao_senha_tokens SET usado_em = NOW() WHERE usuario_id = :id AND usado_em IS NULL');
        $stmt->execute(['id' => $registro['usuario_id']]);

        // 4) zera o contador de senhas atuais erradas
        $stmt = $pdo->prepare('DELETE FROM alteracao_senha_tentativas WHERE usuario_id = :id');
        $stmt->execute(['id' => $registro['usuario_id']]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Monta o e-mail. Retorna [html, texto]. Não contém senha nem hash do token. */
function flowup_montar_email_alteracao_senha(string $nome, string $link, int $minutos): array
{
    $nomeSeguro = htmlspecialchars($nome !== '' ? $nome : 'tudo bem', ENT_QUOTES, 'UTF-8');
    $linkSeguro = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

    $html = '<!DOCTYPE html><html lang="pt-BR"><body style="margin:0;padding:0;background:#f3f4f6;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 12px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">'
        . '<tr><td style="background:#1f3b6d;padding:24px 32px;color:#ffffff;font-size:24px;font-weight:bold;">FlowUp</td></tr>'
        . '<tr><td style="padding:32px;color:#1f2937;font-size:15px;line-height:1.6;">'
        . '<h1 style="margin:0 0 16px;font-size:20px;color:#111827;">Alteração de senha</h1>'
        . '<p style="margin:0 0 12px;">Olá, ' . $nomeSeguro . '!</p>'
        . '<p style="margin:0 0 24px;">Recebemos um pedido para alterar a senha da sua conta no FlowUp. '
        . 'Para continuar, clique no botão abaixo e escolha a nova senha.</p>'
        . '<p style="margin:0 0 24px;text-align:center;">'
        . '<a href="' . $linkSeguro . '" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;padding:14px 28px;border-radius:10px;">Alterar minha senha</a></p>'
        . '<p style="margin:0 0 12px;font-size:13px;color:#6b7280;">Este link vale por ' . (int) $minutos . ' minutos e só pode ser usado uma vez.</p>'
        . '<p style="margin:0 0 12px;font-size:13px;color:#6b7280;">Se o botão não funcionar, copie e cole este endereço no navegador:<br>'
        . '<span style="word-break:break-all;">' . $linkSeguro . '</span></p>'
        . '<p style="margin:24px 0 0;font-size:13px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:16px;">'
        . 'Não foi você? Ignore este e-mail: sua senha atual continua a mesma e nada será alterado.</p>'
        . '</td></tr></table></td></tr></table></body></html>';

    $texto = "Olá, " . ($nome !== '' ? $nome : 'tudo bem') . "!\n\n"
        . "Recebemos um pedido para alterar a senha da sua conta no FlowUp.\n"
        . "Para continuar, abra o link abaixo e escolha a nova senha:\n\n"
        . $link . "\n\n"
        . "Este link vale por " . (int) $minutos . " minutos e só pode ser usado uma vez.\n"
        . "Não foi você? Ignore este e-mail: sua senha atual continua a mesma.\n";

    return [$html, $texto];
}

/**
 * Envia um e-mail pela API do Resend (cURL direto: o projeto não tem
 * composer.json nem vendor/, então o SDK PHP não está instalado).
 * Só retorna ok = true se o Resend responder 2xx com o "id" do e-mail.
 *
 * @return array{ok: bool, status: int}
 */
function flowup_enviar_email_resend(string $para, string $assunto, string $html, string $texto): array
{
    $chave = flowup_env('RESEND_API_KEY');
    if ($chave === null) {
        error_log('[FlowUp] RESEND_API_KEY não está configurada.');
        return ['ok' => false, 'status' => 0];
    }

    if (!function_exists('curl_init')) {
        error_log('[FlowUp] A extensão cURL do PHP não está disponível.');
        return ['ok' => false, 'status' => 0];
    }

    $remetente = flowup_env('RESEND_FROM') ?: 'FlowUp <onboarding@resend.dev>';

    $corpo = json_encode([
        'from' => $remetente,
        'to' => [$para],
        'subject' => $assunto,
        'html' => $html,
        'text' => $texto,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $corpo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $chave,
            'Content-Type: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);

    $resposta = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl = curl_error($ch);

    if ($resposta === false) {
        error_log('[FlowUp] Falha de rede ao chamar o Resend: ' . $erroCurl);
        return ['ok' => false, 'status' => $status];
    }

    $dados = json_decode((string) $resposta, true);

    if ($status >= 200 && $status < 300 && is_array($dados) && !empty($dados['id'])) {
        return ['ok' => true, 'status' => $status];
    }

    // Registra só status e mensagem de erro da API (nunca a chave nem o corpo enviado).
    $mensagem = is_array($dados) && isset($dados['message']) && is_string($dados['message'])
        ? substr($dados['message'], 0, 200)
        : 'sem detalhes';
    error_log('[FlowUp] Resend recusou o envio: HTTP ' . $status . ' - ' . $mensagem);

    if ($status === 403) {
        error_log('[FlowUp] Dica: o remetente onboarding@resend.dev só envia para o e-mail dono da conta Resend. '
            . 'Verifique um domínio no Resend e defina RESEND_FROM para enviar a outros usuários.');
    }

    return ['ok' => false, 'status' => $status];
}
