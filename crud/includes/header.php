<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlowUP - CRUD</title>
    <link rel="icon" type="image/x-icon" href="./assets/flowUp.png">
    <link rel="stylesheet" href="./assets/style.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-content">
            <h1>FlowUP</h1>
            <div class="nav-links">
                <a href="index.php?secao=cadastro" class="<?php echo ($secao == 'cadastro') ? 'ativo' : ''; ?>">Cadastro</a>
                <a href="index.php?secao=tarefas" class="<?php echo ($secao == 'tarefas') ? 'ativo' : ''; ?>">Tarefas</a>
                <a href="index.php?secao=recompensa" class="<?php echo ($secao == 'recompensa') ? 'ativo' : ''; ?>">Recompensas</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo e($tipo_mensagem); ?>">
                <?php echo e($mensagem); ?>
            </div>
        <?php endif; ?>