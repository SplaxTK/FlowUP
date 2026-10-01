<?php
require_once 'config/config.php';

// ===== CADASTRO (Usuários) =====

function criarCadastro($pdo, $email, $usuario, $nomeCompleto, $senha) {
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
    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $resultado = $stmt->fetch();
    return $resultado ?: null;
}

function atualizarCadastro($pdo, $email, $usuario, $nomeCompleto, $senha = null) {
    try {
        // Se a senha for informada, atualiza o hash dela; caso contrário, atualiza apenas o nome
        if (!empty($senha)) {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET usuario = :usuario, nome_completo = :nome_completo, senha = :senha WHERE email = :email";
            $params = [':usuario' => $usuario, ':nome_completo' => $nomeCompleto, ':senha' => $senhaHash, ':email' => $email];
        } else {
            $sql = "UPDATE usuarios SET usuario = :usuario, nome_completo = :nome_completo WHERE email = :email";
            $params = [':usuario' => $usuario, ':nome_completo' => $nomeCompleto, ':email' => $email];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ['sucesso' => true, 'mensagem' => 'Cadastro atualizado com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar o cadastro.'];
    }
}

function deletarCadastro($pdo, $email) {
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
    try {
        $sql = "INSERT INTO Tarefas (descricao, QtdPon_Ganho) VALUES (:descricao, :qtdPontos)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descricao' => $descricao,
            ':qtdPontos' => (int) $qtdPontos
        ]);

        return ['sucesso' => true, 'mensagem' => 'Tarefa criada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao criar a tarefa.'];
    }
}

function obterTodasTarefas($pdo) {
    $sql = "SELECT * FROM Tarefas ORDER BY data_criacao DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function obterTarefaPorId($pdo, $id) {
    $sql = "SELECT * FROM Tarefas WHERE id_Tarefas = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (int) $id]);
    $resultado = $stmt->fetch();
    return $resultado ?: null;
}

function atualizarTarefa($pdo, $id, $descricao, $qtdPontos) {
    try {
        $sql = "UPDATE Tarefas SET descricao = :descricao, QtdPon_Ganho = :qtdPontos WHERE id_Tarefas = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descricao' => $descricao,
            ':qtdPontos' => (int) $qtdPontos,
            ':id'        => (int) $id
        ]);

        return ['sucesso' => true, 'mensagem' => 'Tarefa atualizada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar a tarefa.'];
    }
}

function deletarTarefa($pdo, $id) {
    try {
        $sql = "DELETE FROM Tarefas WHERE id_Tarefas = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => (int) $id]);

        return ['sucesso' => true, 'mensagem' => 'Tarefa deletada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar a tarefa.'];
    }
}

// ===== RECOMPENSA =====

function criarRecompensa($pdo, $descricao, $qtdPontos) {
    try {
        $sql = "INSERT INTO recompensas (descricao, qtd_pon_neces) VALUES (:descricao, :qtdPontos)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descricao' => $descricao,
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
    $sql = "SELECT * FROM recompensas WHERE id_recompensa = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (int) $id]);
    $resultado = $stmt->fetch();
    return $resultado ?: null;
}

function atualizarRecompensa($pdo, $id, $descricao, $qtdPontos) {
    try {
            $sql = "UPDATE recompensas SET descricao = :descricao, qtd_pon_neces = :qtdPontos WHERE id_recompensa = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':descricao' => $descricao,
            ':qtdPontos' => (int) $qtdPontos,
            ':id'        => (int) $id
        ]);

        return ['sucesso' => true, 'mensagem' => 'Recompensa atualizada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar a recompensa.'];
    }
}

function deletarRecompensa($pdo, $id) {
    try {
        $sql = "DELETE FROM recompensas WHERE id_recompensa = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => (int) $id]);

        return ['sucesso' => true, 'mensagem' => 'Recompensa deletada com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar a recompensa.'];
    }
}