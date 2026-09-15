-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 11-Ago-2026 às 02:10
-- Versão do servidor: 10.4.11-MariaDB
-- versão do PHP: 7.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `helpdesk_prefeitura`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `descricao`, `ativo`) VALUES
(1, 'Hardware / Computador', 'Problemas com gabinete, monitor, mouse, teclado, etc.', 1),
(2, 'Sistemas / Software', 'Erros em sistemas internos, navegadores ou programas', 1),
(3, 'Impressora / Escâner', 'Impressora sem papel, atolamento ou sem comunicação', 1),
(4, 'Rede / Internet', 'Sem acesso à rede local ou internet', 1),
(5, 'Hardware / Equipamento', NULL, 1),
(6, 'Sistemas / Software', NULL, 1),
(7, 'Rede / Internet', NULL, 1),
(8, 'Impressora', NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `chamados`
--

CREATE TABLE `chamados` (
  `id` int(11) NOT NULL,
  `protocolo` varchar(20) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `status` enum('novo','aberto','em_andamento','resolvido') NOT NULL DEFAULT 'novo',
  `prioridade` enum('baixa','media','alta','urgente') DEFAULT 'media',
  `usuario_id` int(11) NOT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fechado_em` datetime DEFAULT NULL,
  `tempo_atendimento` int(11) DEFAULT NULL COMMENT 'Tempo em segundos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `chamados`
--

INSERT INTO `chamados` (`id`, `protocolo`, `titulo`, `descricao`, `status`, `prioridade`, `usuario_id`, `tecnico_id`, `categoria_id`, `criado_em`, `atualizado_em`, `fechado_em`, `tempo_atendimento`) VALUES
(15, '20260804-9182', 'Problema de impressora', 'Bom dia.', 'resolvido', 'media', 13, NULL, 1, '2026-08-03 23:11:14', '2026-08-04 00:03:15', '2026-08-03 21:03:15', 3121),
(16, '20260804-3045', 'Problema de impressora', 'boa tarde.', 'resolvido', 'baixa', 13, NULL, 8, '2026-08-03 23:17:42', '2026-08-03 23:18:48', '2026-08-03 20:18:48', 301),
(17, '20260804-3042', 'Problema de impressora', 'boa noite. arrume', 'resolvido', 'media', 13, NULL, 5, '2026-08-03 23:33:24', '2026-08-04 00:02:20', '2026-08-03 21:02:20', 1736),
(18, '20260804-4038', 'sem internet', 'Boa noite', 'resolvido', 'media', 13, NULL, 5, '2026-08-04 00:09:02', '2026-08-04 00:13:58', '2026-08-03 21:13:58', 296);

-- --------------------------------------------------------

--
-- Estrutura da tabela `chamados_respostas`
--

CREATE TABLE `chamados_respostas` (
  `id` int(11) NOT NULL,
  `chamado_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `criado_em` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `chamados_respostas`
--

INSERT INTO `chamados_respostas` (`id`, `chamado_id`, `usuario_id`, `mensagem`, `criado_em`) VALUES
(35, 18, 1, 'ok vamos resolver', '2026-08-03 21:13:32');

-- --------------------------------------------------------

--
-- Estrutura da tabela `computadores_excluidos`
--

CREATE TABLE `computadores_excluidos` (
  `id` int(11) NOT NULL,
  `computador_id_original` int(11) DEFAULT NULL COMMENT 'ID que o PC tinha na tabela principal',
  `nome_computador` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_patrimonio` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sistema_operacional` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `setor_nome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Setor onde o PC estava instalado',
  `tecnico_id` int(11) DEFAULT NULL COMMENT 'ID do técnico/admin que realizou a exclusão',
  `tecnico_nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome registrado no momento da exclusão',
  `motivo_exclusao` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Justificativa para remover o PC (ex: Sucateado, Doador de peças)',
  `excluido_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `dispositivos`
--

CREATE TABLE `dispositivos` (
  `id` int(11) NOT NULL,
  `hostname` varchar(100) NOT NULL,
  `cpu_modelo` varchar(150) DEFAULT NULL,
  `cpu_cores` int(11) DEFAULT NULL,
  `cpu_threads` int(11) DEFAULT NULL,
  `cpu_clock_mhz` int(11) DEFAULT NULL,
  `cpu_socket` varchar(50) DEFAULT NULL,
  `mobo_fabricante` varchar(100) DEFAULT NULL,
  `mobo_modelo` varchar(100) DEFAULT NULL,
  `bios_versao` varchar(100) DEFAULT NULL,
  `gpu_modelo` varchar(150) DEFAULT NULL,
  `gpu_vram_mb` int(11) DEFAULT NULL,
  `ram_total_mb` int(11) NOT NULL,
  `ram_tipo` varchar(20) DEFAULT NULL,
  `ram_clock_mhz` int(11) DEFAULT NULL,
  `ram_pentes` int(11) DEFAULT NULL,
  `disco_total_gb` int(11) DEFAULT NULL,
  `discos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`discos`)),
  `usuario_id` int(11) DEFAULT NULL COMMENT 'Usuário principal ou dono do dispositivo',
  `setor_id` int(11) DEFAULT NULL COMMENT 'Setor onde o PC está alocado',
  `setor_nome` varchar(100) DEFAULT NULL,
  `ip_local` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `os_nome` varchar(100) DEFAULT NULL,
  `primeiro_acesso` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acesso` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `dispositivos`
--

INSERT INTO `dispositivos` (`id`, `hostname`, `cpu_modelo`, `cpu_cores`, `cpu_threads`, `cpu_clock_mhz`, `cpu_socket`, `mobo_fabricante`, `mobo_modelo`, `bios_versao`, `gpu_modelo`, `gpu_vram_mb`, `ram_total_mb`, `ram_tipo`, `ram_clock_mhz`, `ram_pentes`, `disco_total_gb`, `discos`, `usuario_id`, `setor_id`, `setor_nome`, `ip_local`, `mac_address`, `os_nome`, `primeiro_acesso`, `ultimo_acesso`) VALUES
(459, 'antonio 10', 'Intel(R) Celeron(R) CPU 5205U @ 1.90GHz', 2, 2, 1896, 'U3E1', 'SAMSUNG ELECTRONICS CO., LTD.', 'NP550XCJ-KO1BR', 'P12RFH.054.220223.HC', 'Intel(R) UHD Graphics', 1024, 3911, 'DDR4', 2667, 1, 445, '[{\"unidade\":\"C:\",\"total_gb\":445.52,\"livre_gb\":296.51}]', NULL, 23, 'almoxerifado', '192.168.1.2', 'EC:63:D7:E9:29:E2', 'Microsoft Windows 11 Home Single Language (64 bits)', '2026-08-11 00:10:25', '2026-08-11 00:10:25');

-- --------------------------------------------------------

--
-- Estrutura da tabela `dispositivos_hardware_original`
--

CREATE TABLE `dispositivos_hardware_original` (
  `id` int(11) NOT NULL,
  `dispositivo_id` int(11) NOT NULL,
  `alterado_por` varchar(255) DEFAULT NULL,
  `cpu_modelo` varchar(255) DEFAULT NULL,
  `ram_total_mb` int(11) DEFAULT NULL,
  `ram_pentes` int(11) DEFAULT NULL,
  `gpu_modelo` varchar(255) DEFAULT NULL,
  `disco_total_gb` int(11) DEFAULT NULL,
  `discos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`discos`)),
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `dispositivos_hardware_original`
--

