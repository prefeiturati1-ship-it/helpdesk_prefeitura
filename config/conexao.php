<?php
// config/conexao.php

// 1. FUNÇÃO PARA CARREGAR AS VARIÁVEIS DO ARQUIVO .ENV (Apenas localmente)
function carregarEnv($caminho) {
    if (!file_exists($caminho)) return false;
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        if (strpos(trim($linha), '#') === 0) continue;
        
        if (strpos($linha, '=') !== false) {
            list($nome, $valor) = explode('=', $linha, 2);
            $nome = trim($nome);
            $valor = trim($valor);
            
            putenv("$nome=$valor");
            $_ENV[$nome] = $valor;
        }
    }
}

// Carrega o .env se existir (Ambiente local do XAMPP)
carregarEnv(__DIR__ . '/../.env');


// 2. BUSCA AS VARIÁVEIS DIRETAMENTE DO AMBIENTE (Do Render ou do .env injetado acima)
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$port = getenv('DB_PORT');

// Se o Apache falhar em coletar o Host do Render, exibe um aviso claro em vez de quebrar o DNS
if (empty($host)) {
    die("Erro Crítico: As variáveis de ambiente não foram repassadas ao PHP.");
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Permite conexão SSL exigida pela Aiven
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
