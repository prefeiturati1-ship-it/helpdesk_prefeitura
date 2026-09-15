<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// SEGURANÇA: Somente administradores
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'admin') {
    header("Location: /helpdesk_prefeitura/painel.php");
    exit;
}

$mensagemSucesso = '';
$mensagemErro = '';

// ===== CADASTRAR =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar_setor') {
    $nome  = trim($_POST['nome'] ?? '');
    $sigla = strtoupper(trim($_POST['sigla'] ?? ''));

    if (empty($nome) || empty($sigla)) {
        $mensagemErro = "Preencha todos os campos obrigatórios (*).";
    } else {
        try {
            $stmtCheck = $pdo->prepare("SELECT id FROM secretarias_setores WHERE sigla = :sigla OR nome = :nome");
            $stmtCheck->execute(['sigla' => $sigla, 'nome' => $nome]);
            if ($stmtCheck->fetch()) {
                $mensagemErro = "Já existe um setor/secretaria com este Nome ou Sigla.";
            } else {
                $sqlInsert = "INSERT INTO secretarias_setores (nome, sigla, ativo) VALUES (:nome, :sigla, 1)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute(['nome' => $nome, 'sigla' => $sigla]);
                $mensagemSucesso = "Setor/Secretaria cadastrado com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao cadastrar setor: " . $e->getMessage();
        }
    }
}

// ===== EDITAR =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_setor') {
    $id    = (int)$_POST['id'];
    $nome  = trim($_POST['nome'] ?? '');
    $sigla = strtoupper(trim($_POST['sigla'] ?? ''));
    $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;

    if (empty($nome) || empty($sigla) || $id <= 0) {
        $mensagemErro = "Preencha todos os campos corretamente.";
    } else {
        try {
            // Verifica duplicidade (exceto o próprio registro)
            $stmtCheck = $pdo->prepare("SELECT id FROM secretarias_setores WHERE (sigla = :sigla OR nome = :nome) AND id != :id");
            $stmtCheck->execute(['sigla' => $sigla, 'nome' => $nome, 'id' => $id]);
            if ($stmtCheck->fetch()) {
                $mensagemErro = "Já existe outro setor com este Nome ou Sigla.";
            } else {
                $sqlUpdate = "UPDATE secretarias_setores SET nome = :nome, sigla = :sigla, ativo = :ativo WHERE id = :id";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute(['nome' => $nome, 'sigla' => $sigla, 'ativo' => $ativo, 'id' => $id]);
                $mensagemSucesso = "Setor atualizado com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao atualizar setor: " . $e->getMessage();
        }
    }
}

// ===== EXCLUIR =====
if (isset($_GET['excluir_setor'])) {
    $id = (int)$_GET['excluir_setor'];
    try {
        // Verifica se existem USUÁRIOS vinculados a este setor
        $stmtUsuarios = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE setor_id = :id");
        $stmtUsuarios->execute(['id' => $id]);
        $totalUsuarios = $stmtUsuarios->fetchColumn();

        if ($totalUsuarios > 0) {
            $mensagemErro = "Não é possível excluir este setor, pois existem <strong>{$totalUsuarios}</strong> usuário(s) vinculado(s) a ele. Reassigne ou remova os usuários primeiro.";
        } else {
            $stmtDelete = $pdo->prepare("DELETE FROM secretarias_setores WHERE id = :id");
            $stmtDelete->execute(['id' => $id]);
            $mensagemSucesso = "Setor excluído com sucesso!";
        }
    } catch (PDOException $e) {
        $mensagemErro = "Erro ao excluir setor: " . $e->getMessage();
    }
}

// ===== LISTAR (com contagem de usuários) =====
try {
    $sqlSetores = "SELECT s.*, COUNT(u.id) as total_usuarios
                   FROM secretarias_setores s
                   LEFT JOIN usuarios u ON s.id = u.setor_id
                   GROUP BY s.id
                   ORDER BY s.nome ASC";
    $stmtSetores = $pdo->query($sqlSetores);
    $setores = $stmtSetores->fetchAll();
} catch (PDOException $e) {
    $setores = [];
    $mensagemErro = "Erro ao listar setores.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Setores - TI Prefeitura de Borborema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../account/painel.php">TI Prefeitura de Borborema</a>
        <div class="d-flex align-items-center text-white">
            <a href="../account/painel.php" class="btn btn-outline-light btn-sm me-2">Voltar ao Painel</a>
            <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gerenciamento de Setores e Secretarias</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNovoSetor">
            <i class="fas fa-plus"></i> Novo Setor
        </button>
    </div>

    <?php if (!empty($mensagemErro)): ?>
        <div class="alert alert-danger py-2"><?= $mensagemErro ?></div>
    <?php endif; ?>

    <?php if (!empty($mensagemSucesso)): ?>
        <div class="alert alert-success py-2"><?= $mensagemSucesso ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th># ID</th>
                            <th>Nome do Setor</th>
                            <th>Sigla</th>
                            <th>Status</th>
                            <th class="text-center">Usuários</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($setores) > 0): ?>
                            <?php foreach ($setores as $setor): ?>
                                <tr>
                                    <td><strong>#<?= $setor['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($setor['nome']) ?></td>
                                    <td><span class="badge bg-dark"><?= htmlspecialchars($setor['sigla']) ?></span></td>
                                    <td>
                                        <?php if ($setor['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info text-dark"><?= $setor['total_usuarios'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <!-- Botão ATUALIZAR (editar) -->
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarSetor"
                                                data-id="<?= $setor['id'] ?>"
                                                data-nome="<?= htmlspecialchars($setor['nome']) ?>"
                                                data-sigla="<?= htmlspecialchars($setor['sigla']) ?>"
                                                data-ativo="<?= $setor['ativo'] ?>">
                                            <i class="fas fa-edit"></i> Atualizar
                                        </button>
                                        <!-- Botão EXCLUIR -->
                                        <a href="gerenciar_setores.php?excluir_setor=<?= $setor['id'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Tem certeza que deseja excluir este setor? Esta ação não poderá ser desfeita.')">
                                            <i class="fas fa-trash"></i> Excluir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Nenhum setor cadastrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal CADASTRAR -->
<div class="modal fade" id="modalNovoSetor" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Cadastrar Novo Setor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="gerenciar_setores.php" method="POST">
                <input type="hidden" name="acao" value="cadastrar_setor">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Ex: Secretaria de Meio Ambiente">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sigla *</label>
                        <input type="text" name="sigla" class="form-control" required placeholder="Ex: SEMA" maxlength="20">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal EDITAR (com campo status) -->
<div class="modal fade" id="modalEditarSetor" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Editar Setor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="gerenciar_setores.php" method="POST">
                <input type="hidden" name="acao" value="editar_setor">
                <input type="hidden" name="id" id="editar_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="editar_nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sigla *</label>
                        <input type="text" name="sigla" id="editar_sigla" class="form-control" required maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="ativo" id="editar_ativo" class="form-select">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Preenche o modal de edição com os dados do setor
    const modalEditar = document.getElementById('modalEditarSetor');
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // botão que abriu o modal
        const id = button.getAttribute('data-id');
        const nome = button.getAttribute('data-nome');
        const sigla = button.getAttribute('data-sigla');
        const ativo = button.getAttribute('data-ativo');

        document.getElementById('editar_id').value = id;
        document.getElementById('editar_nome').value = nome;
        document.getElementById('editar_sigla').value = sigla;
        document.getElementById('editar_ativo').value = ativo;
    });
</script>
</body>
</html>