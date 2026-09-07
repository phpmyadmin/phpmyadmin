<?php

/**
 * AI Query - AJAX endpoint.
 *
 * Bootstraps phpMyAdmin so we inherit the active session, configured DB server,
 * authentication, and the $dbi handle. Three actions:
 *   - get_config   return current provider settings (api_key redacted)
 *   - save_config  persist provider settings to ai_query.config.php
 *   - generate     read INFORMATION_SCHEMA for the active DB and ask the
 *                  configured LLM provider to return a SQL query
 */

declare(strict_types=1);

use PhpMyAdmin\Common;
use PhpMyAdmin\ResponseRenderer;

if (! defined('ROOT_PATH')) {
  // This file lives in <phpmyadmin>/phpMyAdmin-Ask-AI/. phpMyAdmin's own
  // bootstrap (libraries/constants.php, autoloader, etc.) sits one directory
  // up, so ROOT_PATH must point at phpMyAdmin's install root, not our folder.
  define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}
define('PHPMYADMIN', true);

require_once ROOT_PATH . 'libraries/constants.php';
require AUTOLOAD_FILE;

// Force ajax mode BEFORE Common::run constructs the ResponseRenderer singleton.
// ResponseRenderer's constructor reads $_REQUEST['ajax_request'] and registers
// a shutdown function. In non-ajax mode that shutdown renders a full HTML page
// (which crashes via Navigation's array_merge bug, and would corrupt our JSON
// either way). In ajax mode it emits JSON, but its success-path also tries to
// render header/menu - so we ALSO disable() the renderer after Common::run.
$_REQUEST['ajax_request'] = '1';
$_GET['ajax_request']     = '1';

Common::run();

// Disable header/footer rendering. The shutdown handler then becomes a no-op
// (returns empty getDisplay()) and the output we echo below flows through
// untouched via the OutputBuffering layer.
$aiResponse = ResponseRenderer::getInstance();
$aiResponse->disable();
$aiResponse->setAjax(true);

$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'ai_query.config.php';

/** Emit a JSON response and exit. We echo directly (not via addJSON) because
 *  the renderer is disabled and its shutdown path discards the JSON map. The
 *  echo lands in OutputBuffering, gets captured, and is re-emitted by the
 *  renderer's shutdown handler as the body of the response. */
function ai_json(array $data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: no-store');
  echo json_encode($data);
  exit;
}

/** CSRF: phpMyAdmin's Common::run sets $token_mismatch=true unless the POST
 *  carries a valid token from $_SESSION[' PMA_token ']. We enforce it here
 *  for every state-changing call. The browser-side JS reads the token from
 *  the existing hidden <input name="token"> phpMyAdmin already renders. */
if (! empty($GLOBALS['token_mismatch'])) {
  ai_json(['error' => 'CSRF token mismatch. Reload the page and try again.'], 403);
}

/** Require an authenticated DB connection (Common::run sets $dbi when auth ok). */
if (empty($GLOBALS['dbi'])) {
  ai_json(['error' => 'Not authenticated. Reload phpMyAdmin and log in.'], 401);
}

$action = $_POST['action'] ?? '';

/** Allowed provider types (the API shape used to talk to the upstream). */
const AI_PROVIDER_TYPES = ['anthropic', 'openai_compatible'];

/** Allowed tab keys. 'custom' lets users freely set provider+url+model. */
const AI_TAB_KEYS = ['ollama', 'openai', 'anthropic', 'openrouter', 'groq', 'deepseek', 'custom'];

/** Load the config in the {active, profiles} shape, or return defaults. */
function ai_load_config(string $path): array {
  $default = ['active' => null, 'profiles' => []];
  if (! file_exists($path)) {
    return $default;
  }
  $raw = require $path;
  if (! is_array($raw) || ! isset($raw['profiles']) || ! is_array($raw['profiles'])) {
    return $default;
  }
  return [
    'active'   => isset($raw['active']) && is_string($raw['active']) ? $raw['active'] : null,
    'profiles' => $raw['profiles'],
  ];
}

function ai_save_config(string $path, array $cfg): void {
  $content = "<?php\n\nreturn " . var_export($cfg, true) . ";\n";
  if (file_put_contents($path, $content) === false) {
    throw new RuntimeException('Could not write ' . $path);
  }
  @chmod($path, 0600);
}

/** Reduce a profile to a public-safe summary (no raw api key). */
function ai_profile_summary(array $profile): array {
  return [
    'provider' => $profile['provider'] ?? 'openai_compatible',
    'base_url' => $profile['base_url'] ?? '',
    'model'    => $profile['model'] ?? '',
    'has_key'  => ! empty($profile['api_key']),
  ];
}

