<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// 1. SEGURANÇA: Apenas Admin e Técnico podem acessar
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_perfil'], ['admin', 'tecnico'])) {
    header("Location: /helpdesk_prefeitura/account/login.php");
    exit;
}

$mensagemSucesso = '';
$mensagemErro = '';

// Variáveis para controle de edição
$idEdicao = null;
$nomeEdicao = '';

// -------------------------------------------------------------
// 2. AÇÕES: EXCLUIR
// -------------------------------------------------------------
if (isset($_GET['excluir'])) {
    $idExcluir = intval($_GET['excluir']);
    try {
        $stmtDel = $pdo->prepare("DELETE FROM categorias WHERE id = :id");
        $stmtDel->execute(['id' => $idExcluir]);
        $mensagemSucesso = "Categoria/Problema excluído com sucesso!";
    } catch (PDOException $e) {
        $mensagemErro = "Não foi possível excluir. É provável que existam chamados associados a esta categoria.";
    }
}

// -------------------------------------------------------------
// 3. AÇÕES: CARREGAR DADOS PARA EDIÇÃO
// -------------------------------------------------------------
if (isset($_GET['editar'])) {
    $idEdicao = intval($_GET['editar']);
    $stmtEdit = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
    $stmtEdit->execute(['id' => $idEdicao]);
    $catEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($catEdit) {
        $nomeEdicao = $catEdit['nome'];
    }
}

// -------------------------------------------------------------
// 4. AÇÕES: SALVAR (CADASTRAR OU ATUALIZAR)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $id   = !empty($_POST['id']) ? intval($_POST['id']) : null;

    if (empty($nome)) {
        $mensagemErro = "Por favor, informe o nome do problema/categoria.";
    } else {
        try {
            if ($id) {
                // Atualizar
                $stmtUp = $pdo->prepare("UPDATE categorias SET nome = :nome WHERE id = :id");
                $stmtUp->execute(['nome' => $nome, 'id' => $id]);
                $mensagemSucesso = "Categoria atualizada com sucesso!";
                $idEdicao = null;
                $nomeEdicao = '';
            } else {
                // Cadastrar novo
                $stmtIns = $pdo->prepare("INSERT INTO categorias (nome) VALUES (:nome)");
                $stmtIns->execute(['nome' => $nome]);
                $mensagemSucesso = "Nova categoria cadastrada com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao salvar no banco de dados: " . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// 5. BUSCAR LISTA DE CATEGORIAS/PROBLEMAS
// -------------------------------------------------------------
try {
    $stmtList = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
    $categorias = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias de Problemas - Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/account/painel.php">TI Prefeitura de Borborema</a>
            <div class="d-flex align-items-center text-white">
                <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-outline-light btn-sm me-2">Voltar ao Painel</a>
                <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        
        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagemErro) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagemSucesso)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagemSucesso) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?= $idEdicao ? '✏️ Editar Categoria' : '➕ Cadastrar Novo Problema' ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="cadastro_problemas.php" method="POST">
                            
                            <?php if ($idEdicao): ?>
                                <input type="hidden" name="id" value="<?= $idEdicao ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="nome" class="form-label fw-bold">Descrição do Problema / Categoria *</label>
                                <input type="text" name="nome" id="nome" class="form-control" required 
                                       placeholder="Ex: Impressora sem papel, Sem internet, etc." 
                                       value="<?= htmlspecialchars($nomeEdicao) ?>">
                            </div>

                            <div class="d-flex justify-content-between">
                                <?php if ($idEdicao): ?>
                                    <a href="cadastro_problemas.php" class="btn btn-outline-secondary">Cancelar</a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-success w-100 <?= $idEdicao ? 'ms-2' : '' ?>">
                                    <?= $idEdicao ? 'Atualizar Categoria' : 'Salvar Categoria' ?>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">📋 Problemas Cadastrados</h5>
                        <span class="badge bg-light text-dark"><?= count($categorias) ?> cadastrados</span>
                    </div>
                    <div class="card-body p-0">
                        
                        <?php if (empty($categorias)): ?>
                            <div class="p-3 text-center text-muted">
                                Nenhuma categoria/problema cadastrado no momento.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">ID</th>
                                            <th>Nome do Problema / Categoria</th>
                                            <th class="text-end" style="width: 150px;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categorias as $cat): ?>
                                            <tr>
                                                <td><code>#<?= $cat['id'] ?></code></td>
                                                <td><strong><?= htmlspecialchars($cat['nome']) ?></strong></td>
                                                <td class="text-end">
                                                    <a href="cadastro_problemas.php?editar=<?= $cat['id'] ?>" class="btn btn-sm btn-warning">
                                                        Editar
                                                    </a>
                                                    <a href="cadastro_problemas.php?excluir=<?= $cat['id'] ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Tem certeza que deseja excluir esta categoria?');">
                                                        Excluir
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>