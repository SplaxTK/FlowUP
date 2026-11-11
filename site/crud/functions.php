<?php
require_once __DIR__ . '/config/config.php';

function crudComprimento($valor) {
    return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
}

function crudValidarCadastro($email, $usuario, $nomeCompleto, $senha = null) {
    if (!is_string($email) || !is_string($usuario) || !is_string($nomeCompleto)) {
        return ['sucesso' => false, 'mensagem' => 'Preencha todos os campos corretamente.'];
    }

    $email = strtolower(trim($email));
    $usuario = trim($usuario);
    $nomeCompleto = trim($nomeCompleto);
    if ($email === '' || $usuario === '' || $nomeCompleto === '') {
        return ['sucesso' => false, 'mensagem' => 'Preencha todos os campos corretamente.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'mensagem' => 'Informe um endereço de e-mail válido.', 'campo' => 'email'];
    }
    if (crudComprimento($email) > 150 || crudComprimento($usuario) > 50 || crudComprimento($nomeCompleto) > 150) {
        return ['sucesso' => false, 'mensagem' => 'Nome, usuário ou e-mail excede o tamanho permitido.'];
    }
    if ($senha !== null && (!is_string($senha) || strlen($senha) < 6 || strlen($senha) > 72)) {
        return ['sucesso' => false, 'mensagem' => 'A senha deve ter entre 6 e 72 bytes.', 'campo' => 'senha'];
    }

    return null;
}

function crudValidarItem($descricao, $qtdPontos, $limitePontos) {
    if (!is_string($descricao) || trim($descricao) === '') {
        return ['sucesso' => false, 'mensagem' => 'Informe uma descrição.'];
    }
    if (crudComprimento(trim($descricao)) > 180) {
        return ['sucesso' => false, 'mensagem' => 'A descrição deve ter no máximo 180 caracteres.'];
    }

    $pontos = filter_var($qtdPontos, FILTER_VALIDATE_INT);
    if ($pontos === false || $pontos < 1 || $pontos > $limitePontos) {
        return ['sucesso' => false, 'mensagem' => 'Informe uma quantidade de pontos válida.'];
    }

    return null;
}

// ===== CADASTRO (Usuários) =====

function criarCadastro($pdo, $email, $usuario, $nomeCompleto, $senha) {
    $erro = crudValidarCadastro($email, $usuario, $nomeCompleto, $senha);
    if ($erro !== null) {
        return $erro;
    }

    $email = strtolower(trim($email));
    $usuario = trim($usuario);
    $nomeCompleto = trim($nomeCompleto);

    try {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (email, usuario, nome_completo, senha) VALUES (:email, :usuario, :nome_completo, :senha)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':usuario' => $usuario,
            ':nome_completo' => $nomeCompleto,
            ':senha' => $senhaHash
        ]);

        return ['sucesso' => true, 'mensagem' => 'Cadastro criado com sucesso!'];
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            return ['sucesso' => false, 'mensagem' => 'Email ou usuário já cadastrado.', 'campo' => 'email'];
        }
        return ['sucesso' => false, 'mensagem' => 'Erro ao processar a solicitação.'];
    }
}

function obterTodosCadastros($pdo) {
    $sql = "SELECT email, usuario, nome_completo, criado_em FROM usuarios ORDER BY criado_em DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function buscarCadastros($pdo, $busca) {
    try {
        $sql = "SELECT email, usuario, nome_completo, criado_em FROM usuarios WHERE email LIKE :busca OR usuario LIKE :busca OR nome_completo LIKE :busca ORDER BY criado_em DESC";
        $stmt = $pdo->prepare($sql);
        
        $termo = '%' . $busca . '%';
        $stmt->bindValue(':busca', $termo, PDO::PARAM_STR);
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return array();
    }
}

function obterCadastroPorEmail($pdo, $email) {
    if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $resultado = $stmt->fetch();
    return $resultado ?: null;
}

function atualizarCadastro($pdo, $email, $usuario, $nomeCompleto) {
    $erro = crudValidarCadastro($email, $usuario, $nomeCompleto);
    if ($erro !== null) {
        return $erro;
    }

    $email = strtolower(trim($email));
    $usuario = trim($usuario);
    $nomeCompleto = trim($nomeCompleto);

    try {
        $sql = "UPDATE usuarios SET usuario = :usuario, nome_completo = :nome_completo WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':nome_completo' => $nomeCompleto,
            ':email' => $email
        ]);

        return ['sucesso' => true, 'mensagem' => 'Cadastro atualizado com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar o cadastro.'];
    }
}

function deletarCadastro($pdo, $email) {
    if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'mensagem' => 'E-mail inválido.'];
    }

    try {
        $sql = "DELETE FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        return ['sucesso' => true, 'mensagem' => 'Cadastro deletado com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar o cadastro.'];
    }
}

// ===== TAREFAS =====

function criarTarefa($pdo, $descricao, $qtdPontos) {
    $erro = crudValidarItem($descricao, $qtdPontos, 10000);
    if ($erro !== null) {
        return $erro;
    }

    try {
        $sql = "INSERT INTO tarefas (descricao, qtd_pon_ganho) VALUES (:descricao, :qtdPontos)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descricao' => trim($descricao),
            ':qtdPontos' => (int) $qtdPontos
        ]);

        return ['sucesso' => true, 'mensagem' => 'Tarefa criada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao criar a tarefa.'];
    }
}

function crudTableHasColumn($pdo, $table, $column) {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = :table
           AND column_name = :column'
    );
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (bool) $stmt->fetchColumn();
}

