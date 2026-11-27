<?php
$email = $email ?? null;
$busca = $busca ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['criar'])) {
        $resultado = criarCadastro(
            $pdo,
            $_POST['email'],
            $_POST['usuario'],
            $_POST['nome_completo'],
            $_POST['senha'],
            $isSuperAdmin && isset($_POST['is_admin'])
        );
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
        if (!$resultado['sucesso']) {
            if (isset($resultado['campo']) && $resultado['campo'] === 'email') {
                $erro_email = $resultado['mensagem'];
            } else {
                $mensagem = $resultado['mensagem'];
            }
            $manter_form = true;
        } else {
            $mensagem = $resultado['mensagem'];
            $acao = '';
        }
    }
    if (isset($_POST['atualizar'])) {
        $resultado = atualizarCadastro(
            $pdo,
            $_POST['email'],
            $_POST['usuario'],
            $_POST['nome_completo'],
            (string) ($_POST['nova_senha'] ?? ''),
            (string) ($_POST['confirmar_senha'] ?? ''),
            $isSuperAdmin
        );
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
        $acao = $resultado['sucesso'] ? '' : 'editar';
        if (!$resultado['sucesso']) {
            $email = $_POST['email'];
        }
    }
    if (isset($_POST['atualizar_nivel'])) {
        if ($isSuperAdmin) {
            $resultado = atualizarNivelCadastro($pdo, $_POST['email'], isset($_POST['is_admin']));
            $mensagem = $resultado['mensagem'];
            $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
            $acao = 'editar';
            $email = $_POST['email'];
        } else {
            $mensagem = 'Você não tem permissão para alterar níveis de acesso.';
            $tipo_mensagem = 'erro';
            $acao = 'editar';
            $email = $_POST['email'] ?? '';
        }
    }
    if (isset($_POST['deletar'])) {
        $resultado = deletarCadastro($pdo, $_POST['email'], $isSuperAdmin);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
    }
}

if ($acao == 'editar' && $email) {
    $registro = obterCadastroPorEmail($pdo, $email);
}

if ($busca !== '') {
    $todos = buscarCadastros($pdo, $busca);
} else {
    $todos = obterTodosCadastros($pdo);
}