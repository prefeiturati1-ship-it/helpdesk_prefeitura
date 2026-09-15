<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /helpdesk_prefeitura/account/login.php');
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$sqlHistoricos = "
    SELECT h.id, h.dispositivo_id, h.alterado_por, h.criado_em, d.hostname
    FROM dispositivos_hardware_original h
    LEFT JOIN dispositivos d ON d.id = h.dispositivo_id
    WHERE d.id IS NOT NULL
      AND TRIM(COALESCE(d.hostname, '')) <> ''
      AND TRIM(COALESCE(h.alterado_por, '')) <> ''
    ORDER BY h.criado_em DESC
";

$stmtHistoricos = $pdo->prepare($sqlHistoricos);
$stmtHistoricos->execute();
$historicos = $stmtHistoricos->fetchAll(PDO::FETCH_ASSOC);

$sqlExcluidos = "
    SELECT
        id,
        nome_computador,
        sistema_operacional,
        setor_nome,
        tecnico_nome,
        excluido_em
    FROM computadores_excluidos
    ORDER BY excluido_em DESC
";

$stmtExcluidos = $pdo->prepare($sqlExcluidos);
$stmtExcluidos->execute();
$computadoresExcluidos = $stmtExcluidos->fetchAll(PDO::FETCH_ASSOC);
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
            <div class="flex items-center gap-2 mb-4">
                <i class="fa-solid fa-desktop text-blue-400"></i>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-300">Computadores modificados</h2>
            </div>

            <input type="text" placeholder="Buscar por nome do PC..." class="w-full md:w-80 mb-4 px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-200" onkeyup="filtrarLista(this.value, 'lista-modificados')">

            <?php if (empty($historicos)): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-600 mb-3"></i>
                    <p class="text-slate-400">Nenhum histórico de modificação encontrado.</p>
                </div>
            <?php else: ?>
                <ul id="lista-modificados" class="space-y-3">
                    <?php foreach ($historicos as $item): ?>
                        <?php $hostname = trim($item['hostname'] ?? ''); $alteradoPor = trim($item['alterado_por'] ?? ''); if ($hostname === '' || $alteradoPor === '') { continue; } ?>
                        <li class="item-lista flex flex-col md:flex-row md:items-center md:justify-between gap-3 border border-slate-800 rounded-xl p-4 bg-slate-950/70" data-nome="<?= strtolower(htmlspecialchars($hostname, ENT_QUOTES, 'UTF-8')) ?>">
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

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-lg">
            <div class="flex items-center gap-2 mb-4">
                <i class="fa-solid fa-trash text-rose-400"></i>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-300">Computadores excluídos</h2>
            </div>

            <input type="text" placeholder="Buscar por nome do PC..." class="w-full md:w-80 mb-4 px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-200" onkeyup="filtrarTabela(this.value, 'tabela-excluidos')">

            <?php if (empty($computadoresExcluidos)): ?>
                <div class="text-center py-10">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-600 mb-3"></i>
                    <p class="text-slate-400">Nenhum computador excluído encontrado.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table id="tabela-excluidos" class="min-w-full text-sm text-left">
                        <thead class="bg-slate-950/80 text-slate-300">
                            <tr>
                                <th class="px-3 py-2 font-semibold">Nome do computador</th>
                                <th class="px-3 py-2 font-semibold">Sistema operacional</th>
                                <th class="px-3 py-2 font-semibold">Setor</th>
                                <th class="px-3 py-2 font-semibold">Excluído por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($computadoresExcluidos as $item): ?>
                                <tr class="linha-tabela border-t border-slate-800" data-nome="<?= strtolower(htmlspecialchars($item['nome_computador'] ?? '-', ENT_QUOTES, 'UTF-8')) ?>">
                                    <td class="px-3 py-3 text-white">
                                        <?= htmlspecialchars($item['nome_computador'] ?? '-') ?>
                                    </td>
                                    <td class="px-3 py-3 text-slate-300">
                                        <?= htmlspecialchars($item['sistema_operacional'] ?? '-') ?>
                                    </td>
                                    <td class="px-3 py-3 text-slate-300">
                                        <?= htmlspecialchars($item['setor_nome'] ?? '-') ?>
                                    </td>
                                    <td class="px-3 py-3 text-slate-300">
                                        <?= htmlspecialchars($item['tecnico_nome'] ?? '-') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filtrarLista(valor, idLista) {
            const termo = valor.toLowerCase();
            const lista = document.getElementById(idLista);
            if (!lista) return;

            const itens = lista.querySelectorAll('.item-lista');
            itens.forEach(item => {
                const nome = (item.getAttribute('data-nome') || '').toLowerCase();
                item.style.display = nome.includes(termo) ? '' : 'none';
            });
        }

        function filtrarTabela(valor, idTabela) {
            const termo = valor.toLowerCase();
            const tabela = document.getElementById(idTabela);
            if (!tabela) return;

            const linhas = tabela.querySelectorAll('.linha-tabela');
            linhas.forEach(linha => {
                const nome = (linha.getAttribute('data-nome') || '').toLowerCase();
                linha.style.display = nome.includes(termo) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
