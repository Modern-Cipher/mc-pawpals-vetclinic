<?php
// api/announcements.php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../app/models/Announcement.php';

require_login(['admin']);
header('Content-Type: application/json; charset=utf-8');

$user    = $_SESSION['user'] ?? null;
$user_id = isset($user['id']) ? (int)$user['id'] : 0;

$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action  = $_GET['action'] ?? '';
$debug   = isset($_GET['debug']);

function out(bool $ok, string $msg, array $extra = []): never {
  echo json_encode(array_merge(['success'=>$ok,'message'=>$msg], $extra));
  exit;
}

try {
  if ($method !== 'POST') out(false, 'Invalid method. Use POST.');
  if ($user_id <= 0)      out(false, 'Unauthorized.');

  if ($action === 'create') {
    $data = [
      'title'         => trim((string)($_POST['title'] ?? '')),
      'body'          => trim((string)($_POST['body'] ?? '')),
      'audience'      => trim((string)($_POST['audience'] ?? 'all')),
      'location'      => trim((string)($_POST['location'] ?? 'dashboard')),
      'is_published'  => (int)($_POST['is_published'] ?? 0),
      'published_at'  => trim((string)($_POST['published_at'] ?? '')),
      'expires_at'    => trim((string)($_POST['expires_at'] ?? '')),
      'external_url'  => trim((string)($_POST['external_url'] ?? '')), // Added external_url
    ];

    [$ok, $err] = Announcement::create($data, $_FILES['image'] ?? null, $user_id);
    out($ok, $ok ? 'Announcement created.' : ($err ?: 'Failed to create announcement.'),
        $debug && $err ? ['error_details'=>$err] : []);
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) out(false, 'Invalid ID.');

    $data = [
      'title'         => trim((string)($_POST['title'] ?? '')),
      'body'          => trim((string)($_POST['body'] ?? '')),
      'audience'      => trim((string)($_POST['audience'] ?? 'all')),
      'location'      => trim((string)($_POST['location'] ?? 'dashboard')),
      'is_published'  => (int)($_POST['is_published'] ?? 0),
      'published_at'  => trim((string)($_POST['published_at'] ?? '')),
      'expires_at'    => trim((string)($_POST['expires_at'] ?? '')),
      'external_url'  => trim((string)($_POST['external_url'] ?? '')), // Added external_url
    ];

    [$ok, $err] = Announcement::update($id, $data, $_FILES['image'] ?? null, $user_id);
    out($ok, $ok ? 'Announcement updated.' : ($err ?: 'Failed to update announcement.'),
        $debug && $err ? ['error_details'=>$err] : []);
  }

  if ($action === 'delete') {
    $raw = file_get_contents('php://input') ?: '';
    $payload = $raw ? json_decode($raw, true) : [];
    $id = (int)($payload['id'] ?? 0);
    if ($id <= 0) out(false, 'Invalid ID.');
    [$ok, $err] = Announcement::delete($id);
    out($ok, $ok ? 'Announcement deleted.' : ($err ?: 'Failed to delete announcement.'),
        $debug && $err ? ['error_details'=>$err] : []);
  }

  out(false, 'Unknown action.');
} catch (Throwable $e) {
  error_log('[API_ANNOUNCEMENTS] '.$e->getMessage());
  if ($debug) {
      out(false, 'Server error: ' . $e->getMessage());
  }
  out(false, 'A server error occurred.');
}
