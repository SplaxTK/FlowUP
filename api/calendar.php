<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/GoogleCalendarService.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function calendar_reply(array $data, int $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (empty($_SESSION['user_id'])) {
    calendar_reply(['error' => 'Sua sessão expirou. Entre novamente.'], 401);
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    GoogleCalendarService::prepareSchema($pdo);
    $google = new GoogleCalendarService($pdo, $userId);

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'status') {
        $account = $google->status();
        calendar_reply(['connected' => (bool) $account, 'account' => $account, 'configured' => GoogleCalendarService::configured()]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'google-calendars') {
        calendar_reply(['calendars' => $google->listCalendars()]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'categories') {
        $pdo->beginTransaction();
        $defaults = [['Trabalho', '#5688ff'], ['Pessoal', '#aa83ff'], ['Saúde', '#42c7a5'], ['Outros', '#f5aa53']];
        $insert = $pdo->prepare('INSERT IGNORE INTO calendar_categories (user_id, name, color) VALUES (?, ?, ?)');
        foreach ($defaults as [$name, $color]) {
            $insert->execute([$userId, $name, $color]);
        }
        $pdo->commit();
        $stmt = $pdo->prepare('SELECT id, name, color FROM calendar_categories WHERE user_id = ? ORDER BY id');
        $stmt->execute([$userId]);
        calendar_reply(['categories' => $stmt->fetchAll()]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'events') {
        $start = $_GET['start'] ?? '';
        $end = $_GET['end'] ?? '';
        if (!$start || !$end) {
            calendar_reply(['error' => 'Informe o intervalo do calendário.'], 422);
        }
        if (strlen($start) > 64 || strlen($end) > 64) {
            calendar_reply(['error' => 'O intervalo informado é inválido.'], 422);
        }
        try {
            $rangeStartDate = new DateTimeImmutable($start);
            $rangeEndDate = new DateTimeImmutable($end);
        } catch (Throwable $error) {
            calendar_reply(['error' => 'O intervalo informado é inválido.'], 422);
        }
        if ($rangeEndDate <= $rangeStartDate) {
            calendar_reply(['error' => 'A data final deve ser posterior à inicial.'], 422);
        }
        $where = ['e.user_id = :user_id', '((e.starts_at < :range_end AND e.ends_at >= :range_start) OR e.repeat_rule IS NOT NULL)'];
        $timezone = new DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo');
        $params = [
            'user_id' => $userId,
            'range_start' => $rangeStartDate->setTimezone($timezone)->format('Y-m-d H:i:s'),
            'range_end' => $rangeEndDate->setTimezone($timezone)->format('Y-m-d H:i:s'),
        ];
        $categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
        if ($categoryId) {
            $where[] = 'e.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        $search = trim($_GET['search'] ?? '');
        if ($search !== '') {
            $where[] = '(e.title LIKE :search_title OR e.description LIKE :search_description OR e.location LIKE :search_location)';
            $pattern = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $params['search_title'] = $pattern;
            $params['search_description'] = $pattern;
            $params['search_location'] = $pattern;
        }
        $stmt = $pdo->prepare('SELECT e.*, c.name AS category_name FROM calendar_events e LEFT JOIN calendar_categories c ON c.id = e.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY e.starts_at');
        $stmt->execute($params);
        $rangeStart = new DateTimeImmutable($params['range_start'], $timezone);
        $rangeEnd = new DateTimeImmutable($params['range_end'], $timezone);
        $events = [];
        foreach ($stmt->fetchAll() as $row) {
            if (!$row['repeat_rule']) {
                $events[] = calendar_event_json($row, $row['starts_at'], $row['ends_at']);
                continue;
            }
            $anchor = new DateTimeImmutable($row['starts_at'], $timezone);
            $anchorEnd = new DateTimeImmutable($row['ends_at'], $timezone);
            $duration = $anchorEnd->getTimestamp() - $anchor->getTimestamp();
            $daysUntilRange = max(0, (int) $anchor->diff($rangeStart)->format('%r%a'));
            $index = match ($row['repeat_rule']) {
                'DAILY' => $daysUntilRange,
                'WEEKLY' => intdiv($daysUntilRange, 7),
                'MONTHLY' => max(0, (($rangeStart->format('Y') - $anchor->format('Y')) * 12) + (int) $rangeStart->format('n') - (int) $anchor->format('n') - 1),
                'YEARLY' => max(0, (int) $rangeStart->format('Y') - (int) $anchor->format('Y') - 1),
                default => 0,
            };
            for ($count = 0; $count < 500; $count++, $index++) {
                $occurrenceStart = calendar_recurrence_date($anchor, $row['repeat_rule'], $index);
                $occurrenceEnd = $occurrenceStart->modify('+' . $duration . ' seconds');
                if ($occurrenceStart >= $rangeEnd) {
                    break;
                }
                if ($occurrenceEnd > $rangeStart) {
                    $events[] = calendar_event_json(
                        $row,
                        $occurrenceStart->format('Y-m-d H:i:s'),
                        $occurrenceEnd->format('Y-m-d H:i:s'),
                        $row['id'] . '-' . $occurrenceStart->format('YmdHis')
                    );
                }
            }
        }
        calendar_reply($events);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        calendar_reply(['error' => 'Ação não encontrada.'], 404);
    }
    if (!hash_equals($_SESSION['calendar_csrf'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        calendar_reply(['error' => 'Token de segurança inválido. Atualize a página e tente novamente.'], 403);
    }
    $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($input)) {
        calendar_reply(['error' => 'Dados inválidos.'], 400);
    }

    if ($action === 'sync') {
        $count = $google->sync($pdo);
        calendar_reply(['message' => 'Agenda sincronizada.', 'googleEvents' => $count, 'status' => $google->status()]);
    }

    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    $existing = null;
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM calendar_events WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $existing = $stmt->fetch();
        if (!$existing) {
            calendar_reply(['error' => 'Evento não encontrado.'], 404);
        }
    }

    if ($action === 'delete') {
        if (!$existing) {
            calendar_reply(['error' => 'Evento inválido.'], 422);
        }
        if (!empty($existing['google_event_id'])) {
            $google->deleteEvent($existing);
        }
        $delete = $pdo->prepare('DELETE FROM calendar_events WHERE id = ? AND user_id = ?');
        $delete->execute([$id, $userId]);
        calendar_reply(['message' => 'Evento excluído.']);
    }

    if ($action === 'duplicate') {
        if (!$existing) {
            calendar_reply(['error' => 'Evento inválido.'], 422);
        }
        $copy = $existing;
        $copy['title'] .= ' (cópia)';
        $copy['starts_at'] = date('Y-m-d H:i:s', strtotime($copy['starts_at'] . ' +1 day'));
        $copy['ends_at'] = date('Y-m-d H:i:s', strtotime($copy['ends_at'] . ' +1 day'));
        unset($copy['id'], $copy['google_event_id'], $copy['google_calendar_id'], $copy['google_etag']);
        $newId = save_calendar_event($pdo, $userId, $copy, false);
        calendar_reply(['id' => (string) $newId, 'message' => 'Evento duplicado.'], 201);
    }

    if (!in_array($action, ['create', 'update'], true)) {
        calendar_reply(['error' => 'Ação não encontrada.'], 404);
    }
    $title = trim((string) ($input['title'] ?? ''));
    $allDay = !empty($input['all_day']);
    $startsAt = calendar_datetime($input['start'] ?? '', $allDay);
    $endsAt = calendar_datetime($input['end'] ?? '', $allDay);
    if ($title === '' || mb_strlen($title) > 180 || !$startsAt || !$endsAt || $endsAt <= $startsAt) {
        calendar_reply(['error' => 'Informe um título e horários válidos; o término deve ser posterior ao início.'], 422);
    }
    $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
    if ($categoryId) {
        $categoryCheck = $pdo->prepare('SELECT id FROM calendar_categories WHERE id = ? AND user_id = ?');
        $categoryCheck->execute([$categoryId, $userId]);
        if (!$categoryCheck->fetchColumn()) {
            calendar_reply(['error' => 'Categoria inválida.'], 422);
        }
    }
    $color = $input['color'] ?? '#5688ff';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        calendar_reply(['error' => 'A cor escolhida é inválida.'], 422);
    }
    $repeat = $input['repeat_rule'] ?? '';
    if (!in_array($repeat, ['', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'], true)) {
        calendar_reply(['error' => 'A repetição escolhida é inválida.'], 422);
    }
    $reminder = ($input['reminder_minutes'] ?? '') === '' ? null : filter_var($input['reminder_minutes'], FILTER_VALIDATE_INT);
    if ($reminder !== null && ($reminder === false || $reminder > 40320)) {
        calendar_reply(['error' => 'O lembrete escolhido é inválido.'], 422);
    }
    $event = [
        'category_id' => $categoryId ?: null,
        'title' => $title,
        'description' => trim((string) ($input['description'] ?? '')),
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'all_day' => $allDay ? 1 : 0,
        'location' => trim((string) ($input['location'] ?? '')),
        'color' => $color,
        'guests' => trim((string) ($input['guests'] ?? '')),
        'repeat_rule' => $repeat ?: null,
        'reminder_minutes' => $reminder,
        'notes' => trim((string) ($input['notes'] ?? '')),
        'google_event_id' => $existing['google_event_id'] ?? null,
        'google_calendar_id' => $existing['google_calendar_id'] ?? null,
        'google_etag' => $existing['google_etag'] ?? null,
    ];
    if (mb_strlen($event['description']) > 5000 || mb_strlen($event['location']) > 255 || mb_strlen($event['guests']) > 1000 || mb_strlen($event['notes']) > 5000) {
        calendar_reply(['error' => 'Um dos campos excede o tamanho permitido.'], 422);
    }

    if ($existing && !empty($existing['google_event_id'])) {
        $event['id'] = $id;
        $googleResult = $google->updateEvent($event);
        $event['google_etag'] = $googleResult['etag'] ?? $event['google_etag'];
    }
    $savedId = save_calendar_event($pdo, $userId, $event, (bool) $existing);
    calendar_reply(['id' => (string) $savedId, 'message' => 'Evento salvo.'], $existing ? 200 : 201);
} catch (JsonException $error) {
    calendar_reply(['error' => 'O corpo da solicitação não contém JSON válido.'], 400);
} catch (Throwable $error) {
    error_log('Calendar API error: ' . $error->getMessage());
    calendar_reply(['error' => $error->getMessage()], 502);
}

function calendar_datetime($value, bool $allDay): ?string
{
    if (!is_string($value) || $value === '') {
        return null;
    }
    $format = $allDay ? 'Y-m-d' : 'Y-m-d\TH:i';
    $date = DateTimeImmutable::createFromFormat('!' . $format, $value, new DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo'));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
        return null;
    }
    return $allDay ? $date->format('Y-m-d') . ' 00:00:00' : $date->format('Y-m-d H:i:s');
}

function save_calendar_event(PDO $pdo, int $userId, array $event, bool $update): int
{
    $fields = ['category_id', 'title', 'description', 'starts_at', 'ends_at', 'all_day', 'location', 'color', 'guests', 'repeat_rule', 'reminder_minutes', 'notes'];
    $values = array_map(static fn(string $field) => $event[$field] ?? null, $fields);
    if ($update) {
        $assignments = implode(', ', array_map(static fn(string $field) => $field . ' = ?', $fields));
        $stmt = $pdo->prepare('UPDATE calendar_events SET ' . $assignments . ', google_etag = ? WHERE id = ? AND user_id = ?');
        $stmt->execute(array_merge($values, [$event['google_etag'] ?? null, $event['id'], $userId]));
        return (int) $event['id'];
    }
    $stmt = $pdo->prepare('INSERT INTO calendar_events (user_id, ' . implode(', ', $fields) . ') VALUES (' . implode(', ', array_fill(0, count($fields) + 1, '?')) . ')');
    $stmt->execute(array_merge([$userId], $values));
    return (int) $pdo->lastInsertId();
}

function calendar_event_json(array $row, string $start, string $end, ?string $displayId = null): array
{
    return [
        'id' => $displayId ?? (string) $row['id'],
        'title' => $row['title'],
        'start' => $row['all_day'] ? substr($start, 0, 10) : str_replace(' ', 'T', $start),
        'end' => $row['all_day'] ? substr($end, 0, 10) : str_replace(' ', 'T', $end),
        'allDay' => (bool) $row['all_day'],
        'backgroundColor' => $row['color'],
        'borderColor' => $row['color'],
        'extendedProps' => [
            'description' => $row['description'],
            'location' => $row['location'],
            'category' => $row['category_name'],
            'categoryId' => $row['category_id'],
            'color' => $row['color'],
            'guests' => $row['guests'],
            'repeatRule' => $row['repeat_rule'],
            'reminderMinutes' => $row['reminder_minutes'],
            'notes' => $row['notes'],
            'googleSynced' => (bool) $row['google_event_id'],
            'parentEventId' => (string) $row['id'],
        ],
    ];
}

function calendar_recurrence_date(DateTimeImmutable $anchor, string $rule, int $index): DateTimeImmutable
{
    if ($rule === 'DAILY') {
        return $anchor->modify('+' . $index . ' days');
    }
    if ($rule === 'WEEKLY') {
        return $anchor->modify('+' . ($index * 7) . ' days');
    }
    if ($rule === 'MONTHLY') {
        $month = $anchor->modify('first day of this month')->modify('+' . $index . ' months');
        $day = min((int) $anchor->format('j'), (int) $month->format('t'));
        return $month->setDate((int) $month->format('Y'), (int) $month->format('n'), $day)
            ->setTime((int) $anchor->format('H'), (int) $anchor->format('i'), (int) $anchor->format('s'));
    }
    $year = (int) $anchor->format('Y') + $index;
    $month = (int) $anchor->format('n');
    $first = $anchor->setDate($year, $month, 1);
    $day = min((int) $anchor->format('j'), (int) $first->format('t'));
    return $first->setDate($year, $month, $day);
}
