<?php

require_once __DIR__ . '/../config/conexao.php';

$migrationsTable = "
    CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

$pdo->exec($migrationsTable);

$migrationsPath = __DIR__ . '/migrations';

$files = glob($migrationsPath . '/*.sql');

if (!$files) {
    echo "Nenhuma migration encontrada.\n";
    exit;
}

sort($files);

foreach ($files as $file) {

    $migration = basename($file);

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM migrations
        WHERE migration = ?
    ");

    $stmt->execute([$migration]);

    $executed = $stmt->fetchColumn();

    if ($executed) {
        echo "[OK] $migration já foi executada.\n";
        continue;
    }

    echo "[EXECUTANDO] $migration...\n";

    try {

        $sql = file_get_contents($file);

        $pdo->beginTransaction();

        $pdo->exec($sql);

        $stmt = $pdo->prepare("
            INSERT INTO migrations (migration)
            VALUES (?)
        ");

        $stmt->execute([$migration]);

        $pdo->commit();

        echo "[SUCESSO] $migration executada.\n";

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo "[ERRO] $migration\n";
        echo $e->getMessage() . "\n";

        exit(1);
    }
}

echo "\nTodas as migrations foram verificadas.\n";