<?php
session_start();
date_default_timezone_set('America/Sao_Paulo'); // <-- FUSO CORRETO
require_once __DIR__ . '/../config/conexao.php';

// 1. SEGURANÇA: Apenas admin e tecnico
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_perfil'], ['admin', 'tecnico'])) {
    header("Location: /helpdesk_prefeitura/painel.php");
    exit;
}

$chamado_id = (int)($_GET['id'] ?? 0);
if ($chamado_id <= 0) {
    header("Location: fila_chamados.php");
    exit;
}

// ===== LIMPAR MENSAGENS FLASH ANTIGAS =====
$mensagemSucesso = $_SESSION['flash_sucesso'] ?? '';
$mensagemErro    = $_SESSION['flash_erro'] ?? '';
unset($_SESSION['flash_sucesso'], $_SESSION['flash_erro']);

// ===== PROCESSAR ALTERAÇÃO DE STATUS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_status') {
    $novo_status = $_POST['status'] ?? '';
    if (in_array($novo_status, ['aberto', 'em_andamento', 'resolvido'])) {
        try {
            // Se for fechado, calcula o tempo em segundos via SQL
            if ($novo_status === 'resolvido') {
                $sqlUpdate = "UPDATE chamados 
                              SET status = :status, 
                                  fechado_em = NOW(), 
                                  tempo_atendimento = TIMESTAMPDIFF(SECOND, criado_em, NOW()) 
                              WHERE id = :id";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute(['status' => $novo_status, 'id' => $chamado_id]);
            } else {
                $stmtUpdate = $pdo->prepare("UPDATE chamados SET status = :status WHERE id = :id");
                $stmtUpdate->execute(['status' => $novo_status, 'id' => $chamado_id]);
            }
            $_SESSION['flash_sucesso'] = "Status do chamado atualizado com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['flash_erro'] = "Erro ao atualizar status: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_erro'] = "Status inválido.";
    }
    header("Location: ver_chamado.php?id=" . $chamado_id);
    exit;
}

// ===== PROCESSAR NOVA RESPOSTA =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'enviar_resposta') {
    $resposta = trim($_POST['resposta'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];

    if (empty($resposta)) {
        $_SESSION['flash_erro'] = "Escreva uma resposta antes de enviar.";
    } else {
        try {
            $sqlResp = "INSERT INTO chamados_respostas (chamado_id, usuario_id, mensagem, criado_em) 
                        VALUES (:chamado_id, :usuario_id, :mensagem, NOW())";
            $stmtResp = $pdo->prepare($sqlResp);
            $stmtResp->execute([
                'chamado_id' => $chamado_id,
                'usuario_id' => $usuario_id,
                'mensagem'   => $resposta
            ]);
            $_SESSION['flash_sucesso'] = "Resposta enviada com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['flash_erro'] = "Erro ao salvar resposta. Verifique se a tabela 'chamados_respostas' existe.";
        }
    }
    header("Location: ver_chamado.php?id=" . $chamado_id);
    exit;
}

