-- phpMyAdmin SQL Dump
-- flowup.sql totalmente padronizado com Chaves Estrangeiras por ID (user_id)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `flowup` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `flowup`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- 1. Tabela Principal: Usuários
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome_completo` VARCHAR(150) NOT NULL,
  `usuario` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Tabelas do Sistema Geral (Vinculadas por user_id)
-- --------------------------------------------------------

-- Tarefas Padrão do Sistema (Globais)
CREATE TABLE IF NOT EXISTS `tarefas` (
  `id_tarefas` INT(11) NOT NULL AUTO_INCREMENT,
  `descricao` TEXT NOT NULL,
  `qtd_pon_ganho` INT(11) NOT NULL,
  `status` ENUM('ativa','inativa') NOT NULL DEFAULT 'ativa',
  `data_criacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tarefas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recompensas Padrão do Sistema (Globais)
CREATE TABLE IF NOT EXISTS `recompensas` (
  `id_recompensa` INT(11) NOT NULL AUTO_INCREMENT,
  `descricao` TEXT NOT NULL,
  `qtd_pon_neces` INT(11) NOT NULL,
  `status` ENUM('disponivel','indisponivel') NOT NULL DEFAULT 'disponivel',
  `data_criacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_recompensa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarefas Criadas pelo Usuário
CREATE TABLE IF NOT EXISTS `novas_tarefas` (
  `id_tarefas` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `descricao` TEXT NOT NULL,
  `tipo` ENUM('diaria','semanal','mensal','meta') NOT NULL DEFAULT 'diaria',
  `qtd_pon_ganho` INT(11) NOT NULL,
  `status` ENUM('ativa','inativa','concluida') NOT NULL DEFAULT 'ativa',
  `data_criacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tarefas`),
  KEY `fk_novas_tarefas_user` (`user_id`),
  CONSTRAINT `fk_novas_tarefas_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conclusões de Tarefas
CREATE TABLE IF NOT EXISTS `conclusoes_tarefas` (
  `id_conclusao` INT(11) NOT NULL AUTO_INCREMENT,
  `id_tarefas` INT(11) NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `periodo` VARCHAR(20) NOT NULL,
  `qtd_pontos` INT(11) NOT NULL,
  `data_conclusao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_conclusao`),
  UNIQUE KEY `tarefa_periodo_usuario` (`id_tarefas`,`user_id`,`periodo`),
  KEY `fk_conclusoes_user` (`user_id`),
  CONSTRAINT `fk_conclusoes_tarefa` FOREIGN KEY (`id_tarefas`) REFERENCES `novas_tarefas` (`id_tarefas`) ON DELETE CASCADE,
  CONSTRAINT `fk_conclusoes_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recompensas Personalizadas do Usuário
CREATE TABLE IF NOT EXISTS `novas_recompensas` (
  `id_recompensa` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `descricao` TEXT NOT NULL,
  `qtd_pon_neces` INT(11) NOT NULL,
  `status` ENUM('resgatada','pendente') NOT NULL DEFAULT 'pendente',
  `data_criacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_recompensa`),
  KEY `fk_novas_recompensas_user` (`user_id`),
  CONSTRAINT `fk_novas_recompensas_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pontuação Atual do Usuário
CREATE TABLE IF NOT EXISTS `pontos` (
  `id_pontos` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `qtd_pontos` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_pontos`),
  UNIQUE KEY `uq_pontos_user` (`user_id`),
  CONSTRAINT `fk_pontos_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico de Pontos
CREATE TABLE IF NOT EXISTS `historico_pontos` (
  `id_historico_pontos` INT(11) NOT NULL AUTO_INCREMENT,
  `id_pontos` INT(11) NOT NULL,
  `qtd_pontos_anterior` INT(11) NOT NULL,
  `qtd_pontos_novo` INT(11) NOT NULL,
  `data_alteracao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historico_pontos`),
  KEY `fk_historico_pontos` (`id_pontos`),
  CONSTRAINT `fk_historico_pontos` FOREIGN KEY (`id_pontos`) REFERENCES `pontos` (`id_pontos`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notas Antigas do Calendário
CREATE TABLE IF NOT EXISTS `notas_calendario` (
  `id_calendario` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `dt_nota` DATE NOT NULL,
  `descricao` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_calendario`),
  KEY `fk_notas_calendario_user` (`user_id`),
  CONSTRAINT `fk_notas_calendario_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notificações do Sistema
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id_notificacao` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `mensagem` TEXT NOT NULL,
  `dt_criacao` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dt_leitura` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id_notificacao`),
  KEY `fk_notificacoes_user` (`user_id`),
  CONSTRAINT `fk_notificacoes_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabelas do Calendário Avançado (Vinculadas por user_id)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `calendar_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `name` VARCHAR(60) NOT NULL,
  `color` CHAR(7) NOT NULL DEFAULT '#5688ff',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_calendar_category_user_name` (`user_id`, `name`),
  KEY `idx_calendar_category_user` (`user_id`),
  CONSTRAINT `fk_calendar_category_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(180) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `starts_at` DATETIME NOT NULL,
  `ends_at` DATETIME NOT NULL,
  `all_day` TINYINT(1) NOT NULL DEFAULT 0,
  `location` VARCHAR(255) DEFAULT NULL,
  `color` CHAR(7) NOT NULL DEFAULT '#5688ff',
  `guests` TEXT DEFAULT NULL,
  `repeat_rule` VARCHAR(20) DEFAULT NULL,
  `reminder_minutes` SMALLINT UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `google_event_id` VARCHAR(255) DEFAULT NULL,
  `google_calendar_id` VARCHAR(255) DEFAULT NULL,
  `google_etag` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_calendar_google_event` (`user_id`, `google_calendar_id`, `google_event_id`),
  KEY `idx_calendar_event_dates` (`user_id`, `starts_at`, `ends_at`),
  KEY `idx_calendar_event_category` (`user_id`, `category_id`),
  CONSTRAINT `fk_calendar_event_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_calendar_event_category` FOREIGN KEY (`category_id`) REFERENCES `calendar_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `calendar_google_accounts` (
  `user_id` INT(10) UNSIGNED NOT NULL PRIMARY KEY,
  `email` VARCHAR(255) DEFAULT NULL,
  `encrypted_token` MEDIUMTEXT NOT NULL,
  `token_expires_at` DATETIME DEFAULT NULL,
  `calendar_id` VARCHAR(255) NOT NULL DEFAULT 'primary',
  `last_synced_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_calendar_google_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

CREATE TABLE IF NOT EXISTS `alteracao_senha_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expira_em` DATETIME NOT NULL,
  `usado_em` DATETIME NULL DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alt_senha_token_hash` (`token_hash`),
  KEY `idx_alt_senha_tokens_usuario_criado` (`usuario_id`, `criado_em`),
  CONSTRAINT `fk_alt_senha_tokens_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Senhas atuais erradas (limita tentativas de adivinhar a senha atual).
CREATE TABLE IF NOT EXISTS `alteracao_senha_tentativas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_alt_senha_tentativas_usuario_criado` (`usuario_id`, `criado_em`),
  CONSTRAINT `fk_alt_senha_tentativas_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;