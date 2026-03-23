<?php
// Logout handler
require_once __DIR__ . '/includes/session.php';
session_unset();
session_destroy();
header('Location: /index.php?logged_out=1');
exit();
