<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// 1. VERIFICAÇÃO DE SEGURANÇA: Apenas Admin e Técnico têm permissão
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_perfil'], ['admin', 'tecnico'])) {
    header("Location: /helpdesk_prefeitura/account/login.php");
    exit;
}

$mensagemSucesso = '';
$mensagemErro = '';

// Variáveis para controle do modo de Edição
$idEdicao       = null;
$nomeEdicao     = '';
$emailEdicao    = '';
$telefoneEdicao = '';
$setorEdicao    = '';

// -------------------------------------------------------------
/// 2. AÇÃO: EXCLUIR USUÁRIO
if (isset($_GET['excluir'])) {
    $idExcluir = intval($_GET['excluir']);
    
    // ID 1 é a conta protegida do Chefe de TI
    if ($idExcluir === 1) {
        $mensagemErro = "🚫 AÇÃO BLOQUEADA: A conta principal do Chefe do Setor de TI não pode ser excluída!";
    } elseif ($idExcluir === (int)$_SESSION['usuario_id']) {
        $mensagemErro = "Você não pode excluir a sua própria conta ativa!";
    } else {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmtDel->execute(['id' => $idExcluir]);
            $mensagemSucesso = "Usuário excluído com sucesso!";
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao excluir: Não é possível remover um usuário que possui chamados vinculados.";
        }
    }
}
// -------------------------------------------------------------
// 3. AÇÃO: CARREGAR DADOS PARA EDIÇÃO
// -------------------------------------------------------------
if (isset($_GET['editar'])) {
    $idEdicao = intval($_GET['editar']);
    $stmtEdit = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmtEdit->execute(['id' => $idEdicao]);
    $userEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($userEdit) {
        $nomeEdicao     = $userEdit['nome'];
        $emailEdicao    = $userEdit['email'];
        $telefoneEdicao = $userEdit['telefone'];
        $setorEdicao    = $userEdit['setor_id'];
    }
}

