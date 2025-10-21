<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    logoutUser();
} else {
    header('Location: index.php');
    exit();
}
?>
