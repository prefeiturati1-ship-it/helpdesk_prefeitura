<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

session_start();

// Redirecionamento correto para produção na nuvem (sem a subpasta do XAMPP)
header("Location: /account/login.php");
exit;
