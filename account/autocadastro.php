<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: ../painel.php");
    exit;
}

$mensagemSucesso = '';
$mensagemErro = '';

try {
    $stmtSetores = $pdo->query("SELECT id, nome, sigla FROM secretarias_setores WHERE ativo = 1 ORDER BY nome ASC");
    $setores = $stmtSetores->fetchAll();
} catch (PDOException $e) {
    $setores = [];
    $mensagemErro = "Erro ao carregar lista de setores.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome           = trim($_POST['nome'] ?? '');
    $sobrenome      = trim($_POST['sobrenome'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $setor_id       = $_POST['setor_id'] ?? '';
    $whatsapp       = trim($_POST['whatsapp'] ?? '');
    $senha          = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    // Validação dos campos
    if (empty($nome) || empty($sobrenome) || empty($email) || empty($setor_id) || empty($senha) || empty($confirmarSenha)) {
        $mensagemErro = "Por favor, preencha todos os campos obrigatórios (*).";
    } elseif ($senha !== $confirmarSenha) {
        // Validação da confirmação de senha
        $mensagemErro = "As senhas digitadas não conferem!";
    } elseif (strlen($senha) < 6) {
        $mensagemErro = "A senha deve ter no mínimo 6 caracteres.";
    } else {
        try {
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmtCheck->execute(['email' => $email]);
            
            if ($stmtCheck->fetch()) {
                $mensagemErro = "Este e-mail já está cadastrado no sistema.";
            } else {
                $nomeCompleto = $nome . ' ' . $sobrenome;
                $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

                $sqlInsert = "INSERT INTO usuarios (nome, email, senha, telefone, setor_id, perfil, ativo) 
                              VALUES (:nome, :email, :senha, :telefone, :setor_id, 'usuario', 1)";
                
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    'nome'     => $nomeCompleto,
                    'email'    => $email,
                    'senha'    => $senhaHash,
                    'telefone' => $whatsapp,
                    'setor_id' => $setor_id
                ]);

                $mensagemSucesso = "Cadastro realizado com sucesso! Você já pode fazer login.";
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - TI Prefeitura de Borborema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-4">

    <div class="card shadow p-4" style="max-width: 500px; width: 100%;">
        <div class="text-center mb-4">
            <h4 class="fw-bold">Prefeitura de Borborema</h4>
            <p class="text-muted small">Criar conta no Help Desk</p>
        </div>

        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?= htmlspecialchars($mensagemErro) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagemSucesso)): ?>
            <div class="alert alert-success py-2" role="alert">
                <?= htmlspecialchars($mensagemSucesso) ?>
                <div class="mt-2">
                    <a href="login.php" class="btn btn-sm btn-success w-100">Ir para a tela de Login</a>
                </div>
            </div>
        <?php else: ?>

        <form action="autocadastro.php" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" name="nome" id="nome" class="form-control" required placeholder="João" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="sobrenome" class="form-label">Sobrenome *</label>
                    <input type="text" name="sobrenome" id="sobrenome" class="form-control" required placeholder="Silva" value="<?= htmlspecialchars($_POST['sobrenome'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail *</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="joao@borborema.sp.gov.br" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="setor_id" class="form-label">Setor / Secretaria *</label>
                <select name="setor_id" id="setor_id" class="form-select" required>
                    <option value="" selected disabled>Selecione seu setor...</option>
                    <?php foreach ($setores as $setor): ?>
                        <option value="<?= $setor['id'] ?>" <?= (($_POST['setor_id'] ?? '') == $setor['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($setor['nome']) ?> (<?= htmlspecialchars($setor['sigla']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="whatsapp" class="form-label">Número do WhatsApp</label>
                <input type="text" name="whatsapp" id="whatsapp" class="form-control" placeholder="(16) 99999-9999" value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="senha" class="form-label">Senha *</label>
                    <input type="password" name="senha" id="senha" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required minlength="6" placeholder="Repita a senha">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">Cadastrar</button>
        </form>

        <?php endif; ?>

        <div class="text-center mt-2">
            <p class="small text-muted mb-0">Já tem uma conta?</p>
            <a href="login.php" class="small text-decoration-none fw-bold">Fazer Login</a>
        </div>
    </div>

</body>
</html>