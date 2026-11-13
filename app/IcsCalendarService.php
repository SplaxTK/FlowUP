<?php
declare(strict_types=1);

final class IcsCalendarService
{
    private const MAX_ARCHIVE_BYTES = 20_000_000;
    private const MAX_ICS_BYTES = 10_000_000;
    private const MAX_ARCHIVE_FILES = 100;
    private const MAX_EVENTS = 5000;

    public static function importFile(PDO $pdo, int $userId, string $uploadPath, string $fileName, DateTimeZone $timezone): array
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension === 'ics') {
            $content = file_get_contents($uploadPath);
            if ($content === false) {
                throw new RuntimeException('Não foi possível ler o arquivo selecionado.');
            }
            $calendars = [$content];
        } elseif ($extension === 'zip') {
            $calendars = self::readZip($uploadPath);
        } else {
            throw new InvalidArgumentException('Selecione um arquivo .zip do Google Agenda ou um arquivo .ics.');
        }

        $events = [];
        foreach ($calendars as $content) {
            $calendarEvents = self::parse($content, $timezone);
            if ($calendarEvents) {
                array_push($events, ...$calendarEvents);
            }
            if (count($events) > self::MAX_EVENTS) {
                throw new InvalidArgumentException('O arquivo contém mais de 5.000 eventos.');
            }
        }
        if (!$events) {
            throw new InvalidArgumentException('Não encontrei eventos válidos no arquivo. O ZIP deve conter arquivos .ics exportados do Google Agenda.');
        }

        $find = $pdo->prepare('SELECT id FROM calendar_events WHERE user_id = ? AND ics_uid = ?');
        $insert = $pdo->prepare(
            'INSERT INTO calendar_events
                (user_id, title, description, starts_at, ends_at, all_day, location, color, guests, repeat_rule, event_type, is_completed, ics_uid)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $update = $pdo->prepare(
            'UPDATE calendar_events
             SET title = ?, description = ?, starts_at = ?, ends_at = ?, all_day = ?, location = ?, guests = ?, repeat_rule = ?, event_type = ?, is_completed = ?
             WHERE id = ? AND user_id = ?'
        );

        $pdo->beginTransaction();
        try {
            foreach ($events as $event) {
                $find->execute([$userId, $event['ics_uid']]);
                $existingId = $find->fetchColumn();
                $values = [
                    $event['title'],
                    $event['description'],
                    $event['starts_at'],
                    $event['ends_at'],
                    $event['all_day'],
                    $event['location'],
                    $event['guests'],
                    $event['repeat_rule'],
                    $event['event_type'],
                    $event['is_completed'],
                ];
                if ($existingId) {
                    $update->execute(array_merge($values, [$existingId, $userId]));
                    continue;
                }
                $insert->execute(array_merge(
                    [$userId],
                    array_slice($values, 0, 6),
                    ['#5688ff'],
                    array_slice($values, 6),
                    [$event['ics_uid']]
                ));
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }

        $firstEventDate = min(array_column($events, 'starts_at'));
        return [
            'imported' => count($events),
            'first_date' => substr($firstEventDate, 0, 10),
        ];
    }

    public static function export(PDO $pdo, int $userId, DateTimeZone $timezone): array
    {
        $stmt = $pdo->prepare('SELECT e.*, c.name AS category_name FROM calendar_events e LEFT JOIN calendar_categories c ON c.id = e.category_id WHERE e.user_id = ? ORDER BY e.starts_at');
        $stmt->execute([$userId]);
        $events = $stmt->fetchAll();
        $saveUid = $pdo->prepare('UPDATE calendar_events SET ics_uid = ? WHERE id = ? AND user_id = ? AND ics_uid IS NULL');

        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//FlowUp//Calendar//PT-BR', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH'];
        foreach ($events as $event) {
            if (empty($event['ics_uid'])) {
                $event['ics_uid'] = hash('sha256', 'flowup-' . $event['id'] . '@flowup.local');
                $saveUid->execute([$event['ics_uid'], $event['id'], $userId]);
            }
            $uid = 'flowup-' . $event['ics_uid'] . '@flowup.local';
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . self::escapeText($uid);
            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
            if (!empty($event['all_day'])) {
                $lines[] = 'DTSTART;VALUE=DATE:' . (new DateTimeImmutable($event['starts_at'], $timezone))->format('Ymd');
                $lines[] = 'DTEND;VALUE=DATE:' . (new DateTimeImmutable($event['ends_at'], $timezone))->format('Ymd');
            } else {
                $start = (new DateTimeImmutable($event['starts_at'], $timezone))->setTimezone(new DateTimeZone('UTC'));
                $end = (new DateTimeImmutable($event['ends_at'], $timezone))->setTimezone(new DateTimeZone('UTC'));
                $lines[] = 'DTSTART:' . $start->format('Ymd\THis\Z');
                $lines[] = 'DTEND:' . $end->format('Ymd\THis\Z');
            }
            $lines[] = 'SUMMARY:' . self::escapeText($event['title']);
            if (!empty($event['description'])) {
                $lines[] = 'DESCRIPTION:' . self::escapeText($event['description']);
            }
            if (!empty($event['location'])) {
                $lines[] = 'LOCATION:' . self::escapeText($event['location']);
            }
            if (!empty($event['guests'])) {
                foreach (explode(',', $event['guests']) as $guest) {
                    $guest = trim($guest);
                    if (filter_var($guest, FILTER_VALIDATE_EMAIL)) {
                        $lines[] = 'ATTENDEE;CN=' . self::escapeText($guest) . ':mailto:' . $guest;
                    }
                }
            }
            if (!empty($event['repeat_rule'])) {
                $lines[] = 'RRULE:FREQ=' . $event['repeat_rule'];
            }
            if (!empty($event['category_name'])) {
                $lines[] = 'CATEGORIES:' . self::escapeText($event['category_name']);
            }
            $lines[] = 'X-FLOWUP-TYPE:' . strtoupper((string) $event['event_type']);
            $lines[] = 'X-FLOWUP-COMPLETED:' . (!empty($event['is_completed']) ? 'TRUE' : 'FALSE');
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';

        return [self::foldLines($lines), count($events)];
    }

    private static function readZip(string $uploadPath): array
    {
        $archivePath = tempnam(sys_get_temp_dir(), 'flowup-calendar-');
        if ($archivePath === false) {
            throw new RuntimeException('Não foi possível preparar o arquivo ZIP para leitura.');
        }
        $zipPath = $archivePath . '.zip';
        if (!rename($archivePath, $zipPath)) {
            if (is_file($archivePath) && !unlink($archivePath)) {
                error_log('Não foi possível remover um arquivo temporário de importação do calendário.');
            }
            throw new RuntimeException('Não foi possível preparar o arquivo ZIP para leitura.');
        }

        try {
            if (!copy($uploadPath, $zipPath)) {
                throw new RuntimeException('Não foi possível ler o arquivo ZIP enviado.');
            }
            $archive = new PharData($zipPath, 0, null, Phar::ZIP);
            $calendars = [];
            $totalBytes = 0;
            $fileCount = 0;
            foreach (new RecursiveIteratorIterator($archive) as $entry) {
                if (!$entry->isFile()) {
                    continue;
                }
                if (++$fileCount > self::MAX_ARCHIVE_FILES) {
                    throw new InvalidArgumentException('O ZIP contém arquivos demais para importar.');
                }
                if (strtolower(pathinfo($entry->getFilename(), PATHINFO_EXTENSION)) !== 'ics') {
                    continue;
                }
                $size = $entry->getSize();
                if ($size > self::MAX_ICS_BYTES || $totalBytes + $size > self::MAX_ARCHIVE_BYTES) {
                    throw new InvalidArgumentException('O arquivo ZIP excede o limite de tamanho para importação.');
                }
                $content = $entry->getContent();
                if (strlen($content) !== $size) {
                    throw new RuntimeException('Um arquivo .ics dentro do ZIP não pôde ser lido por completo.');
                }
                $totalBytes += $size;
                $calendars[] = $content;
            }
            unset($archive);
            return $calendars;
        } catch (InvalidArgumentException | RuntimeException $error) {
            throw $error;
        } catch (Throwable $error) {
            throw new InvalidArgumentException('Não foi possível abrir o ZIP. Selecione um arquivo .zip válido exportado do Google Agenda.', 0, $error);
        } finally {
            if (isset($archive)) {
                unset($archive);
            }
            if (is_file($zipPath) && !unlink($zipPath)) {
                error_log('Não foi possível remover um arquivo temporário de importação do calendário.');
            }
        }
    }

    private static function parse(string $content, DateTimeZone $timezone): array
    {
        if (strlen($content) > self::MAX_ICS_BYTES || stripos($content, 'BEGIN:VCALENDAR') === false) {
            throw new InvalidArgumentException('Um dos arquivos não é um calendário .ics válido.');
        }
        $content = preg_replace("/\r\n[ \t]|\n[ \t]|\r[ \t]/", '', $content);
        if ($content === null) {
            throw new InvalidArgumentException('Não foi possível ler as linhas do arquivo .ics.');
        }
        $events = [];
        $properties = [];
        $insideEvent = false;
        foreach (preg_split('/\r\n|\n|\r/', $content) as $line) {
            if (strcasecmp($line, 'BEGIN:VEVENT') === 0) {
                $properties = [];
                $insideEvent = true;
                continue;
            }
            if (strcasecmp($line, 'END:VEVENT') === 0 && $insideEvent) {
                $event = self::parseEvent($properties, $timezone);
                if ($event !== null) {
                    $events[] = $event;
                    if (count($events) > self::MAX_EVENTS) {
                        throw new InvalidArgumentException('O arquivo contém mais de 5.000 eventos.');
                    }
                }
                $insideEvent = false;
                continue;
            }
            if (!$insideEvent || !str_contains($line, ':')) {
                continue;
            }
            $separator = self::propertySeparator($line);
            if ($separator === null) {
                continue;
            }
            $nameAndParams = substr($line, 0, $separator);
            $value = substr($line, $separator + 1);
            $parts = explode(';', $nameAndParams);
            $name = strtoupper(array_shift($parts));
            $params = [];
            foreach ($parts as $part) {
                if (str_contains($part, '=')) {
                    [$key, $paramValue] = explode('=', $part, 2);
                    $params[strtoupper($key)] = trim($paramValue, '"');
                }
            }
            $properties[$name][] = ['value' => $value, 'params' => $params];
        }
        return $events;
    }

    private static function parseEvent(array $properties, DateTimeZone $timezone): ?array
    {
        if (empty($properties['DTSTART'][0]) || empty($properties['SUMMARY'][0])) {
            return null;
        }
        $startProperty = $properties['DTSTART'][0];
        $start = self::parseDate($startProperty, $timezone);
        $allDay = $start['all_day'];
        if (!empty($properties['DTEND'][0])) {
            $end = self::parseDate($properties['DTEND'][0], $timezone)['date'];
        } elseif ($allDay) {
            $end = $start['date']->modify('+1 day');
        } else {
            $end = $start['date']->modify('+1 hour');
        }
        if ($end <= $start['date']) {
            throw new InvalidArgumentException('Um evento no arquivo tem horário de término anterior ao início.');
        }
        $uid = self::unescapeText($properties['UID'][0]['value'] ?? '');
        $type = strtoupper(self::unescapeText($properties['X-FLOWUP-TYPE'][0]['value'] ?? 'EVENT'));
        if (!in_array($type, ['EVENT', 'TASK', 'APPOINTMENT'], true)) {
            $type = 'EVENT';
        }
        $guests = [];
        foreach ($properties['ATTENDEE'] ?? [] as $attendee) {
            $address = preg_replace('/^mailto:/i', '', $attendee['value']);
            if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                $guests[] = $address;
            }
        }
        $frequency = '';
        foreach ($properties['RRULE'] ?? [] as $rule) {
            if (preg_match('/(?:^|;)FREQ=(DAILY|WEEKLY|MONTHLY|YEARLY)(?:;|$)/i', $rule['value'], $match)) {
                $frequency = strtoupper($match[1]);
                break;
            }
        }
        $uidDigest = null;
        if ($uid !== '') {
            $uidDigest = preg_match('/^flowup-([a-f0-9]{64})@flowup\.local$/i', $uid, $match)
                ? strtolower($match[1])
                : hash('sha256', $uid);
        }
        return [
            'ics_uid' => $uidDigest,
            'title' => mb_substr(self::unescapeText($properties['SUMMARY'][0]['value']), 0, 180),
            'description' => mb_substr(self::unescapeText($properties['DESCRIPTION'][0]['value'] ?? ''), 0, 5000),
            'starts_at' => $start['date']->format('Y-m-d H:i:s'),
            'ends_at' => $end->format('Y-m-d H:i:s'),
            'all_day' => $allDay ? 1 : 0,
            'location' => mb_substr(self::unescapeText($properties['LOCATION'][0]['value'] ?? ''), 0, 255),
            'guests' => mb_substr(implode(', ', array_unique($guests)), 0, 1000),
            'repeat_rule' => $frequency ?: null,
            'event_type' => strtolower($type),
            'is_completed' => strtoupper($properties['X-FLOWUP-COMPLETED'][0]['value'] ?? '') === 'TRUE' ? 1 : 0,
        ];
    }

    private static function parseDate(array $property, DateTimeZone $defaultTimezone): array
    {
        $value = $property['value'];
        $allDay = ($property['params']['VALUE'] ?? '') === 'DATE' || preg_match('/^\d{8}$/', $value) === 1;
        if ($allDay && preg_match('/^\d{8}$/', $value)) {
            $date = DateTimeImmutable::createFromFormat('!Ymd', $value, $defaultTimezone);
        } elseif (preg_match('/^(\d{8})T(\d{6})(Z?)$/', $value, $matches)) {
            try {
                $sourceTimezone = $matches[3] === 'Z'
                    ? new DateTimeZone('UTC')
                    : new DateTimeZone($property['params']['TZID'] ?? $defaultTimezone->getName());
            } catch (Throwable $error) {
                throw new InvalidArgumentException('Um evento no arquivo contém um fuso horário inválido.', 0, $error);
            }
            $date = DateTimeImmutable::createFromFormat('!Ymd\THis', $matches[1] . 'T' . $matches[2], $sourceTimezone);
        } elseif (preg_match('/^(\d{8})T(\d{4})(Z?)$/', $value, $matches)) {
            try {
                $sourceTimezone = $matches[3] === 'Z'
                    ? new DateTimeZone('UTC')
                    : new DateTimeZone($property['params']['TZID'] ?? $defaultTimezone->getName());
            } catch (Throwable $error) {
                throw new InvalidArgumentException('Um evento no arquivo contém um fuso horário inválido.', 0, $error);
            }
            $date = DateTimeImmutable::createFromFormat('!Ymd\THi', $matches[1] . 'T' . $matches[2], $sourceTimezone);
        } else {
            throw new InvalidArgumentException('Um evento no arquivo contém uma data ou hora inválida.');
        }
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) {
            throw new InvalidArgumentException('Um evento no arquivo contém uma data ou hora inválida.');
        }
        return ['date' => $date->setTimezone($defaultTimezone), 'all_day' => $allDay];
    }

    private static function escapeText(string $value): string
    {
        return str_replace(["\\", "\r\n", "\n", "\r", ';', ','], ["\\\\", '\n', '\n', '\n', '\;', '\,'], $value);
    }

    private static function unescapeText(string $value): string
    {
        return preg_replace_callback('/\\\\([\\\\,;nN])/', static function (array $match): string {
            return strtolower($match[1]) === 'n' ? "\n" : $match[1];
        }, $value) ?? $value;
    }

    private static function propertySeparator(string $line): ?int
    {
        $inQuotes = false;
        $length = strlen($line);
        for ($index = 0; $index < $length; $index++) {
            if ($line[$index] === '"') {
                $inQuotes = !$inQuotes;
            } elseif ($line[$index] === ':' && !$inQuotes) {
                return $index;
            }
        }
        return null;
    }

    private static function foldLines(array $lines): string
    {
        $folded = [];
        foreach ($lines as $line) {
            $current = '';
            $bytes = 0;
            foreach (preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: str_split($line) as $character) {
                $length = strlen($character);
                if ($bytes + $length > 73) {
                    $folded[] = $current;
                    $current = ' ';
                    $bytes = 1;
                }
                $current .= $character;
                $bytes += $length;
            }
            $folded[] = $current;
        }
        return implode("\r\n", $folded) . "\r\n";
    }
}
