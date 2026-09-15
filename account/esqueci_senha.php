<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Se já estiver logado, vai pro painel
if (isset($_SESSION['usuario_id'])) {
    header("Location: /helpdesk_prefeitura/painel.php");
    exit;
}

$mensagemErro = '';
$mensagemSucesso = '';
$etapa = 1; // 1 = Validar Dados | 2 = Nova Senha

// Recupera dados salvos temporariamente na sessão se já passou da Etapa 1
$usuario_id_reset = $_SESSION['reset_usuario_id'] ?? null;
if ($usuario_id_reset) {
    $etapa = 2;
}

// -------------------------------------------------------------
// ETAPA 1: VALIDAR E-MAIL E TELEFONE
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'validar_telefone') {
    $email    = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    // Remove caracteres especiais do telefone para comparar limpo
    $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);

    if (empty($email) || empty($telefoneLimpo)) {
        $mensagemErro = "Por favor, preencha o e-mail e o telefone cadastrado.";
    } else {
        try {
            // Busca usuário pelo e-mail
            $stmt = $pdo->prepare("SELECT id, telefone FROM usuarios WHERE email = :email AND ativo = 1");
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                // Compara apenas os números do telefone salvo no banco
                $telefoneBancoLimpo = preg_replace('/[^0-9]/', '', $usuario['telefone'] ?? '');

                if (!empty($telefoneBancoLimpo) && $telefoneBancoLimpo === $telefoneLimpo) {
                    // Validação OK! Salva permissão temporária na sessão e avança para a Etapa 2
                    $_SESSION['reset_usuario_id'] = $usuario['id'];
                    $etapa = 2;
                } else {
                    $mensagemErro = "O telefone informado não confere com o cadastrado nesta conta.";
                }
            } else {
                $mensagemErro = "Nenhuma conta ativa foi encontrada com este e-mail.";
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao validar informações: " . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// ETAPA 2: GRAVAR A NOVA SENHA
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha') {
    $nova_senha    = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (!$usuario_id_reset) {
        $mensagemErro = "Sessão expirada. Recomece o processo.";
        $etapa = 1;
    } elseif (empty($nova_senha) || empty($confirma_senha)) {
        $mensagemErro = "Preencha os campos de senha.";
    } elseif ($nova_senha !== $confirma_senha) {
        $mensagemErro = "As senhas não conferem. Digite novamente.";
    } elseif (strlen($nova_senha) < 6) {
        $mensagemErro = "A nova senha deve ter no mínimo 6 caracteres.";
    } else {
        try {
            // Criptografa a nova senha
            $novaSenhaHash = password_hash($nova_senha, PASSWORD_BCRYPT);

            $stmtUpdate = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
            $stmtUpdate->execute([
                'senha' => $novaSenhaHash,
                'id'    => $usuario_id_reset
            ]);

            // Limpa a chave da sessão
            unset($_SESSION['reset_usuario_id']);
            
            $mensagemSucesso = "Senha alterada com sucesso! Você já pode fazer login.";
            $etapa = 3; // Etapa de Conclusão
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao atualizar senha: " . $e->getMessage();
        }
    }
}

// Botão para cancelar / recomeçar
if (isset($_GET['cancelar'])) {
    unset($_SESSION['reset_usuario_id']);
    header("Location: esqueci_senha.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow p-4" style="max-width: 420px; width: 100%;">
        <div class="text-center mb-3">
            <h4 class="fw-bold">Recuperar Senha</h4>
            <p class="text-muted small mb-0">Help Desk - Prefeitura de Borborema</p>
        </div>

        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <?php if ($etapa === 1): ?>
            <form action="esqueci_senha.php" method="POST">
                <input type="hidden" name="acao" value="validar_telefone">

                <p class="small text-muted">Informe o e-mail da sua conta e o telefone/WhatsApp cadastrado para confirmar sua identidade.</p>

                <div class="mb-3">
                    <label for="email" class="form-label fw-bold">E-mail da Conta *</label>
                    <input type="email" name="email" id="email" class="form-control" required placeholder="exemplo@borborema.sp.gov.br">
                </div>

                <div class="mb-3">
                    <label for="telefone" class="form-label fw-bold">Telefone / WhatsApp Cadastrado *</label>
                    <input type="text" name="telefone" id="telefone" class="form-control" required placeholder="(16) 99999-9999">
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Validar e Avançar</button>
            </form>

        <?php elseif ($etapa === 2): ?>
            <div class="alert alert-success py-2 small">
                ✅ Telefone validado com sucesso! Digite sua nova senha abaixo.
            </div>

            <form action="esqueci_senha.php" method="POST">
                <input type="hidden" name="acao" value="alterar_senha">

                <div class="mb-3">
                    <label for="nova_senha" class="form-label fw-bold">Nova Senha *</label>
                    <input type="password" name="nova_senha" id="nova_senha" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
                </div>

                <div class="mb-3">
                    <label for="confirma_senha" class="form-label fw-bold">Confirmar Nova Senha *</label>
                    <input type="password" name="confirma_senha" id="confirma_senha" class="form-control" required minlength="6" placeholder="Repita a nova senha">
                </div>

                <button type="submit" class="btn btn-success w-100 mb-2">Salvar Nova Senha</button>
                <a href="esqueci_senha.php?cancelar=1" class="btn btn-outline-secondary w-100 btn-sm">Cancelar</a>
            </form>

        <?php else: ?>
            <div class="alert alert-success py-3 text-center">
                <h5 class="mb-2">✅ Pronto!</h5>
                <p class="mb-0 small"><?= htmlspecialchars($mensagemSucesso) ?></p>
            </div>
            <a href="login.php" class="btn btn-primary w-100">Ir para a Tela de Login</a>
        <?php endif; ?>

        <?php if ($etapa !== 3): ?>
            <div class="text-center mt-3 border-top pt-2">
                <a href="login.php" class="small text-decoration-none">← Voltar para o Login</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>