<?php
// api/staffs/medical/soap.php
// Creates a minimal SOAP table if missing, and exposes GET(list), POST(create/update), DELETE(delete).
// DDL used (create once if not present):
/*
CREATE TABLE IF NOT EXISTS pet_consultations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pet_id INT NOT NULL,
  staff_id INT NOT NULL,
  record_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  weight_kg DECIMAL(6,2) NULL,
  temp_c DECIMAL(4,1) NULL,
  pulse_bpm INT NULL,
  resp_cpm INT NULL,
  subjective TEXT NULL,
  objective  TEXT NULL,
  assessment TEXT NULL,
  plan       TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../config/connection.php';
require_login(['staff']);
header('Content-Type: application/json; charset=utf-8');

try{
    $pdo = db();

    // create table if not exists (first run convenience)
    $pdo->exec("CREATE TABLE IF NOT EXISTS pet_consultations (
      id INT AUTO_INCREMENT PRIMARY KEY,
      pet_id INT NOT NULL,
      staff_id INT NOT NULL,
      record_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      weight_kg DECIMAL(6,2) NULL,
      temp_c DECIMAL(4,1) NULL,
      pulse_bpm INT NULL,
      resp_cpm INT NULL,
      subjective TEXT NULL,
      objective  TEXT NULL,
      assessment TEXT NULL,
      plan       TEXT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET'){
        $pet_id = (int)($_GET['pet_id'] ?? 0);
        if ($pet_id<=0){ echo json_encode(['ok'=>false,'error'=>'Invalid pet']); exit; }
        $st=$pdo->prepare("SELECT * FROM pet_consultations WHERE pet_id=:pid ORDER BY record_date DESC, id DESC");
        $st->execute([':pid'=>$pet_id]);
        echo json_encode(['ok'=>true,'items'=>$st->fetchAll(PDO::FETCH_ASSOC)]); exit;
    }

    if ($method === 'POST'){
        $sid = (int)($_SESSION['user']['id'] ?? 0);
        $id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $pet = (int)($_POST['pet_id'] ?? 0);
        if ($pet<=0){ echo json_encode(['ok'=>false,'error'=>'Invalid pet']); exit; }

        $vals = [
            ':pet_id'=>$pet,
            ':staff_id'=>$sid,
            ':weight_kg'=>($_POST['weight_kg'] ?? null),
            ':temp_c'=>($_POST['temp_c'] ?? null),
            ':pulse_bpm'=>($_POST['pulse_bpm'] ?? null),
            ':resp_cpm'=>($_POST['resp_cpm'] ?? null),
            ':subjective'=>($_POST['subjective'] ?? null),
            ':objective'=>($_POST['objective'] ?? null),
            ':assessment'=>($_POST['assessment'] ?? null),
            ':plan'=>($_POST['plan'] ?? null),
        ];

        if ($id>0){
            $sql="UPDATE pet_consultations SET
                    weight_kg=:weight_kg, temp_c=:temp_c, pulse_bpm=:pulse_bpm, resp_cpm=:resp_cpm,
                    subjective=:subjective, objective=:objective, assessment=:assessment, plan=:plan
                  WHERE id=:id AND pet_id=:pet_id";
            $vals[':id']=$id;
            $st=$pdo->prepare($sql); $st->execute($vals);
            echo json_encode(['ok'=>true,'updated'=>true]); exit;
        } else {
            $sql="INSERT INTO pet_consultations
                 (pet_id, staff_id, weight_kg, temp_c, pulse_bpm, resp_cpm, subjective, objective, assessment, plan)
                  VALUES (:pet_id,:staff_id,:weight_kg,:temp_c,:pulse_bpm,:resp_cpm,:subjective,:objective,:assessment,:plan)";
            $st=$pdo->prepare($sql); $st->execute($vals);
            echo json_encode(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]); exit;
        }
    }

    if ($method === 'DELETE'){
        parse_str($_SERVER['QUERY_STRING'] ?? '', $q);
        $id = (int)($q['id'] ?? 0);
        if ($id<=0){ echo json_encode(['ok'=>false,'error'=>'Invalid id']); exit; }
        $st=$pdo->prepare("DELETE FROM pet_consultations WHERE id=:id");
        $st->execute([':id'=>$id]);
        echo json_encode(['ok'=>true,'deleted'=>true]); exit;
    }

    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
}catch(Throwable $e){
    error_log('[soap_api] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Server error']);
}
