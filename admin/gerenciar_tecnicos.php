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

// ===== BUSCAR SETORES ATIVOS PARA OS MODAIS =====
try {
    $stmtSetores = $pdo->query("SELECT id, nome, sigla FROM secretarias_setores WHERE ativo = 1 ORDER BY nome ASC");
    $setores = $stmtSetores->fetchAll();
} catch (PDOException $e) {
    $setores = [];
}

// ===== CADASTRAR NOVO TÉCNICO =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar_tecnico') {
    $nome           = trim($_POST['nome'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $setor_id       = $_POST['setor_id'] ?? '';
    $whatsapp       = trim($_POST['whatsapp'] ?? '');
    $senha          = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if (empty($nome) || empty($email) || empty($setor_id) || empty($senha)) {
        $mensagemErro = "Preencha todos os campos obrigatórios (*).";
    } elseif ($senha !== $confirmarSenha) {
        $mensagemErro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $mensagemErro = "A senha deve ter no mínimo 6 caracteres.";
    } else {
        try {
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmtCheck->execute(['email' => $email]);
            if ($stmtCheck->fetch()) {
                $mensagemErro = "Este e-mail já está cadastrado no sistema.";
            } else {
                $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                $sqlInsert = "INSERT INTO usuarios (nome, email, senha, telefone, setor_id, perfil, ativo) 
                              VALUES (:nome, :email, :senha, :telefone, :setor_id, 'tecnico', 1)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    'nome'     => $nome,
                    'email'    => $email,
                    'senha'    => $senhaHash,
                    'telefone' => $whatsapp,
                    'setor_id' => $setor_id
                ]);
                $mensagemSucesso = "Técnico cadastrado com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao cadastrar técnico: " . $e->getMessage();
        }
    }
}

