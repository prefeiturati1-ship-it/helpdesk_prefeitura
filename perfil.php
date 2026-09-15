<?php
session_start();
require_once __DIR__ . '/config/conexao.php';

// 1. SEGURANÇA: Exige login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /helpdesk_prefeitura/account/login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensagemSucesso = '';
$mensagemErro = '';

// 2. PROCESSAR ATUALIZAÇÃO DOS DADOS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome          = trim($_POST['nome'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');
    $senha_atual   = $_POST['senha_atual'] ?? '';
    $nova_senha    = $_POST['nova_senha'] ?? '';
    $confirma_nova = $_POST['confirma_nova'] ?? '';

    if (empty($nome) || empty($email)) {
        $mensagemErro = "Nome e E-mail são obrigatórios.";
    } else {
        try {
            // Verificar se o e-mail já pertence a outro usuário
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
            $stmtCheck->execute(['email' => $email, 'id' => $usuario_id]);

            if ($stmtCheck->fetch()) {
                $mensagemErro = "Este e-mail já está em uso por outra conta.";
            } else {
                // Atualiza Nome, E-mail e Telefone
                $sqlUpdate = "UPDATE usuarios SET nome = :nome, email = :email, telefone = :telefone WHERE id = :id";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    'nome'     => $nome,
                    'email'    => $email,
                    'telefone' => $telefone,
                    'id'       => $usuario_id
                ]);

                // Atualiza o nome na Sessão ativa
                $_SESSION['usuario_nome'] = $nome;

                // Se informou nova senha, vamos validar e trocar
                if (!empty($nova_senha)) {
                    if (empty($senha_atual)) {
                        $mensagemErro = "Informe sua senha atual para autorizar a troca de senha.";
                    } elseif ($nova_senha !== $confirma_nova) {
                        $mensagemErro = "A nova senha e a confirmação não conferem.";
                    } elseif (strlen($nova_senha) < 6) {
                        $mensagemErro = "A nova senha deve ter no mínimo 6 caracteres.";
                    } else {
                        // Busca a senha atual do banco para conferir
                        $stmtPass = $pdo->prepare("SELECT senha FROM usuarios WHERE id = :id");
                        $stmtPass->execute(['id' => $usuario_id]);
                        $userDb = $stmtPass->fetch();

                        if (!password_verify($senha_atual, $userDb['senha'])) {
                            $mensagemErro = "A senha atual informada está incorreta.";
                        } else {
                            // Criptografa e atualiza a nova senha
                            $novaSenhaHash = password_hash($nova_senha, PASSWORD_BCRYPT);
                            $stmtNovaSenha = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
                            $stmtNovaSenha->execute(['senha' => $novaSenhaHash, 'id' => $usuario_id]);
                            
                            $mensagemSucesso = "Dados e senha atualizados com sucesso!";
                        }
                    }
                } else {
                    if (empty($mensagemErro)) {
                        $mensagemSucesso = "Informações perfil atualizadas com sucesso!";
                    }
                }
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao atualizar perfil: " . $e->getMessage();
        }
    }
}

// 3. BUSCAR DADOS ATUAIS DO USUÁRIO
try {
    $stmtUser = $pdo->prepare("SELECT nome, email, telefone, perfil FROM usuarios WHERE id = :id");
    $stmtUser->execute(['id' => $usuario_id]);
    $dadosUsuario = $stmtUser->fetch();
} catch (PDOException $e) {
    $mensagemErro = "Erro ao carregar dados do usuário.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Help Desk Borborema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/account/painel.php">TI Prefeitura de  Borborema</a>
            <div class="d-flex align-items-center text-white">
                <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-outline-light btn-sm me-2">Voltar ao Painel</a>
                <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4" style="max-width: 600px;">

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Meu Perfil</h5>
                <span class="badge bg-secondary"><?= strtoupper($dadosUsuario['perfil'] ?? '') ?></span>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($mensagemErro)): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($mensagemErro) ?></div>
                <?php endif; ?>

                <?php if (!empty($mensagemSucesso)): ?>
                    <div class="alert alert-success py-2"><?= htmlspecialchars($mensagemSucesso) ?></div>
                <?php endif; ?>

                <form action="perfil.php" method="POST">
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label fw-bold">Nome Completo *</label>
                        <input type="text" name="nome" id="nome" class="form-control" required 
                               value="<?= htmlspecialchars($dadosUsuario['nome'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">E-mail *</label>
                        <input type="email" name="email" id="email" class="form-control" required 
                               value="<?= htmlspecialchars($dadosUsuario['email'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label fw-bold">WhatsApp / Telefone</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" 
                               value="<?= htmlspecialchars($dadosUsuario['telefone'] ?? '') ?>" placeholder="(16) 99999-9999">
                    </div>

                    <hr class="my-4">
                    <h6>Alterar Senha <small class="text-muted fw-normal">(deixe em branco se não quiser alterar)</small></h6>

                    <div class="mb-3">
                        <label for="senha_atual" class="form-label">Senha Atual</label>
                        <input type="password" name="senha_atual" id="senha_atual" class="form-control" placeholder="Necessária para trocar a senha">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" name="nova_senha" id="nova_senha" class="form-control" minlength="6" placeholder="Mínimo 6 caracteres">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirma_nova" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" name="confirma_nova" id="confirma_nova" class="form-control" minlength="6" placeholder="Repita a nova senha">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-secondary">Voltar</a>
                        <button type="submit" class="btn btn-primary px-4">Salvar Alterações</button>
                    </div>

                </form>

            </div>
        </div>

    </div>

</body>
</html>