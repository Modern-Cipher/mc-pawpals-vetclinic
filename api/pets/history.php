<?php
// api/pets/history.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';
require_login(['user']);
require_once __DIR__ . '/../../config/connection.php';

header('Content-Type: application/json');

$pdo  = db();
$uid  = $_SESSION['user']['id'] ?? 0;
$BASE = base_path();
$pid  = (int)($_GET['pet_id'] ?? 0);

try{
  // Secure ownership: check if the pet belongs to the logged-in user
  $st = $pdo->prepare("SELECT * FROM pets WHERE id=:id AND user_id=:uid AND deleted_at IS NULL LIMIT 1");
  $st->execute([':id'=>$pid, ':uid'=>$uid]);
  $pet = $st->fetch(PDO::FETCH_ASSOC);
  if(!$pet){ http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Pet not found or you do not have permission to view it.']); exit; }
  $pet['photo_url'] = $pet['photo_path'] ? $BASE . ltrim($pet['photo_path'],'/') : $BASE.'assets/images/pet-placeholder.jpg';

  // --- START OF CHANGES ---
  // Fetch ALL relevant medical history records

  // Fetch S.O.A.P. records (Consultations) and join with staff's name
  $soap = $pdo->prepare("
    SELECT mr.record_date, mr.subjective, mr.assessment, CONCAT('Dr. ', u.first_name, ' ', u.last_name) as staff_name 
    FROM medical_records mr 
    JOIN users u ON mr.staff_id = u.id 
    WHERE mr.pet_id=:id ORDER BY mr.record_date DESC
  ");
  $soap->execute([':id'=>$pid]);

  // Fetch Vaccinations
  $vacc = $pdo->prepare("SELECT vaccine_name,date_administered,next_due_date,vet_name FROM pet_vaccinations WHERE pet_id=:id ORDER BY date_administered DESC");
  $vacc->execute([':id'=>$pid]);

  // Fetch Deworming
  $deworm = $pdo->prepare("SELECT product_name,date_administered,next_due_date FROM pet_deworming WHERE pet_id=:id ORDER BY date_administered DESC");
  $deworm->execute([':id'=>$pid]);
  
  // Fetch Parasite Preventions
  $prevent = $pdo->prepare("SELECT product_name,type,date_administered,next_due_date FROM pet_parasite_preventions WHERE pet_id=:id ORDER BY date_administered DESC");
  $prevent->execute([':id'=>$pid]);

  // Fetch Allergies
  $alg  = $pdo->prepare("SELECT allergen,reaction,severity,noted_at FROM pet_allergies WHERE pet_id=:id ORDER BY noted_at DESC");
  $alg->execute([':id'=>$pid]);

  // Fetch Medications
  $meds = $pdo->prepare("SELECT drug_name,dosage,frequency,start_date,end_date FROM pet_medications WHERE pet_id=:id ORDER BY start_date DESC");
  $meds->execute([':id'=>$pid]);

  // Fetch Documents
  $docs = $pdo->prepare("SELECT title,doc_type,file_path,uploaded_at FROM pet_documents WHERE pet_id=:id AND user_id=:uid ORDER BY uploaded_at DESC");
  $docs->execute([':id'=>$pid, ':uid'=>$uid]);

  echo json_encode([
    'ok'=>true,
    'pet'=>$pet,
    'history'=>[
      'consultations' =>$soap->fetchAll(PDO::FETCH_ASSOC)   ?: [],
      'vaccinations'  =>$vacc->fetchAll(PDO::FETCH_ASSOC)   ?: [],
      'deworming'     =>$deworm->fetchAll(PDO::FETCH_ASSOC) ?: [],
      'preventions'   =>$prevent->fetchAll(PDO::FETCH_ASSOC)?: [],
      'allergies'     =>$alg->fetchAll(PDO::FETCH_ASSOC)    ?: [],
      'medications'   =>$meds->fetchAll(PDO::FETCH_ASSOC)   ?: [],
      'documents'     =>$docs->fetchAll(PDO::FETCH_ASSOC)   ?: [],
    ]
  ]);
  // --- END OF CHANGES ---

}catch(Throwable $e){
  error_log('[api/pets/history] '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Server error while fetching pet history.']);
}