INSERT INTO `dispositivos_hardware_original` (`id`, `dispositivo_id`, `alterado_por`, `cpu_modelo`, `ram_total_mb`, `ram_pentes`, `gpu_modelo`, `disco_total_gb`, `discos`, `criado_em`) VALUES
(9, 459, NULL, 'Intel(R) Celeron(R) CPU 5205U @ 1.90GHz', 3911, 1, 'Intel(R) UHD Graphics', 445, '[{\"unidade\":\"C:\",\"total_gb\":445.52,\"livre_gb\":296.51}]', '2026-08-10 21:10:25');

-- --------------------------------------------------------

--
-- Estrutura da tabela `dispositivos_telemetria`
--

CREATE TABLE `dispositivos_telemetria` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dispositivo_id` int(11) NOT NULL,
  `ram_livre_mb` int(11) NOT NULL,
  `disco_livre_gb` int(11) DEFAULT NULL,
  `alertas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ex: ["CRITICAL_LOW_RAM", "CRITICAL_LOW_DISK"]' CHECK (json_valid(`alertas`)),
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `dispositivos_telemetria`
--

INSERT INTO `dispositivos_telemetria` (`id`, `dispositivo_id`, `ram_livre_mb`, `disco_livre_gb`, `alertas`, `criado_em`) VALUES
(75, 459, 618, 296, '[]', '2026-08-11 00:10:25');

