<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Apenas Admin e Técnico podem acessar esta página de cadastro de chamados
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_perfil'], ['admin', 'tecnico'])) {
    header("Location: /helpdesk_prefeitura/account/login.php");
    exit;
}

$mensagemSucesso = '';
$mensagemErro = '';

// Buscar categorias, usuários e técnicos para popular selects
try {
    $stmtCategorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
    $categorias = $stmtCategorias->fetchAll();

    $stmtUsuarios = $pdo->query("SELECT id, nome, email FROM usuarios ORDER BY nome ASC");
    $usuarios = $stmtUsuarios->fetchAll();

    $stmtTecnicos = $pdo->prepare("SELECT id, nome FROM usuarios WHERE perfil = 'tecnico' ORDER BY nome ASC");
    $stmtTecnicos->execute();
    $tecnicos = $stmtTecnicos->fetchAll();
} catch (PDOException $e) {
    $categorias = $usuarios = $tecnicos = [];
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo                       = trim($_POST['titulo'] ?? '');
    $descricao                    = trim($_POST['descricao'] ?? '');
    $lugar                        = trim($_POST['lugar'] ?? null);
    $status                       = $_POST['status'] ?? 'novo';
    $prioridade                   = $_POST['prioridade'] ?? 'media';
    // Solicitante livre: salva em outro_usuario
    $outro_usuario                = trim($_POST['usuario_solicitante_texto'] ?? null);
    // Preservar valores de fechamento/tempo para re-popular formulário
    $fechado_em_raw               = $_POST['fechado_em'] ?? '';
    $tempo_atendimento_minutos    = $_POST['tempo_atendimento_minutos'] ?? '';
    // Quem está criando o chamado será o usuário registrado (usuario_id) e também o técnico responsável
    $usuario_id                   = (int) $_SESSION['usuario_id'];
    $tecnico_id                   = (int) $_SESSION['usuario_id'];
    $categoria_id                 = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;

    if (empty($titulo) || empty($descricao) || empty($categoria_id) || empty($outro_usuario)) {
        $mensagemErro = 'Por favor, preencha os campos obrigatórios: Título, Descrição, Categoria e Nome do Solicitante.';
    } else {
        try {
            // Gerar protocolo automaticamente
            $protocolo = date('Ymd') . '-' . rand(1000, 9999);

            // Processar campos de fechamento/tempo (opcionais)
            $fechado_em = null;
            $tempo_atendimento = null; // em segundos
            if (!empty($_POST['fechado_em'])) {
                $raw = $_POST['fechado_em']; // formato datetime-local: YYYY-MM-DDTHH:MM
                $ts = strtotime(str_replace('T', ' ', $raw));
                if ($ts !== false) {
                    $fechado_em = date('Y-m-d H:i:s', $ts);
                }
            }
            if (!empty($_POST['tempo_atendimento_minutos'])) {
                $mins = intval($_POST['tempo_atendimento_minutos']);
                if ($mins >= 0) {
                    $tempo_atendimento = $mins * 60;
                }
            }

            $sql = "INSERT INTO chamados (protocolo, titulo, descricao, lugar, status, prioridade, usuario_id, outro_usuario, tecnico_id, categoria_id, criado_em, fechado_em, tempo_atendimento)
                    VALUES (:protocolo, :titulo, :descricao, :lugar, :status, :prioridade, :usuario_id, :outro_usuario, :tecnico_id, :categoria_id, NOW(), :fechado_em, :tempo_atendimento)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'protocolo'         => $protocolo,
                'titulo'            => $titulo,
                'descricao'         => $descricao,
                'lugar'             => $lugar,
                'status'            => $status,
                'prioridade'        => $prioridade,
                'usuario_id'        => $usuario_id,
                'outro_usuario'     => $outro_usuario,
                'tecnico_id'        => $tecnico_id,
                'categoria_id'      => $categoria_id,
                'fechado_em'        => $fechado_em,
                'tempo_atendimento' => $tempo_atendimento,
            ]);

            $mensagemSucesso = "Chamado cadastrado com sucesso. Protocolo: " . $protocolo;
            // Limpar campos após sucesso
                    $titulo = $descricao = $lugar = $outro_usuario = '';
            $status = 'novo';
            $prioridade = 'media';
            $categoria_id = null;
        } catch (PDOException $e) {
            $mensagemErro = 'Erro ao salvar chamado: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Chamados - Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/account/painel.php">TI Prefeitura de Borborema</a>
            <div class="d-flex align-items-center text-white">
                <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-outline-light btn-sm me-2">Voltar ao Painel</a>
                <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container" style="max-width:900px;">
        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>
        <?php if (!empty($mensagemSucesso)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">➕ Cadastrar Chamado</h5>
            </div>
            <div class="card-body">
                <form action="cadastro_chamados.php" method="POST">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select">
                                <option value="novo" <?= (isset($status) && $status==='novo') ? 'selected' : '' ?>>novo</option>
                                <option value="aberto" <?= (isset($status) && $status==='aberto') ? 'selected' : '' ?>>aberto</option>
                                <option value="em_andamento" <?= (isset($status) && $status==='em_andamento') ? 'selected' : '' ?>>em_andamento</option>
                                <option value="resolvido" <?= (isset($status) && $status==='resolvido') ? 'selected' : '' ?>>resolvido</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prioridade *</label>
                            <select name="prioridade" class="form-select">
                                <option value="baixa" <?= (isset($prioridade) && $prioridade==='baixa') ? 'selected' : '' ?>>baixa</option>
                                <option value="media" <?= (isset($prioridade) && $prioridade==='media') ? 'selected' : '' ?>>media</option>
                                <option value="alta" <?= (isset($prioridade) && $prioridade==='alta') ? 'selected' : '' ?>>alta</option>
                                <option value="urgente" <?= (isset($prioridade) && $prioridade==='urgente') ? 'selected' : '' ?>>urgente</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Solicitante (digite o nome desejado) *</label>
                            <input type="text" name="usuario_solicitante_texto" class="form-control" required value="<?= htmlspecialchars($outro_usuario ?? '') ?>" placeholder="Ex: Maria Silva, João Souza">
                            <div class="form-text">Este nome será salvo como solicitante do chamado.</div>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Técnico Responsável</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? '') ?>" disabled>
                            <div class="form-text">Será salvo como técnico responsável o usuário da sessão atual.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lugar</label>
                            <input type="text" name="lugar" class="form-control" value="<?= htmlspecialchars($lugar ?? '') ?>" placeholder="Ex: Sala, setor, prédio">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Categoria *</label>
                        <select name="categoria_id" class="form-select" required>
                            <option value="">Selecione a categoria...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($categoria_id) && $categoria_id == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="titulo" class="form-control" required value="<?= htmlspecialchars($titulo ?? '') ?>">
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Descrição *</label>
                        <textarea name="descricao" class="form-control" rows="6" required><?= htmlspecialchars($descricao ?? '') ?></textarea>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Data/Hora de Fechamento (opcional)</label>
                            <input type="datetime-local" name="fechado_em" class="form-control" value="<?= isset($fechado_em_raw) ? htmlspecialchars($fechado_em_raw) : '' ?>">
                            <div class="form-text">Preencha somente se o chamado já foi finalizado.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tempo de Atendimento (minutos)</label>
                            <input type="number" name="tempo_atendimento_minutos" min="0" class="form-control" value="<?= htmlspecialchars(isset($tempo_atendimento_minutos) ? $tempo_atendimento_minutos : '') ?>" placeholder="Ex: 30">
                            <div class="form-text">Informe o tempo total de atendimento em minutos (será salvo em segundos).</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Chamado</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>
</html>
