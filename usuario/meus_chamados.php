<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// 1. SEGURANÇA: Garante que o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /helpdesk_prefeitura/account/login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensagemErro = '';

// 2. BUSCAR APENAS OS CHAMADOS DO USUÁRIO LOGADO
try {
    $sql = "SELECT 
                c.id, 
                c.protocolo, 
                c.titulo, 
                c.status, 
                c.prioridade, 
                c.criado_em,
                cat.nome AS categoria_nome
            FROM chamados c
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE c.usuario_id = :usuario_id
            ORDER BY c.criado_em DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario_id' => $usuario_id]);
    $meusChamados = $stmt->fetchAll();
} catch (PDOException $e) {
    $meusChamados = [];
    $mensagemErro = "Erro ao buscar seus chamados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Chamados - TI Prefeitura Borborema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/account/painel.php">TI Prefeitura Borborema</a>
            <div class="d-flex align-items-center text-white">
                <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-outline-light btn-sm me-2">Voltar ao Painel</a>
                <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Meus Chamados</h2>
                <p class="text-muted small mb-0">Acompanhe o andamento das suas solicitações</p>
            </div>
            <a href="abrir_chamado.php" class="btn btn-primary">+ Novo Chamado</a>
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
                                <th>Protocolo</th>
                                <th>Assunto</th>
                                <th>Categoria</th>
                                <th>Prioridade</th>
                                <th>Situação</th>
                                <th>Data de Abertura</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($meusChamados) > 0): ?>
                                <?php foreach ($meusChamados as $chamado): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-dark"><?= htmlspecialchars($chamado['protocolo'] ?? '#'.$chamado['id']) ?></span>
                                        </td>

                                        <td><strong><?= htmlspecialchars($chamado['titulo']) ?></strong></td>

                                        <td><?= htmlspecialchars($chamado['categoria_nome'] ?? 'Geral') ?></td>

                                        <td>
                                            <?php if ($chamado['prioridade'] === 'alta'): ?>
                                                <span class="badge bg-danger">Alta</span>
                                            <?php elseif ($chamado['prioridade'] === 'media'): ?>
                                                <span class="badge bg-warning text-dark">Média</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark">Baixa</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($chamado['status'] === 'aberto'): ?>
                                                <span class="badge bg-primary">Aberto</span>
                                            <?php elseif ($chamado['status'] === 'em_andamento'): ?>
                                                <span class="badge bg-warning text-dark">Em Andamento</span>
                                            <?php elseif ($chamado['status'] === 'fechado'): ?>
                                                <span class="badge bg-success">Fechado</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($chamado['status']) ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <small><?= date('d/m/Y H:i', strtotime($chamado['criado_em'])) ?></small>
                                        </td>

                                        <td class="text-center">
                                            <a href="ver_chamado.php?id=<?= $chamado['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                Ver Resposta
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Você ainda não abriu nenhum chamado. <br>
                                        <a href="abrir_chamado.php" class="btn btn-sm btn-primary mt-2">Clique aqui para abrir um chamado</a>
                                    </td>
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