/** Build a compact textual schema for the AI. Format is human/LLM-friendly DDL-ish. */
function ai_collect_schema($dbi, string $db): string {
  $esc = $dbi->escapeString($db);

  $out = [];
  $tablesRes = $dbi->query(
    "SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES "
      . "WHERE TABLE_SCHEMA = '" . $esc . "' ORDER BY TABLE_NAME"
  );
  $tables = [];
  while ($row = $tablesRes->fetchAssoc()) {
    $tables[] = $row;
  }

  if (empty($tables)) {
    // information_schema is privilege-filtered, so zero rows means EITHER the
    // database is genuinely empty OR the current MySQL user lacks privileges on
    // every object in it. Tell the model both possibilities so it can ask.
    return "(no tables visible to current MySQL user in `{$db}` - database may be empty, or user lacks privileges on its objects)";
  }

  foreach ($tables as $t) {
    $tn = $t['TABLE_NAME'];
    $tnEsc = $dbi->escapeString($tn);
    $label = ($t['TABLE_TYPE'] === 'VIEW') ? 'VIEW' : 'TABLE';
    $out[] = "{$label} `{$tn}` (";

    $colsRes = $dbi->query(
      "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, "
        . "COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT "
        . "FROM information_schema.COLUMNS "
        . "WHERE TABLE_SCHEMA = '" . $esc . "' AND TABLE_NAME = '" . $tnEsc . "' "
        . "ORDER BY ORDINAL_POSITION"
    );
    $colLines = [];
    while ($c = $colsRes->fetchAssoc()) {
      $line = '  `' . $c['COLUMN_NAME'] . '` ' . $c['COLUMN_TYPE'];
      if ($c['IS_NULLABLE'] === 'NO') {
        $line .= ' NOT NULL';
      }
      if ($c['COLUMN_KEY'] === 'PRI') {
        $line .= ' PK';
      } elseif ($c['COLUMN_KEY'] === 'UNI') {
        $line .= ' UNIQUE';
      } elseif ($c['COLUMN_KEY'] === 'MUL') {
        $line .= ' INDEX';
      }
      if ($c['EXTRA'] !== null && $c['EXTRA'] !== '') {
        $line .= ' ' . $c['EXTRA'];
      }
      if ($c['COLUMN_COMMENT'] !== null && $c['COLUMN_COMMENT'] !== '') {
        $line .= ' -- ' . str_replace(["\r", "\n"], ' ', $c['COLUMN_COMMENT']);
      }
      $colLines[] = $line;
    }
    $out[] = implode(",\n", $colLines);
    $out[] = ')';

    $fksRes = $dbi->query(
      "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME "
        . "FROM information_schema.KEY_COLUMN_USAGE "
        . "WHERE TABLE_SCHEMA = '" . $esc . "' AND TABLE_NAME = '" . $tnEsc . "' "
        . "AND REFERENCED_TABLE_NAME IS NOT NULL"
    );
    while ($fk = $fksRes->fetchAssoc()) {
      $out[] = "  FK `{$tn}`.`{$fk['COLUMN_NAME']}` -> `{$fk['REFERENCED_TABLE_NAME']}`.`{$fk['REFERENCED_COLUMN_NAME']}`";
    }
    $out[] = '';
  }

  return implode("\n", $out);
}

/** Extract a clean SQL string from the model's reply.
 *  Models often disobey "no markdown" and wrap the query in a ```sql ... ```
 *  fenced block, sometimes with prose before/after. We prefer the FIRST fenced
 *  block (sql/mysql/plain) anywhere in the response; if no fence is present we
 *  return the trimmed response as-is (covers obedient single-statement replies). */
function ai_clean_sql(string $s): string {
  $s = trim($s);

  // First, try a fenced block anywhere in the response.
  if (preg_match('/```(?:sql|mysql)?\s*\n?([\s\S]*?)\n?```/i', $s, $m)) {
    return trim($m[1]);
  }

  // Fallback: strip a stray leading/trailing fence if present.
  $s = preg_replace('/^```(?:sql|mysql)?\s*\n?/i', '', $s);
  $s = preg_replace('/\n?```\s*$/', '', $s);
  return trim($s);
}

function ai_http_post_json(string $url, array $headers, string $body, int $timeout = 60): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => $timeout,
    CURLOPT_CONNECTTIMEOUT => 15,
  ]);
  $resp = curl_exec($ch);
  if ($resp === false) {
    $err = curl_error($ch);
    curl_close($ch);
    throw new RuntimeException('HTTP error: ' . $err);
  }
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, $resp];
}

