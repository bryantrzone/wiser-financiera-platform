<?php
require_once 'includes/auth.php';
destroyUserSession();
header('Location: /wiser-financiera-project/login.php');
exit;
