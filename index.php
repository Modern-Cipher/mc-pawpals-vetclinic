<?php
die("Router check: OK!"); // <--- IDAGDAG ITONG LINYA
// index.php (router)
require_once __DIR__ . '/vendor/autoload.php';

/* ---------- Resolve request path relative to app base ---------- */
$reqPath   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$path      = trim(str_starts_with($reqPath, $scriptDir) ? substr($reqPath, strlen($scriptDir)) : $reqPath, '/');
$path      = rtrim($path, '/');

/* ---------- Small helpers ---------- */
function safe_require(string $absPath): void {
    if (!is_file($absPath)) {
        http_response_code(500);
        echo "<h2>Router error</h2><p>View file not found: <code>" . htmlspecialchars($absPath) . "</code></p>";
        exit;
    }
    require $absPath;
    exit;
}

function try_include_api_php(string $relative): void {
    if (!str_starts_with($relative, 'api/')) return;
    if (str_contains($relative, '..')) return;

    $apiRoot   = realpath(__DIR__ . '/api') ?: (__DIR__ . '/api');
    $candidate = __DIR__ . '/' . (str_ends_with($relative, '.php') ? $relative : $relative . '.php');
    $real      = realpath($candidate);

    if ($real && str_starts_with($real, $apiRoot) && is_file($real)) {
        require $real;
        exit;
    }
}

/* ---------- Static uploads passthrough ---------- */
if (str_starts_with($path, 'api/pets/uploads/')) {
    header('Location: ' . $scriptDir . '/' . substr($path, strlen('api/pets/')));
    exit;
}