function ai_call_anthropic(array $cfg, string $system, array $messages): string {
  $base = rtrim($cfg['base_url'] ?: 'https://api.anthropic.com', '/');
  $url  = $base . '/v1/messages';
  $body = json_encode([
    'model'      => $cfg['model'],
    'max_tokens' => 4096,
    'system'     => $system,
    'messages'   => $messages,
  ]);
  [$code, $resp] = ai_http_post_json($url, [
    'Content-Type: application/json',
    'x-api-key: ' . ($cfg['api_key'] ?? ''),
    'anthropic-version: 2023-06-01',
  ], $body);
  $data = json_decode($resp, true);
  if ($code >= 400) {
    $msg = $data['error']['message'] ?? $resp;
    throw new RuntimeException('Anthropic ' . $code . ': ' . $msg);
  }
  return (string) ($data['content'][0]['text'] ?? '');
}

function ai_call_openai_compatible(array $cfg, string $system, array $messages): string {
  $base = rtrim($cfg['base_url'] ?: 'https://api.openai.com/v1', '/');
  $url  = $base . '/chat/completions';
  $allMessages = array_merge(
    [['role' => 'system', 'content' => $system]],
    $messages
  );
  $body = json_encode([
    'model'       => $cfg['model'],
    'temperature' => 0,
    'messages'    => $allMessages,
  ]);
  $headers = ['Content-Type: application/json'];
  if (! empty($cfg['api_key'])) {
    $headers[] = 'Authorization: Bearer ' . $cfg['api_key'];
  }
  [$code, $resp] = ai_http_post_json($url, $headers, $body);
  $data = json_decode($resp, true);
  if ($code >= 400) {
    $msg = $data['error']['message'] ?? $data['error'] ?? $resp;
    if (is_array($msg)) {
      $msg = json_encode($msg);
    }
    throw new RuntimeException('Provider ' . $code . ': ' . $msg);
  }
  return (string) ($data['choices'][0]['message']['content'] ?? '');
}

/** Sanitise a backticked identifier for safe interpolation. */
function ai_bq(string $ident): string {
  return '`' . str_replace('`', '``', $ident) . '`';
}

/** Read MySQL/MariaDB version string. */
function ai_server_version($dbi): string {
  try {
    $res = $dbi->query('SELECT VERSION()');
    if ($res) {
      $row = $res->fetchRow();
      if (is_array($row) && isset($row[0])) {
        return (string) $row[0];
      }
    }
  } catch (\Throwable $e) {
    // best effort
  }
  return '';
}

/** Full `SHOW CREATE TABLE` (or VIEW) statement for one table. */
function ai_show_create($dbi, string $db, string $table): string {
  try {
    $res = $dbi->query('SHOW CREATE TABLE ' . ai_bq($db) . '.' . ai_bq($table));
    if ($res) {
      $row = $res->fetchAssoc();
      if (is_array($row)) {
        return (string) ($row['Create Table'] ?? $row['Create View'] ?? '');
      }
    }
  } catch (\Throwable $e) {
    // best effort - permissions or table renamed mid-flight
  }
  return '';
}

/** Up to N sample rows from a table, returned as an array of assoc arrays.
 *  Binary / huge values are truncated so the prompt stays compact. */
function ai_sample_rows($dbi, string $db, string $table, int $limit = 3): array {
  $rows = [];
  try {
    $res = $dbi->query('SELECT * FROM ' . ai_bq($db) . '.' . ai_bq($table) . ' LIMIT ' . (int) $limit);
    if (! $res) {
      return [];
    }
    while ($row = $res->fetchAssoc()) {
      foreach ($row as $k => $v) {
        if (is_string($v) && strlen($v) > 200) {
          $row[$k] = substr($v, 0, 200) . '...<truncated>';
        }
      }
      $rows[] = $row;
    }
  } catch (\Throwable $e) {
    // Table might be a view that can't be sampled, or perms issue. Best effort.
  }
  return $rows;
}

