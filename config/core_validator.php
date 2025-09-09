<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\DatabaseException;

$nodeId = 'MC_PAWPALS_VET_CLINIC_001';

try {
    $serviceAccountKeyPath = __DIR__ . '/auth_config.json';

    if (!file_exists($serviceAccountKeyPath)) {
        throw new \Exception('Authentication file not found. Please check the path: ' . $serviceAccountKeyPath);
    }

    $factory = (new Factory)
        ->withServiceAccount($serviceAccountKeyPath)
        ->withDatabaseUri('https://mc-vlc-default-rtdb.asia-southeast1.firebasedatabase.app');

    $database = $factory->createDatabase();

    $manifestRef = $database->getReference('system_manifests/' . $nodeId);
    $manifestData = $manifestRef->getValue();

    if ($manifestData === null) {
        die("<h1>Configuration Not Found</h1><p>The system manifest for this project ID ('" . $nodeId . "') does not exist in the database. Please contact your administrator.</p>");
    }
    // --- END OF CHANGE ---

    $integrity = $manifestData['integrity_status'];
    $validationMode = $manifestData['validation_mode'];
    $validUntil = $manifestData['valid_until'];
    $currentTime = time();

    if ($validationMode === 'permissive') {
        if ($integrity === 'ok') {
            return;
        }
    }

    if ($validationMode === 'strict') {
        if ($integrity === 'ok' && $currentTime <= $validUntil) {
            return;
        }
    }
    
    require_once __DIR__ . '/../config_error.php';
    exit();
    
} catch (\Throwable $e) {
    error_log("Core Validator Exception: " . $e->getMessage());
    die("<h1>System Integrity Error</h1><p>Could not validate the system manifest. Please contact your administrator.</p>");
}