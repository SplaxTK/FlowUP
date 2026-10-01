-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/09/2026 às 21:18
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `flowup` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `flowup`;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `flowup`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `conclusoes_tarefas`
--

CREATE TABLE `conclusoes_tarefas` (
  `id_conclusao` int(11) NOT NULL,
  `id_tarefas` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `periodo` varchar(20) NOT NULL,
  `qtd_pontos` int(11) NOT NULL,
  `data_conclusao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_pontos`
--

CREATE TABLE `historico_pontos` (
  `id_historico_pontos` int(11) NOT NULL,
  `id_pontos` int(11) NOT NULL,
  `qtd_pontos_anterior` int(11) NOT NULL,
  `qtd_pontos_novo` int(11) NOT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notas_calendario`
--

CREATE TABLE `notas_calendario` (
  `id_calendario` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `dt_nota` date NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id_notificacao` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensagem` text NOT NULL,
  `dt_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `dt_leitura` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `novas_recompensas`
--

CREATE TABLE `novas_recompensas` (
  `id_recompensa` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `qtd_pon_neces` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `status` enum('resgatada','pendente') NOT NULL DEFAULT 'pendente',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `novas_tarefas`
--

CREATE TABLE `novas_tarefas` (
  `id_tarefas` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `tipo` enum('diaria','semanal','mensal','meta') NOT NULL DEFAULT 'diaria',
  `qtd_pon_ganho` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `status` enum('ativa','inativa','concluida') NOT NULL DEFAULT 'ativa',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pontos`
--

CREATE TABLE `pontos` (
  `id_pontos` int(11) NOT NULL,
  `qtd_pontos` int(11) NOT NULL DEFAULT 0,
  `email` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `recompensas`
--

CREATE TABLE `recompensas` (
  `id_recompensa` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `qtd_pon_neces` int(11) NOT NULL,
  `status` enum('disponivel','indisponivel') NOT NULL DEFAULT 'disponivel',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tarefas`
--

CREATE TABLE `tarefas` (
  `id_tarefas` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `qtd_pon_ganho` int(11) NOT NULL,
  `status` enum('ativa','inativa') NOT NULL DEFAULT 'ativa',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome_completo` varchar(150) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `conclusoes_tarefas`
--
ALTER TABLE `conclusoes_tarefas`
  ADD PRIMARY KEY (`id_conclusao`),
  ADD UNIQUE KEY `tarefa_periodo_usuario` (`id_tarefas`,`email`,`periodo`),
  ADD KEY `fk_conclusoes_usuario` (`email`);

--
-- Índices de tabela `historico_pontos`
--
ALTER TABLE `historico_pontos`
  ADD PRIMARY KEY (`id_historico_pontos`),
  ADD KEY `fk_historico_pontos` (`id_pontos`);

--
-- Índices de tabela `notas_calendario`
--
ALTER TABLE `notas_calendario`
  ADD PRIMARY KEY (`id_calendario`),
  ADD KEY `fk_notas_calendario_usuario` (`email`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id_notificacao`),
  ADD KEY `fk_notificacoes_usuario` (`email`);

--
-- Índices de tabela `novas_recompensas`
--
ALTER TABLE `novas_recompensas`
  ADD PRIMARY KEY (`id_recompensa`),
  ADD KEY `fk_novas_recompensas_usuario` (`email`);

--
-- Índices de tabela `novas_tarefas`
--
ALTER TABLE `novas_tarefas`
  ADD PRIMARY KEY (`id_tarefas`),
  ADD KEY `fk_novas_tarefas_usuario` (`email`);

--
-- Índices de tabela `pontos`
--
ALTER TABLE `pontos`
  ADD PRIMARY KEY (`id_pontos`),
  ADD KEY `fk_pontos_usuario` (`email`);

--
-- Índices de tabela `recompensas`
--
ALTER TABLE `recompensas`
  ADD PRIMARY KEY (`id_recompensa`);

--
-- Índices de tabela `tarefas`
--
ALTER TABLE `tarefas`
  ADD PRIMARY KEY (`id_tarefas`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `conclusoes_tarefas`
--
ALTER TABLE `conclusoes_tarefas`
  MODIFY `id_conclusao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_pontos`
--
ALTER TABLE `historico_pontos`
  MODIFY `id_historico_pontos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notas_calendario`
--
ALTER TABLE `notas_calendario`
  MODIFY `id_calendario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id_notificacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `novas_recompensas`
--
ALTER TABLE `novas_recompensas`
  MODIFY `id_recompensa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `novas_tarefas`
--
ALTER TABLE `novas_tarefas`
  MODIFY `id_tarefas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pontos`
--
ALTER TABLE `pontos`
  MODIFY `id_pontos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `recompensas`
--
ALTER TABLE `recompensas`
  MODIFY `id_recompensa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tarefas`
--
ALTER TABLE `tarefas`
  MODIFY `id_tarefas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `conclusoes_tarefas`
--
ALTER TABLE `conclusoes_tarefas`
  ADD CONSTRAINT `fk_conclusoes_tarefa` FOREIGN KEY (`id_tarefas`) REFERENCES `novas_tarefas` (`id_tarefas`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conclusoes_usuario` FOREIGN KEY (`email`) REFERENCES `usuarios` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `historico_pontos`
--
ALTER TABLE `historico_pontos`
  ADD CONSTRAINT `fk_historico_pontos` FOREIGN KEY (`id_pontos`) REFERENCES `pontos` (`id_pontos`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notas_calendario`
--
ALTER TABLE `notas_calendario`
  ADD CONSTRAINT `fk_notas_calendario_usuario` FOREIGN KEY (`email`) REFERENCES `usuarios` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `fk_notificacoes_usuario` FOREIGN KEY (`email`) REFERENCES `usuarios` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `novas_recompensas`
--
ALTER TABLE `novas_recompensas`
  ADD CONSTRAINT `fk_novas_recompensas_usuario` FOREIGN KEY (`email`) REFERENCES `usuarios` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `novas_tarefas`
--
ALTER TABLE `novas_tarefas`
  ADD CONSTRAINT `fk_novas_tarefas_usuario` FOREIGN KEY (`email`) REFERENCES `usuarios` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `pontos`
--
ALTER TABLE `pontos`
  ADD CONSTRAINT `fk_pontos_usuario` FOREIGN KEY (`email`) REFERENCES `usuarios` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