// -------------------------------------------------------------
// 4. PROCESSAMENTO DO FORMULÁRIO (SALVAR / ATUALIZAR)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha    = $_POST['senha'] ?? '';
    $setor_id = !empty($_POST['setor_id']) ? intval($_POST['setor_id']) : null;
    $perfil   = $_POST['perfil'] ?? 'usuario';

    if (empty($nome) || empty($email) || (empty($id) && empty($senha))) {
        $mensagemErro = "Preencha todos os campos obrigatórios (*).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagemErro = "Formato de e-mail inválido.";
    } else {
        try {
            if ($id) {
                // --- MODO ATUALIZAR USUÁRIO EXISTENTE ---
                // Verifica se o e-mail pertence a outro usuário
                $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
                $stmtCheck->execute(['email' => $email, 'id' => $id]);

                if ($stmtCheck->fetch()) {
                    $mensagemErro = "Este e-mail já está em uso por outro usuário.";
                } else {
                    if (!empty($senha)) {
                        // Se preencheu a senha, atualiza a senha criptografada
                        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                        $sqlUpdate = "UPDATE usuarios SET nome = :nome, email = :email, telefone = :telefone, setor_id = :setor_id, senha = :senha WHERE id = :id";
                        $params = ['nome' => $nome, 'email' => $email, 'telefone' => $telefone, 'setor_id' => $setor_id, 'senha' => $senhaHash, 'id' => $id];
                    } else {
                        // Mantém a senha antiga
                        $sqlUpdate = "UPDATE usuarios SET nome = :nome, email = :email, telefone = :telefone, setor_id = :setor_id WHERE id = :id";
                        $params = ['nome' => $nome, 'email' => $email, 'telefone' => $telefone, 'setor_id' => $setor_id, 'id' => $id];
                    }

                    $stmtUp = $pdo->prepare($sqlUpdate);
                    $stmtUp->execute($params);

                    $mensagemSucesso = "Usuário '$nome' atualizado com sucesso!";
                    // Limpa variáveis de edição
                    $idEdicao = null; $nomeEdicao = ''; $emailEdicao = ''; $telefoneEdicao = ''; $setorEdicao = '';
                }

            } else {
                // --- MODO INSERIR NOVO USUÁRIO ---
                $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
                $stmtCheck->execute(['email' => $email]);

                if ($stmtCheck->fetch()) {
                    $mensagemErro = "Este e-mail já está cadastrado no sistema.";
                } else {
                    $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO usuarios (nome, email, telefone, senha, perfil, setor_id, ativo, criado_em) 
                            VALUES (:nome, :email, :telefone, :senha, :perfil, :setor_id, 1, NOW())";
                    
                    $stmtInsert = $pdo->prepare($sql);
                    $stmtInsert->execute([
                        'nome'     => $nome,
                        'email'    => $email,
                        'telefone' => $telefone,
                        'senha'    => $senhaHash,
                        'perfil'   => $perfil,
                        'setor_id' => $setor_id
                    ]);

                    $mensagemSucesso = "Usuário '$nome' cadastrado com sucesso!";
                }
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro na operação: " . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// 5. CONSULTAS DE DADOS (SETORES E USUÁRIOS)
// -------------------------------------------------------------
try {
    // Busca setores para o select
    $stmtSetores = $pdo->query("SELECT id, nome FROM secretarias_setores ORDER BY nome ASC");
    $setores = $stmtSetores->fetchAll(PDO::FETCH_ASSOC);
    
    /*
    // Busca todos os usuários cadastrados com o nome do seu respectivo setor
    $sqlUsuarios = "SELECT u.id, u.nome, u.email, u.telefone, u.perfil, s.nome AS setor_nome 
                    FROM usuarios u 
                    LEFT JOIN secretarias_setores s ON u.setor_id = s.id 
                    ORDER BY u.id DESC";
    $stmtUsuarios = $pdo->query($sqlUsuarios);
    $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);*/
    
    
    // Busca os usuários ignorando o ID 1 (Chefe de TI)
            $sqlUsuarios = "SELECT u.id, u.nome, u.email, u.telefone, u.perfil, s.nome AS setor_nome 
                FROM usuarios u 
                LEFT JOIN secretarias_setores s ON u.setor_id = s.id 
                WHERE u.id != 1
                ORDER BY u.id DESC";

                $stmtUsuarios = $pdo->query($sqlUsuarios);
                $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

                


} catch (PDOException $e) {
    $setores = [];
    $usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/account/painel.php">TI Prefeitura de Borborema</a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">Olá, <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong> (<?= ucfirst($_SESSION['usuario_perfil']) ?>)</span>
                <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-outline-light btn-sm me-2">Voltar ao Painel</a>
                <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        
        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger py-2 alert-dismissible fade show"><?= htmlspecialchars($mensagemErro) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($mensagemSucesso)): ?>
            <div class="alert alert-success py-2 alert-dismissible fade show"><?= htmlspecialchars($mensagemSucesso) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= $idEdicao ? '✏️ Editar Usuário' : '👤 Cadastrar Novo Usuário / Servidor' ?></h5>
                <span class="badge bg-light text-primary">Área Restrita</span>
            </div>

            <div class="card-body p-4">
                <form action="cadastro_usuario.php" method="POST">
                    <input type="hidden" name="perfil" value="usuario">
                    <?php if ($idEdicao): ?>
                        <input type="hidden" name="id" value="<?= $idEdicao ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label fw-bold">Nome Completo *</label>
                            <input type="text" name="nome" id="nome" class="form-control" required placeholder="Ex: João da Silva" value="<?= htmlspecialchars($nomeEdicao) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-bold">E-mail *</label>
                            <input type="email" name="email" id="email" class="form-control" required placeholder="joao@borborema.sp.gov.br" value="<?= htmlspecialchars($emailEdicao) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="telefone" class="form-label fw-bold">Telefone / WhatsApp</label>
                            <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(16) 99999-9999" value="<?= htmlspecialchars($telefoneEdicao) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="senha" class="form-label fw-bold">Senha <?= $idEdicao ? '(Deixe em branco para não alterar)' : '*' ?></label>
                            <input type="password" name="senha" id="senha" class="form-control" <?= $idEdicao ? '' : 'required' ?> placeholder="******">
                        </div>

                        <div class="col-md-12">
                            <label for="setor_id" class="form-label fw-bold">Setor / Secretaria *</label>
                            <select name="setor_id" id="setor_id" class="form-select" required>
                                <option value="">-- Selecione o Setor --</option>
                                <?php foreach ($setores as $setor): ?>
                                    <option value="<?= $setor['id'] ?>" <?= $setorEdicao == $setor['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($setor['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <?php if ($idEdicao): ?>
                            <a href="cadastro_usuario.php" class="btn btn-outline-secondary">Cancelar Edição</a>
                        <?php else: ?>
                            <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-success px-4">
                            <?= $idEdicao ? 'Atualizar Usuário' : 'Cadastrar Usuário' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">📋 Lista de Usuários Cadastrados</h5>
                <span class="badge bg-light text-dark"><?= count($usuarios) ?> usuários</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($usuarios)): ?>
                    <div class="p-4 text-center text-muted">Nenhum usuário cadastrado até o momento.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Telefone</th>
                                    <th>Setor / Secretaria</th>
                                    <th>Perfil</th>
                                    <th class="text-end" style="width: 160px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td><code>#<?= $user['id'] ?></code></td>
                                        <td><strong><?= htmlspecialchars($user['nome']) ?></strong></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['telefone'] ?: '-') ?></td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                <?= htmlspecialchars($user['setor_nome'] ?: 'Sem Setor') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $user['perfil'] === 'admin' ? 'danger' : ($user['perfil'] === 'tecnico' ? 'warning text-dark' : 'secondary') ?>">
                                                <?= ucfirst($user['perfil']) ?>
                                            </span>
                                        </td>
                                  <td class="text-end">
    <a href="cadastro_usuario.php?editar=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
    
    <?php if ((int)$user['id'] === 1): ?>
        <span class="badge bg-secondary p-2" title="Esta conta é protegida pelo sistema">🔒 Protegido</span>
    <?php else: ?>
        <a href="cadastro_usuario.php?excluir=<?= $user['id'] ?>" 
           class="btn btn-sm btn-danger" 
           onclick="return confirm('Tem certeza que deseja excluir o usuário <?= htmlspecialchars($user['nome']) ?>?');">
            Excluir
        </a>
    <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>