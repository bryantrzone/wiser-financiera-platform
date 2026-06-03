<?php
require_once 'includes/auth.php';
destroyUserSession();
header('Location: /login.php');
exit;
