<?php
require_once 'config.php';

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page with logout message
header("Location: index.php?error=logout");
exit();
?>
