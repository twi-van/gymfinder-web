<?php
declare(strict_types=1);

/** Số phiên tối đa mỗi user; vượt thì xóa phiên cũ nhất. */
const GF_AI_SESSION_MAX_PER_USER = 20;
/** Số lượt tối đa trong một phiên. */
const GF_AI_SESSION_MAX_TURNS = 30;

/**
 * @return list<array{id:int,title:string,updated_at:string,turn_count:int}>
 */
function gf_ai_sessions_list(int $userId): array
{
    $st = gf_pdo()->prepare(
        'SELECT id, title, turns_json, updated_at
         FROM ai_chat_sessions
         WHERE user_id = ?
         ORDER BY updated_at DESC, id DESC
         LIMIT 50'
    );
    $st->execute([$userId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = [];
    foreach ($rows as $row) {
        $turns = gf_ai_session_decode_turns($row['turns_json'] ?? '[]');
        $out[] = [
            'id' => (int) $row['id'],
            'title' => (string) ($row['title'] ?? 'Hội thoại mới'),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
            'turn_count' => count($turns),
        ];
    }
    return $out;
}

/**
 * @return array{id:int,user_id:int,title:string,turns:list<array<string,mixed>>,created_at:string,updated_at:string}|null
 */
function gf_ai_session_get(int $userId, int $sessionId): ?array
{
    if ($sessionId <= 0) {
        return null;
    }
    $st = gf_pdo()->prepare(
        'SELECT id, user_id, title, turns_json, created_at, updated_at
         FROM ai_chat_sessions
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );
    $st->execute([$sessionId, $userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $turns = gf_ai_session_decode_turns($row['turns_json'] ?? '[]');
    if (function_exists('gf_ai_hydrate_turns_media')) {
        $turns = gf_ai_hydrate_turns_media($turns);
    }
    return [
        'id' => (int) $row['id'],
        'user_id' => (int) $row['user_id'],
        'title' => (string) ($row['title'] ?? 'Hội thoại mới'),
        'turns' => $turns,
        'created_at' => (string) ($row['created_at'] ?? ''),
        'updated_at' => (string) ($row['updated_at'] ?? ''),
    ];
}

/**
 * @return array{id:int,title:string,turns:list<array<string,mixed>>}
 */
function gf_ai_session_create(int $userId, string $title = 'Hội thoại mới'): array
{
    $title = gf_ai_session_normalize_title($title);
    $emptyJson = '[]';
    $st = gf_pdo()->prepare(
        'INSERT INTO ai_chat_sessions (user_id, title, turns_json) VALUES (?, ?, ?)'
    );
    $st->execute([$userId, $title, $emptyJson]);
    $id = (int) gf_pdo()->lastInsertId();
    gf_ai_sessions_prune_excess($userId);
    return [
        'id' => $id,
        'title' => $title,
        'turns' => [],
    ];
}

/**
 * @param list<array<string,mixed>> $gyms
 * @param list<array<string,mixed>> $trainers
 * @return array{id:int,title:string,turns:list<array<string,mixed>>}|null
 */
function gf_ai_session_append_turn(
    int $userId,
    int $sessionId,
    string $userText,
    string $botText,
    array $gyms = [],
    array $trainers = [],
    bool $isError = false,
    bool $suggestRefine = false
): ?array {
    $session = gf_ai_session_get($userId, $sessionId);
    if ($session === null) {
        return null;
    }

    $turns = $session['turns'];
    $turns[] = gf_ai_build_history_turn($userText, $botText, $gyms, $trainers, $isError, $suggestRefine);
    if (count($turns) > GF_AI_SESSION_MAX_TURNS) {
        $turns = array_slice($turns, -GF_AI_SESSION_MAX_TURNS);
    }

    $title = $session['title'];
    if ($title === 'Hội thoại mới' || $title === '') {
        $title = gf_ai_session_normalize_title($userText);
    }

    $json = json_encode($turns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        $json = '[]';
    }

    $st = gf_pdo()->prepare(
        'UPDATE ai_chat_sessions
         SET title = ?, turns_json = ?, updated_at = CURRENT_TIMESTAMP
         WHERE id = ? AND user_id = ?'
    );
    $st->execute([$title, $json, $sessionId, $userId]);

    return [
        'id' => $sessionId,
        'title' => $title,
        'turns' => $turns,
    ];
}

function gf_ai_session_clear_turns(int $userId, int $sessionId): bool
{
    $st = gf_pdo()->prepare(
        "UPDATE ai_chat_sessions
         SET title = 'Hội thoại mới', turns_json = '[]', updated_at = CURRENT_TIMESTAMP
         WHERE id = ? AND user_id = ?"
    );
    $st->execute([$sessionId, $userId]);
    return $st->rowCount() > 0;
}

function gf_ai_session_delete(int $userId, int $sessionId): bool
{
    $st = gf_pdo()->prepare('DELETE FROM ai_chat_sessions WHERE id = ? AND user_id = ?');
    $st->execute([$sessionId, $userId]);
    return $st->rowCount() > 0;
}

function gf_ai_sessions_prune_excess(int $userId): void
{
    $st = gf_pdo()->prepare(
        'SELECT id FROM ai_chat_sessions
         WHERE user_id = ?
         ORDER BY updated_at DESC, id DESC'
    );
    $st->execute([$userId]);
    $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
    if (count($ids) <= GF_AI_SESSION_MAX_PER_USER) {
        return;
    }
    $drop = array_slice($ids, GF_AI_SESSION_MAX_PER_USER);
    if ($drop === []) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($drop), '?'));
    $del = gf_pdo()->prepare(
        "DELETE FROM ai_chat_sessions WHERE user_id = ? AND id IN ($placeholders)"
    );
    $del->execute(array_merge([$userId], $drop));
}

function gf_ai_session_normalize_title(string $text): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if ($text === '') {
        return 'Hội thoại mới';
    }
    if (function_exists('mb_substr')) {
        $cut = mb_substr($text, 0, 60);
        if (mb_strlen($text) > 60) {
            $cut .= '…';
        }
        return $cut;
    }
    $cut = substr($text, 0, 60);
    if (strlen($text) > 60) {
        $cut .= '…';
    }
    return $cut;
}

/**
 * @return list<array<string,mixed>>
 */
function gf_ai_session_decode_turns(mixed $raw): array
{
    if (is_array($raw)) {
        return array_values($raw);
    }
    if (!is_string($raw) || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? array_values($decoded) : [];
}
