-- Dados iniciais do Help Desk Borborema
-- INSERTs reordenados conforme dependências das chaves estrangeiras.
-- Estrutura das tabelas: 001_estrutura_inicial.sql

START TRANSACTION;

INSERT INTO `secretarias_setores` (`id`, `nome`, `sigla`, `ativo`, `criado_em`) VALUES
(1, 'Secretaria de Administração e TI', 'TI', 1, '2026-07-23 02:36:02'),
(2, 'Secretaria de Educação', 'SEDUC', 1, '2026-07-23 02:36:02'),
(5, 'TI prefeitura de Borborema', 'TEC TI', 1, '2026-07-24 23:08:40'),
(6, 'Almoxarifado borbo', 'AM', 1, '2026-07-27 16:30:20'),
(7, 'obras e postura', 'OP', 1, '2026-07-27 17:42:54'),
(21, 'test11', 'TEST1', 1, '2026-08-03 19:16:05'),
(22, 'Geral', 'GERAL', 1, '2026-08-03 19:16:09'),
(23, 'almoxerifado', 'ALMOX', 1, '2026-08-03 19:57:58'),
(24, 'TI', 'TI', 1, '2026-08-05 23:35:59');

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `cpf`, `telefone`, `perfil`, `setor_id`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'testadmin', 'testadmin@borborema.sp.gov.br', '$2y$10$FFnSpFO6YqO6RSNFFeQzmOoXRq.FV6qvAOy1coGhYhkF1NgJfgXPS', NULL, '(31) 99999-9999', 'admin', 1, 1, '2026-07-23 02:36:02', '2026-07-25 01:05:19'),
(13, 'maria silva', 'maria@gmail.com', '$2y$10$3HilKiAaUbRPk0HlUnxN/eAmEoBePC86bspDKZQG5QcItXKZxM9U6', NULL, '(16) 99723-8521', 'usuario', 6, 1, '2026-08-03 22:44:46', '2026-08-03 22:44:46');

INSERT INTO `categorias` (`id`, `nome`, `descricao`, `ativo`) VALUES
(1, 'Hardware / Computador', 'Problemas com gabinete, monitor, mouse, teclado, etc.', 1),
(2, 'Sistemas / Software', 'Erros em sistemas internos, navegadores ou programas', 1),
(3, 'Impressora / Escâner', 'Impressora sem papel, atolamento ou sem comunicação', 1),
(4, 'Rede / Internet', 'Sem acesso à rede local ou internet', 1),
(5, 'Hardware / Equipamento', NULL, 1),
(6, 'Sistemas / Software', NULL, 1),
(7, 'Rede / Internet', NULL, 1),
(8, 'Impressora', NULL, 1);

INSERT INTO `dispositivos` (`id`, `hostname`, `cpu_modelo`, `cpu_cores`, `cpu_threads`, `cpu_clock_mhz`, `cpu_socket`, `mobo_fabricante`, `mobo_modelo`, `bios_versao`, `gpu_modelo`, `gpu_vram_mb`, `ram_total_mb`, `ram_tipo`, `ram_clock_mhz`, `ram_pentes`, `disco_total_gb`, `discos`, `usuario_id`, `setor_id`, `setor_nome`, `ip_local`, `mac_address`, `os_nome`, `primeiro_acesso`, `ultimo_acesso`) VALUES
(459, 'antonio 10', 'Intel(R) Celeron(R) CPU 5205U @ 1.90GHz', 2, 2, 1896, 'U3E1', 'SAMSUNG ELECTRONICS CO., LTD.', 'NP550XCJ-KO1BR', 'P12RFH.054.220223.HC', 'Intel(R) UHD Graphics', 1024, 3911, 'DDR4', 2667, 1, 445, '[{\"unidade\":\"C:\",\"total_gb\":445.52,\"livre_gb\":296.51}]', NULL, 23, 'almoxerifado', '192.168.1.2', 'EC:63:D7:E9:29:E2', 'Microsoft Windows 11 Home Single Language (64 bits)', '2026-08-11 00:10:25', '2026-08-11 00:10:25');

INSERT INTO `dispositivos_hardware_original` (`id`, `dispositivo_id`, `alterado_por`, `cpu_modelo`, `ram_total_mb`, `ram_pentes`, `gpu_modelo`, `disco_total_gb`, `discos`, `criado_em`) VALUES
(9, 459, NULL, 'Intel(R) Celeron(R) CPU 5205U @ 1.90GHz', 3911, 1, 'Intel(R) UHD Graphics', 445, '[{\"unidade\":\"C:\",\"total_gb\":445.52,\"livre_gb\":296.51}]', '2026-08-10 21:10:25');

INSERT INTO `dispositivos_telemetria` (`id`, `dispositivo_id`, `ram_livre_mb`, `disco_livre_gb`, `alertas`, `criado_em`) VALUES
(75, 459, 618, 296, '[]', '2026-08-11 00:10:25');

INSERT INTO `chamados` (`id`, `protocolo`, `titulo`, `descricao`, `status`, `prioridade`, `usuario_id`, `tecnico_id`, `categoria_id`, `criado_em`, `atualizado_em`, `fechado_em`, `tempo_atendimento`) VALUES
(15, '20260804-9182', 'Problema de impressora', 'Bom dia.', 'resolvido', 'media', 13, NULL, 1, '2026-08-03 23:11:14', '2026-08-04 00:03:15', '2026-08-03 21:03:15', 3121),
(16, '20260804-3045', 'Problema de impressora', 'boa tarde.', 'resolvido', 'baixa', 13, NULL, 8, '2026-08-03 23:17:42', '2026-08-03 23:18:48', '2026-08-03 20:18:48', 301),
(17, '20260804-3042', 'Problema de impressora', 'boa noite. arrume', 'resolvido', 'media', 13, NULL, 5, '2026-08-03 23:33:24', '2026-08-04 00:02:20', '2026-08-03 21:02:20', 1736),
(18, '20260804-4038', 'sem internet', 'Boa noite', 'resolvido', 'media', 13, NULL, 5, '2026-08-04 00:09:02', '2026-08-04 00:13:58', '2026-08-03 21:13:58', 296);

INSERT INTO `chamados_respostas` (`id`, `chamado_id`, `usuario_id`, `mensagem`, `criado_em`) VALUES
(35, 18, 1, 'ok vamos resolver', '2026-08-03 21:13:32');

COMMIT;