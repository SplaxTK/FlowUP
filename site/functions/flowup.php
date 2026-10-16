<?php
require_once __DIR__ . '/../config/config.php';

function flowup_user_id(): ?int
{
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    return $userId !== false && $userId > 0 ? $userId : null;
}

function flowup_table_has_column(PDO $pdo, string $table, string $column): bool
{
    static $columns = [];
    $cacheKey = spl_object_id($pdo) . ':' . $table . ':' . $column;

    if (!array_key_exists($cacheKey, $columns)) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table
               AND column_name = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        $columns[$cacheKey] = (bool) $stmt->fetchColumn();
    }

    return $columns[$cacheKey];
}

function flowup_user_filter(PDO $pdo, string $table, string $alias): string
{
    if (flowup_table_has_column($pdo, $table, 'user_id')) {
        return $alias . '.user_id = :user_id';
    }

    return 'EXISTS (
        SELECT 1
        FROM usuarios AS flowup_user
        WHERE flowup_user.id = :user_id
          AND flowup_user.email = ' . $alias . '.email
    )';
}

function flowup_prepare_schema(PDO $pdo): void
{
    if (flowup_table_has_column($pdo, 'novas_tarefas', 'user_id')) {
        $ownerColumn = 'user_id INT(10) UNSIGNED NOT NULL';
        $ownerIndex = 'UNIQUE KEY tarefa_periodo_usuario (id_tarefas, user_id, periodo)';
        $ownerConstraint = 'CONSTRAINT fk_conclusoes_user FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE';
    } else {
        $ownerColumn = 'email VARCHAR(150) NOT NULL';
        $ownerIndex = 'UNIQUE KEY tarefa_periodo_usuario (id_tarefas, email, periodo)';
        $ownerConstraint = 'CONSTRAINT fk_conclusoes_usuario FOREIGN KEY (email) REFERENCES usuarios (email) ON DELETE CASCADE ON UPDATE CASCADE';
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS conclusoes_tarefas (
            id_conclusao INT NOT NULL AUTO_INCREMENT,
            id_tarefas INT NOT NULL,
            ' . $ownerColumn . ',
            periodo VARCHAR(20) NOT NULL,
            qtd_pontos INT NOT NULL,
            data_conclusao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_conclusao),
            ' . $ownerIndex . ',
            CONSTRAINT fk_conclusoes_tarefa FOREIGN KEY (id_tarefas) REFERENCES novas_tarefas (id_tarefas) ON DELETE CASCADE,
            ' . $ownerConstraint . '
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

function flowup_pontos(PDO $pdo, ?int $userId): int
{
    if (!$userId) {
        return 0;
    }

    $stmt = $pdo->prepare(
        'SELECT p.qtd_pontos
         FROM pontos AS p
         WHERE ' . flowup_user_filter($pdo, 'pontos', 'p')
    );
    $stmt->execute(['user_id' => $userId]);
    $pontos = $stmt->fetchColumn();
    return $pontos === false ? 0 : (int) $pontos;
}

function flowup_tarefas_hoje(PDO $pdo, ?int $userId): int
{
    if (!$userId) {
        return 0;
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM novas_tarefas AS t
         WHERE ' . flowup_user_filter($pdo, 'novas_tarefas', 't') . '
           AND tipo = \'diaria\'
           AND status = \'ativa\''
    );
    $stmt->execute(['user_id' => $userId]);
    return (int) $stmt->fetchColumn();
}

function flowup_metas_hoje(PDO $pdo, ?int $userId): int
{
    if (!$userId) {
        return 0;
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM novas_tarefas AS t
         WHERE ' . flowup_user_filter($pdo, 'novas_tarefas', 't') . '
           AND tipo = \'meta\'
           AND status = \'ativa\''
    );
    $stmt->execute(['user_id' => $userId]);
    return (int) $stmt->fetchColumn();
}

function flowup_pontos_id(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare(
        'SELECT p.id_pontos
         FROM pontos AS p
         WHERE ' . flowup_user_filter($pdo, 'pontos', 'p') . '
         FOR UPDATE'
    );
    $stmt->execute(['user_id' => $userId]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int) $id;
    }

    if (flowup_table_has_column($pdo, 'pontos', 'user_id')) {
        $stmt = $pdo->prepare('INSERT INTO pontos (user_id, qtd_pontos) VALUES (:user_id, 0)');
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO pontos (email, qtd_pontos)
             SELECT email, 0 FROM usuarios WHERE id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);
    }

    return (int) $pdo->lastInsertId();
}

