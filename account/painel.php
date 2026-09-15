<?php
session_start();

// 1. Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: account/login.php");
    exit;
}

$perfil = $_SESSION['usuario_perfil'];
$nome   = $_SESSION['usuario_nome'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel -TI Prefeitura de Borborema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/painel.php">TI Prefeitura de Borborema</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Olá, <strong><?= htmlspecialchars($nome) ?></strong> 
                <span class="badge bg-secondary"><?= strtoupper($perfil) ?></span>
            </span>
            <a href="/helpdesk_prefeitura/perfil.php" class="btn btn-outline-info btn-sm me-2">Meu Perfil</a>
            <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

    <div class="container mt-4">

       <?php if ($perfil === 'admin'): ?>

                <?php include_once __DIR__ . '/../includes/cards_admin.php'; ?>

        <?php elseif ($perfil === 'tecnico'): ?>

                 <?php include_once __DIR__ . '/../includes/cards_tecnico.php'; ?>

        <?php else: ?>

            <?php include_once __DIR__ . '/../includes/cards_usuario.php'; ?>

        <?php endif; ?>

    </div>
    
</body>
</html>