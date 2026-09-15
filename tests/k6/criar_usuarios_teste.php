
<?php

require_once __DIR__ . '/../../config/conexao.php';

$senhaTeste = 'TesteCarga2026!';

// Cria usuários de 01 até 50
for ($i = 1; $i <= 50; $i++) {

    $numero = str_pad($i, 2, '0', STR_PAD_LEFT);

    $nome = "Usuario Teste $numero";
    $email = "teste$numero@teste.com";

    // Verifica se já existe
    $stmt = $pdo->prepare(
        "SELECT id FROM usuarios WHERE email = :email"
    );

    $stmt->execute([
        'email' => $email
    ]);

    if ($stmt->fetch()) {
        echo "Já existe: $email" . PHP_EOL;
        continue;
    }

    // Gera o hash da senha
    $hash = password_hash(
        $senhaTeste,
        PASSWORD_DEFAULT
    );

    // Insere o usuário
    $stmt = $pdo->prepare("
        INSERT INTO usuarios
            (nome, email, senha, perfil, ativo)
        VALUES
            (:nome, :email, :senha, 'usuario', 1)
    ");

    $stmt->execute([
        'nome'  => $nome,
        'email' => $email,
        'senha' => $hash
    ]);

    echo "Criado: $email" . PHP_EOL;
}

echo PHP_EOL;
echo "Usuários de teste finalizados." . PHP_EOL;

