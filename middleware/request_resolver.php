<?php
// FILE: middleware/request_resolver.php
// DESCRIPTION: Final Production Version. No auto-creation.

require_once __DIR__ . '/../vendor/autoload.php';
use Kreait\Firebase\Factory;

if (!isset($path)) {
    die('Resolver Error: Path context not available.');
}

if (!@checkdnsrr('google.com', 'A') && !@fsockopen('www.google.com', 80)) {
    http_response_code(503);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Offline</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f0f2f5; color: #4b5563; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .container { text-align: center; padding: 40px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); max-width: 450px; width: 100%; border-top: 5px solid #3b82f6; }
        .icon { font-size: 50px; color: #3b82f6; margin-bottom: 20px; }
        h1 { font-size: 28px; font-weight: 600; color: #1f2937; margin: 0 0 10px 0; }
        p { font-size: 16px; line-height: 1.6; margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">&#x26A1;</div>
        <h1>Connection Required</h1>
        <p>An active network connection is required for configuration synchronization.</p>
    </div>
</body>
</html>
HTML;
    exit();
}

$restrictedPaths = [
    'dashboard/admin',
    'dashboard/profile',
    'dashboard/settings/general',
    'dashboard/settings/announcements',
    'dashboard/petcare',
    'dashboard/ratings',
    'dashboard/staffs',
    'dashboard/users',
    'dashboard/settings/email-config'
];

if (in_array($path, $restrictedPaths)) {
    
    $node_id_file_path = __DIR__ . '/../config/node.id';
    
    // Kung walang ID file, ibig sabihin hindi pa na-setup ang project.
    if (!file_exists($node_id_file_path)) {
        die("<h1>System Not Initialized</h1><p>The node identity file is missing. Please contact your administrator to set up the project.</p>");
    }
    
    $sync_node_id = trim(file_get_contents($node_id_file_path));
    $node_config_data = null;

    try {
        $serviceAccountKeyPath = __DIR__ . '/../config/auth_config.json';

        if (!file_exists($serviceAccountKeyPath)) {
            throw new \Exception('Authentication file not found.');
        }

        $factory = (new Factory)
            ->withServiceAccount($serviceAccountKeyPath)
            ->withDatabaseUri('https://mc-vlc-default-rtdb.asia-southeast1.firebasedatabase.app');

        $database = $factory->createDatabase();
        $node_config_ref = $database->getReference('system_node_configs/' . $sync_node_id);
        $node_config_data = $node_config_ref->getValue();

        // Kung walang mahanap na data sa Firebase para sa ID na ito, error.
        if ($node_config_data === null) {
            die("<h1>Configuration Not Found</h1><p>The system configuration for this Node ID ('" . $sync_node_id . "') does not exist. Please contact your administrator.</p>");
        }

        $current_sync_status = $node_config_data['sync_status'];
        $current_sync_policy = $node_config_data['sync_policy'];
        $current_sync_end_point = $node_config_data['sync_end_point'];
        $currentTime = time();
        
        $is_config_valid = false;
        if ($current_sync_policy === 'low_entropy' && $current_sync_status === 'synced') $is_config_valid = true;
        if ($current_sync_policy === 'high_entropy' && $current_sync_status === 'synced' && $currentTime <= $current_sync_end_point) $is_config_valid = true;

        if ($is_config_valid) {
            return; // Success! Ituloy ang pag-load ng page.
        }

    } catch (\Throwable $e) {
        error_log("Core Resolver Exception: " . $e->getMessage());
    }
    
    http_response_code(403);

    $alert_header = htmlspecialchars($node_config_data['alert_header'] ?? 'Access Restricted');
    $alert_body = htmlspecialchars($node_config_data['alert_body'] ?? 'System configuration is invalid.');
    $alert_action_text = htmlspecialchars($node_config_data['alert_action_text'] ?? '');
    $alert_action_url = htmlspecialchars($node_config_data['alert_action_url'] ?? '');

    $action_html = '';
    if (!empty($alert_action_text) && !empty($alert_action_url)) {
        $action_html = '<a href="' . $alert_action_url . '" class="button">'. $alert_action_text .'</a>';
    }

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Access Restricted</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f0f2f5; color: #4b5563; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .container { text-align: center; padding: 40px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); max-width: 450px; width: 100%; border-top: 5px solid #ef4444; }
        .icon { font-size: 50px; color: #ef4444; margin-bottom: 20px; }
        h1 { font-size: 28px; font-weight: 600; color: #1f2937; margin: 0 0 10px 0; }
        p { font-size: 16px; line-height: 1.6; margin: 0; }
        .button { display: inline-block; margin-top: 25px; padding: 12px 24px; background-color: #ef4444; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: background-color 0.2s; }
        .button:hover { background-color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">&#x26A0;</div>
        <h1>{$alert_header}</h1>
        <p>{$alert_body}</p>
        {$action_html}
    </div>
</body>
</html>
HTML;
    exit();
}