// ===== BUSCAR DADOS DO CHAMADO =====
try {
    $sql = "SELECT 
                c.*, 
                u.nome AS solicitante_nome, 
                u.email AS solicitante_email, 
                u.telefone AS solicitante_telefone,
                s.nome AS setor_nome, 
                s.sigla AS setor_sigla,
                cat.nome AS categoria_nome
            FROM chamados c
            INNER JOIN usuarios u ON c.usuario_id = u.id
            LEFT JOIN secretarias_setores s ON u.setor_id = s.id
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE c.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $chamado_id]);
    $chamado = $stmt->fetch();

    if (!$chamado) {
        header("Location: fila_chamados.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['flash_erro'] = "Erro ao carregar detalhes: " . $e->getMessage();
    header("Location: fila_chamados.php");
    exit;
}

// ===== BUSCAR RESPOSTAS =====
$respostas = [];
try {
    $sqlRespostas = "SELECT r.*, u.nome AS autor_nome, u.perfil AS autor_perfil 
                     FROM chamados_respostas r 
                     INNER JOIN usuarios u ON r.usuario_id = u.id 
                     WHERE r.chamado_id = :chamado_id 
                     ORDER BY r.criado_em ASC";
    $stmtR = $pdo->prepare($sqlRespostas);
    $stmtR->execute(['chamado_id' => $chamado_id]);
    $respostas = $stmtR->fetchAll();
} catch (PDOException $e) {
    // Tabela pode não existir, ignoramos
}

// ===== FUNÇÃO PARA FORMATAR TEMPO (segundos -> H:i:s) =====
function formatarTempo($segundos) {
    if (is_null($segundos) || $segundos <= 0) {
        return '--';
    }
    $horas = floor($segundos / 3600);
    $minutos = floor(($segundos % 3600) / 60);
    $seg = $segundos % 60;
    return sprintf("%02d:%02d:%02d", $horas, $minutos, $seg);
}

// ===== CALCULAR TEMPO DECORRIDO OU TOTAL =====
$tempoExibicao = '';
if ($chamado['status'] === 'resolvido') {
    // Se já fechado, usa o tempo armazenado (em segundos)
    if (!empty($chamado['tempo_atendimento'])) {
        $tempoExibicao = "Tempo total: " . formatarTempo($chamado['tempo_atendimento']);
    } else {
        $tempoExibicao = "Tempo não registrado";
    }
} else {
    // Em andamento: calcular diferença em segundos usando DateTime com fuso
    $criado = new DateTime($chamado['criado_em']);
    $agora = new DateTime('now');
    $intervalo = $criado->diff($agora);
    $diff = $intervalo->days * 86400 + $intervalo->h * 3600 + $intervalo->i * 60 + $intervalo->s;
    $tempoExibicao = "Em andamento: " . formatarTempo($diff);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamado #<?= $chamado['id'] ?> - Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/painel.php">TI Prefeitura de Borborema</a>
        <div class="d-flex align-items-center text-white">
            <a href="fila_chamados.php" class="btn btn-outline-light btn-sm me-2">Voltar à Fila</a>
            <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">

    <?php if (!empty($mensagemErro)): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($mensagemErro) ?></div>
    <?php endif; ?>

    <?php if (!empty($mensagemSucesso)): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($mensagemSucesso) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-8">
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Protocolo: <?= htmlspecialchars($chamado['protocolo'] ?? '#'.$chamado['id']) ?></h5>
                    <small><?= date('d/m/Y H:i', strtotime($chamado['criado_em'])) ?></small>
                </div>
                <div class="card-body">
                    <h4 class="card-title text-primary"><?= htmlspecialchars($chamado['titulo']) ?></h4>
                    <p class="text-muted small">Categoria: <strong><?= htmlspecialchars($chamado['categoria_nome'] ?? 'Geral') ?></strong></p>
                    <hr>
                    <h6><strong>Descrição do Problema:</strong></h6>
                    <div class="bg-light p-3 rounded border mb-3">
                        <?= nl2br(htmlspecialchars($chamado['descricao'])) ?>
                    </div>
                    
                    <!-- Exibição do tempo corrigida -->
                    <div class="mt-3 p-2 bg-info bg-opacity-10 rounded border border-info">
                        <i class="bi bi-clock"></i> <strong><?= $tempoExibicao ?></strong>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">Histórico de Interações</h6>
                </div>
                <div class="card-body">
                    <?php if (count($respostas) > 0): ?>
                        <?php foreach ($respostas as $resp): ?>
                            <div class="border-bottom pb-2 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong><?= htmlspecialchars($resp['autor_nome']) ?> 
                                        <span class="badge bg-info text-dark ms-1"><?= strtoupper($resp['autor_perfil']) ?></span>
                                    </strong>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($resp['criado_em'])) ?></small>
                                </div>
                                <p class="mb-0 text-secondary"><?= nl2br(htmlspecialchars($resp['mensagem'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Nenhuma resposta registrada.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Responder Chamado</h6>
                </div>
                <div class="card-body">
                    <form action="ver_chamado.php?id=<?= $chamado_id ?>" method="POST">
                        <input type="hidden" name="acao" value="enviar_resposta">
                        <div class="mb-3">
                            <textarea name="resposta" class="form-control" rows="4" required placeholder="Digite sua resposta, orientação ou parecer técnico..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar Resposta</button>
                    </form>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">Situação / Status</h6>
                </div>
                <div class="card-body">
                    <form action="ver_chamado.php?id=<?= $chamado_id ?>" method="POST">
                        <input type="hidden" name="acao" value="atualizar_status">
                        <div class="mb-3">
                            <label for="status" class="form-label">Mudar Status para:</label>
                            <select name="status" id="status" class="form-select fw-bold">
                                <option value="aberto" <?= $chamado['status'] === 'aberto' ? 'selected' : '' ?>>🔵 Aberto</option>
                                <option value="em_andamento" <?= $chamado['status'] === 'em_andamento' ? 'selected' : '' ?>>🟡 Em Andamento</option>
                                <option value="resolvido" <?= $chamado['status'] === 'resolvido' ? 'selected' : '' ?>>🟢 Fechado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark w-100">Atualizar Status</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">Dados do Solicitante</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Nome:</strong> <?= htmlspecialchars($chamado['solicitante_nome']) ?></p>
                    <p class="mb-1"><strong>Setor:</strong> <?= htmlspecialchars($chamado['setor_nome'] ?? 'N/I') ?> (<?= htmlspecialchars($chamado['setor_sigla'] ?? '-') ?>)</p>
                    <p class="mb-1"><strong>E-mail:</strong> <?= htmlspecialchars($chamado['solicitante_email']) ?></p>
                    <p class="mb-0"><strong>WhatsApp:</strong> <?= htmlspecialchars($chamado['solicitante_telefone'] ?? 'Não informado') ?></p>
                </div>
            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>