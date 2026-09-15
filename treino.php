<?php
// api/telemetria.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensagem' => 'JSON inválido.']);
    exit;
}

// -------------------------------------------------------------------------
// RECEBIMENTO E SANITIZAÇÃO DOS DADOS DO PAYLOAD
// -------------------------------------------------------------------------
$hostname       = trim($input['hostname'] ?? '');
$setorNome      = trim($input['setor_nome'] ?? '');
$ipLocal        = trim($input['ip_local'] ?? '');
$macAddress     = trim($input['mac_address'] ?? '');
$osNome         = trim($input['os_nome'] ?? '');

// Dados da CPU
$cpuModelo      = trim($input['cpu_modelo'] ?? '');
$cpuCores       = (int)($input['cpu_cores'] ?? 0);
$cpuThreads     = (int)($input['cpu_threads'] ?? 0);
$cpuClockMhz    = (int)($input['cpu_clock_mhz'] ?? 0);
$cpuSocket      = trim($input['cpu_socket'] ?? '');

// Dados da Placa-Mãe
$moboFabricante = trim($input['mobo_fabricante'] ?? '');
$moboModelo     = trim($input['mobo_modelo'] ?? '');
$biosVersao     = trim($input['bios_versao'] ?? '');

// Dados da GPU
$gpuModelo      = trim($input['gpu_modelo'] ?? '');
$gpuVramMb      = (int)($input['gpu_vram_mb'] ?? 0);

// Dados da Memória RAM
$ramTotalMb     = (int)($input['ram_total_mb'] ?? 0);
$ramLivreMb     = (int)($input['ram_livre_mb'] ?? 0);
$ramTipo        = trim($input['ram_tipo'] ?? '');
$ramClockMhz    = (int)($input['ram_clock_mhz'] ?? 0);
$ramPentes      = (int)($input['ram_pentes'] ?? 0);

// Dados de Discos
$discoTotalGb   = (int)($input['disco_total_gb'] ?? 0);
$discoLivreGb   = (int)($input['disco_livre_gb'] ?? 0);

// Recebe o array de múltiplos discos e converte para JSON
$discosInput    = isset($input['discos']) && is_array($input['discos']) ? $input['discos'] : [];
$discosJson     = json_encode($discosInput, JSON_UNESCAPED_UNICODE);

// Alertas de Telemetria
$alertas        = json_encode($input['alertas'] ?? [], JSON_UNESCAPED_UNICODE);

