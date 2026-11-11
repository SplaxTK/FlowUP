<?php
$origem = $_GET['origem'] ?? 'sistema';
if (!in_array($origem, ['sistema', 'usuario'], true)) {
    $origem = 'sistema';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $origem = $_POST['origem'] ?? $origem;
    if (!in_array($origem, ['sistema', 'usuario'], true)) {
        $origem = 'sistema';
    }

    if (isset($_POST['criar'])) {
        $resultado = criarTarefa($pdo, $_POST['descricao'] ?? null, $_POST['qtdpontos'] ?? null);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
        $acao = '';
    }
    if (isset($_POST['atualizar'])) {
        $resultado = atualizarTarefa(
            $pdo,
            $_POST['id'] ?? null,
            $_POST['descricao'] ?? null,
            $_POST['qtdpontos'] ?? null,
            $origem
        );
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
        $acao = '';
    }
    if (isset($_POST['deletar'])) {
        $resultado = deletarTarefa($pdo, $_POST['id'] ?? null, $origem);
        $mensagem = $resultado['mensagem'];
        $tipo_mensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
    }
}

if ($acao == 'editar' && $id) {
    $registro = obterTarefaPorId($pdo, $id, $origem);
}
$todos = obterTodasTarefas($pdo);