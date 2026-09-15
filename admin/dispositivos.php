<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /helpdesk_prefeitura/account/login.php');
    exit;
}

$usuarioNome = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? $_SESSION['usuario'] ?? 'Usuário não identificado';
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

// 1. Corrige o fuso horário
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../config/conexao.php';


// Ação para recadastrar o hardware atual como o novo padrão/original
if (isset($_GET['aprovar_hardware'])) {
    $idAprovar = (int)$_GET['aprovar_hardware'];
    if ($idAprovar > 0) {
        try {
            // Busca os dados atuais da máquina
            $stmtDev = $pdo->prepare("SELECT * FROM dispositivos WHERE id = :id LIMIT 1");
            $stmtDev->execute(['id' => $idAprovar]);
            $dev = $stmtDev->fetch(PDO::FETCH_ASSOC);

            if ($dev) {
                $colunaExiste = $pdo->query("SHOW COLUMNS FROM dispositivos_hardware_original LIKE 'alterado_por'")->fetch();
                if (!$colunaExiste) {
                    $pdo->exec("ALTER TABLE dispositivos_hardware_original ADD COLUMN alterado_por VARCHAR(255) NULL DEFAULT NULL AFTER dispositivo_id");
                }

                // Atualiza a baseline com a configuração atual
                $stmtUpdateOrig = $pdo->prepare("
                    INSERT INTO dispositivos_hardware_original 
                        (dispositivo_id, alterado_por, cpu_modelo, ram_total_mb, ram_pentes, gpu_modelo, disco_total_gb, discos, criado_em)
                    VALUES 
                        (:dispositivo_id, :alterado_por, :cpu_modelo, :ram_total_mb, :ram_pentes, :gpu_modelo, :disco_total_gb, :discos, NOW())
                    ON DUPLICATE KEY UPDATE 
                        alterado_por    = VALUES(alterado_por),
                        cpu_modelo      = VALUES(cpu_modelo),
                        ram_total_mb    = VALUES(ram_total_mb),
                        ram_pentes      = VALUES(ram_pentes),
                        gpu_modelo      = VALUES(gpu_modelo),
                        disco_total_gb  = VALUES(disco_total_gb),
                        discos          = VALUES(discos),
                        criado_em       = NOW()
                ");

                $stmtUpdateOrig->execute([
                    ':dispositivo_id' => $dev['id'],
                    ':alterado_por'   => $usuarioNome,
                    ':cpu_modelo'     => $dev['cpu_modelo'],
                    ':ram_total_mb'   => $dev['ram_total_mb'],
                    ':ram_pentes'     => $dev['ram_pentes'],
                    ':gpu_modelo'     => $dev['gpu_modelo'],
                    ':disco_total_gb' => $dev['disco_total_gb'],
                    ':discos'         => $dev['discos']
                ]);

                // Limpa os alertas antigos de fraude da telemetria
                $stmtClearTel = $pdo->prepare("UPDATE dispositivos_telemetria SET alertas = '[]' WHERE dispositivo_id = :id");
                $stmtClearTel->execute(['id' => $dev['id']]);
            }

            header('Location: dispositivos.php');
            exit;
        } catch (PDOException $e) {
            $mensagemErro = 'Erro ao aprovar novo hardware: ' . $e->getMessage();
        }
    }
}



if (isset($_GET['excluir_dispositivo'])) {
    $idExcluir = (int)$_GET['excluir_dispositivo'];

    if ($idExcluir > 0) {
        try {
            $sqlCreateTabela = "
                CREATE TABLE IF NOT EXISTS computadores_excluidos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    computador_id_original INT NULL COMMENT 'ID que o PC tinha na tabela principal',
                    nome_computador VARCHAR(100) NOT NULL,
                    numero_patrimonio VARCHAR(50) NULL,
                    ip VARCHAR(45) NULL,
                    mac_address VARCHAR(17) NULL,
                    sistema_operacional VARCHAR(100) NULL,
                    setor_nome VARCHAR(100) NULL COMMENT 'Setor onde o PC estava instalado',
                    tecnico_id INT NULL COMMENT 'ID do técnico/admin que realizou a exclusão',
                    tecnico_nome VARCHAR(150) NOT NULL COMMENT 'Nome registrado no momento da exclusão',
                    motivo_exclusao TEXT NULL COMMENT 'Justificativa para remover o PC (ex: Sucateado, Doador de peças)',
                    excluido_em DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $pdo->exec($sqlCreateTabela);

            $stmtDev = $pdo->prepare("SELECT d.*, s.nome AS setor_nome FROM dispositivos d LEFT JOIN secretarias_setores s ON d.setor_id = s.id WHERE d.id = :id LIMIT 1");
            $stmtDev->execute(['id' => $idExcluir]);
            $dev = $stmtDev->fetch(PDO::FETCH_ASSOC);

            if ($dev) {
                $pdo->beginTransaction();

                $sqlInsertExcluido = "
                    INSERT INTO computadores_excluidos (
                        computador_id_original, nome_computador, numero_patrimonio, ip, mac_address,
                        sistema_operacional, setor_nome, tecnico_id, tecnico_nome, motivo_exclusao
                    ) VALUES (
                        :computador_id_original, :nome_computador, :numero_patrimonio, :ip, :mac_address,
                        :sistema_operacional, :setor_nome, :tecnico_id, :tecnico_nome, :motivo_exclusao
                    )
                ";
                $stmtInsertExcluido = $pdo->prepare($sqlInsertExcluido);

                $stmtInsertExcluido->execute([
                    ':computador_id_original' => (int)$dev['id'],
                    ':nome_computador'        => $dev['hostname'] ?? 'Sem nome',
                    ':numero_patrimonio'     => null,
                    ':ip'                     => $dev['ip_local'] ?? null,
                    ':mac_address'            => $dev['mac_address'] ?? null,
                    ':sistema_operacional'    => $dev['os_nome'] ?? null,
                    ':setor_nome'             => $dev['setor_nome'] ?? null,
                    ':tecnico_id'             => $usuarioId,
                    ':tecnico_nome'           => $usuarioNome,
                    ':motivo_exclusao'        => 'Exclusão manual',
                ]);

                $stmtTelemetria = $pdo->prepare("DELETE FROM dispositivos_telemetria WHERE dispositivo_id = :id");
                $stmtTelemetria->execute(['id' => $idExcluir]);

                $stmtDispositivo = $pdo->prepare("DELETE FROM dispositivos WHERE id = :id");
                $stmtDispositivo->execute(['id' => $idExcluir]);

                $pdo->commit();
            } else {
                $mensagemErro = 'Dispositivo não encontrado para exclusão.';
            }

            header('Location: dispositivos.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mensagemErro = 'Erro ao excluir dispositivo: ' . $e->getMessage();
        }
    }
}

try {
    // 2. Consulta MySQL incluindo d.discos da tabela dispositivos
    $sql = "
        SELECT 
            d.id,
            d.hostname,
            d.ip_local,
            d.mac_address,
            d.os_nome,
            d.cpu_modelo,
            d.cpu_cores,
            d.cpu_threads,
            d.cpu_clock_mhz,
            d.cpu_socket,
            d.mobo_fabricante,
            d.mobo_modelo,
            d.bios_versao,
            d.gpu_modelo,
            d.gpu_vram_mb,
            d.ram_total_mb,
            d.ram_tipo,
            d.ram_clock_mhz,
            d.ram_pentes,
            d.disco_total_gb,
            d.discos,
            d.ultimo_acesso,
            TIMESTAMPDIFF(SECOND, d.ultimo_acesso, NOW()) AS segundos_desde_ultimo_acesso,
            s.nome AS setor_nome,
            t.ram_livre_mb,
            t.disco_livre_gb,
            t.alertas,
            t.criado_em AS ultima_telemetria
        FROM dispositivos d
        LEFT JOIN secretarias_setores s ON d.setor_id = s.id
        LEFT JOIN dispositivos_telemetria t ON t.id = (
            SELECT MAX(id) 
            FROM dispositivos_telemetria 
            WHERE dispositivo_id = d.id
        )
        ORDER BY d.ultimo_acesso DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dispositivosBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao consultar dispositivos no banco de dados: " . $e->getMessage());
}

$dispositivos = [];

foreach ($dispositivosBanco as $d) {
    $diferencaSegundos = (int) ($d['segundos_desde_ultimo_acesso'] ?? 99999);
    $alertasArray = !empty($d['alertas']) ? json_decode($d['alertas'], true) : [];

    // REGRA DE STATUS: Offline se passar de 180s (3 minutos)
    if ($diferencaSegundos > 180) {
        $status = 'offline';
    } elseif (!empty($alertasArray)) {
        $status = 'alerta';
    } else {
        $status = 'online';
    }

    // Porcentagem calculada de uso da memória RAM
    $ramTotal = $d['ram_total_mb'] ?: 1;
    $ramLivre = $d['ram_livre_mb'] ?? $ramTotal;
    $ramUsoPorcentagem = round((($ramTotal - $ramLivre) / $ramTotal) * 100);

    // Formatação de tempo de atualização
    if ($status === 'offline') {
        $uptimeTexto = 'Sem comunicação';
    } else {
        $minutosAtras = floor($diferencaSegundos / 60);
        $uptimeTexto = $minutosAtras <= 1 ? 'Ativo agora' : "Visto há {$minutosAtras} min";
    }

    // Processamento do Array de Múltiplos Discos (JSON de d.discos)
    $discosLista = !empty($d['discos']) ? json_decode($d['discos'], true) : [];

    // Fallback: Se não houver array de discos salvo, monta o padrão com o C:
    if (empty($discosLista) && !empty($d['disco_total_gb'])) {
        $discosLista[] = [
            'unidade'  => 'C:',
            'total_gb' => (float)$d['disco_total_gb'],
            'livre_gb' => (float)($d['disco_livre_gb'] ?? 0)
        ];
    }

    $dispositivos[] = [
        'id'                => $d['id'],
        'hostname'          => $d['hostname'],
        'status'            => $status,
        'setor'             => $d['setor_nome'] ?? 'Setor Não Atribuído',
        'ip_local'          => $d['ip_local'] ?: 'N/A',
        'mac_address'       => $d['mac_address'] ?: 'N/A',
        'os_nome'           => $d['os_nome'] ?: 'Windows OS',
        
        // Dados de CPU
        'cpu_modelo'        => $d['cpu_modelo'] ?: 'Desconhecido',
        'cpu_cores'         => $d['cpu_cores'] ?: 0,
        'cpu_threads'       => $d['cpu_threads'] ?: 0,
        'cpu_clock'         => $d['cpu_clock_mhz'] ? $d['cpu_clock_mhz'] . ' MHz' : 'N/A',
        'cpu_socket'        => $d['cpu_socket'] ?: 'N/A',
        
        // Dados de Placa-Mãe
        'mobo_fabricante'   => $d['mobo_fabricante'] ?: 'Desconhecido',
        'mobo_modelo'       => $d['mobo_modelo'] ?: 'Desconhecido',
        'bios_versao'       => $d['bios_versao'] ?: 'N/A',

        // Dados de GPU
        'gpu_modelo'        => $d['gpu_modelo'] ?: 'Integrada/Desconhecida',
        'gpu_vram'          => $d['gpu_vram_mb'] ? round($d['gpu_vram_mb'] / 1024, 1) . ' GB' : 'N/A',

        // Dados de RAM
        'ram_total'         => round($d['ram_total_mb'] / 1024, 1) . ' GB',
        'ram_uso'           => $ramUsoPorcentagem,
        'ram_tipo'          => $d['ram_tipo'] ?: 'DDR',
        'ram_clock'         => $d['ram_clock_mhz'] ? $d['ram_clock_mhz'] . ' MHz' : 'N/A',
        'ram_pentes'        => $d['ram_pentes'] ?: 1,

        // Dados de Disco Legados
        'disco_total'       => $d['disco_total_gb'] ? $d['disco_total_gb'] . ' GB' : 'N/A',
        'disco_livre'       => ($d['disco_livre_gb'] !== null) ? $d['disco_livre_gb'] . ' GB' : 'N/A',
        
        // Novo Array de Múltiplos Discos para passar ao JavaScript/Modal
        'discos'            => $discosLista,

        'uptime'            => $uptimeTexto,
        'ultima_manutencao' => date('d/m/Y H:i', strtotime($d['ultimo_acesso'])),
        'alertas'           => $alertasArray
    ];
}

// 3. Contadores para o topo da tela
$total_pcs   = count($dispositivos);
$online_pcs  = count(array_filter($dispositivos, fn($d) => $d['status'] === 'online'));
$offline_pcs = count(array_filter($dispositivos, fn($d) => $d['status'] === 'offline'));
$alerta_pcs  = count(array_filter($dispositivos, fn($d) => $d['status'] === 'alerta'));
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Monitoramento - Helpdesk TI</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0f172a',
                        darkcard: '#1e293b',
                        darkborder: '#334155'
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-darkbg text-slate-100 min-h-screen font-sans antialiased pb-12">

    <?php include __DIR__ . '/../includes/components/card_dispositivo.php'; ?>

    <script>
        const usuarioNome = <?= json_encode($usuarioNome) ?>;
        let filtroAtual = 'todos';

        function confirmarExclusao(id, hostname, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const nome = hostname || 'este computador';
            const mensagem = 'Deseja realmente excluir ' + nome + ' e registrar os dados na lista de computadores excluídos?';

            if (confirm(mensagem)) {
                window.location.href = 'dispositivos.php?excluir_dispositivo=' + id;
            }
        }

        function abrirModal(pc) {
            window.pcAtual = pc;

            // Header Modal
            document.getElementById('modalHostname').innerText = pc.hostname;
            document.getElementById('modalStatus').innerText = 'Status: ' + pc.status.toUpperCase() + ' - ' + pc.uptime;
            
            // Especificações da CPU
            document.getElementById('modalCpu').innerText = pc.cpu_modelo;
            document.getElementById('modalCpuDetalhes').innerText = pc.cpu_cores + ' Cores / ' + pc.cpu_threads + ' Threads | Clock: ' + pc.cpu_clock + ' | Soquete: ' + pc.cpu_socket;

            // Especificações da Placa-Mãe
            document.getElementById('modalMobo').innerText = pc.mobo_fabricante + ' - ' + pc.mobo_modelo;
            document.getElementById('modalBios').innerText = 'BIOS: ' + pc.bios_versao;

            // Especificações da Memória RAM
            document.getElementById('modalRam').innerText = pc.ram_total + ' ' + pc.ram_tipo + ' (' + pc.ram_uso + '% em uso)';
            document.getElementById('modalRamDetalhes').innerText = pc.ram_pentes + ' Pente(s) | Frequência: ' + pc.ram_clock;

            // Placa de Vídeo
            document.getElementById('modalGpu').innerText = pc.gpu_modelo + (pc.gpu_vram !== 'N/A' ? ' (' + pc.gpu_vram + ' VRAM)' : '');

            // Armazenamento

              // Renderiza a lista de discos dinamicamente
    const container = document.getElementById('modalDisco');
    container.innerHTML = ''; // Limpa a lista do modal anterior

    if (pc.discos && pc.discos.length > 0) {
        pc.discos.forEach(disco => {
            const total = parseFloat(disco.total_gb) || 1;
            const livre = parseFloat(disco.livre_gb) || 0;
            const usado = total - livre;
            const porcentagemUso = Math.min(100, Math.max(0, Math.round((usado / total) * 100)));

            // Seleciona a cor com base na porcentagem de uso
            let corBarra = 'bg-blue-500';
            if (porcentagemUso > 90) {
                corBarra = 'bg-rose-500';
            } else if (porcentagemUso > 75) {
                corBarra = 'bg-amber-500';
            }

            const cardDiscoHtml = `
                <div class="bg-slate-800/60 p-3 rounded-lg border border-slate-700/60">
                    <div class="flex justify-between items-center text-xs mb-1">
                        <span class="font-bold text-slate-200">
                            Unidade ${disco.unidade}
                        </span>
                        <span class="text-slate-400 font-mono">
                            ${livre} GB livres de ${total} GB (${porcentagemUso}% usado)
                        </span>
                    </div>
                    <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full ${corBarra} transition-all duration-300" style="width: ${porcentagemUso}%"></div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', cardDiscoHtml);
        });
    } else {
        container.innerHTML = '<p class="text-xs text-slate-500 italic">Nenhum disco detectado.</p>';
    }

const linkAprovar = document.getElementById('linkAprovarHardware');
            if (linkAprovar) {
                linkAprovar.href = '/helpdesk_prefeitura/admin/dispositivos.php?aprovar_hardware=' + pc.id;
                linkAprovar.onclick = function (event) {
                    const hostname = pc.hostname || 'este dispositivo';
                    const mensagem = 'Deseja salvar a configuração atual como o novo Hardware Original de ' + hostname + '?\n\nEsta ação será registrada para ' + usuarioNome + '.';
                    return confirm(mensagem);
                };
            }

            const linkExcluir = document.getElementById('linkExcluirComputador');
            if (linkExcluir) {
                linkExcluir.href = '#';
                linkExcluir.onclick = function (event) {
                    event.preventDefault();
                    const hostname = pc.hostname || 'este computador';
                    const mensagem = 'Deseja realmente excluir ' + hostname + ' e registrar os dados na lista de computadores excluídos?';
                    if (confirm(mensagem)) {
                        window.location.href = 'dispositivos.php?excluir_dispositivo=' + pc.id;
                    }
                };
            }

            // Exibe o modal
    document.getElementById('modalDetalhes').classList.remove('hidden');
           
              // fim codigo armazenamento
            
              // Sistema Operacional e Rede
            document.getElementById('modalSetor').innerText = pc.setor;
            document.getElementById('modalOs').innerText = pc.os_nome;
            document.getElementById('modalIp').innerText = pc.ip_local;
            document.getElementById('modalMac').innerText = pc.mac_address;
            document.getElementById('modalManutencao').innerText = pc.ultima_manutencao;

            // Alertas
            const alertasContainer = document.getElementById('modalAlertasContainer');
            const alertasTexto = document.getElementById('modalAlertasTexto');
            if (pc.alertas && pc.alertas.length > 0) {
                alertasTexto.innerText = pc.alertas.join(', ');
                alertasContainer.classList.remove('hidden');
            } else {
                alertasContainer.classList.add('hidden');
            }

            document.getElementById('modalDetalhes').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modalDetalhes').classList.add('hidden');
        }

        function setFiltro(status) {
            filtroAtual = status;
            document.querySelectorAll('.btn-filtro').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-darkcard', 'text-slate-300');
            });

            const btnAtivo = document.getElementById('btn-' + status);
            btnAtivo.classList.remove('bg-darkcard', 'text-slate-300');
            btnAtivo.classList.add('bg-blue-600', 'text-white');

            filtrarDispositivos();
        }

        function filtrarDispositivos() {
            const busca = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.card-pc');

            cards.forEach(card => {
                const hostname = card.getAttribute('data-hostname');
                const status = card.getAttribute('data-status');

                const bateuBusca = hostname.includes(busca);
                const bateuFiltro = (filtroAtual === 'todos') || (status === filtroAtual);

                if (bateuBusca && bateuFiltro) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>