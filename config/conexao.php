<?php
// config/conexao.php
error_log('DB_HOST recebido: [' . getenv('DB_HOST') . ']');
/**
 * Carrega variáveis de um arquivo .env (usado apenas no ambiente local/XAMPP).
 * No Render as variáveis vêm do painel Environment, então este arquivo não existe lá.
 */
function carregarEnv($caminho) {
    if (!file_exists($caminho)) {
        return false;
    }

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        // Ignora comentários
        if ($linha === '' || strpos($linha, '#') === 0) {
            continue;
        }

        if (strpos($linha, '=') === false) {
            continue;
        }

        list($nome, $valor) = explode('=', $linha, 2);
        $nome  = trim($nome);
        $valor = trim($valor);

        // Remove aspas simples ou duplas em volta do valor, se houver
        $valor = trim($valor, "\"'");

        putenv("$nome=$valor");
        $_ENV[$nome]    = $valor;
        $_SERVER[$nome] = $valor;
    }

    return true;
}

carregarEnv(__DIR__ . '/../.env');

/**
 * Lê uma variável de ambiente tentando as três fontes possíveis.
 */
function env($chave, $padrao = null) {
    $valor = getenv($chave);

    if ($valor === false || $valor === '') {
        $valor = $_ENV[$chave] ?? ($_SERVER[$chave] ?? null);
    }

    if ($valor === null || $valor === '') {
        return $padrao;
    }

    return trim($valor);
}

// ---------------------------------------------------------------------------
// Credenciais
// ---------------------------------------------------------------------------

$host = env('DB_HOST', 'mysql-a42a1b4-prefeiturati1-756f.d.aivencloud.com');
$db   = env('DB_NAME', 'defaultdb');
$user = env('DB_USER', 'avnadmin');
$pass = env('DB_PASSWORD');
$port = env('DB_PORT', '20521');

// A senha nunca tem valor padrão: precisa vir do ambiente.
if (empty($pass)) {
    die('Erro de configuração: a variável DB_PASSWORD não foi definida no ambiente.');
}

$charset = 'utf8mb4';
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

// ---------------------------------------------------------------------------
// Opções do PDO
// ---------------------------------------------------------------------------

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 10,
];

// A Aiven exige conexão TLS. Se o certificado da CA estiver presente, usamos ele.
$caminhoCa = __DIR__ . '/ca.pem';

if (file_exists($caminhoCa)) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $caminhoCa;
}

// Desliga a checagem do nome no certificado (o host da Aiven costuma divergir).
$options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;

// ---------------------------------------------------------------------------
// Conexão
// ---------------------------------------------------------------------------

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Em produção, não exponha a mensagem crua para o usuário final.
    error_log('Falha na conexão com o banco: ' . $e->getMessage());

    $exibirDetalhes = env('APP_DEBUG') === 'true';

    if ($exibirDetalhes) {
        die('Erro ao conectar com o banco de dados: ' . $e->getMessage());
    }

    die('Não foi possível conectar ao banco de dados. Tente novamente em alguns instantes.');
}