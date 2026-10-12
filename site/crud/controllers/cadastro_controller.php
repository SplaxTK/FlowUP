<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['criar'])) {
        $resultado = criarCadastro($pdo, $_POST['email'], $_POST['usuario'], $_POST['nome_completo'], $_POST['senha']);
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
        $resultado = atualizarCadastro($pdo, $_POST['email'], $_POST['usuario'], $_POST['nome_completo'], $_POST['senha']);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
        $acao = '';
    }
    if (isset($_POST['deletar'])) {
        $resultado = deletarCadastro($pdo, $_POST['email']);
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