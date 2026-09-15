<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// 1. SEGURANÇA: Garante que o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /helpdesk_prefeitura/account/login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$chamado_id = (int)($_GET['id'] ?? 0);

// ===== LIMPAR MENSAGENS FLASH =====
$mensagemSucesso = $_SESSION['flash_sucesso'] ?? '';
$mensagemErro    = $_SESSION['flash_erro'] ?? '';
unset($_SESSION['flash_sucesso'], $_SESSION['flash_erro']);

if ($chamado_id <= 0) {
    header("Location: meus_chamados.php");
    exit;
}

// ===== PROCESSAR NOVA MENSAGEM DO USUÁRIO (RÉPLICA) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'enviar_resposta') {
    $resposta = trim($_POST['resposta'] ?? '');

    if (empty($resposta)) {
        $_SESSION['flash_erro'] = "Escreva uma mensagem antes de enviar.";
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
            $_SESSION['flash_sucesso'] = "Mensagem enviada ao suporte com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['flash_erro'] = "Erro ao enviar resposta: " . $e->getMessage();
        }
    }
    header("Location: ver_chamado.php?id=" . $chamado_id);
    exit;
}

// ===== BUSCAR O CHAMADO (GARANTINDO QUE É DO PRÓPRIO USUÁRIO) =====
try {
    $sql = "SELECT 
                c.*, 
                cat.nome AS categoria_nome
            FROM chamados c
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE c.id = :id AND c.usuario_id = :usuario_id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $chamado_id, 'usuario_id' => $usuario_id]);
    $chamado = $stmt->fetch();

    if (!$chamado) {
        header("Location: meus_chamados.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['flash_erro'] = "Erro ao carregar detalhes do chamado.";
    header("Location: meus_chamados.php");
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
    // Tabela pode ainda estar sem registros
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

// ===== EXIBIÇÃO DO TEMPO (APENAS SE FECHADO) =====
$tempoExibicao = '';
if ($chamado['status'] === 'resolvido' && !empty($chamado['tempo_atendimento'])) {
    $tempoExibicao = "Tempo total de atendimento: " . formatarTempo($chamado['tempo_atendimento']);
} elseif ($chamado['status'] === 'resolvido' && empty($chamado['tempo_atendimento'])) {
    $tempoExibicao = "Chamado resolvido, mas tempo não registrado.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar Chamado #<?= $chamado['id'] ?> - Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/painel.php">Help Desk Borborema</a>
            <div class="d-flex align-items-center text-white">
                <a href="meus_chamados.php" class="btn btn-outline-light btn-sm me-2">Voltar aos Meus Chamados</a>
                <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5" style="max-width: 800px;">

        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <?php if (!empty($mensagemSucesso)): ?>
            <div class="alert alert-success py-2"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Protocolo: <?= htmlspecialchars($chamado['protocolo'] ?? '#'.$chamado['id']) ?></h5>
                <div>
                    <?php if ($chamado['status'] === 'aberto'): ?>
                        <span class="badge bg-primary fs-6">Aberto</span>
                    <?php elseif ($chamado['status'] === 'em_andamento'): ?>
                        <span class="badge bg-warning text-dark fs-6">Em Andamento</span>
                    <?php elseif ($chamado['status'] === 'resolvido'): ?>
                        <span class="badge bg-success fs-6">Fechado</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <h4 class="card-title text-primary"><?= htmlspecialchars($chamado['titulo']) ?></h4>
                <p class="text-muted small">
                    Categoria: <strong><?= htmlspecialchars($chamado['categoria_nome'] ?? 'Geral') ?></strong> | 
                    Aberto em: <strong><?= date('d/m/Y H:i', strtotime($chamado['criado_em'])) ?></strong>
                </p>
                <hr>
                <h6><strong>Sua Descrição Inicial:</strong></h6>
                <div class="bg-light p-3 rounded border mb-3">
                    <?= nl2br(htmlspecialchars($chamado['descricao'])) ?>
                </div>
                
                <!-- Exibição do tempo (apenas se fechado) -->
                <?php if (!empty($tempoExibicao)): ?>
                    <div class="mt-3 p-2 bg-info bg-opacity-10 rounded border border-info">
                        <i class="bi bi-clock"></i> <strong><?= $tempoExibicao ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">Respostas da Equipe de TI e Suporte</h6>
            </div>
            <div class="card-body">
                <?php if (count($respostas) > 0): ?>
                    <?php foreach ($respostas as $resp): ?>
                        <div class="p-3 mb-3 rounded border <?= $resp['autor_perfil'] === 'usuario' ? 'bg-light text-end' : 'bg-white border-start border-4 border-primary' ?>">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong><?= htmlspecialchars($resp['autor_nome']) ?> 
                                    <?php if ($resp['autor_perfil'] !== 'usuario'): ?>
                                        <span class="badge bg-primary text-white ms-1">TÉCNICO / ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-white ms-1">VOCÊ</span>
                                    <?php endif; ?>
                                </strong>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($resp['criado_em'])) ?></small>
                            </div>
                            <p class="mb-0 text-dark" style="white-space: pre-line;"><?= htmlspecialchars($resp['mensagem']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3 mb-0">Ainda não há respostas da equipe para esta solicitação. Você será notificado assim que um técnico responder.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($chamado['status'] !== 'resolvido'): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Enviar Mensagem Adicional / Réplica</h6>
                </div>
                <div class="card-body">
                    <form action="ver_chamado.php?id=<?= $chamado_id ?>" method="POST">
                        <input type="hidden" name="acao" value="enviar_resposta">
                        <div class="mb-3">
                            <textarea name="resposta" class="form-control" rows="3" required placeholder="Digite uma dúvida ou informação complementar para o suporte..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary text-center">
                Este chamado já foi encerrado. Caso precise de mais ajuda, por favor <a href="abrir_chamado.php" class="alert-link">abra um novo chamado</a>.
            </div>
        <?php endif; ?>

    </div>

</body>
</html>