<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/../helpers/senha_helper.php';

// ===== CADASTRO (Usuários) =====

function criarCadastro($pdo, $email, $usuario, $nomeCompleto, $senha, $isAdmin = false) {
    try {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (email, usuario, nome_completo, senha, is_admin) VALUES (:email, :usuario, :nome_completo, :senha, :is_admin)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':usuario' => $usuario,
            ':nome_completo' => $nomeCompleto,
            ':senha' => $senhaHash,
            ':is_admin' => $isAdmin ? 1 : 0
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
    $sql = "SELECT email, usuario, nome_completo, is_admin, criado_em FROM usuarios ORDER BY criado_em DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function buscarCadastros($pdo, $busca) {
    try {
        $sql = "SELECT email, usuario, nome_completo, is_admin, criado_em FROM usuarios WHERE email LIKE :busca OR usuario LIKE :busca OR nome_completo LIKE :busca ORDER BY criado_em DESC";
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

function atualizarCadastro($pdo, $email, $usuario, $nomeCompleto, $novaSenha = '', $confirmacaoSenha = '', $isSuperAdmin = false) {
    try {
        $alterarSenha = $novaSenha !== '' || $confirmacaoSenha !== '';
        if ($alterarSenha && !$isSuperAdmin) {
            return ['sucesso' => false, 'mensagem' => 'Somente a conta principal pode alterar senhas pelo painel.'];
        }

        if ($alterarSenha) {
            $erroSenha = flowup_validar_nova_senha($novaSenha, $confirmacaoSenha);
            if ($erroSenha !== null) {
                return ['sucesso' => false, 'mensagem' => $erroSenha];
            }
        }

        $sql = "UPDATE usuarios SET usuario = :usuario, nome_completo = :nome_completo";
        $parametros = [
            ':usuario' => $usuario,
            ':nome_completo' => $nomeCompleto,
            ':email' => $email
        ];

        if ($alterarSenha) {
            $sql .= ", senha = :senha";
            $parametros[':senha'] = password_hash($novaSenha, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return ['sucesso' => true, 'mensagem' => 'Cadastro atualizado com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar o cadastro.'];
    }
}

function atualizarNivelCadastro($pdo, $email, $isAdmin) {
    try {
        if (flowup_user_is_super_admin(['email' => $email])) {
            return ['sucesso' => false, 'mensagem' => 'O nível da conta principal não pode ser alterado.'];
        }

        $sql = "UPDATE usuarios SET is_admin = :is_admin WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':is_admin' => $isAdmin ? 1 : 0,
            ':email' => $email
        ]);

        if ($stmt->rowCount() === 0) {
            $verificar = $pdo->prepare("SELECT 1 FROM usuarios WHERE email = :email");
            $verificar->execute([':email' => $email]);
            if (!$verificar->fetchColumn()) {
                return ['sucesso' => false, 'mensagem' => 'Cadastro não encontrado.'];
            }
        }

        return ['sucesso' => true, 'mensagem' => 'Nível de acesso atualizado com sucesso!'];
    } catch (PDOException $e) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao atualizar o nível de acesso.'];
    }
}

function deletarCadastro($pdo, $email, $isSuperAdmin = false) {
    try {
        if (flowup_user_is_super_admin(['email' => $email])) {
            return ['sucesso' => false, 'mensagem' => 'A conta principal não pode ser excluída pelo painel.'];
        }

        if (!$isSuperAdmin) {
            $verificar = $pdo->prepare("SELECT is_admin FROM usuarios WHERE email = :email");
            $verificar->execute([':email' => $email]);
            if ((int) $verificar->fetchColumn() === 1) {
                return ['sucesso' => false, 'mensagem' => 'Somente a conta principal pode excluir outros administradores.'];
            }
        }

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
        $sql = "INSERT INTO tarefas (descricao, qtd_pon_ganho) VALUES (:descricao, :qtdPontos)";
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
    $sql = "SELECT * FROM tarefas ORDER BY data_criacao DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function obterTodasTarefasDosUsuarios($pdo) {
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = 'novas_tarefas'
           AND column_name = 'user_id'"
    );
    $usaUserId = (bool) $stmt->fetchColumn();
    $joinUsuario = $usaUserId ? 'u.id = t.user_id' : 'u.email = t.email';

    $sql = "SELECT t.*, u.nome_completo, u.email
            FROM novas_tarefas AS t
            LEFT JOIN usuarios AS u ON $joinUsuario
            ORDER BY t.data_criacao DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function obterTarefaPorId($pdo, $id) {
    $sql = "SELECT * FROM tarefas WHERE id_tarefas = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (int) $id]);
    $resultado = $stmt->fetch();
    return $resultado ?: null;
}

function atualizarTarefa($pdo, $id, $descricao, $qtdPontos) {
    try {
        $sql = "UPDATE tarefas SET descricao = :descricao, qtd_pon_ganho = :qtdPontos WHERE id_tarefas = :id";
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
        $sql = "DELETE FROM tarefas WHERE id_tarefas = :id";
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