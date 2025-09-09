<?php
// api/staffs/pets/search.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../config/connection.php';
header('Content-Type: application/json');

try {
    $q = trim($_GET['q'] ?? '');
    if ($q === '') {
        echo json_encode(['ok' => true, 'pets' => []]);
        exit;
    }

    $pdo = db();
    $term = '%' . $q . '%';

    // This is the final, smartest query with the crash fix applied.
    // It searches pet details OR finds a matching owner (by name, email, or username) and lists all their pets.
    $sql = "
        SELECT
            p.id,
            p.name AS pet_name,
            p.photo_path,
            p.species,
            p.breed,
            CONCAT_WS(' ', u.first_name, u.last_name) AS owner_name
        FROM pets p
        JOIN users u ON u.id = p.user_id
        WHERE 
            p.deleted_at IS NULL 
            AND (
                -- Condition 1: Match the pet's own details
                p.name LIKE :term1
                OR p.breed LIKE :term2
                OR p.species LIKE :term3
                OR p.microchip_no LIKE :term4
                
                -- Condition 2: OR the pet's owner is a match
                OR u.id IN (
                    SELECT id FROM users
                    WHERE role = 'user' AND (
                           CONCAT_WS(' ', first_name, last_name) LIKE :term5
                        OR email LIKE :term6
                        OR username LIKE :term7
                    )
                )
            )
        ORDER BY owner_name ASC, p.name ASC
        LIMIT 50
    ";
    
    $st = $pdo->prepare($sql);
    
    // Bind the same search term to each unique placeholder to prevent errors
    $st->bindValue(':term1', $term, PDO::PARAM_STR);
    $st->bindValue(':term2', $term, PDO::PARAM_STR);
    $st->bindValue(':term3', $term, PDO::PARAM_STR);
    $st->bindValue(':term4', $term, PDO::PARAM_STR);
    $st->bindValue(':term5', $term, PDO::PARAM_STR);
    $st->bindValue(':term6', $term, PDO::PARAM_STR);
    $st->bindValue(':term7', $term, PDO::PARAM_STR);
    
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Format the photo URL for the frontend.
    $BASE = base_path();
    foreach ($rows as &$r) {
        $r['photo_url'] = !empty($r['photo_path']) ? ($BASE . ltrim($r['photo_path'], '/')) : null;
    }
    unset($r);

    echo json_encode(['ok' => true, 'pets' => $rows]);

} catch (Throwable $e) {
    http_response_code(500);
    error_log('[staffs/pets/search] FATAL ERROR: ' . $e->getMessage());
    $payload = ['ok' => false, 'error' => 'Server error while searching.'];
    echo json_encode($payload);
}