// ===== EDITAR TÉCNICO =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_tecnico') {
    $id             = (int)$_POST['id'];
    $nome           = trim($_POST['nome'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $setor_id       = $_POST['setor_id'] ?? '';
    $whatsapp       = trim($_POST['whatsapp'] ?? '');
    $ativo          = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
    $novaSenha      = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if (empty($nome) || empty($email) || empty($setor_id) || $id <= 0) {
        $mensagemErro = "Preencha todos os campos obrigatórios.";
    } elseif (!empty($novaSenha) && $novaSenha !== $confirmarSenha) {
        $mensagemErro = "As senhas não coincidem.";
    } elseif (!empty($novaSenha) && strlen($novaSenha) < 6) {
        $mensagemErro = "A nova senha deve ter no mínimo 6 caracteres.";
    } else {
        try {
            // Verifica duplicidade de e-mail (exceto o próprio)
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
            $stmtCheck->execute(['email' => $email, 'id' => $id]);
            if ($stmtCheck->fetch()) {
                $mensagemErro = "Este e-mail já está cadastrado para outro usuário.";
            } else {
                // Monta o UPDATE dinamicamente
                $sqlUpdate = "UPDATE usuarios SET nome = :nome, email = :email, telefone = :telefone, setor_id = :setor_id, ativo = :ativo";
                $params = [
                    'nome'     => $nome,
                    'email'    => $email,
                    'telefone' => $whatsapp,
                    'setor_id' => $setor_id,
                    'ativo'    => $ativo,
                    'id'       => $id
                ];
                if (!empty($novaSenha)) {
                    $sqlUpdate .= ", senha = :senha";
                    $params['senha'] = password_hash($novaSenha, PASSWORD_BCRYPT);
                }
                $sqlUpdate .= " WHERE id = :id AND perfil = 'tecnico'";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute($params);
                $mensagemSucesso = "Dados do técnico atualizados com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao atualizar técnico: " . $e->getMessage();
        }
    }
}

// ===== EXCLUIR TÉCNICO =====
if (isset($_GET['excluir_tecnico'])) {
    $id = (int)$_GET['excluir_tecnico'];
    try {
        // Verifica se existem chamados atribuídos a este técnico
        $stmtChamados = $pdo->prepare("SELECT COUNT(*) FROM chamados WHERE tecnico_id = :id");
        $stmtChamados->execute(['id' => $id]);
        $totalChamados = $stmtChamados->fetchColumn();

        if ($totalChamados > 0) {
            $mensagemErro = "Não é possível excluir este técnico, pois existem <strong>{$totalChamados}</strong> chamado(s) atribuído(s) a ele. Reatribua os chamados antes de excluir.";
        } else {
            // Verifica também se ele tem respostas (opcional, mas recomendado)
            $stmtRespostas = $pdo->prepare("SELECT COUNT(*) FROM chamados_respostas WHERE usuario_id = :id");
            $stmtRespostas->execute(['id' => $id]);
            $totalRespostas = $stmtRespostas->fetchColumn();
            if ($totalRespostas > 0) {
                $mensagemErro = "Não é possível excluir este técnico, pois ele possui <strong>{$totalRespostas}</strong> resposta(s) em chamados. Remova ou reatribua as respostas primeiro.";
            } else {
                $stmtDelete = $pdo->prepare("DELETE FROM usuarios WHERE id = :id AND perfil = 'tecnico'");
                $stmtDelete->execute(['id' => $id]);
                $mensagemSucesso = "Técnico excluído com sucesso!";
            }
        }
    } catch (PDOException $e) {
        $mensagemErro = "Erro ao excluir técnico: " . $e->getMessage();
    }
}

// ===== LISTAR TÉCNICOS (com contagem de chamados) =====
try {
    $sqlTecnicos = "SELECT u.id, u.nome, u.email, u.telefone, u.ativo, u.criado_em, 
                           s.nome AS setor_nome, s.sigla AS setor_sigla,
                           (SELECT COUNT(*) FROM chamados c WHERE c.tecnico_id = u.id) AS total_chamados
                    FROM usuarios u 
                    LEFT JOIN secretarias_setores s ON u.setor_id = s.id 
                    WHERE u.perfil = 'tecnico' 
                    ORDER BY u.nome ASC";
    $stmtTecnicos = $pdo->query($sqlTecnicos);
    $tecnicos = $stmtTecnicos->fetchAll();
} catch (PDOException $e) {
    $tecnicos = [];
    $mensagemErro = "Erro ao listar técnicos.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Técnicos - TI Prefeitura de Borborema</title>
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
        <h2>Gerenciamento de Técnicos de TI</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNovoTecnico">
            <i class="fas fa-plus"></i> Novo Técnico
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
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Setor</th>
                            <th>WhatsApp</th>
                            <th>Status</th>
                            <th class="text-center">Chamados</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tecnicos) > 0): ?>
                            <?php foreach ($tecnicos as $tec): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($tec['nome']) ?></strong></td>
                                    <td><?= htmlspecialchars($tec['email']) ?></td>
                                    <td><?= htmlspecialchars($tec['setor_nome'] ?? 'Sem setor') ?> (<?= htmlspecialchars($tec['setor_sigla'] ?? '-') ?>)</td>
                                    <td><?= htmlspecialchars($tec['telefone'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($tec['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info text-dark"><?= $tec['total_chamados'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <!-- Botão ATUALIZAR (editar) -->
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarTecnico"
                                                data-id="<?= $tec['id'] ?>"
                                                data-nome="<?= htmlspecialchars($tec['nome']) ?>"
                                                data-email="<?= htmlspecialchars($tec['email']) ?>"
                                                data-setor="<?= $tec['setor_id'] ?? '' ?>"
                                                data-whatsapp="<?= htmlspecialchars($tec['telefone'] ?? '') ?>"
                                                data-ativo="<?= $tec['ativo'] ?>">
                                            <i class="fas fa-edit"></i> Atualizar
                                        </button>
                                        <!-- Botão EXCLUIR -->
                                        <a href="gerenciar_tecnicos.php?excluir_tecnico=<?= $tec['id'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Tem certeza que deseja excluir este técnico? Esta ação não poderá ser desfeita.')">
                                            <i class="fas fa-trash"></i> Excluir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Nenhum técnico cadastrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal CADASTRAR Técnico -->
<div class="modal fade" id="modalNovoTecnico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Cadastrar Novo Técnico de TI</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="gerenciar_tecnicos.php" method="POST">
                <input type="hidden" name="acao" value="cadastrar_tecnico">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Carlos Oliveira">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="email" class="form-control" required placeholder="carlos.ti@borborema.sp.gov.br">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Setor *</label>
                        <select name="setor_id" class="form-select" required>
                            <option value="" selected disabled>Selecione o setor...</option>
                            <?php foreach ($setores as $setor): ?>
                                <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?> (<?= htmlspecialchars($setor['sigla']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-control" placeholder="(16) 99999-9999">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Senha Inicial *</label>
                            <input type="password" name="senha" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmar Senha *</label>
                            <input type="password" name="confirmar_senha" class="form-control" required minlength="6">
                        </div>
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

<!-- Modal EDITAR Técnico -->
<div class="modal fade" id="modalEditarTecnico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Editar Técnico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="gerenciar_tecnicos.php" method="POST">
                <input type="hidden" name="acao" value="editar_tecnico">
                <input type="hidden" name="id" id="editar_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="nome" id="editar_nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="email" id="editar_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Setor *</label>
                        <select name="setor_id" id="editar_setor" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($setores as $setor): ?>
                                <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?> (<?= htmlspecialchars($setor['sigla']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="whatsapp" id="editar_whatsapp" class="form-control" placeholder="(16) 99999-9999">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="ativo" id="editar_ativo" class="form-select">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                    <hr>
                    <p class="text-muted small">Deixe os campos de senha em branco para manter a atual.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nova Senha</label>
                            <input type="password" name="nova_senha" class="form-control" minlength="6" placeholder="Nova senha (opcional)">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmar Nova Senha</label>
                            <input type="password" name="confirmar_senha" class="form-control" placeholder="Confirmar">
                        </div>
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
    // Preenche o modal de edição com os dados do técnico
    const modalEditar = document.getElementById('modalEditarTecnico');
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('editar_id').value = button.getAttribute('data-id');
        document.getElementById('editar_nome').value = button.getAttribute('data-nome');
        document.getElementById('editar_email').value = button.getAttribute('data-email');
        document.getElementById('editar_setor').value = button.getAttribute('data-setor');
        document.getElementById('editar_whatsapp').value = button.getAttribute('data-whatsapp');
        document.getElementById('editar_ativo').value = button.getAttribute('data-ativo');
        // Limpa campos de senha por segurança
        document.querySelector('input[name="nova_senha"]').value = '';
        document.querySelector('input[name="confirmar_senha"]').value = '';
    });
</script>
</body>
</html>