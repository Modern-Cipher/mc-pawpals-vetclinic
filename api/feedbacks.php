<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../app/models/Feedback.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($method==='GET' ? 'list' : 'create');

function ok($data=[]){ echo json_encode(['success'=>true] + $data); exit; }
function bad($msg){ http_response_code(400); echo json_encode(['success'=>false,'message'=>$msg]); exit; }

try {
    if ($action === 'create') {
        $isJSON = ($method==='POST' && stripos($_SERVER['CONTENT_TYPE']??'','application/json')!==false);
        $in = $isJSON ? (json_decode(file_get_contents('php://input'), true) ?? []) : $_POST;

        $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
        $name   = trim((string)($in['name'] ?? ''));
        $email  = trim((string)($in['email'] ?? ''));
        $msg    = trim((string)($in['message'] ?? ''));
        $rating = (float)($in['rating'] ?? 0);

        if ($name==='' || $email==='' || $msg==='' || $rating<=0) bad('Complete all fields.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) bad('Invalid email.');
        // allow 0.1 steps (0.1..5.0)
        if ($rating < 0.1 || $rating > 5) bad('Invalid rating.');

        $res = Feedback::create([
            'user_id'=>$userId, 'name'=>$name, 'email'=>$email,
            'message'=>$msg, 'rating'=>$rating
        ]);
        if (!$res['success']) bad($res['message'] ?? 'Insert failed');
        ok(['id'=>$res['id']]);
    }

    if ($action === 'list') {
        $status = $_GET['status'] ?? null;
        $rows = Feedback::all(['status'=>$status]);
        ok(['rows'=>$rows]);
    }

    if ($action === 'approve' || $action === 'archive' || $action === 'delete') {
        if (empty($_SESSION['user']['id'])) bad('Not authorized');
        $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
        if ($id<=0) bad('Invalid id');

        if ($action==='approve')  $ok = Feedback::approve($id, (int)$_SESSION['user']['id']);
        if ($action==='archive')  $ok = Feedback::archive($id, (int)$_SESSION['user']['id']);
        if ($action==='delete')   $ok = Feedback::delete($id);

        $ok ? ok() : bad('Operation failed');
    }

    bad('Unknown action');
} catch (Throwable $e) {
    bad($e->getMessage());
}
