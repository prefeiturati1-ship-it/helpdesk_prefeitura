<?php
// helpdesk_prefeitura/gerar_admin.php
require_once __DIR__ . '/config/conexao.php';

$nome  = 'Administrador TI';
$email = 'admin@borborema.sp.gov.br';
$senha = 'admin123';
$perfil = 'admin';

// Gera o hash compatível exatamente com a sua versão do PHP
$senhaHash = password_hash($senha, PASSWORD_BCRYPT);

try {
    // 1. Garante que existe ao menos um setor cadastrado
    $stmtSetor = $pdo->query("SELECT id FROM secretarias_setores LIMIT 1");
    $setor = $stmtSetor->fetch();
    
    if (!$setor) {
        $pdo->exec("INSERT INTO secretarias_setores (nome, sigla) VALUES ('Secretaria de Administração e TI', 'TI')");
        $setorId = $pdo->lastInsertId();
    } else {
        $setorId = $setor['id'];
    }

    // 2. Verifica se o usuário admin já existe pelo e-mail
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmtCheck->execute(['email' => $email]);
    $existe = $stmtCheck->fetch();

    if ($existe) {
        // Se já existe, atualiza a senha e perfil
        $sqlUpdate = "UPDATE usuarios 
                      SET nome = :nome, senha = :senha, perfil = :perfil, setor_id = :setor_id, ativo = 1 
                      WHERE email = :email";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'nome'     => $nome,
            'senha'    => $senhaHash,
            'perfil'   => $perfil,
            'setor_id' => $setorId,
            'email'    => $email
        ]);
    } else {
        // Se não existe, insere o novo usuário
        $sqlInsert = "INSERT INTO usuarios (nome, email, senha, perfil, setor_id, ativo) 
                      VALUES (:nome, :email, :senha, :perfil, :setor_id, 1)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            'nome'     => $nome,
            'email'    => $email,
            'senha'    => $senhaHash,
            'perfil'   => $perfil,
            'setor_id' => $setorId
        ]);
    }

    echo "<h2 style='color: green;'>✅ Administrador cadastrado/atualizado com sucesso!</h2>";
    echo "<p><b>E-mail:</b> admin@borborema.sp.gov.br</p>";
    echo "<p><b>Senha:</b> admin123</p>";
    echo "<a href='account/login.php'>Clique aqui para ir ao Login</a>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Erro ao criar admin:</h2> " . $e->getMessage();
}