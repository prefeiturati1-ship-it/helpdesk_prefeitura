<?php
// account/logout.php
session_start();

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão no servidor
session_destroy();

// Redireciona de volta para a tela de login
header("Location: login.php");
exit;