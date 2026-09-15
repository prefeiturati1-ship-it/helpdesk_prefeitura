<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /helpdesk_prefeitura/account/login.php');
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$sql = "
    SELECT h.id, h.dispositivo_id, h.alterado_por, h.criado_em, d.hostname
    FROM dispositivos_hardware_original h
    LEFT JOIN dispositivos d ON d.id = h.dispositivo_id
    WHERE d.id IS NOT NULL
      AND TRIM(COALESCE(d.hostname, '')) <> ''
      AND TRIM(COALESCE(h.alterado_por, '')) <> ''
    ORDER BY h.criado_em DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$historicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nomesPcs = [];
foreach ($historicos as $item) {
    $nome = trim($item['hostname'] ?? '');
    if ($nome !== '' && !in_array($nome, $nomesPcs, true)) {
        $nomesPcs[] = $nome;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de PCs modificados</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans">
    <div class="max-w-6xl mx-auto px-6 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-white">Histórico de PCs modificados</h1>
            <p class="text-sm text-slate-400 mt-1">Lista dos computadores com hardware recalibrado ou aprovado.</p>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 mb-6 shadow-lg">
            <div class="flex items-center gap-2 mb-3">
                <i class="fa-solid fa-desktop text-blue-400"></i>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-300">PCs da lista</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php if (!empty($nomesPcs)): ?>
                    <?php foreach ($nomesPcs as $nome): ?>
                        <span class="px-3 py-1.5 rounded-full text-sm border border-blue-500/30 bg-blue-500/10 text-blue-300">
                            <?= htmlspecialchars($nome) ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-sm text-slate-500">Nenhum PC encontrado.</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-lg">
            <?php if (empty($historicos)): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-600 mb-3"></i>
                    <p class="text-slate-400">Nenhum histórico de modificação encontrado.</p>
                </div>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($historicos as $item): ?>
                        <?php $hostname = trim($item['hostname'] ?? ''); $alteradoPor = trim($item['alterado_por'] ?? ''); if ($hostname === '' || $alteradoPor === '') { continue; } ?>
                        <li class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border border-slate-800 rounded-xl p-4 bg-slate-950/70">
                            <div>
                                <p class="font-semibold text-white">
                                    <?= htmlspecialchars($hostname) ?>
                                </p>
                                <p class="text-sm text-slate-400 mt-1">
                                    Modificado por: <span class="text-slate-200"><?= htmlspecialchars($alteradoPor) ?></span>
                                </p>
                            </div>
                            <div class="text-sm text-slate-400 md:text-right">
                                <p class="font-medium text-slate-300">Data da modificação</p>
                                <p><?= date('d/m/Y H:i', strtotime($item['criado_em'])) ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
