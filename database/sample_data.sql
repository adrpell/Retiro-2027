-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 02-Abr-2026 às 17:17
-- Versão do servidor: 5.7.24
-- versão do PHP: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `retiro2027`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `access_code` varchar(30) NOT NULL,
  `registration_type` varchar(20) NOT NULL DEFAULT 'individual',
  `responsible_name` varchar(190) NOT NULL,
  `responsible_age` int(11) DEFAULT NULL,
  `responsible_phone` varchar(30) DEFAULT NULL,
  `responsible_email` varchar(190) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `payment_method` varchar(40) NOT NULL DEFAULT 'nao_definido',
  `installments` int(11) NOT NULL DEFAULT '1',
  `receipt_file` varchar(255) DEFAULT NULL,
  `notes` text,
  `status` varchar(30) NOT NULL DEFAULT 'intencao',
  `total_people` int(11) NOT NULL DEFAULT '1',
  `suggested_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `amount_pending` decimal(10,2) NOT NULL DEFAULT '0.00',
  `financial_status` varchar(30) NOT NULL DEFAULT 'pendente',
  `group_accommodation` varchar(30) NOT NULL DEFAULT 'personalizado',
  `has_child` tinyint(1) NOT NULL DEFAULT '0',
  `has_elderly` tinyint(1) NOT NULL DEFAULT '0',
  `source` varchar(30) NOT NULL DEFAULT 'site',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `groups`
--

INSERT INTO `groups` (`id`, `access_code`, `registration_type`, `responsible_name`, `responsible_age`, `responsible_phone`, `responsible_email`, `city`, `payment_method`, `installments`, `receipt_file`, `notes`, `status`, `total_people`, `suggested_value`, `discount_value`, `amount_paid`, `amount_pending`, `financial_status`, `group_accommodation`, `has_child`, `has_elderly`, `source`, `created_at`, `updated_at`) VALUES
(1, 'RET001', 'familia', 'John', 43, '(21) 98802-4614', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 5, '0.00', '0.00', '0.00', '0.00', 'pendente', 'personalizado', 1, 0, 'base_planilha', '2026-03-23 10:39:15', '2026-04-02 16:36:26'),
(2, 'RET002', 'familia', 'Marvyn', 35, '(21) 97943-3250', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 10:41:29', '2026-04-02 16:37:17'),
(3, 'RET003', 'familia', 'Tiago Rocha', NULL, '(21) 99370-0034', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 10:48:25', '2026-04-02 16:38:07'),
(4, 'RET004', 'familia', 'Samuel Decco', NULL, '(21) 98113-0742', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 2, '0.00', '0.00', '0.00', '0.00', 'pendente', 'casa', 0, 0, 'base_planilha', '2026-03-23 11:16:07', '2026-04-02 16:38:54'),
(5, 'RET005', 'familia', 'Fernando', NULL, '(21) 96488-6786', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 7, '0.00', '0.00', '0.00', '0.00', 'pendente', 'personalizado', 0, 1, 'base_planilha', '2026-03-23 11:25:29', '2026-04-02 16:39:44'),
(6, 'RET006', 'individual', 'Raphael Muglia', 37, '(21) 99510-1914', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 1, '0.00', '0.00', '0.00', '0.00', 'pendente', 'alojamento', 0, 0, 'base_planilha', '2026-03-23 11:29:14', '2026-04-02 16:40:39'),
(7, 'RET007', 'familia', 'Renato de Souza', 48, '(21) 99635-4543', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'alojamento', 0, 0, 'base_planilha', '2026-03-23 11:59:52', '2026-04-02 16:41:51'),
(8, 'RET008', 'individual', 'Débora Oliveira', 17, '(21) 98685-1983', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 1, '0.00', '0.00', '0.00', '0.00', 'pendente', 'alojamento', 0, 0, 'base_planilha', '2026-03-23 12:00:43', '2026-04-02 16:41:39'),
(9, 'RET009', 'familia', 'Gleverson', NULL, '(21) 97204-0202', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 0, 0, 'base_planilha', '2026-03-23 12:17:49', '2026-04-02 16:42:34'),
(10, 'RET010', 'familia', 'Enrico', 30, '(21) 99985-1547', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 12:44:36', '2026-04-02 16:43:25'),
(11, 'RET011', 'familia', 'Adriano', 54, '(21) 99532-7786', 'adrpat@gmail.com', NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'personalizado', 0, 0, 'base_planilha', '2026-03-23 14:27:06', '2026-04-02 16:44:04'),
(12, 'RET012', 'individual', 'Ana Paula', 32, '(21) 97040-3839', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 1, '0.00', '0.00', '0.00', '0.00', 'pendente', 'alojamento', 0, 0, 'base_planilha', '2026-03-23 15:33:27', '2026-04-02 16:44:39'),
(13, 'RET013', 'familia', 'Matheus Fernandes', NULL, '(21) 96523-3020', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 18:52:39', '2026-04-02 16:45:17'),
(14, 'RET014', 'individual', 'Carlos Alberto de Matos Freitas', 73, '(21) 99442-6660', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 1, '0.00', '0.00', '0.00', '0.00', 'pendente', 'alojamento', 0, 1, 'base_planilha', '2026-03-24 10:28:19', '2026-04-02 16:45:56'),
(15, 'RET015', 'individual', 'Gustavo Pimentel Lerner', 34, '(21) 98626-1644', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 1, '0.00', '0.00', '0.00', '0.00', 'pendente', 'alojamento', 0, 0, 'base_planilha', '2026-03-24 13:05:41', '2026-04-02 16:46:24'),
(16, 'RET016', 'familia', 'Rodrigo Gama', 34, '(21) 96678-5123', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-24 13:41:21', '2026-04-02 16:47:20'),
(17, 'RET017', 'familia', 'Cláudio Augusto Vital dos Santos', 36, '(21) 97464-1530', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-25 11:39:44', '2026-04-02 16:48:06'),
(18, 'RET018', 'familia', 'Alexis Oliveira', NULL, '(21) 97548-4831', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-25 12:36:27', '2026-04-02 16:48:37'),
(19, 'RET019', 'familia', 'Leandro Pacheco de Melo', 46, '(21) 99122-3233', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:49:23'),
(20, 'RET020', 'familia', 'Bruno Carvalho de Castro', NULL, '(21) 97227-7583', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 5, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:50:01'),
(21, 'RET021', 'familia', 'Roberto Cordeiro Faulhaber', 46, '(21) 98049-4856', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 0, 1, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:50:46'),
(22, 'RET022', 'familia', 'Roberto Rodrigues', NULL, '(21) 98022-6525', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 0, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:51:26'),
(23, 'RET023', 'familia', 'Mylena lyra', 22, '(21) 99899-6082', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'alojamento', 0, 1, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:51:56'),
(24, 'RET024', 'familia', 'Thiago Pedrosa', 38, '(21) 98218-9759', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:52:39'),
(25, 'RET025', 'familia', 'Marcio Vinicius Soares Baptista de Oliveira', 43, '(21) 98685-1983', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 5, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:53:18'),
(26, 'RET026', 'familia', 'Rodrigo B. Rosa', 52, '(21) 98240-2115', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 1, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:54:09'),
(27, 'RET027', 'familia', 'Marcelo Maia', NULL, '(21) 98802-4609', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'personalizado', 0, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:54:52'),
(28, 'RET028', 'individual', 'Felipe Onoda', 19, '(21) 99750-0277', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 1, '0.00', '0.00', '0.00', '0.00', 'pendente', 'alojamento', 0, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:55:25'),
(29, 'RET029', 'familia', 'Claudio dos Santos Rosa ', NULL, '(21) 99374-0835', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 3, '0.00', '0.00', '0.00', '0.00', 'pendente', 'personalizado', 0, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:55:55'),
(30, 'RET030', 'familia', 'Eduardo Rangel', 35, '(21) 96958-6370', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:56:33'),
(31, 'RET031', 'individual', 'Mariana Homero ( sobrinha Roberto e Fatima)', 16, '(21) 98022-6525', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 1, '0.00', '0.00', '0.00', '0.00', 'pendente', 'alojamento', 0, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:57:07'),
(32, 'RET032', 'familia', 'Felipe Ovelha', 40, '(21) 98113-6187', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-03-23 10:00:00', '2026-04-02 16:57:39'),
(33, 'RET033', 'familia', 'Andrew', NULL, '(21) 98802-4608', NULL, NULL, 'nao_definido', 1, NULL, NULL, 'intencao', 4, '0.00', '0.00', '0.00', '0.00', 'pendente', 'chale', 1, 0, 'base_planilha', '2026-01-04 09:15:35', '2026-04-02 16:58:15');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `access_code` (`access_code`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