function obterTodasTarefas($pdo) {
    if (crudTableHasColumn($pdo, 'novas_tarefas', 'user_id')) {
        $usuarioJoin = 'LEFT JOIN usuarios AS u ON u.id = nt.user_id';
    } else {
        $usuarioJoin = 'LEFT JOIN usuarios AS u ON u.email = nt.email';
    }

    $sql = "SELECT
                id_tarefas AS id_Tarefas,
                descricao,
                qtd_pon_ganho AS QtdPon_Ganho,
                status,
                data_criacao,
                'sistema' AS origem,
                NULL AS criado_por
            FROM tarefas
            UNION ALL
            SELECT
                nt.id_tarefas AS id_Tarefas,
                nt.descricao,
                nt.qtd_pon_ganho AS QtdPon_Ganho,
                nt.status,
                nt.data_criacao,
                'usuario' AS origem,
                COALESCE(u.usuario, u.email, 'Usuário') AS criado_por
            FROM novas_tarefas AS nt
            $usuarioJoin
            ORDER BY data_criacao DESC, id_Tarefas DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function obterTarefaPorId($pdo, $id, $origem = 'sistema') {
    $idValidado = filter_var($id, FILTER_VALIDATE_INT);
    if ($idValidado === false || $idValidado < 1) {
        return null;
    }

    if ($origem === 'usuario') {
        $sql = "SELECT id_tarefas AS id_Tarefas, descricao, qtd_pon_ganho AS QtdPon_Ganho,
                       status, data_criacao, 'usuario' AS origem
                FROM novas_tarefas WHERE id_tarefas = :id";
    } else {
        $sql = "SELECT id_tarefas AS id_Tarefas, descricao, qtd_pon_ganho AS QtdPon_Ganho,
                       status, data_criacao, 'sistema' AS origem
                FROM tarefas WHERE id_tarefas = :id";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idValidado]);
    $resultado = $stmt->fetch();
    return $resultado ?: null;
}

function atualizarTarefa($pdo, $id, $descricao, $qtdPontos, $origem = 'sistema') {
    $erro = crudValidarItem($descricao, $qtdPontos, 10000);
    if ($erro !== null) {
        return $erro;
    }
    $idValidado = filter_var($id, FILTER_VALIDATE_INT);
    if ($idValidado === false || $idValidado < 1) {
        return ['sucesso' => false, 'mensagem' => 'Tarefa inválida.'];
    }

    try {
        $tabela = $origem === 'usuario' ? 'novas_tarefas' : 'tarefas';
        $sql = "UPDATE $tabela SET descricao = :descricao, qtd_pon_ganho = :qtdPontos WHERE id_tarefas = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descricao' => trim($descricao),
            ':qtdPontos' => (int) $qtdPontos,
            ':id'        => $idValidado
        ]);

        return ['sucesso' => true, 'mensagem' => 'Tarefa atualizada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar a tarefa.'];
    }
}

function deletarTarefa($pdo, $id, $origem = 'sistema') {
    $idValidado = filter_var($id, FILTER_VALIDATE_INT);
    if ($idValidado === false || $idValidado < 1) {
        return ['sucesso' => false, 'mensagem' => 'Tarefa inválida.'];
    }

    try {
        $tabela = $origem === 'usuario' ? 'novas_tarefas' : 'tarefas';
        $sql = "DELETE FROM $tabela WHERE id_tarefas = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $idValidado]);

        return ['sucesso' => true, 'mensagem' => 'Tarefa deletada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar a tarefa.'];
    }
}

// ===== RECOMPENSA =====

function criarRecompensa($pdo, $descricao, $qtdPontos) {
    $erro = crudValidarItem($descricao, $qtdPontos, 100000);
    if ($erro !== null) {
        return $erro;
    }

    try {
        $sql = "INSERT INTO recompensas (descricao, qtd_pon_neces) VALUES (:descricao, :qtdPontos)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descricao' => trim($descricao),
            ':qtdPontos' => (int) $qtdPontos
        ]);

        return ['sucesso' => true, 'mensagem' => 'Recompensa criada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao criar a recompensa.'];
    }
}

function obterTodasRecompensas($pdo) {
    $sql = "SELECT * FROM recompensas ORDER BY data_criacao DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function obterRecompensaPorId($pdo, $id) {
    $idValidado = filter_var($id, FILTER_VALIDATE_INT);
    if ($idValidado === false || $idValidado < 1) {
        return null;
    }

    $sql = "SELECT * FROM recompensas WHERE id_recompensa = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idValidado]);
    $resultado = $stmt->fetch();
    return $resultado ?: null;
}

function atualizarRecompensa($pdo, $id, $descricao, $qtdPontos) {
    $erro = crudValidarItem($descricao, $qtdPontos, 100000);
    if ($erro !== null) {
        return $erro;
    }
    $idValidado = filter_var($id, FILTER_VALIDATE_INT);
    if ($idValidado === false || $idValidado < 1) {
        return ['sucesso' => false, 'mensagem' => 'Recompensa inválida.'];
    }

    try {
            $sql = "UPDATE recompensas SET descricao = :descricao, qtd_pon_neces = :qtdPontos WHERE id_recompensa = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descricao' => trim($descricao),
            ':qtdPontos' => (int) $qtdPontos,
            ':id'        => $idValidado
        ]);

        return ['sucesso' => true, 'mensagem' => 'Recompensa atualizada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar a recompensa.'];
    }
}

function deletarRecompensa($pdo, $id) {
    $idValidado = filter_var($id, FILTER_VALIDATE_INT);
    if ($idValidado === false || $idValidado < 1) {
        return ['sucesso' => false, 'mensagem' => 'Recompensa inválida.'];
    }

    try {
        $sql = "DELETE FROM recompensas WHERE id_recompensa = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $idValidado]);

        return ['sucesso' => true, 'mensagem' => 'Recompensa deletada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar a recompensa.'];
    }
}