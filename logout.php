<?php
require_once 'includes/auth.php';
destroyUserSession();
header('Location: ' . APP_BASE_PATH . '/login.php');
exit;
