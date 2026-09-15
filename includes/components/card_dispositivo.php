<header class="border-b border-darkborder bg-slate-900/80 backdrop-blur sticky top-0 z-30 px-6 py-4">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-600/20 text-blue-400 rounded-lg border border-blue-500/30">
                <i class="fa-solid fa-server text-2xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white tracking-wide">Helpdesk TI <span class="text-xs bg-blue-500/20 text-blue-400 border border-blue-500/30 px-2 py-0.5 rounded ml-2">Monitoramento</span></h1>
                <p class="text-xs text-slate-400">Telemetria de computadores em tempo real</p>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-2 w-full md:w-auto text-center">
            <div class="bg-darkcard border border-darkborder px-3 py-1.5 rounded-lg">
                <span class="text-xs text-slate-400 block">Total</span>
                <span class="font-bold text-slate-200"><?= $total_pcs ?></span>
            </div>
            <div class="bg-darkcard border border-emerald-500/30 px-3 py-1.5 rounded-lg">
                <span class="text-xs text-emerald-400 block">Online</span>
                <span class="font-bold text-emerald-400"><?= $online_pcs ?></span>
            </div>
            <div class="bg-darkcard border border-amber-500/30 px-3 py-1.5 rounded-lg">
                <span class="text-xs text-amber-400 block">Alertas</span>
                <span class="font-bold text-amber-400"><?= $alerta_pcs ?></span>
            </div>
            <div class="bg-darkcard border border-rose-500/30 px-3 py-1.5 rounded-lg">
                <span class="text-xs text-rose-400 block">Offline</span>
                <span class="font-bold text-rose-400"><?= $offline_pcs ?></span>
            </div>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 mt-8">

    <div class="flex flex-col md:flex-row gap-4 justify-between items-center mb-6">
        <div class="relative w-full md:w-80">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" id="searchInput" onkeyup="filtrarDispositivos()" placeholder="Buscar por nome..." 
                   class="w-full pl-9 pr-4 py-2 bg-darkcard border border-darkborder rounded-lg text-sm text-slate-200 placeholder-slate-400 focus:outline-none focus:border-blue-500 transition-colors">
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
            <button onclick="setFiltro('todos')" id="btn-todos" class="btn-filtro active px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white transition-colors">
                Todos
            </button>
            <button onclick="setFiltro('online')" id="btn-online" class="btn-filtro px-3 py-1.5 rounded-lg text-xs font-semibold bg-darkcard text-slate-300 hover:bg-slate-700 transition-colors border border-darkborder">
                <i class="fa-solid fa-circle text-[8px] text-emerald-400 mr-1"></i> Online
            </button>
            <button onclick="setFiltro('alerta')" id="btn-alerta" class="btn-filtro px-3 py-1.5 rounded-lg text-xs font-semibold bg-darkcard text-slate-300 hover:bg-slate-700 transition-colors border border-darkborder">
                <i class="fa-solid fa-triangle-exclamation text-[10px] text-amber-400 mr-1"></i> Com Alertas
            </button>
            <button onclick="setFiltro('offline')" id="btn-offline" class="btn-filtro px-3 py-1.5 rounded-lg text-xs font-semibold bg-darkcard text-slate-300 hover:bg-slate-700 transition-colors border border-darkborder">
                <i class="fa-solid fa-circle text-[8px] text-rose-500 mr-1"></i> Offline
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="gridDispositivos">
        <?php if (empty($dispositivos)): ?>
            <div class="col-span-full text-center py-12 bg-darkcard rounded-xl border border-darkborder">
                <i class="fa-solid fa-laptop text-4xl text-slate-600 mb-3 block"></i>
                <p class="text-slate-400">Nenhum computador registrado no banco de dados até o momento.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($dispositivos as $pc): ?>
            <?php
                $badgeClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
                $dotColor = 'bg-emerald-400';
                $statusText = 'Online';
                $borderCard = 'hover:border-slate-600';

                if ($pc['status'] === 'alerta') {
                    $badgeClass = 'bg-amber-500/10 text-amber-400 border-amber-500/30 animate-pulse';
                    $dotColor = 'bg-amber-400';
                    $statusText = 'Atenção';
                    $borderCard = 'border-amber-500/40 hover:border-amber-500';
                } elseif ($pc['status'] === 'offline') {
                    $badgeClass = 'bg-rose-500/10 text-rose-400 border-rose-500/30';
                    $dotColor = 'bg-rose-500';
                    $statusText = 'Offline';
                    $borderCard = 'border-rose-900/30 opacity-75 hover:opacity-100';
                }
            ?>
            <div class="card-pc bg-darkcard border border-darkborder rounded-xl p-5 shadow-lg <?= $borderCard ?> transition-all cursor-pointer relative group flex flex-col justify-between"
                 data-hostname="<?= strtolower($pc['hostname']) ?>"
                 data-status="<?= $pc['status'] ?>"
                 onclick="abrirModal(<?= htmlspecialchars(json_encode($pc), ENT_QUOTES, 'UTF-8') ?>)">

               
                <div>
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-slate-800 rounded-lg border border-slate-700 group-hover:border-blue-500/50 transition-colors">
                                <i class="fa-solid fa-desktop text-slate-300 group-hover:text-blue-400"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-white tracking-tight group-hover:text-blue-400 transition-colors"><?= $pc['hostname'] ?></h3>
                                <p class="text-xs text-slate-400 font-mono"><?= $pc['setor'] ?></p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border <?= $badgeClass ?>">
                            <span class="w-2 h-2 rounded-full <?= $dotColor ?>"></span>
                            <?= $statusText ?>
                        </span>
                    </div>

                    <?php if ($pc['status'] !== 'offline'): ?>
                        <div class="space-y-3 mt-4 border-t border-slate-800 pt-3">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-400">Uso de RAM</span>
                                    <span class="font-semibold text-slate-300"><?= $pc['ram_uso'] ?>%</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full <?= $pc['ram_uso'] > 85 ? 'bg-amber-500' : 'bg-emerald-500' ?>" style="width: <?= $pc['ram_uso'] ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-slate-800/50 rounded-lg p-3 text-center border border-slate-800/80 my-2">
                            <span class="text-xs text-slate-500 italic"><i class="fa-solid fa-power-off mr-1"></i> Dispositivo sem comunicação</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-800/80 flex justify-between items-center text-xs text-slate-400">
                    <span><i class="fa-regular fa-clock mr-1"></i> <?= $pc['uptime'] ?></span>
                    <span class="text-blue-400 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1 font-medium">
                        Detalhes <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<div id="modalDetalhes" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-darkcard border border-darkborder w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden transform transition-all">
        
        <div class="bg-slate-900 px-6 py-4 border-b border-darkborder flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-600/20 text-blue-400 rounded-lg border border-blue-500/30">
                    <i class="fa-solid fa-microchip text-xl"></i>
                </div>
                <div>
                    <h2 id="modalHostname" class="text-lg font-bold text-white">---</h2>
                    <p id="modalStatus" class="text-xs text-slate-400 font-mono">---</p>
                </div>
            </div>
            <button onclick="fecharModal()" class="text-slate-400 hover:text-white p-2 rounded-lg hover:bg-slate-800 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
            <div class="flex flex-wrap gap-2">
                <a href="#" id="linkAprovarHardware"
                   class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3 py-2 rounded-lg font-medium transition inline-flex items-center">
                    <i class="fa-solid fa-shield-check mr-1"></i> Aprovar / Recalibrar Hardware
                </a>

                <a href="#" id="linkExcluirComputador"
                   class="bg-rose-600 hover:bg-rose-700 text-white text-xs px-3 py-2 rounded-lg font-medium transition inline-flex items-center">
                    <i class="fa-solid fa-trash mr-1"></i> Excluir Computador
                </a>
            </div>
        
        <div id="modalAlertasContainer" class="hidden">
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-3 flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-400 text-lg mt-0.5"></i>
                <div>
                    <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider">Alertas Identificados</h4>
                    <p id="modalAlertasTexto" class="text-xs text-slate-300 mt-0.5">---</p>
                </div>
            </div>
        </div>
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-microchip text-blue-400"></i> Processador & Placa-Mãe
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-900/60 p-4 rounded-xl border border-slate-800/80">
                    <div>
                        <span class="text-xs text-slate-500 block">Modelo do Processador</span>
                        <span id="modalCpu" class="text-sm font-medium text-slate-200">---</span>
                        <span id="modalCpuDetalhes" class="text-xs text-slate-400 block mt-0.5 font-mono">---</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">Placa-Mãe</span>
                        <span id="modalMobo" class="text-sm font-medium text-slate-200">---</span>
                        <span id="modalBios" class="text-xs text-slate-400 block mt-0.5 font-mono">---</span>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-memory text-purple-400"></i> Memória, Vídeo & Armazenamento
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-900/60 p-4 rounded-xl border border-slate-800/80">
                    <div>
                        <span class="text-xs text-slate-500 block">Memória RAM</span>
                        <span id="modalRam" class="text-sm font-medium text-slate-200">---</span>
                        <span id="modalRamDetalhes" class="text-xs text-slate-400 block mt-0.5 font-mono">---</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">Placa de Vídeo (GPU)</span>
                        <span id="modalGpu" class="text-sm font-medium text-slate-200">---</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">Unidades de disco</span>
                        <span id="modalDisco" class="text-sm font-medium text-slate-200">---</span>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-network-wired text-emerald-400"></i> Rede & Sistema Operacional
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-900/60 p-4 rounded-xl border border-slate-800/80">
                    <div>
                        <span class="text-xs text-slate-500 block">Setor / Secretaria</span>
                        <span id="modalSetor" class="text-sm font-medium text-slate-200">---</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">Sistema Operacional</span>
                        <span id="modalOs" class="text-sm font-medium text-slate-200">---</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">Endereço IP Local</span>
                        <span id="modalIp" class="text-sm font-mono text-emerald-400">---</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 block">Endereço MAC</span>
                        <span id="modalMac" class="text-sm font-mono text-slate-300">---</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-xs text-slate-500 block">Último Envio de Telemetria</span>
                        <span id="modalManutencao" class="text-sm font-medium text-slate-200">---</span>
                    </div>
                </div>
            </div>

        </div>

        <div class="bg-slate-900 px-6 py-4 border-t border-darkborder flex justify-end">
            <button onclick="fecharModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-medium transition-colors">
                Fechar
            </button>
        </div>
    </div>
</div>