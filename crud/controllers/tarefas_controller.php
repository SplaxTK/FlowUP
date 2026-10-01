<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['criar'])) {
        $resultado = criarTarefa($pdo, $_POST['descricao'], $_POST['qtdpontos']);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
        $acao = '';
    }
    if (isset($_POST['atualizar'])) {
        $resultado = atualizarTarefa($pdo, $_POST['id'], $_POST['descricao'], $_POST['qtdpontos']);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
        $acao = '';
    }
    if (isset($_POST['deletar'])) {
        $resultado = deletarTarefa($pdo, $_POST['id']);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
    }
}

if ($acao == 'editar' && $id) {
    $registro = obterTarefaPorId($pdo, $id);
}
$todos = obterTodasTarefas($pdo);