<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/security.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Settings logic here

include 'includes/header.php';
?>

<div class="container">
    <h1>System Settings</h1>
    <!-- Settings form -->
</div>

<?php include 'includes/footer.php'; ?>
