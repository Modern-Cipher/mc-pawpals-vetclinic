<?php
// api/social_links.php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../app/models/SocialLink.php';

require_login(['admin']); // ensure logged-in admin
header('Content-Type: application/json; charset=utf-8');

$user    = $_SESSION['user'] ?? null;
$user_id = isset($user['id']) ? (int)$user['id'] : 0;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$debug  = isset($_GET['debug']);

function read_json_or_form(): array {
    // Prefer JSON body
    $raw = file_get_contents('php://input') ?: '';
    if ($raw !== '') {
        $data = json_decode($raw, true);
        if (is_array($data)) return $data;
    }
    // Fallback to form-encoded
    if (!empty($_POST)) return $_POST;
    return [];
}

function out(bool $ok, string $msg, array $extra = []): void {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

function clean_str($v): string { return trim((string)$v); }
function clean_int($v): int { return (int)filter_var($v, FILTER_VALIDATE_INT, ['options'=>['default'=>0]]); }

try {
    if ($method !== 'POST') out(false, 'Invalid method. Use POST.');
    if ($user_id <= 0)     out(false, 'Unauthorized.');

    $payload = read_json_or_form();

    // Normalize keys
    $norm = [
        'id'            => clean_int($payload['id'] ?? 0),
        'platform'      => clean_str($payload['platform'] ?? ''),
        'icon_class'    => clean_str($payload['icon_class'] ?? ($payload['icon'] ?? '')),
        'url'           => clean_str($payload['url'] ?? ''),
        'display_order' => clean_int($payload['display_order'] ?? 0),
    ];

    // Simple validation before hitting the model
    $validate = function(array $n): string {
        if ($n['platform']   === '') return 'Platform is required.';
        if ($n['icon_class'] === '') return 'Icon class is required.';
        if ($n['url']        === '') return 'URL is required.';
        if (!preg_match('#^https?://#i', $n['url'])) return 'URL must start with http:// or https://';
        if (!filter_var($n['url'], FILTER_VALIDATE_URL)) return 'URL is invalid.';
        return '';
    };

    switch ($action) {
        case 'create': {
            if ($err = $validate($norm)) out(false, $err, ['payload' => $debug ? $norm : null]);

            $modelErr = null;
            $ok = SocialLink::create([
                'platform'      => $norm['platform'],
                'icon_class'    => $norm['icon_class'],
                'url'           => $norm['url'],
                'display_order' => $norm['display_order'],
            ], $user_id, $modelErr);

            out($ok, $ok ? 'Social link created.' : ($modelErr ?: 'Failed to create social link.'), ['payload' => $debug ? $norm : null]);
        }

        case 'update': {
            if ($norm['id'] <= 0) out(false, 'Invalid ID.', ['payload' => $debug ? $norm : null]);
            if ($err = $validate($norm)) out(false, $err, ['payload' => $debug ? $norm : null]);

            $modelErr = null;
            $ok = SocialLink::update($norm['id'], [
                'platform'      => $norm['platform'],
                'icon_class'    => $norm['icon_class'],
                'url'           => $norm['url'],
                'display_order' => $norm['display_order'],
            ], $user_id, $modelErr);

            out($ok, $ok ? 'Social link updated.' : ($modelErr ?: 'Failed to update social link.'), ['payload' => $debug ? $norm : null]);
        }

        case 'delete': {
            if ($norm['id'] <= 0) out(false, 'Invalid ID.', ['payload' => $debug ? $norm : null]);

            $ok = SocialLink::delete($norm['id']);
            out($ok, $ok ? 'Social link deleted.' : 'Failed to delete social link.', ['payload' => $debug ? $norm : null]);
        }

        default:
            out(false, 'Unknown action.');
    }
} catch (Throwable $e) {
    error_log('[API_SOCIAL_LINKS] ' . $e->getMessage());
    out(false, 'Server error.');
}
