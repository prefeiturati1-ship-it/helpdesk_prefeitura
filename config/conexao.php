<?php
// config/conexao.php

// 1. FUNÇÃO PARA CARREGAR AS VARIÁVEIS DO ARQUIVO .ENV
function carregarEnv($caminho) {
    if (!file_exists($caminho)) return false;
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        // Ignora linhas que são comentários
        if (strpos(trim($linha), '#') === 0) continue;
        
        // Divide a linha no primeiro '=' encontrado
        if (strpos($linha, '=') !== false) {
            list($nome, $valor) = explode('=', $linha, 2);
            $nome = trim($nome);
            $valor = trim($valor);
            
            // Define a variável para o getenv() e $_ENV funcionarem
            putenv("$nome=$valor");
            $_ENV[$nome] = $valor;
        }
    }
}

// Carrega o arquivo .env que está na raiz do projeto (um nível acima de /config)
carregarEnv(__DIR__ . '/../.env');


// 2. SEU CÓDIGO DE CONEXÃO (Ajustado)
$host = getenv('DB_HOST') ?: '://aivencloud.com';
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER') ?: 'avnadmin';
// Ajustado para buscar a variável DB_PASSWORD do seu .env
$pass = getenv('DB_PASSWORD') ?: ''; 
$port = getenv('DB_PORT') ?: '20521';

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
