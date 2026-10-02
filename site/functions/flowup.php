<?php
require_once __DIR__ . '/../config/config.php';

function flowup_email(PDO $pdo): ?string
{
    if (!empty($_SESSION['user_email'])) {
        return $_SESSION['user_email'];
    }

    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT email FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $email = $stmt->fetchColumn();
        if ($email) {
            $_SESSION['user_email'] = $email;
            return $email;
        }
    }

    return null;
}

function flowup_prepare_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS conclusoes_tarefas (
            id_conclusao INT NOT NULL AUTO_INCREMENT,
            id_tarefas INT NOT NULL,
            email VARCHAR(150) NOT NULL,
            periodo VARCHAR(20) NOT NULL,
            qtd_pontos INT NOT NULL,
            data_conclusao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_conclusao),
            UNIQUE KEY tarefa_periodo_usuario (id_tarefas, email, periodo),
            CONSTRAINT fk_conclusoes_tarefa FOREIGN KEY (id_tarefas) REFERENCES novas_tarefas (id_tarefas) ON DELETE CASCADE,
            CONSTRAINT fk_conclusoes_usuario FOREIGN KEY (email) REFERENCES usuarios (email) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function flowup_periodo(string $tipo): string
{
    if ($tipo === 'diaria') {
        return date('Y-m-d');
    }
    if ($tipo === 'semanal') {
        return date('o-W');
    }
    if ($tipo === 'mensal') {
        return date('Y-m');
    }
    return 'meta';
}

function flowup_pontos(PDO $pdo, ?string $email): int
{
    if (!$email) {
        return 0;
    }

    $stmt = $pdo->prepare('SELECT qtd_pontos FROM pontos WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $pontos = $stmt->fetchColumn();
    return $pontos === false ? 0 : (int) $pontos;
}

function flowup_tarefas_hoje(PDO $pdo, ?string $email): int
{
    if (!$email) {
        return 0;
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM novas_tarefas
         WHERE email = :email
           AND tipo = \'diaria\'
           AND status = \'ativa\''
    );
    $stmt->execute(['email' => $email]);
    return (int) $stmt->fetchColumn();
}

function flowup_metas_hoje(PDO $pdo, ?string $email): int
{
    if (!$email) {
        return 0;
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM novas_tarefas
         WHERE email = :email
           AND tipo = \'meta\'
           AND status = \'ativa\''
    );
    $stmt->execute(['email' => $email]);
    return (int) $stmt->fetchColumn();
}

function flowup_pontos_id(PDO $pdo, string $email): int
{
    $stmt = $pdo->prepare('SELECT id_pontos FROM pontos WHERE email = :email FOR UPDATE');
    $stmt->execute(['email' => $email]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int) $id;
    }

    $stmt = $pdo->prepare('INSERT INTO pontos (qtd_pontos, email) VALUES (0, :email)');
    $stmt->execute(['email' => $email]);
    return (int) $pdo->lastInsertId();
}

function flowup_concluidas(PDO $pdo, string $email, array $tarefas): array
{
    $concluidas = [];
    foreach ($tarefas as $tarefa) {
        $periodo = flowup_periodo($tarefa['tipo']);
        $stmt = $pdo->prepare('SELECT id_conclusao FROM conclusoes_tarefas WHERE id_tarefas = :id AND email = :email AND periodo = :periodo');
        $stmt->execute([
            'id' => $tarefa['id_tarefas'],
            'email' => $email,
            'periodo' => $periodo,
        ]);
        if ($stmt->fetchColumn()) {
            $concluidas[(int) $tarefa['id_tarefas']] = true;
        }
    }
    return $concluidas;
}

function flowup_concluir(PDO $pdo, string $email, int $id): string
{
    $stmt = $pdo->prepare('SELECT * FROM novas_tarefas WHERE id_tarefas = :id AND email = :email AND status <> \'inativa\'');
    $stmt->execute(['id' => $id, 'email' => $email]);
    $tarefa = $stmt->fetch();
    if (!$tarefa) {
        return 'Tarefa não encontrada.';
    }

    $periodo = flowup_periodo($tarefa['tipo']);
    $pdo->beginTransaction();
    try {
        $check = $pdo->prepare('SELECT id_conclusao FROM conclusoes_tarefas WHERE id_tarefas = :id AND email = :email AND periodo = :periodo FOR UPDATE');
        $check->execute(['id' => $id, 'email' => $email, 'periodo' => $periodo]);
        if ($check->fetchColumn()) {
            $pdo->rollBack();
            return 'Essa tarefa já foi concluída neste período.';
        }

        $pontosId = flowup_pontos_id($pdo, $email);
        $atual = flowup_pontos($pdo, $email);
        $novo = $atual + (int) $tarefa['qtd_pon_ganho'];
        $update = $pdo->prepare('UPDATE pontos SET qtd_pontos = :pontos WHERE id_pontos = :id');
        $update->execute(['pontos' => $novo, 'id' => $pontosId]);

        $insert = $pdo->prepare('INSERT INTO conclusoes_tarefas (id_tarefas, email, periodo, qtd_pontos) VALUES (:tarefa, :email, :periodo, :pontos)');
        $insert->execute(['tarefa' => $id, 'email' => $email, 'periodo' => $periodo, 'pontos' => $tarefa['qtd_pon_ganho']]);

        $history = $pdo->prepare('INSERT INTO historico_pontos (id_pontos, qtd_pontos_anterior, qtd_pontos_novo) VALUES (:id, :anterior, :novo)');
        $history->execute(['id' => $pontosId, 'anterior' => $atual, 'novo' => $novo]);
        $pdo->commit();
        return 'Tarefa concluída! Você ganhou ' . (int) $tarefa['qtd_pon_ganho'] . ' pontos.';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return 'Não foi possível concluir a tarefa.';
    }
}

function flowup_resgatar(PDO $pdo, string $email, int $id): string
{
    $pdo->beginTransaction();
    try {
        $reward = $pdo->prepare('SELECT * FROM recompensas WHERE id_recompensa = :id AND status = \'disponivel\' FOR UPDATE');
        $reward->execute(['id' => $id]);
        $recompensa = $reward->fetch();
        if (!$recompensa) {
            $pdo->rollBack();
            return 'Recompensa não encontrada.';
        }

        $pointsId = flowup_pontos_id($pdo, $email);
        $points = flowup_pontos($pdo, $email);
        if ($points < (int) $recompensa['qtd_pon_neces']) {
            $pdo->rollBack();
            return 'Você ainda não tem pontos suficientes.';
        }

        $novo = $points - (int) $recompensa['qtd_pon_neces'];
        $update = $pdo->prepare('UPDATE pontos SET qtd_pontos = :pontos WHERE id_pontos = :id');
        $update->execute(['pontos' => $novo, 'id' => $pointsId]);
        $history = $pdo->prepare('INSERT INTO historico_pontos (id_pontos, qtd_pontos_anterior, qtd_pontos_novo) VALUES (:id, :anterior, :novo)');
        $history->execute(['id' => $pointsId, 'anterior' => $points, 'novo' => $novo]);

        $insert = $pdo->prepare('INSERT INTO novas_recompensas (descricao, qtd_pon_neces, email, status) VALUES (:descricao, :pontos, :email, \'resgatada\')');
        $insert->execute(['descricao' => $recompensa['descricao'], 'pontos' => $recompensa['qtd_pon_neces'], 'email' => $email]);
        $pdo->commit();
        return 'Recompensa resgatada com sucesso!';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return 'Não foi possível resgatar essa recompensa.';
    }
}
