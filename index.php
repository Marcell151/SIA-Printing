<?php
require_once 'config.php';

// Redirect to dashboard if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Redirect to login page
redirect('login.php');
?>