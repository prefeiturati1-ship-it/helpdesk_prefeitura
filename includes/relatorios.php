<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config/conexao.php';

// SEGURANÇA: apenas admin e técnico
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_perfil'], ['admin', 'tecnico'])) {
    header("Location: /helpdesk_prefeitura/account/login.php");
    exit;
}

// ============================================================
// 1. CONSULTAS PRINCIPAIS
// ============================================================

try {
    // Total de chamados
    $stmtTotal = $pdo->query("SELECT COUNT(*) AS total FROM chamados");
    $totalChamados = $stmtTotal->fetchColumn();

    // Em andamento (aberto + em_andamento)
    $stmtAndamento = $pdo->query("SELECT COUNT(*) FROM chamados WHERE status IN ('aberto', 'em_andamento')");
    $totalAndamento = $stmtAndamento->fetchColumn();

    // Resolvidos (fechados)
    $stmtResolvidos = $pdo->query("SELECT COUNT(*) FROM chamados WHERE status = 'resolvido'");
    $totalResolvidos = $stmtResolvidos->fetchColumn();

    // Tempo médio de atendimento (apenas dos resolvidos com tempo_atendimento > 0)
    $stmtMedia = $pdo->query("SELECT AVG(tempo_atendimento) AS media FROM chamados WHERE status = 'resolvido' AND tempo_atendimento IS NOT NULL AND tempo_atendimento > 0");
    $mediaSegundos = (int)$stmtMedia->fetchColumn();

    // Título mais adicionado (frequência)
    $stmtTitulo = $pdo->query("SELECT titulo, COUNT(*) AS qtd FROM chamados GROUP BY titulo ORDER BY qtd DESC LIMIT 1");
    $tituloMais = $stmtTitulo->fetch();
    $tituloMaisFreq = $tituloMais ? $tituloMais['titulo'] . " ({$tituloMais['qtd']} vezes)" : 'Nenhum';

    // Lista de chamados com dados do solicitante e categoria
    $sqlLista = "SELECT 
                    c.id, c.protocolo, c.titulo, c.status, c.prioridade, c.criado_em, 
                    c.tempo_atendimento, u.nome AS solicitante, cat.nome AS categoria
                FROM chamados c
                INNER JOIN usuarios u ON c.usuario_id = u.id
                LEFT JOIN categorias cat ON c.categoria_id = cat.id
                ORDER BY c.criado_em DESC
                LIMIT 50"; // Últimos 50 chamados
    $stmtLista = $pdo->query($sqlLista);
    $chamadosLista = $stmtLista->fetchAll();

    // Chamados por categoria (contagem)
    $stmtCat = $pdo->query("SELECT cat.nome, COUNT(*) AS total 
                            FROM chamados c 
                            LEFT JOIN categorias cat ON c.categoria_id = cat.id 
                            GROUP BY cat.nome 
                            ORDER BY total DESC");
    $categorias = $stmtCat->fetchAll();

    // Chamados por setor (contagem)
    $stmtSetor = $pdo->query("SELECT s.nome AS setor, COUNT(*) AS total 
                              FROM chamados c 
                              INNER JOIN usuarios u ON c.usuario_id = u.id 
                              LEFT JOIN secretarias_setores s ON u.setor_id = s.id 
                              GROUP BY s.nome 
                              ORDER BY total DESC");
    $setores = $stmtSetor->fetchAll();

} catch (PDOException $e) {
    die("Erro ao carregar relatório: " . $e->getMessage());
}

// ============================================================
// 2. FUNÇÃO AUXILIAR PARA FORMATAR TEMPO (segundos -> HH:MM:SS)
// ============================================================
function formatarTempo($segundos) {
    if (!$segundos || $segundos <= 0) return '--';
    $h = floor($segundos / 3600);
    $m = floor(($segundos % 3600) / 60);
    $s = $segundos % 60;
    return sprintf("%02d:%02d:%02d", $h, $m, $s);
}

// ============================================================
// 3. HTML DA PÁGINA
// ============================================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Geral - Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-stats {
            border-left: 5px solid #0d6efd;
            transition: 0.2s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2.2rem;
            opacity: 0.6;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .bg-andamento { border-left-color: #ffc107; }
        .bg-resolvido { border-left-color: #198754; }
        .bg-media { border-left-color: #0dcaf0; }
        .bg-titulo { border-left-color: #6f42c1; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/account/painel.php">TI Prefeitura de Borborema</a>
            <div class="d-flex align-items-center text-white">
                <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-outline-light btn-sm me-2">Voltar ao Painel</a>
                <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">

        <h2 class="mb-4"><i class="fas fa-chart-bar me-2"></i>Relatório Geral de Chamados</h2>

        <!-- Cards de estatísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="card card-stats shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Total</span>
                            <div class="stat-number"><?= $totalChamados ?></div>
                        </div>
                        <i class="fas fa-ticket-alt stat-icon text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card card-stats bg-andamento shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Em Andamento</span>
                            <div class="stat-number"><?= $totalAndamento ?></div>
                        </div>
                        <i class="fas fa-spinner stat-icon text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card card-stats bg-resolvido shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Resolvidos</span>
                            <div class="stat-number"><?= $totalResolvidos ?></div>
                        </div>
                        <i class="fas fa-check-circle stat-icon text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card card-stats bg-media shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Tempo Médio</span>
                            <div class="stat-number"><?= formatarTempo($mediaSegundos) ?></div>
                        </div>
                        <i class="fas fa-clock stat-icon text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Título mais adicionado -->
        <div class="card card-stats bg-titulo shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted">Título mais adicionado</span>
                    <div class="fs-4 fw-bold"><?= htmlspecialchars($tituloMaisFreq) ?></div>
                </div>
                <i class="fas fa-star stat-icon text-purple"></i>
            </div>
        </div>

        <div class="row g-4">
            <!-- Tabela de chamados recentes -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Últimos Chamados</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Protocolo</th>
                                        <th>Título</th>
                                        <th>Status</th>
                                        <th>Prioridade</th>
                                        <th>Categoria</th>
                                        <th>Tempo</th>
                                        <th>Solicitante</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($chamadosLista): ?>
                                        <?php foreach ($chamadosLista as $c): ?>
                                            <tr>
                                                <td><code><?= htmlspecialchars($c['protocolo']) ?></code></td>
                                                <td><?= htmlspecialchars($c['titulo']) ?></td>
                                                <td>
                                                    <?php
                                                    $badge = 'secondary';
                                                    if ($c['status'] === 'aberto') $badge = 'primary';
                                                    elseif ($c['status'] === 'em_andamento') $badge = 'warning';
                                                    elseif ($c['status'] === 'resolvido') $badge = 'success';
                                                    ?>
                                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($c['status']) ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $prioridade = $c['prioridade'] ?? 'baixa';
                                                    $cor = 'secondary';
                                                    if ($prioridade === 'alta') $cor = 'danger';
                                                    elseif ($prioridade === 'media') $cor = 'warning';
                                                    ?>
                                                    <span class="badge bg-<?= $cor ?>"><?= ucfirst($prioridade) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($c['categoria'] ?? 'Sem categoria') ?></td>
                                                <td><?= formatarTempo($c['tempo_atendimento']) ?></td>
                                                <td><?= htmlspecialchars($c['solicitante']) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8" class="text-center text-muted">Nenhum chamado encontrado.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos rápidos: por categoria e por setor -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Por Categoria</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categorias as $cat): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($cat['nome'] ?? 'Sem categoria') ?>
                                <span class="badge bg-primary rounded-pill"><?= $cat['total'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-building me-2"></i>Por Setor</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($setores as $s): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($s['setor'] ?? 'Sem setor') ?>
                                <span class="badge bg-primary rounded-pill"><?= $s['total'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>