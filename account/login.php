<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';
// Se o usuário já estiver logado, redireciona diretamente para o painel
if (isset($_SESSION['usuario_id'])) {
    header("Location: painel.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!empty($email) && !empty($senha)) {
        // Busca o usuário pelo e-mail
        $stmt = $pdo->prepare("SELECT id, nome, email, senha, perfil, ativo FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        // Verifica se usuário existe, se está ativo e se a senha está correta
        if ($usuario && $usuario['ativo'] && password_verify($senha, $usuario['senha'])) {
            // Regenera o ID da sessão por segurança contra Session Fixation
            session_regenerate_id(true);

            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nome']   = $usuario['nome'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];

            header("Location: painel.php");
            exit;
        } else {
            $erro = "E-mail ou senha incorretos (ou conta inativa).";
        }
    } else {
        $erro = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TI Prefeitura de Borborema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-4">
            <h4 class="fw-bold">Prefeitura de Borborema</h4>
            <p class="text-muted small">Sistema de Help Desk</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger py-2" role="alert">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="exemplo@borborema.sp.gov.br">
            </div>

            <div class="mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <label for="senha" class="form-label mb-0">Senha</label>
        <a href="esqueci_senha.php" class="small text-decoration-none">Esqueceu a senha?</a>
    </div>
    <input type="password" name="senha" id="senha" class="form-control mt-1" required placeholder="******">
</div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button>
        </form>

                

            
        <div class="text-center mt-2">
            <p class="small text-muted mb-0">Não tem uma conta?</p>
            <a href="autocadastro.php" class="small text-decoration-none fw-bold">Cadastre-se aqui</a>
        </div>
    </div>

</body>
</html>