/** Compose the first user-message body: schema, active-table hint, samples. */
function ai_build_context_block($dbi, string $db, string $table): string {
  $out = [];
  $version = ai_server_version($dbi);
  if ($version !== '') {
    $out[] = 'Server version: ' . $version;
  }
  $out[] = 'Active database: `' . $db . '`';
  if ($table !== '') {
    $out[] = 'Currently open table: `' . $table . '` -- this is just where the user happened to be in phpMyAdmin; the request may target ANY table (or multiple). Do not assume this is the target table.';
  }
  $out[] = '';
  $out[] = 'SCHEMA (all tables in `' . $db . '`, compact form):';
  $out[] = ai_collect_schema($dbi, $db);
  $out[] = '';

  if ($table !== '') {
    $create = ai_show_create($dbi, $db, $table);
    if ($create !== '') {
      $out[] = 'FULL DDL for `' . $table . '` (currently open table):';
      $out[] = $create . ';';
      $out[] = '';
    }
    $samples = ai_sample_rows($dbi, $db, $table, 3);
    if (! empty($samples)) {
      $out[] = 'SAMPLE ROWS from `' . $table . '` (up to 3, for value-format reference only - DO NOT assume these are representative):';
      $out[] = json_encode($samples, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      $out[] = '';
    }
  }

  return implode("\n", $out);
}

/** Decide whether the model's reply is SQL or a clarification question. */
function ai_parse_reply(string $raw): array {
  $clean = ai_clean_sql($raw);
  if (preg_match('/^\s*QUESTION\s*:\s*(.+)$/is', $clean, $m)) {
    return ['kind' => 'question', 'text' => trim($m[1]), 'raw' => $raw];
  }
  return ['kind' => 'sql', 'text' => $clean, 'raw' => $raw];
}

/** The system prompt - rules, response format, few-shot examples. */
function ai_system_prompt(string $db): string {
  return <<<EOT
You are a senior MySQL/MariaDB SQL engineer helping a developer work with their database through phpMyAdmin. The target database is `{$db}`.

You will receive:
  - The server version
  - The active database name
  - A note about which table is currently open in phpMyAdmin (this is a HINT, not a constraint - the user may want any table)
  - A compact schema listing for every table in the database
  - Full DDL (`SHOW CREATE TABLE`) for the currently open table
  - Up to 3 sample rows from the currently open table (for value-format reference only)
  - The user's request, optionally followed by a clarification thread

Respond in EXACTLY ONE of these two forms:

1. SQL (strongly preferred): a single MySQL/MariaDB query that fulfils the request. No markdown fences, no explanation, no leading or trailing prose. Use backticks for identifiers. Prefer explicit column lists over `SELECT *`. A trailing semicolon is fine. Nothing else after the query.

2. A clarification question, ONLY when a critical detail is genuinely ambiguous and a reasonable guess is likely to produce a wrong or destructive query. Format the entire reply as one line:
   QUESTION: <one short, specific question>
   and nothing else.

Default strongly to producing SQL. Ask a question only when the user references a column/table that doesn't exist, when a destructive operation could match multiple tables, or when the request is missing a metric you can't reasonably infer.

Worked examples:

USER: list all users
REPLY: SELECT `id`, `email`, `created_at` FROM `users` ORDER BY `id`;

USER: top 10 sellers last month
REPLY: SELECT `seller_id`, SUM(`amount`) AS `total_amount` FROM `orders` WHERE `created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH) GROUP BY `seller_id` ORDER BY `total_amount` DESC LIMIT 10;

USER: delete the test records
REPLY: QUESTION: Which table do you mean - `users` (filtered by email like '%@test.local') or `imports` (which has an `is_test` flag)?

USER: how many active sessions
REPLY: SELECT COUNT(*) AS `active_sessions` FROM `sessions` WHERE `expires_at` > NOW();
EOT;
}

try {
  if ($action === 'get_config') {
    $cfg = ai_load_config($configPath);
    $profiles = [];
    foreach ($cfg['profiles'] as $tab => $p) {
      if (is_array($p)) {
        $profiles[$tab] = ai_profile_summary($p);
      }
    }
    $active = $cfg['active'];
    $configured = $active !== null && isset($cfg['profiles'][$active]['model'])
      && $cfg['profiles'][$active]['model'] !== '';
    ai_json([
      'configured' => $configured,
      'active'     => $active,
      'profiles'   => (object) $profiles,
    ]);
  }

  if ($action === 'save_config') {
    $tab = $_POST['tab'] ?? '';
    if (! in_array($tab, AI_TAB_KEYS, true)) {
      ai_json(['error' => 'Unknown provider tab.'], 400);
    }
    $provider = $_POST['provider'] ?? 'openai_compatible';
    if (! in_array($provider, AI_PROVIDER_TYPES, true)) {
      ai_json(['error' => 'Unknown provider type.'], 400);
    }

    $cfg = ai_load_config($configPath);
    $existing = $cfg['profiles'][$tab] ?? [];

    $apiKey = trim((string) ($_POST['api_key'] ?? ''));
    // Empty key on save keeps whatever was previously stored for this tab.
    if ($apiKey === '' && ! empty($existing['api_key'])) {
      $apiKey = $existing['api_key'];
    }

    $profile = [
      'provider' => $provider,
      'base_url' => trim((string) ($_POST['base_url'] ?? '')),
      'model'    => trim((string) ($_POST['model'] ?? '')),
      'api_key'  => $apiKey,
    ];
    if ($profile['model'] === '') {
      ai_json(['error' => 'Model is required.'], 400);
    }

    $cfg['profiles'][$tab] = $profile;
    $cfg['active'] = $tab;
    ai_save_config($configPath, $cfg);
    ai_json(['ok' => true]);
  }

  if ($action === 'generate') {
    $cfg = ai_load_config($configPath);
    $active = $cfg['active'];
    if ($active === null || empty($cfg['profiles'][$active]['model'])) {
      ai_json(['error' => 'Not configured. Open Settings and pick a provider.'], 400);
    }
    $profile = $cfg['profiles'][$active];

    $db    = trim((string) ($_POST['db'] ?? ''));
    $table = trim((string) ($_POST['table'] ?? ''));
    if ($db === '') {
      ai_json(['error' => 'No database selected. Pick a database in phpMyAdmin first.'], 400);
    }

    // Accept either a single 'prompt' field (first request) or a 'conversation'
    // JSON array of {role,content} turns (when the user is replying to a QUESTION).
    $conversation = [];
    if (! empty($_POST['conversation'])) {
      $decoded = json_decode((string) $_POST['conversation'], true);
      if (is_array($decoded)) {
        foreach ($decoded as $turn) {
          if (! is_array($turn) || ! isset($turn['role'], $turn['content'])) {
            continue;
          }
          $role = $turn['role'] === 'assistant' ? 'assistant' : 'user';
          $conversation[] = ['role' => $role, 'content' => (string) $turn['content']];
        }
      }
    }
    if (empty($conversation)) {
      $prompt = trim((string) ($_POST['prompt'] ?? ''));
      if ($prompt === '') {
        ai_json(['error' => 'Prompt is empty.'], 400);
      }
      $conversation = [['role' => 'user', 'content' => $prompt]];
    }
    if ($conversation[0]['role'] !== 'user') {
      ai_json(['error' => 'Conversation must start with a user turn.'], 400);
    }

    // Inject schema/context into the FIRST user message so the model has it for
    // every turn without us repeating it in each request.
    $context = ai_build_context_block($GLOBALS['dbi'], $db, $table);
    $firstUserContent = "REQUEST: " . $conversation[0]['content'];
    $conversation[0]['content'] = $context . "\n" . $firstUserContent;

    $system = ai_system_prompt($db);

    $reply = ($profile['provider'] === 'anthropic')
      ? ai_call_anthropic($profile, $system, $conversation)
      : ai_call_openai_compatible($profile, $system, $conversation);

    if (trim($reply) === '') {
      ai_json(['error' => 'Provider returned an empty response.'], 502);
    }

    $parsed = ai_parse_reply($reply);
    if ($parsed['kind'] === 'question') {
      ai_json(['kind' => 'question', 'text' => $parsed['text'], 'raw' => $parsed['raw']]);
    }
    ai_json(['kind' => 'sql', 'sql' => $parsed['text'], 'raw' => $parsed['raw']]);
  }

  if ($action === 'detect_ollama') {
    // Ollama exposes installed models at /api/tags. Accept either the bare host
    // (http://localhost:11434) or the OpenAI-compatible base (.../v1).
    $base = trim((string) ($_POST['base_url'] ?? 'http://localhost:11434/v1'));
    $base = rtrim($base, '/');
    if (substr($base, -3) === '/v1') {
      $base = substr($base, 0, -3);
    }
    $url = $base . '/api/tags';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 10,
      CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false) {
      ai_json(['error' => 'Cannot reach Ollama at ' . $url . ' - ' . $err], 502);
    }
    if ($code >= 400) {
      ai_json(['error' => 'Ollama returned HTTP ' . $code], 502);
    }
    $data = json_decode((string) $resp, true);
    $models = [];
    if (is_array($data) && isset($data['models']) && is_array($data['models'])) {
      foreach ($data['models'] as $m) {
        if (isset($m['name'])) {
          $models[] = $m['name'];
        }
      }
    }
    ai_json(['models' => $models]);
  }

  ai_json(['error' => 'Unknown action.'], 400);
} catch (Throwable $e) {
  ai_json(['error' => $e->getMessage()], 500);
}
