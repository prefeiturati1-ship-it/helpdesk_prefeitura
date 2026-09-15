<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// 1. SEGURANÇA: Garante que o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /helpdesk_prefeitura/account/login.php");
    exit;
}

$mensagemSucesso = '';
$mensagemErro = '';
// Valores padrão para evitar "undefined variable" ao carregar a página
$titulo = '';
$categoria_id = '';
$prioridade = 'baixa';
$descricao = '';
$tipoSolicitante = 'eu';
$usuarioSolicitanteTexto = '';
$lugar = '';
$err_titulo = $err_categoria = $err_descricao = $err_usuarioSolicitanteText = $err_prioridade = $err_local = false;

// 2. BUSCAR AS CATEGORIAS ATIVAS NO BANCO
try {
    $stmtCategorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
    $categorias = $stmtCategorias->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
}

// 3. PROCESSAR O ENVIO DO FORMULÁRIO DE CHAMADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo               = trim($_POST['titulo'] ?? '');
    $categoria_id         = $_POST['categoria_id'] ?? '';
    $prioridade           = $_POST['prioridade'] ?? 'baixa';
    $descricao            = trim($_POST['descricao'] ?? '');
    $tipoSolicitante      = $_POST['tipo_solicitante'] ?? 'eu';
    $usuarioSolicitanteTexto = trim($_POST['usuario_solicitante_texto'] ?? '');
    $lugar                = trim($_POST['local'] ?? '');
    $usuario_id           = (int) $_SESSION['usuario_id'];
    $usuario_chamado_id   = $usuario_id;
    $outroUsuario = null;

    // Flags de erro por campo (para feedback inline)
    $err_titulo = $err_categoria = $err_descricao = $err_usuarioSolicitanteText = $err_prioridade = $err_local = false;

    // Validação servidor: marca campos específicos
    $missing = [];
    if (empty($titulo)) { $err_titulo = true; $missing[] = 'titulo'; }
    if (empty($categoria_id)) { $err_categoria = true; $missing[] = 'categoria'; }
    if (empty($descricao)) { $err_descricao = true; $missing[] = 'descricao'; }

    // Valida prioridade (deve ser uma das opções permitidas)
    $prioridades_validas = ['baixa','media','alta'];
    if (empty($prioridade) || !in_array($prioridade, $prioridades_validas, true)) { $err_prioridade = true; $missing[] = 'prioridade'; }

    // Valida lugar como obrigatório (avisos solicitados)
    if (empty($lugar)) { $err_local = true; $missing[] = 'local'; }

    if (!empty($missing)) {
        $mensagemErro = "Por favor, preencha todos os campos obrigatórios (*).";
    }

    if ($tipoSolicitante === 'outro') {
        if (empty($usuarioSolicitanteTexto)) {
            $err_usuarioSolicitanteText = true;
            $mensagemErro = "Digite o nome da pessoa para quem o chamado será aberto.";
        } else {
            $outroUsuario = $usuarioSolicitanteTexto;
            $usuario_chamado_id = $usuario_id;
        }
    }

    if (empty($mensagemErro)) {
        try {
            // Garante que as colunas existam na tabela chamados
            $verificaColunaOutro = $pdo->query("SHOW COLUMNS FROM chamados LIKE 'outro_usuario'");
            if ($verificaColunaOutro->rowCount() === 0) {
                $pdo->exec("ALTER TABLE chamados ADD COLUMN outro_usuario VARCHAR(255) DEFAULT NULL");
            }

            $verificaColunaLugar = $pdo->query("SHOW COLUMNS FROM chamados LIKE 'lugar'");
            if ($verificaColunaLugar->rowCount() === 0) {
                $pdo->exec("ALTER TABLE chamados ADD COLUMN lugar VARCHAR(255) DEFAULT NULL");
            }

            // Gerar protocolo único (AnoMêsDia-NúmeroAleatório)
            $protocolo = date('Ymd') . '-' . rand(1000, 9999);

            // Insere o novo chamado incluindo o protocolo gerado
            $sql = "INSERT INTO chamados (protocolo, usuario_id, categoria_id, titulo, descricao, prioridade, outro_usuario, lugar, status, criado_em) 
                    VALUES (:protocolo, :usuario_id, :categoria_id, :titulo, :descricao, :prioridade, :outro_usuario, :lugar, 'novo', NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'protocolo'     => $protocolo,
                'usuario_id'    => $usuario_chamado_id,
                'categoria_id'  => $categoria_id,
                'titulo'        => $titulo,
                'descricao'     => $descricao,
                'prioridade'    => $prioridade,
                'outro_usuario' => $outroUsuario,
                'lugar'         => $lugar
            ]);

            $mensagemSucesso = "Chamado aberto com sucesso! Protocolo: " . $protocolo;
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao registrar o chamado: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir Chamado - Help Desk Borborema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/helpdesk_prefeitura/account/painel.php">Help Desk Borborema</a>
            <div class="d-flex align-items-center text-white">
                <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-outline-light btn-sm me-2">Voltar ao Painel</a>
                <a href="/helpdesk_prefeitura/account/logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4" style="max-width: 700px;">

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0 fs-5">Abrir Novo Chamado de Suporte</h4>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($mensagemErro)): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($mensagemErro) ?></div>
                <?php endif; ?>

                <?php if (!empty($mensagemSucesso)): ?>
                    <div class="alert alert-success py-3 text-center">
                        <h5>✅ <?= htmlspecialchars($mensagemSucesso) ?></h5>
                        <div class="mt-3">
                            <a href="abrir_chamado.php" class="btn btn-outline-success btn-sm me-2">Abrir outro chamado</a>
                            <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-primary btn-sm">Ir para o Painel</a>
                        </div>
                    </div>
                <?php else: ?>

                <form id="form-chamado" action="abrir_chamado.php" method="POST" novalidate>
                    <div id="alert-js"></div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Este chamado é para *</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_solicitante" id="tipo_eu" value="eu" <?= (($tipoSolicitante ?? 'eu') === 'eu') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tipo_eu">Para mim</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_solicitante" id="tipo_outro" value="outro" <?= (($tipoSolicitante ?? 'eu') === 'outro') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tipo_outro">Para outra pessoa</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="bloco-outra-pessoa" style="<?= (($tipoSolicitante ?? 'eu') === 'outro') ? '' : 'display:none;' ?>">
                        <label for="usuario_solicitante_texto" class="form-label fw-bold">Quem precisa do chamado? *</label>
                        <input type="text" name="usuario_solicitante_texto" id="usuario_solicitante_texto" class="form-control <?= (!empty($err_usuarioSolicitanteText) ? 'is-invalid' : '') ?>"
                               placeholder="Digite o nome ou e-mail da pessoa"
                               value="<?= htmlspecialchars($usuarioSolicitanteTexto ?? '') ?>">
                        <div class="invalid-feedback" id="feedback-usuario">Informe o nome ou e-mail da pessoa.</div>
                        <div class="form-text">Digite o nome ou e-mail da pessoa para quem o chamado deve ser aberto.</div>
                    </div>

                    <div class="mb-3">
                        <label for="titulo" class="form-label fw-bold">Assunto / Título da Solicitação *</label>
                        <input type="text" name="titulo" id="titulo" class="form-control <?= (!empty($err_titulo) ? 'is-invalid' : '') ?>" required 
                               placeholder="Ex: Computador não liga / Impressora sem papel"
                               value="<?= htmlspecialchars($titulo ?? '') ?>">
                        <div class="invalid-feedback" id="feedback-titulo">Preencha o Assunto / Título.</div>
                    </div>
                    <div class="mb-3">
                        <label for="local" class="form-label fw-bold">Lugar *</label>
                        <input type="text" name="local" id="local" class="form-control <?= (!empty($err_local) ? 'is-invalid' : '') ?>" 
                               placeholder="Ex: Sala da Diretoria / Secretaria de Educação"
                               value="<?= htmlspecialchars($lugar ?? '') ?>">
                        <div class="invalid-feedback" id="feedback-local">Informe o local do atendimento.</div>
                    </div>

                    <div class="mb-3">
                        <label for="categoria_id" class="form-label fw-bold">Categoria do Problema *</label>
                        <select name="categoria_id" id="categoria_id" class="form-select <?= (!empty($err_categoria) ? 'is-invalid' : '') ?>" required>
                            <option value="" disabled <?= empty($categoria_id) ? 'selected' : '' ?>>Selecione uma categoria...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ((string)($categoria_id ?? '') === (string)$cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback" id="feedback-categoria">Selecione a Categoria do Problema.</div>
                    </div>

                    <div class="mb-3">
                        <label for="prioridade" class="form-label fw-bold">Prioridade *</label>
                        <select name="prioridade" id="prioridade" class="form-select <?= (!empty($err_prioridade) ? 'is-invalid' : '') ?>" required>
                            <option value="baixa" <?= ($prioridade === 'baixa') ? 'selected' : '' ?>>Baixa (Dúvida, ajuste simples)</option>
                            <option value="media" <?= ($prioridade === 'media') ? 'selected' : '' ?>>Média (Atrapalha a rotina)</option>
                            <option value="alta" <?= ($prioridade === 'alta') ? 'selected' : '' ?>>Alta (Setor parado ou urgente)</option>
                        </select>
                        <div class="invalid-feedback" id="feedback-prioridade">Selecione a Prioridade.</div>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label fw-bold">Descrição do Problema *</label>
                        <textarea name="descricao" id="descricao" class="form-control <?= (!empty($err_descricao) ? 'is-invalid' : '') ?>" rows="5" required 
                                  placeholder="Descreva detalhadamente o que está acontecendo..."><?= htmlspecialchars($descricao ?? '') ?></textarea>
                        <div class="invalid-feedback" id="feedback-descricao">Descreva o problema.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="/helpdesk_prefeitura/account/painel.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4">Enviar Chamado</button>
                    </div>

                </form>

                <?php endif; ?>

            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tipoEu = document.getElementById('tipo_eu');
            const tipoOutro = document.getElementById('tipo_outro');
            const blocoOutraPessoa = document.getElementById('bloco-outra-pessoa');
            const inputUsuario = document.getElementById('usuario_solicitante_texto');
            const form = document.getElementById('form-chamado');
            const alertJs = document.getElementById('alert-js');

            function atualizarBloco() {
                const mostrar = tipoOutro.checked;
                blocoOutraPessoa.style.display = mostrar ? '' : 'none';

                if (!mostrar && inputUsuario) {
                    inputUsuario.value = '';
                    inputUsuario.classList.remove('is-invalid');
                }
            }

            if (tipoEu && tipoOutro) {
                tipoEu.addEventListener('change', atualizarBloco);
                tipoOutro.addEventListener('change', atualizarBloco);
                atualizarBloco();
            }

            // Remove marcação de erro ao digitar/selecionar
            ['titulo','categoria_id','prioridade','descricao','usuario_solicitante_texto','local'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('input', function () {
                    this.classList.remove('is-invalid');
                    if (alertJs) alertJs.innerHTML = '';
                });
                el.addEventListener('change', function () {
                    this.classList.remove('is-invalid');
                    if (alertJs) alertJs.innerHTML = '';
                });
            });

            if (form) {
                form.addEventListener('submit', function (e) {
                    // Limpa alertas e feedbacks anteriores
                    if (alertJs) alertJs.innerHTML = '';
                    ['feedback-titulo','feedback-categoria','feedback-prioridade','feedback-descricao','feedback-usuario','feedback-local'].forEach(id => {
                        const f = document.getElementById(id);
                        if (f) f.textContent = '';
                    });

                    const titulo = (document.getElementById('titulo') || {}).value || '';
                    const categoria = (document.getElementById('categoria_id') || {}).value || '';
                    const prioridade = (document.getElementById('prioridade') || {}).value || '';
                    const descricao = (document.getElementById('descricao') || {}).value || '';
                    const local = (document.getElementById('local') || {}).value || '';
                    const outroChecked = document.getElementById('tipo_outro') ? document.getElementById('tipo_outro').checked : false;
                    const usuarioSolicitante = (document.getElementById('usuario_solicitante_texto') || {}).value || '';

                    const errors = [];

                    if (!titulo.trim()) {
                        errors.push('Preencha o Assunto / Título.');
                        const el = document.getElementById('titulo'); if (el) el.classList.add('is-invalid');
                        const fb = document.getElementById('feedback-titulo'); if (fb) fb.textContent = 'Preencha o Assunto / Título.';
                    }
                    if (!categoria) {
                        errors.push('Selecione a Categoria do Problema.');
                        const el = document.getElementById('categoria_id'); if (el) el.classList.add('is-invalid');
                        const fb = document.getElementById('feedback-categoria'); if (fb) fb.textContent = 'Selecione a Categoria do Problema.';
                    }
                    if (!prioridade) {
                        errors.push('Selecione a Prioridade.');
                        const el = document.getElementById('prioridade'); if (el) el.classList.add('is-invalid');
                        const fb = document.getElementById('feedback-prioridade'); if (fb) fb.textContent = 'Selecione a Prioridade.';
                    }
                    if (!local.trim()) {
                        errors.push('Informe o local do atendimento.');
                        const el = document.getElementById('local'); if (el) el.classList.add('is-invalid');
                        const fb = document.getElementById('feedback-local'); if (fb) fb.textContent = 'Informe o local do atendimento.';
                    }
                    if (!descricao.trim()) {
                        errors.push('Descreva o problema.');
                        const el = document.getElementById('descricao'); if (el) el.classList.add('is-invalid');
                        const fb = document.getElementById('feedback-descricao'); if (fb) fb.textContent = 'Descreva o problema.';
                    }
                    if (outroChecked && !usuarioSolicitante.trim()) {
                        errors.push('Informe quem receberá o chamado.');
                        const el = document.getElementById('usuario_solicitante_texto'); if (el) el.classList.add('is-invalid');
                        const fb = document.getElementById('feedback-usuario'); if (fb) fb.textContent = 'Informe quem receberá o chamado.';
                    }

                    if (errors.length) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (alertJs) {
                            alertJs.innerHTML = '<div class="alert alert-danger py-2">' + errors[0] + '</div>';
                            alertJs.scrollIntoView({behavior: 'smooth'});
                        }
                        return false;
                    }

                    return true; // permite envio
                });
            }
        });
    </script>

</body>
</html>