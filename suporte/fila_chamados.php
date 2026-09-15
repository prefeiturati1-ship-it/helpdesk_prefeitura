<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// 1. SEGURANÇA: Permite acesso apenas para 'admin' e 'tecnico'
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_perfil'], ['admin', 'tecnico'])) {
    header("Location: ../painel.php");
    exit;
}

$mensagemErro = '';

// 2. BUSCAR TODOS OS CHAMADOS ORDENADOS POR STATUS E DATA
try {
    $verificaColunaOutro = $pdo->query("SHOW COLUMNS FROM chamados LIKE 'outro_usuario'");
    if ($verificaColunaOutro->rowCount() === 0) {
        $pdo->exec("ALTER TABLE chamados ADD COLUMN outro_usuario VARCHAR(255) DEFAULT NULL");
    }

    $verificaColunaLugar = $pdo->query("SHOW COLUMNS FROM chamados LIKE 'lugar'");
    if ($verificaColunaLugar->rowCount() === 0) {
        $pdo->exec("ALTER TABLE chamados ADD COLUMN lugar VARCHAR(255) DEFAULT NULL");
    }

    $sql = "SELECT 
                c.id, 
                c.titulo, 
                c.status, 
                c.prioridade, 
                c.criado_em,
                c.outro_usuario,
                c.lugar,
                u.nome AS solicitante_nome, 
                u.email AS solicitante_email,
                s.nome AS setor_nome, 
                s.sigla AS setor_sigla
            FROM chamados c
            INNER JOIN usuarios u ON c.usuario_id = u.id
            LEFT JOIN secretarias_setores s ON u.setor_id = s.id
            ORDER BY 
                CASE 
                    WHEN c.status = 'novo' THEN 1
                    WHEN c.status = 'em_andamento' THEN 2
                    WHEN c.status IN ('fechado', 'resolvido') THEN 3
                    ELSE 4
                END ASC, 
                c.criado_em DESC";

    $stmt = $pdo->query($sql);
    $chamados = $stmt->fetchAll();
} catch (PDOException $e) {
    $chamados = [];
    $mensagemErro = "Erro ao buscar chamados na fila: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fila de Chamados - TI Prefeitura de Borborema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../account/painel.php"> TI Prefeitura de Borborema</a>
            <div class="d-flex align-items-center text-white">
                <a href="../account/painel.php" class="btn btn-outline-light btn-sm me-2">Voltar ao Painel</a>
                <a href="../account/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Fila de Atendimento de Chamados</h2>
                <p class="text-muted small mb-0">Visualização exclusiva para Administradores e Técnicos</p>
            </div>
            <div>
                <span class="badge bg-primary fs-6">Total: <?= count($chamados) ?></span>
            </div>
        </div>

        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th># ID</th>
                                <th>Solicitante (Quem abriu)</th>
                                <th>Quem precisa</th>
                                <th>Lugar</th>
                                <th>Setor / Secretaria</th>
                                <th>Assunto</th>
                                <th>Situação</th>
                                <th>Aberto Em</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($chamados) > 0): ?>
                                <?php foreach ($chamados as $chamado): ?>
                                    <tr>
                                        <td><strong>#<?= $chamado['id'] ?></strong></td>
                                        
                                        <td>
                                            <strong><?= htmlspecialchars($chamado['solicitante_nome']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($chamado['solicitante_email']) ?></small>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(!empty($chamado['outro_usuario']) ? $chamado['outro_usuario'] : '-') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(!empty($chamado['lugar']) ? $chamado['lugar'] : '-') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($chamado['setor_nome'] ?? 'Não informado') ?>
                                            <?php if (!empty($chamado['setor_sigla'])): ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($chamado['setor_sigla']) ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= htmlspecialchars($chamado['titulo']) ?></td>

                                        <td>
                                            <?php if ($chamado['status'] === 'novo'): ?>
                                                <span class="badge bg-primary">Novo</span>
                                            <?php elseif ($chamado['status'] === 'em_andamento'): ?>
                                                <span class="badge bg-warning text-dark">Em Andamento</span>
                                            <?php elseif (in_array($chamado['status'], ['fechado', 'resolvido'])): ?>
                                                <span class="badge bg-success">Resolvido</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($chamado['status'])) ?></span>
                                            <?php endif; ?>
                                            </td>

                                        <td>
                                            <small><?= date('d/m/Y H:i', strtotime($chamado['criado_em'])) ?></small>
                                        </td>

                                        <td class="text-center">
                                            <a href="ver_chamado.php?id=<?= $chamado['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                Ver Chamado
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Nenhum chamado encontrado na fila.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</body>
</html>