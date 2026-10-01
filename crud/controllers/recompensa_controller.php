<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['criar'])) {
        $resultado = criarRecompensa($pdo, $_POST['descricao'], $_POST['qtdpontos']);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
        $acao = '';
    }
    if (isset($_POST['atualizar'])) {
        $resultado = atualizarRecompensa($pdo, $_POST['id'], $_POST['descricao'], $_POST['qtdpontos']);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
        $acao = '';
    }
    if (isset($_POST['deletar'])) {
        $resultado = deletarRecompensa($pdo, $_POST['id']);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
    }
}

if ($acao == 'editar' && $id) {
    $registro = obterRecompensaPorId($pdo, $id);
}
$todos = obterTodasRecompensas($pdo);