function flowup_concluidas(PDO $pdo, int $userId, array $tarefas): array
{
    $concluidas = [];
    foreach ($tarefas as $tarefa) {
        $periodo = flowup_periodo($tarefa['tipo']);
        $stmt = $pdo->prepare(
            'SELECT c.id_conclusao
             FROM conclusoes_tarefas AS c
             WHERE c.id_tarefas = :id
               AND ' . flowup_user_filter($pdo, 'conclusoes_tarefas', 'c') . '
               AND c.periodo = :periodo'
        );
        $stmt->execute([
            'id' => $tarefa['id_tarefas'],
            'user_id' => $userId,
            'periodo' => $periodo,
        ]);
        if ($stmt->fetchColumn()) {
            $concluidas[(int) $tarefa['id_tarefas']] = true;
        }
    }
    return $concluidas;
}

function flowup_concluir(PDO $pdo, int $userId, int $id): string
{
    $stmt = $pdo->prepare(
        'SELECT t.*
         FROM novas_tarefas AS t
         WHERE t.id_tarefas = :id
           AND ' . flowup_user_filter($pdo, 'novas_tarefas', 't') . '
           AND t.status <> \'inativa\''
    );
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    $tarefa = $stmt->fetch();
    if (!$tarefa) {
        return 'Tarefa não encontrada.';
    }

    $periodo = flowup_periodo($tarefa['tipo']);
    $pdo->beginTransaction();
    try {
        $check = $pdo->prepare(
            'SELECT c.id_conclusao
             FROM conclusoes_tarefas AS c
             WHERE c.id_tarefas = :id
               AND ' . flowup_user_filter($pdo, 'conclusoes_tarefas', 'c') . '
               AND c.periodo = :periodo
             FOR UPDATE'
        );
        $check->execute(['id' => $id, 'user_id' => $userId, 'periodo' => $periodo]);
        if ($check->fetchColumn()) {
            $pdo->rollBack();
            return 'Essa tarefa já foi concluída neste período.';
        }

        $pontosId = flowup_pontos_id($pdo, $userId);
        $atual = flowup_pontos($pdo, $userId);
        $novo = $atual + (int) $tarefa['qtd_pon_ganho'];
        $update = $pdo->prepare('UPDATE pontos SET qtd_pontos = :pontos WHERE id_pontos = :id');
        $update->execute(['pontos' => $novo, 'id' => $pontosId]);

        if (flowup_table_has_column($pdo, 'conclusoes_tarefas', 'user_id')) {
            $insert = $pdo->prepare(
                'INSERT INTO conclusoes_tarefas (id_tarefas, user_id, periodo, qtd_pontos)
                 VALUES (:tarefa, :user_id, :periodo, :pontos)'
            );
            $insert->execute(['tarefa' => $id, 'user_id' => $userId, 'periodo' => $periodo, 'pontos' => $tarefa['qtd_pon_ganho']]);
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO conclusoes_tarefas (id_tarefas, email, periodo, qtd_pontos)
                 SELECT :tarefa, email, :periodo, :pontos FROM usuarios WHERE id = :user_id'
            );
            $insert->execute(['tarefa' => $id, 'periodo' => $periodo, 'pontos' => $tarefa['qtd_pon_ganho'], 'user_id' => $userId]);
        }

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

function flowup_resgatar(PDO $pdo, int $userId, int $id): string
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

        $pointsId = flowup_pontos_id($pdo, $userId);
        $points = flowup_pontos($pdo, $userId);
        if ($points < (int) $recompensa['qtd_pon_neces']) {
            $pdo->rollBack();
            return 'Você ainda não tem pontos suficientes.';
        }

        $novo = $points - (int) $recompensa['qtd_pon_neces'];
        $update = $pdo->prepare('UPDATE pontos SET qtd_pontos = :pontos WHERE id_pontos = :id');
        $update->execute(['pontos' => $novo, 'id' => $pointsId]);
        $history = $pdo->prepare('INSERT INTO historico_pontos (id_pontos, qtd_pontos_anterior, qtd_pontos_novo) VALUES (:id, :anterior, :novo)');
        $history->execute(['id' => $pointsId, 'anterior' => $points, 'novo' => $novo]);

        if (flowup_table_has_column($pdo, 'novas_recompensas', 'user_id')) {
            $insert = $pdo->prepare(
                'INSERT INTO novas_recompensas (user_id, descricao, qtd_pon_neces, status)
                 VALUES (:user_id, :descricao, :pontos, \'resgatada\')'
            );
            $insert->execute(['user_id' => $userId, 'descricao' => $recompensa['descricao'], 'pontos' => $recompensa['qtd_pon_neces']]);
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO novas_recompensas (email, descricao, qtd_pon_neces, status)
                 SELECT email, :descricao, :pontos, \'resgatada\' FROM usuarios WHERE id = :user_id'
            );
            $insert->execute(['user_id' => $userId, 'descricao' => $recompensa['descricao'], 'pontos' => $recompensa['qtd_pon_neces']]);
        }
        $pdo->commit();
        return 'Recompensa resgatada com sucesso!';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return 'Não foi possível resgatar essa recompensa.';
    }
}
