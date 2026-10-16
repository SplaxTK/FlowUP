<?php

final class GoogleCalendarService
{
    private const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API_URL = 'https://www.googleapis.com/calendar/v3';
    private PDO $pdo;
    private int $userId;

    public function __construct(PDO $pdo, int $userId)
    {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    public static function prepareSchema(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS calendar_categories (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(10) UNSIGNED NOT NULL,
                name VARCHAR(60) NOT NULL,
                color CHAR(7) NOT NULL DEFAULT \'#5688ff\',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_calendar_category_user_name (user_id, name),
                KEY idx_calendar_category_user (user_id),
                CONSTRAINT fk_calendar_category_user FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS calendar_events (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(10) UNSIGNED NOT NULL,
                category_id INT UNSIGNED DEFAULT NULL,
                title VARCHAR(180) NOT NULL,
                description TEXT DEFAULT NULL,
                starts_at DATETIME NOT NULL,
                ends_at DATETIME NOT NULL,
                all_day TINYINT(1) NOT NULL DEFAULT 0,
                location VARCHAR(255) DEFAULT NULL,
                color CHAR(7) NOT NULL DEFAULT \'#5688ff\',
                guests TEXT DEFAULT NULL,
                repeat_rule VARCHAR(20) DEFAULT NULL,
                reminder_minutes SMALLINT UNSIGNED DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                google_event_id VARCHAR(255) DEFAULT NULL,
                google_calendar_id VARCHAR(255) DEFAULT NULL,
                google_etag VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_calendar_google_event (user_id, google_calendar_id, google_event_id),
                KEY idx_calendar_event_dates (user_id, starts_at, ends_at),
                KEY idx_calendar_event_category (user_id, category_id),
                CONSTRAINT fk_calendar_event_user FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE CASCADE,
                CONSTRAINT fk_calendar_event_category FOREIGN KEY (category_id) REFERENCES calendar_categories (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS calendar_google_accounts (
                user_id INT(10) UNSIGNED NOT NULL PRIMARY KEY,
                email VARCHAR(255) DEFAULT NULL,
                encrypted_token MEDIUMTEXT NOT NULL,
                token_expires_at DATETIME DEFAULT NULL,
                calendar_id VARCHAR(255) NOT NULL DEFAULT \'primary\',
                last_synced_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_calendar_google_user FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public static function configured(): bool
    {
        $key = base64_decode(getenv('APP_ENCRYPTION_KEY') ?: '', true);
        return (bool) (
            getenv('GOOGLE_CLIENT_ID')
            && getenv('GOOGLE_CLIENT_SECRET')
            && getenv('GOOGLE_REDIRECT_URI')
            && $key !== false
            && strlen($key) === 32
        );
    }

    public static function authorizationUrl(string $state): string
    {
        self::requireConfiguration();
        return self::AUTHORIZE_URL . '?' . http_build_query([
            'client_id' => getenv('GOOGLE_CLIENT_ID'),
            'redirect_uri' => getenv('GOOGLE_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar.calendarlist.readonly https://www.googleapis.com/auth/userinfo.email',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function connect(string $code): void
    {
        $tokens = $this->postForm(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => getenv('GOOGLE_CLIENT_ID'),
            'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => getenv('GOOGLE_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
        ]);
        $email = $this->get('https://www.googleapis.com/oauth2/v2/userinfo', $tokens['access_token'])['email'] ?? null;
        $this->saveTokens($tokens, $email);
    }

    public function status(): ?array
    {
        $stmt = $this->pdo->prepare('SELECT email, last_synced_at FROM calendar_google_accounts WHERE user_id = ?');
        $stmt->execute([$this->userId]);
        return $stmt->fetch() ?: null;
    }

    public function listCalendars(): array
    {
        $this->account();
        return $this->get(self::API_URL . '/users/me/calendarList')['items'] ?? [];
    }

    public function sync(PDO $pdo): int
    {
        $account = $this->account();
        $calendarId = rawurlencode($account['calendar_id']);
        $now = new DateTimeImmutable('now', new DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo'));
        $query = [
            'timeMin' => $now->modify('-1 year')->format(DateTimeInterface::ATOM),
            'timeMax' => $now->modify('+2 years')->format(DateTimeInterface::ATOM),
            'singleEvents' => 'true',
            'showDeleted' => 'true',
            'maxResults' => 2500,
        ];
        $items = [];
        do {
            $page = $this->get(self::API_URL . '/calendars/' . $calendarId . '/events?' . http_build_query($query));
            $items = array_merge($items, $page['items'] ?? []);
            $query['pageToken'] = $page['nextPageToken'] ?? null;
        } while (!empty($query['pageToken']));

        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                if (empty($item['id'])) {
                    continue;
                }
                if (($item['status'] ?? '') === 'cancelled') {
                    $delete = $pdo->prepare('DELETE FROM calendar_events WHERE user_id = ? AND google_calendar_id = ? AND google_event_id = ?');
                    $delete->execute([$this->userId, $account['calendar_id'], $item['id']]);
                    continue;
                }
                if (!empty($item['recurringEventId'])) {
                    $master = $pdo->prepare('SELECT id FROM calendar_events WHERE user_id = ? AND google_calendar_id = ? AND google_event_id = ? LIMIT 1');
                    $master->execute([$this->userId, $account['calendar_id'], $item['recurringEventId']]);
                    if ($master->fetchColumn()) {
                        continue;
                    }
                }
                $event = $this->fromGoogle($item);
                $this->upsertImported($pdo, $account['calendar_id'], $item, $event);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $stmt = $pdo->prepare('SELECT * FROM calendar_events WHERE user_id = ? AND google_event_id IS NULL');
        $stmt->execute([$this->userId]);
        foreach ($stmt->fetchAll() as $event) {
            $googleEvent = $this->toGoogle($event);
            $created = $this->post(self::API_URL . '/calendars/' . $calendarId . '/events', $googleEvent);
            $save = $pdo->prepare('UPDATE calendar_events SET google_event_id = ?, google_calendar_id = ?, google_etag = ? WHERE id = ? AND user_id = ?');
            $save->execute([$created['id'], $account['calendar_id'], $created['etag'] ?? null, $event['id'], $this->userId]);
        }
        $updated = $pdo->prepare('UPDATE calendar_google_accounts SET last_synced_at = NOW() WHERE user_id = ?');
        $updated->execute([$this->userId]);

        return count($items);
    }

    public function createEvent(array $event): array
    {
        $account = $this->account();
        return $this->post(self::API_URL . '/calendars/' . rawurlencode($account['calendar_id']) . '/events', $this->toGoogle($event));
    }

    public function updateEvent(array $event): array
    {
        $account = $this->account();
        return $this->put(self::API_URL . '/calendars/' . rawurlencode($event['google_calendar_id'] ?: $account['calendar_id']) . '/events/' . rawurlencode($event['google_event_id']), $this->toGoogle($event));
    }

    public function deleteEvent(array $event): void
    {
        $account = $this->account();
        $this->delete(self::API_URL . '/calendars/' . rawurlencode($event['google_calendar_id'] ?: $account['calendar_id']) . '/events/' . rawurlencode($event['google_event_id']));
    }

    public static function apiErrorMessage(Throwable $error): string
    {
        return $error->getMessage();
    }

    private function account(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM calendar_google_accounts WHERE user_id = ?');
        $stmt->execute([$this->userId]);
        $account = $stmt->fetch();
        if (!$account) {
            throw new RuntimeException('Conecte sua conta do Google Agenda antes de sincronizar.');
        }
        $tokens = self::decrypt($account['encrypted_token']);
        if (empty($tokens['expires_at']) || $tokens['expires_at'] < time() + 60) {
            $tokens = $this->postForm(self::TOKEN_URL, [
                'client_id' => getenv('GOOGLE_CLIENT_ID'),
                'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
                'refresh_token' => $tokens['refresh_token'] ?? '',
                'grant_type' => 'refresh_token',
            ]) + $tokens;
            $this->saveTokens($tokens, $account['email']);
            $account['encrypted_token'] = self::encrypt($tokens);
        }
        $this->accessToken = $tokens['access_token'];
        return $account;
    }

    private string $accessToken = '';

    private function upsertImported(PDO $pdo, string $calendarId, array $item, array $event): void
    {
        $categoryId = $this->categoryForColor($event['color']);
        $stmt = $pdo->prepare('SELECT id FROM calendar_events WHERE user_id = ? AND google_calendar_id = ? AND google_event_id = ?');
        $stmt->execute([$this->userId, $calendarId, $item['id']]);
        $id = $stmt->fetchColumn();
        $values = [$categoryId, $event['title'], $event['description'], $event['starts_at'], $event['ends_at'], $event['all_day'], $event['location'], $event['color'], $event['guests'], $event['repeat_rule'], $event['reminder_minutes'], $event['notes']];
        if ($id) {
            $update = $pdo->prepare('UPDATE calendar_events SET category_id=?, title=?, description=?, starts_at=?, ends_at=?, all_day=?, location=?, color=?, guests=?, repeat_rule=?, reminder_minutes=?, notes=?, google_etag=? WHERE id=? AND user_id=?');
            $update->execute(array_merge($values, [$item['etag'] ?? null, $id, $this->userId]));
            return;
        }
        $insert = $pdo->prepare('INSERT INTO calendar_events (user_id, category_id, title, description, starts_at, ends_at, all_day, location, color, guests, repeat_rule, reminder_minutes, notes, google_event_id, google_calendar_id, google_etag) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $insert->execute(array_merge([$this->userId], $values, [$item['id'], $calendarId, $item['etag'] ?? null]));
    }

    private function categoryForColor(string $color): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM calendar_categories WHERE user_id = ? AND color = ? LIMIT 1');
        $stmt->execute([$this->userId, $color]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function fromGoogle(array $item): array
    {
        $allDay = isset($item['start']['date']);
        $start = $allDay ? $item['start']['date'] . ' 00:00:00' : (new DateTimeImmutable($item['start']['dateTime']))->setTimezone(new DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo'))->format('Y-m-d H:i:s');
        $end = $allDay ? $item['end']['date'] . ' 00:00:00' : (new DateTimeImmutable($item['end']['dateTime']))->setTimezone(new DateTimeZone(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo'))->format('Y-m-d H:i:s');
        $guests = array_map(static fn(array $guest): string => $guest['email'] ?? '', $item['attendees'] ?? []);
        $reminder = $item['reminders']['overrides'][0]['minutes'] ?? null;
        $repeat = $item['recurrence'][0] ?? '';
        $repeat = preg_match('/^RRULE:FREQ=(DAILY|WEEKLY|MONTHLY|YEARLY)/', $repeat, $match) ? $match[1] : '';
        return ['title' => $item['summary'] ?? '(Sem título)', 'description' => $item['description'] ?? '', 'starts_at' => $start, 'ends_at' => $end, 'all_day' => $allDay ? 1 : 0, 'location' => $item['location'] ?? '', 'color' => '#5688ff', 'guests' => implode(', ', $guests), 'repeat_rule' => $repeat, 'reminder_minutes' => $reminder, 'notes' => ''];
    }

    private function toGoogle(array $event): array
    {
        $timezone = getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo';
        $start = $event['all_day']
            ? ['date' => substr($event['starts_at'], 0, 10)]
            : ['dateTime' => (new DateTimeImmutable($event['starts_at'], new DateTimeZone($timezone)))->format(DateTimeInterface::ATOM), 'timeZone' => $timezone];
        $end = $event['all_day']
            ? ['date' => substr($event['ends_at'], 0, 10)]
            : ['dateTime' => (new DateTimeImmutable($event['ends_at'], new DateTimeZone($timezone)))->format(DateTimeInterface::ATOM), 'timeZone' => $timezone];
        $payload = ['summary' => $event['title'], 'description' => trim(($event['description'] ?? '') . (!empty($event['notes']) ? "\n\nObservações: " . $event['notes'] : '')), 'location' => $event['location'] ?? '', 'start' => $start, 'end' => $end];
        if (!empty($event['guests'])) {
            $payload['attendees'] = array_map(static fn(string $email): array => ['email' => trim($email)], array_filter(explode(',', $event['guests'])));
        }
        if (!empty($event['repeat_rule'])) {
            $payload['recurrence'] = ['RRULE:FREQ=' . $event['repeat_rule']];
        }
        if ($event['reminder_minutes'] !== null && $event['reminder_minutes'] !== '') {
            $payload['reminders'] = ['useDefault' => false, 'overrides' => [['method' => 'popup', 'minutes' => (int) $event['reminder_minutes']]]];
        }
        return $payload;
    }

    private function saveTokens(array $tokens, ?string $email): void
    {
        if (isset($tokens['expires_in'])) {
            $tokens['expires_at'] = time() + (int) $tokens['expires_in'];
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO calendar_google_accounts (user_id, email, encrypted_token, token_expires_at) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE email=VALUES(email), encrypted_token=VALUES(encrypted_token), token_expires_at=VALUES(token_expires_at)'
        );
        $stmt->execute([$this->userId, $email, self::encrypt($tokens), isset($tokens['expires_at']) ? date('Y-m-d H:i:s', (int) $tokens['expires_at']) : null]);
    }

    private static function encryptionKey(): string
    {
        $key = getenv('APP_ENCRYPTION_KEY') ?: '';
        $decoded = base64_decode($key, true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException('APP_ENCRYPTION_KEY deve conter uma chave base64 válida de 32 bytes.');
        }
        return $decoded;
    }

    private static function encrypt(array $data): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(json_encode($data, JSON_THROW_ON_ERROR), 'aes-256-gcm', self::encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new RuntimeException('Não foi possível proteger os tokens Google.');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    private static function decrypt(string $value): array
    {
        $data = base64_decode($value, true);
        if ($data === false || strlen($data) < 29) {
            throw new RuntimeException('O token Google salvo está inválido.');
        }
        $plain = openssl_decrypt(substr($data, 28), 'aes-256-gcm', self::encryptionKey(), OPENSSL_RAW_DATA, substr($data, 0, 12), substr($data, 12, 16));
        if ($plain === false) {
            throw new RuntimeException('Não foi possível descriptografar o token Google. Verifique APP_ENCRYPTION_KEY.');
        }
        return json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
    }

    private static function requireConfiguration(): void
    {
        if (!self::configured()) {
            throw new RuntimeException('Configure GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI e uma APP_ENCRYPTION_KEY válida no arquivo Site/.env.');
        }
    }

    private function get(string $url, ?string $token = null): array
    {
        return $this->request('GET', $url, null, $token ?? $this->accessToken);
    }

    private function post(string $url, array $payload): array
    {
        return $this->request('POST', $url, $payload, $this->accessToken);
    }

    private function put(string $url, array $payload): array
    {
        return $this->request('PUT', $url, $payload, $this->accessToken);
    }

    private function delete(string $url): void
    {
        $this->request('DELETE', $url, null, $this->accessToken);
    }

    private function postForm(string $url, array $data): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
        return $this->decodeResponse($curl);
    }

    private function request(string $method, string $url, ?array $payload, ?string $token): array
    {
        $curl = curl_init($url);
        $headers = ['Accept: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        curl_setopt_array($curl, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => $headers]);
        if ($payload !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
        }
        return $this->decodeResponse($curl);
    }

    private function decodeResponse($curl): array
    {
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        if ($body === false) {
            throw new RuntimeException('Falha de comunicação com o Google: ' . $curlError);
        }
        $data = json_decode($body, true);
        if ($status < 200 || $status >= 300) {
            $message = $data['error']['message'] ?? $data['error_description'] ?? 'resposta HTTP ' . $status;
            throw new RuntimeException('Google Agenda: ' . $message);
        }
        return is_array($data) ? $data : [];
    }
}