if (empty($hostname)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensagem' => 'Hostname é obrigatório.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Tenta vincular ou cadastrar o Setor digitado na tabela secretarias_setores
    $setorId = null;
    if (!empty($setorNome)) {
        $stmtSetor = $pdo->prepare("SELECT id FROM secretarias_setores WHERE LOWER(nome) = LOWER(:nome) LIMIT 1");
        $stmtSetor->execute([':nome' => $setorNome]);
        $setorId = $stmtSetor->fetchColumn();

        if (!$setorId) {
            try {
                $sigla = strtoupper(substr($setorNome, 0, 5));
                $stmtInsSetor = $pdo->prepare("INSERT INTO secretarias_setores (nome, sigla, ativo) VALUES (:nome, :sigla, 1)");
                $stmtInsSetor->execute([':nome' => $setorNome, ':sigla' => $sigla]);
                $setorId = $pdo->lastInsertId();
            } catch (\Throwable $e) {
                $setorId = null;
            }
        }
    }

    // 2. Insere ou Atualiza o Dispositivo (agora incluindo a coluna 'discos')
    $sqlDispositivo = "INSERT INTO dispositivos (
            hostname, setor_id, setor_nome, ip_local, mac_address, os_nome,
            cpu_modelo, cpu_cores, cpu_threads, cpu_clock_mhz, cpu_socket,
            mobo_fabricante, mobo_modelo, bios_versao,
            gpu_modelo, gpu_vram_mb,
            ram_total_mb, ram_tipo, ram_clock_mhz, ram_pentes,
            disco_total_gb, discos, primeiro_acesso, ultimo_acesso
        ) VALUES (
            :hostname, :setor_id, :setor_nome, :ip_local, :mac_address, :os_nome,
            :cpu_modelo, :cpu_cores, :cpu_threads, :cpu_clock_mhz, :cpu_socket,
            :mobo_fabricante, :mobo_modelo, :bios_versao,
            :gpu_modelo, :gpu_vram_mb,
            :ram_total_mb, :ram_tipo, :ram_clock_mhz, :ram_pentes,
            :disco_total_gb, :discos, NOW(), NOW()
        )
        ON DUPLICATE KEY UPDATE 
            hostname        = VALUES(hostname),
            setor_id        = COALESCE(VALUES(setor_id), setor_id),
            setor_nome      = VALUES(setor_nome),
            ip_local        = VALUES(ip_local),
            mac_address     = VALUES(mac_address),
            os_nome         = VALUES(os_nome),
            cpu_modelo      = VALUES(cpu_modelo),
            cpu_cores       = VALUES(cpu_cores),
            cpu_threads     = VALUES(cpu_threads),
            cpu_clock_mhz   = VALUES(cpu_clock_mhz),
            cpu_socket      = VALUES(cpu_socket),
            mobo_fabricante = VALUES(mobo_fabricante),
            mobo_modelo     = VALUES(mobo_modelo),
            bios_versao     = VALUES(bios_versao),
            gpu_modelo      = VALUES(gpu_modelo),
            gpu_vram_mb     = VALUES(gpu_vram_mb),
            ram_total_mb    = VALUES(ram_total_mb),
            ram_tipo        = VALUES(ram_tipo),
            ram_clock_mhz   = VALUES(ram_clock_mhz),
            ram_pentes      = VALUES(ram_pentes),
            disco_total_gb  = VALUES(disco_total_gb),
            discos          = VALUES(discos),
            ultimo_acesso   = NOW()";

    $stmt = $pdo->prepare($sqlDispositivo);

    $stmt->execute([
        ':hostname'        => $hostname,
        ':setor_id'        => $setorId ?: null,
        ':setor_nome'      => $setorNome,
        ':ip_local'        => $ipLocal,
        ':mac_address'     => $macAddress,
        ':os_nome'         => $osNome,
        ':cpu_modelo'      => $cpuModelo,
        ':cpu_cores'       => $cpuCores,
        ':cpu_threads'     => $cpuThreads,
        ':cpu_clock_mhz'   => $cpuClockMhz,
        ':cpu_socket'      => $cpuSocket,
        ':mobo_fabricante' => $moboFabricante,
        ':mobo_modelo'     => $moboModelo,
        ':bios_versao'     => $biosVersao,
        ':gpu_modelo'      => $gpuModelo,
        ':gpu_vram_mb'     => $gpuVramMb,
        ':ram_total_mb'    => $ramTotalMb,
        ':ram_tipo'        => $ramTipo,
        ':ram_clock_mhz'   => $ramClockMhz,
        ':ram_pentes'      => $ramPentes,
        ':disco_total_gb'  => $discoTotalGb,
        ':discos'          => $discosJson
    ]);

    // 3. Obtém o ID do dispositivo para o vínculo da telemetria
    $stmtId = $pdo->prepare("SELECT id FROM dispositivos WHERE hostname = :hostname LIMIT 1");
    $stmtId->execute([':hostname' => $hostname]);
    $dispositivoId = $stmtId->fetchColumn();

    // 4. Registra/Atualiza os dados de Telemetria Dinâmica (Apenas RAM e espaço do Disco principal/Alertas)
    $stmtCheck = $pdo->prepare("SELECT id FROM dispositivos_telemetria WHERE dispositivo_id = :dispositivo_id LIMIT 1");
    $stmtCheck->execute([':dispositivo_id' => $dispositivoId]);
    $existeTelemetria = $stmtCheck->fetchColumn();

    if ($existeTelemetria) {
        $stmtTel = $pdo->prepare("UPDATE dispositivos_telemetria SET 
                                    ram_livre_mb   = :ram_livre_mb, 
                                    disco_livre_gb = :disco_livre_gb, 
                                    alertas        = :alertas, 
                                    criado_em      = NOW() 
                                  WHERE dispositivo_id = :dispositivo_id");
    } else {
        $stmtTel = $pdo->prepare("INSERT INTO dispositivos_telemetria 
                                    (dispositivo_id, ram_livre_mb, disco_livre_gb, alertas, criado_em) 
                                  VALUES 
                                    (:dispositivo_id, :ram_livre_mb, :disco_livre_gb, :alertas, NOW())");
    }

    $stmtTel->execute([
        ':dispositivo_id' => $dispositivoId,
        ':ram_livre_mb'   => $ramLivreMb,
        ':disco_livre_gb' => $discoLivreGb,
        ':alertas'        => $alertas
    ]);

    $pdo->commit();
    http_response_code(200);
    echo json_encode(['status' => 'success', 'mensagem' => 'Dados de telemetria e discos atualizados com sucesso.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensagem' => $e->getMessage()]);
}