<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit('Acesso negado');
}

$chamado_id = (int)($_GET['chamado_id'] ?? 0);
if ($chamado_id <= 0) {
    http_response_code(400);
    exit('ID inválido');
}

try {
    $sqlRespostas = "SELECT r.*, u.nome AS autor_nome, u.perfil AS autor_perfil 
                     FROM chamados_respostas r 
                     INNER JOIN usuarios u ON r.usuario_id = u.id 
                     WHERE r.chamado_id = :chamado_id 
                     ORDER BY r.criado_em ASC";
    $stmtR = $pdo->prepare($sqlRespostas);
    $stmtR->execute(['chamado_id' => $chamado_id]);
    $respostas = $stmtR->fetchAll();

    if (count($respostas) > 0): ?>
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
    <?php endif;
} catch (PDOException $e) {
    echo '<p class="text-danger small mb-0">Erro ao carregar respostas.</p>';
}
?>