/* ---------- Explicit route table ---------- */
$routes = [
    // --- PAGES ---
    ''                                   => __DIR__ . '/views/pages/landing-page.php',

    // Auth Pages
    'auth/login'                         => __DIR__ . '/views/auth/login/login.php',
    'auth/logout'                        => __DIR__ . '/views/auth/logout.php',
    'auth/signup'                        => __DIR__ . '/views/auth/signup/signup.php',
    'auth/verify-email'                  => __DIR__ . '/views/auth/verify-email.php',
    'auth/forgot'                        => __DIR__ . '/views/auth/forgot/index.php',
    'auth/forgot/otp'                    => __DIR__ . '/views/auth/forgot/otp.php',
    'auth/forgot/verify-otp'             => __DIR__ . '/views/auth/forgot/verify-otp.php',
    'auth/reset'                         => __DIR__ . '/views/auth/forgot/reset.php',
    'auth/reset/otp'                     => __DIR__ . '/views/auth/forgot/otp.php',

    // Dashboard Pages
    'dashboard/profile'                  => __DIR__ . '/views/dashboards/profile/index.php',
    'dashboard/admin'                    => __DIR__ . '/views/dashboards/admin/dashboard-admin.php',
    'dashboard/admin/staff'              => __DIR__ . '/views/dashboards/admin/staff.php',
    'dashboard/admin/pet-owner'          => __DIR__ . '/views/dashboards/admin/pet-owner.php',
    'dashboard/admin/staff_time_off'     => __DIR__ . '/views/dashboards/admin/staff_time_off.php',
    'dashboard/staffs'                   => __DIR__ . '/views/dashboards/staffs/dashboard-staff.php',
    'dashboard/staffs/appointments'      => __DIR__ . '/views/dashboards/staffs/appointments.php',
    'dashboard/staffs/medical'           => __DIR__ . '/views/dashboards/staffs/medical.php',
    'dashboard/staffs/medical/entry'     => __DIR__ . '/views/dashboards/staffs/medical-form.php',
    'dashboard/staffs/my-schedule'       => __DIR__ . '/views/dashboards/staffs/schedule.php',
    'dashboard/staffs/pet-documentation' => __DIR__ . '/views/dashboards/staffs/pet-documentation.php',
    'dashboard/users'                    => __DIR__ . '/views/dashboards/users/dashboard-users.php',
    'dashboard/users/pets'               => __DIR__ . '/views/dashboards/users/pets.php',
    'dashboard/users/appointments'       => __DIR__ . '/views/dashboards/users/appointments.php',
    'dashboard/users/records'            => __DIR__ . '/views/dashboards/users/records.php',
    'dashboard/users/documents'          => __DIR__ . '/views/dashboards/users/pet-document.php', // ===== BAGONG ROUTE PARA SA PAGE =====
    'dashboard/users/notifications'      => __DIR__ . '/views/dashboards/users/notifications.php',
    'dashboard/ratings'                  => __DIR__ . '/views/dashboards/ratings/index.php',
    'dashboard/settings/general'         => __DIR__ . '/views/dashboards/settings/index.php',
    'dashboard/settings/clinic-hours'    => __DIR__ . '/views/dashboards/settings/clinic-hours.php',
    'dashboard/settings/announcements'   => __DIR__ . '/views/dashboards/settings/announcements.php',
    'dashboard/settings/email-config'    => __DIR__ . '/views/dashboards/settings/email-config.php',
    'dashboard/petcare'                  => __DIR__ . '/views/dashboards/petcare/index.php',

    // --- API ENDPOINTS ---
    // General
    'api/pet-owners-register'            => __DIR__ . '/api/pet-owners-register.php',
    'api/resend-verification'            => __DIR__ . '/api/resend-verification.php',
    'api/check-username'                 => __DIR__ . '/api/check-username.php',
    'api/change-password'                => __DIR__ . '/api/change-password.php',
    'api/social_links'                   => __DIR__ . '/api/social_links.php',
    'api/announcements'                  => __DIR__ . '/api/announcements.php',
    'api/petcare'                        => __DIR__ . '/api/petcare.php',
    'api/feedbacks'                      => __DIR__ . '/api/feedbacks.php',
    'api/mail-test'                      => __DIR__ . '/api/mail-test.php',
    'api/check-session'                  => __DIR__ . '/api/check-session.php',
    'api/settings/clinic_hours'          => __DIR__ . '/api/settings/clinic_hours.php',
    'api/settings/clinic_hours_update'   => __DIR__ . '/api/settings/clinic_hours_update.php',

    // Staff Management
    'api/staffs/list'                    => __DIR__ . '/api/staffs/list.php',
    'api/staffs/create'                  => __DIR__ . '/api/staffs/create.php',
    'api/staffs/update'                  => __DIR__ . '/api/staffs/update.php',
    'api/staffs/toggle_active'           => __DIR__ . '/api/staffs/toggle_active.php',
    'api/staffs/reset_password'          => __DIR__ . '/api/staffs/reset_password.php',
    'api/staffs/docs_list'               => __DIR__ . '/api/staffs/docs_list.php',
    'api/staffs/docs_delete'             => __DIR__ . '/api/staffs/docs_delete.php',
    'api/staffs/schedule/hours'          => __DIR__ . '/api/staffs/schedule/hours.php',
    'api/staffs/schedule/update_hours'   => __DIR__ . '/api/staffs/schedule/update_hours.php',
    'api/staffs/schedule/time_off'       => __DIR__ . '/api/staffs/schedule/time_off.php',
    'api/staffs/schedule/add_time_off'   => __DIR__ . '/api/staffs/schedule/add_time_off.php',
    'api/staffs/schedule/delete_time_off' => __DIR__ . '/api/staffs/schedule/delete_time_off.php',
    'api/staffs/time_off_requests'       => __DIR__ . '/api/staffs/time_off_requests.php',
    'api/staffs/approve_time_off'        => __DIR__ . '/api/staffs/approve_time_off.php',
    'api/staffs/deny_time_off'           => __DIR__ . '/api/staffs/deny_time_off.php',
    
    // Pet Document Management
    'api/pet-documents/upload_document'   => __DIR__ . '/api/pet-documents/upload_document.php',
    'api/pet-documents/list_by_pet_staff' => __DIR__ . '/api/pet-documents/list_by_pet_staff.php',
    'api/pet-documents/delete_by_staff'   => __DIR__ . '/api/pet-documents/delete_by_staff.php',
    'api/pet-documents/download'          => __DIR__ . '/api/pet-documents/download.php', // ===== BAGONG ROUTE PARA SA DOWNLOAD =====

    // Pet Owners (admin management)
    'api/pet-owners/list'                => __DIR__ . '/api/pet-owners/list.php',
    'api/pet-owners/toggle_active'       => __DIR__ . '/api/pet-owners/toggle_active.php',
    'api/pet-owners/reset_password'      => __DIR__ . '/api/pet-owners/reset_password.php',

    // Appointments (user)
    'api/appointments/create'            => __DIR__ . '/api/appointments/create.php',
    'api/appointments/list_mine'         => __DIR__ . '/api/appointments/list_mine.php',
    'api/appointments/update'            => __DIR__ . '/api/appointments/update.php',
    'api/appointments/delete'            => __DIR__ . '/api/appointments/delete.php',
    'api/appointments/slots'             => __DIR__ . '/api/appointments/slots.php',
    
    // User APIs
    'api/pets/list'                      => __DIR__ . '/api/pets/list.php',
    'api/users/medical-summary'          => __DIR__ . '/api/users/medical-summary.php',
    'api/users/pet-documents'            => __DIR__ . '/api/users/pet_documents.php', // ===== BAGONG ROUTE PARA SA API =====

    // Staff Medical APIs
    'api/staffs/pets/search'             => __DIR__ . '/api/staffs/pets/search.php',
    'api/staffs/pets/today_upcoming'     => __DIR__ . '/api/staffs/pets/today_upcoming.php',
    'api/staffs/medical/history'         => __DIR__ . '/api/staffs/medical/history.php',
    'api/staffs/medical/pet_overview'    => __DIR__ . '/api/staffs/medical/pet_overview.php',
    'api/staffs/medical/records'         => __DIR__ . '/api/staffs/medical/records.php',
];

/* ---------- Request preprocessing ---------- */
require_once __DIR__ . '/middleware/request_resolver.php';

/* ---------- Routing ---------- */
if (isset($routes[$path])) {
    safe_require($routes[$path]);
}
try_include_api_php($path);

http_response_code(404);
require __DIR__ . '/404.php';