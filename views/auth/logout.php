<?php
require_once __DIR__ . '/../../middleware/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
logout_now(true); // will redirect to home with ?logout=1