-- --------------------------------------------------------

--
-- Estrutura da tabela `interacoes_chamado`
--

CREATE TABLE `interacoes_chamado` (
  `id` int(11) NOT NULL,
  `chamado_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `secretarias_setores`
--

CREATE TABLE `secretarias_setores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `sigla` varchar(20) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `secretarias_setores`
--

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

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `perfil` enum('admin','tecnico','usuario') NOT NULL DEFAULT 'usuario',
  `setor_id` int(11) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `cpf`, `telefone`, `perfil`, `setor_id`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'testadmin', 'testadmin@borborema.sp.gov.br', '$2y$10$FFnSpFO6YqO6RSNFFeQzmOoXRq.FV6qvAOy1coGhYhkF1NgJfgXPS', NULL, '(31) 99999-9999', 'admin', 1, 1, '2026-07-23 02:36:02', '2026-07-25 01:05:19'),
(13, 'maria silva', 'maria@gmail.com', '$2y$10$3HilKiAaUbRPk0HlUnxN/eAmEoBePC86bspDKZQG5QcItXKZxM9U6', NULL, '(16) 99723-8521', 'usuario', 6, 1, '2026-08-03 22:44:46', '2026-08-03 22:44:46');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `chamados`
--
ALTER TABLE `chamados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `protocolo` (`protocolo`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `tecnico_id` (`tecnico_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices para tabela `chamados_respostas`
--
ALTER TABLE `chamados_respostas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chamado_id` (`chamado_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `computadores_excluidos`
--
ALTER TABLE `computadores_excluidos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `dispositivos`
--
ALTER TABLE `dispositivos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_hostname` (`hostname`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_setor_id` (`setor_id`);

--
-- Índices para tabela `dispositivos_hardware_original`
--
ALTER TABLE `dispositivos_hardware_original`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dispositivo_id` (`dispositivo_id`);

--
-- Índices para tabela `dispositivos_telemetria`
--
ALTER TABLE `dispositivos_telemetria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dispositivo_id` (`dispositivo_id`),
  ADD KEY `idx_dispositivo_data` (`dispositivo_id`,`criado_em`),
  ADD KEY `idx_criado_em` (`criado_em`);

--
-- Índices para tabela `interacoes_chamado`
--
ALTER TABLE `interacoes_chamado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chamado_id` (`chamado_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices para tabela `secretarias_setores`
--
ALTER TABLE `secretarias_setores`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `setor_id` (`setor_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `chamados`
--
ALTER TABLE `chamados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `chamados_respostas`
--
ALTER TABLE `chamados_respostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de tabela `computadores_excluidos`
--
ALTER TABLE `computadores_excluidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `dispositivos`
--
ALTER TABLE `dispositivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=460;

--
-- AUTO_INCREMENT de tabela `dispositivos_hardware_original`
--
ALTER TABLE `dispositivos_hardware_original`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `dispositivos_telemetria`
--
ALTER TABLE `dispositivos_telemetria`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT de tabela `interacoes_chamado`
--
ALTER TABLE `interacoes_chamado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `secretarias_setores`
--
ALTER TABLE `secretarias_setores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `chamados`
--
ALTER TABLE `chamados`
  ADD CONSTRAINT `chamados_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `chamados_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `chamados_ibfk_3` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Limitadores para a tabela `chamados_respostas`
--
ALTER TABLE `chamados_respostas`
  ADD CONSTRAINT `chamados_respostas_ibfk_1` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chamados_respostas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `dispositivos`
--
ALTER TABLE `dispositivos`
  ADD CONSTRAINT `fk_dispositivos_setores` FOREIGN KEY (`setor_id`) REFERENCES `secretarias_setores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dispositivos_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `dispositivos_hardware_original`
--
ALTER TABLE `dispositivos_hardware_original`
  ADD CONSTRAINT `dispositivos_hardware_original_ibfk_1` FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `dispositivos_telemetria`
--
ALTER TABLE `dispositivos_telemetria`
  ADD CONSTRAINT `fk_telemetria_dispositivos` FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `interacoes_chamado`
--
ALTER TABLE `interacoes_chamado`
  ADD CONSTRAINT `interacoes_chamado_ibfk_1` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interacoes_chamado_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`setor_id`) REFERENCES `secretarias_setores` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
