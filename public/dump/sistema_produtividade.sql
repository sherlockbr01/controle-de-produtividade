-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 18/03/2025 às 13:46
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema_produtividade`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `afastamentos`
--

CREATE TABLE `afastamentos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tipo_afastamento_id` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_termino` date NOT NULL,
  `comentario` text DEFAULT NULL,
  `status` enum('Pendente','Aprovado','Rejeitado') DEFAULT 'Pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `afastamentos`
--

INSERT INTO `afastamentos` (`id`, `user_id`, `tipo_afastamento_id`, `data_inicio`, `data_termino`, `comentario`, `status`, `created_at`, `updated_at`) VALUES
(45, 40, 2, '2025-03-07', '2025-03-07', 'OPCIONAL', 'Aprovado', '2025-03-07 18:23:25', '2025-03-07 18:25:06'),
(46, 40, 2, '2025-03-08', '2025-03-08', 'Venho por meio deste justificar o meu afastamento temporário das atividades laborais devido à necessidade de um período de licença médica. A recomendação do profissional de saúde é de repouso para recuperação de [especificar problema de saúde, se desejar], o que impossibilita o desempenho das funções habituais. Comprometo-me a apresentar o devido atestado médico e retornar assim que houver melhora em meu quadro de saúde. Agradeço pela compreensão.', 'Aprovado', '2025-03-07 18:24:19', '2025-03-07 18:25:31'),
(47, 40, 2, '2025-03-09', '2025-03-09', '', 'Rejeitado', '2025-03-07 18:24:30', '2025-03-07 18:25:50'),
(48, 33, 1, '2025-03-11', '2025-03-15', '', 'Aprovado', '2025-03-10 12:11:39', '2025-03-10 12:11:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `decision_types`
--

CREATE TABLE `decision_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `decision_types`
--

INSERT INTO `decision_types` (`id`, `name`, `points`) VALUES
(1, 'Vara - Despacho', 1),
(2, 'Vara - Decisão Simples/Padrão', 2),
(3, 'Vara - Decisão Média Complexidade', 3),
(4, 'Vara - Decisão Complexa', 4),
(5, 'Vara - Sent. Mérito Simples', 3),
(6, 'Vara - Sent. Mérito Média Complexidade', 5),
(7, 'Vara - Sent. Mérito Complexo', 7),
(8, 'Vara - Sent. Extintiva', 2),
(9, 'Vara - Sent. Homologatória/Repetitiva', 2),
(10, 'JEF - Despacho', 1),
(11, 'JEF - Decisão', 2),
(12, 'JEF - Sent. Mérito', 3),
(13, 'JEF - Sent. Extintiva', 2),
(14, 'JEF - Sent. Homologatória/Repetitiva', 2),
(15, 'Audiência', 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `groups`
--

INSERT INTO `groups` (`id`, `name`, `description`, `created_at`) VALUES
(22, 'Grupo Laranja', 'Execução Fiscal', '2025-02-27 12:10:10'),
(23, 'd', 'd', '2025-02-28 18:51:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `group_users`
--

CREATE TABLE `group_users` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `group_users`
--

INSERT INTO `group_users` (`id`, `group_id`, `user_id`) VALUES
(52, 22, 41),
(54, 22, 33),
(55, 23, 34);

-- --------------------------------------------------------

--
-- Estrutura para tabela `minute_types`
--

CREATE TABLE `minute_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `minute_types`
--

INSERT INTO `minute_types` (`id`, `name`, `user_id`) VALUES
(40, 'Minuta 1', 41),
(41, 'minuta 2', 40),
(42, '321312', 34),
(43, '321312', 34),
(44, '6666', 34),
(45, '3123123', 34),
(46, 'ronaldo', 34),
(47, 'ronaldo', 34),
(48, '11111', 34),
(49, '321321', 34),
(50, 'testx', 34),
(51, 'dsadsadsadsa', 34),
(52, 'modal', 34),
(53, '7777', 34),
(54, '888888', 33),
(55, 'decisão sobre penhora de bens', 41),
(56, 'decisão sobre penhora de bens', 41),
(57, 'decisão sobre penhora de bens', 41),
(58, 'decisão sobre penhora de bens', 41),
(59, 'decisão sobre penhora de bens', 41),
(60, 'decisão sobre penhora de bens', 41),
(61, 'decisão sobre penhora de bens', 41),
(62, 'extinção pelo pagamento', 41),
(63, '12321', 33),
(64, 'teste', 33),
(65, 'teste', 33),
(66, 'decisão sobre penhora de bens', 33),
(67, 'dsadsasaddas', 33),
(68, 'fffffffff', 33),
(69, '4343', 33),
(70, 'fxxx', 33),
(71, 'jjjjjjj', 33),
(72, 'jjjjjjj', 33),
(73, 'jjjjjjj', 33),
(74, 'affas', 33),
(75, 'ba', 33),
(76, '451', 34),
(77, 'teste1', 34),
(78, 'teste1', 34),
(79, 'teste2', 34),
(80, '1', 38),
(81, '2', 38),
(82, '3', 38),
(83, '4', 38),
(84, '5', 38),
(85, '6', 38);

-- --------------------------------------------------------

--
-- Estrutura para tabela `productivity`
--

CREATE TABLE `productivity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `process_number` varchar(50) NOT NULL,
  `minute_type_id` int(11) DEFAULT NULL,
  `decision_type_id` int(11) DEFAULT NULL,
  `points` int(11) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `productivity`
--

INSERT INTO `productivity` (`id`, `user_id`, `process_number`, `minute_type_id`, `decision_type_id`, `points`, `date`, `created_at`) VALUES
(27, 41, '0800441', 40, 1, 1, '0000-00-00', '2025-02-27 15:31:24'),
(28, 40, '0800654', 41, 2, 2, '0000-00-00', '2025-02-27 15:32:09'),
(29, 41, '56465', 40, 3, 3, '0000-00-00', '2025-03-05 18:08:40'),
(30, 41, '43242342', 40, 2, 2, '0000-00-00', '2025-03-05 18:11:33'),
(31, 41, '321312312', 40, 5, 3, '0000-00-00', '2025-03-05 18:17:33'),
(32, 41, '312321', 40, 3, 3, '0000-00-00', '2025-03-05 18:18:46'),
(33, 41, '321321312', 40, 1, 1, '0000-00-00', '2025-03-05 18:21:08'),
(34, 41, '321321312', 40, 2, 2, '0000-00-00', '2025-03-05 18:24:25'),
(35, 41, '321321312', 40, 4, 4, '0000-00-00', '2025-03-05 18:24:59'),
(36, 40, '32312312', 41, 2, 2, '0000-00-00', '2025-03-07 18:29:29'),
(37, 33, '65465', 54, 1, 1, '0000-00-00', '2025-03-10 16:46:43'),
(38, 33, '46456456', 54, 2, 2, '0000-00-00', '2025-03-10 16:47:02'),
(39, 41, '987879978', 55, 4, 4, '0000-00-00', '2025-03-12 19:18:56'),
(40, 41, '56465564', 62, 14, 2, '0000-00-00', '2025-03-12 19:19:13'),
(41, 33, '312312', 63, 2, 2, '0000-00-00', '2025-03-17 13:44:07'),
(42, 33, '534534534534', 69, 3, 3, '0000-00-00', '2025-03-17 13:57:00'),
(43, 33, '32432432', 63, 5, 3, '0000-00-00', '2025-03-17 13:57:18'),
(44, 33, '123456', 65, 3, 3, '0000-00-00', '2025-03-17 14:01:42'),
(45, 33, '12312321', 66, 3, 3, '0000-00-00', '2025-03-17 14:02:26'),
(46, 34, '1', 78, 1, 1, '0000-00-00', '2025-03-17 18:01:53'),
(47, 34, '2', 78, 1, 1, '0000-00-00', '2025-03-17 18:02:18'),
(48, 34, '3', 52, 3, 3, '0000-00-00', '2025-03-17 18:02:29'),
(49, 34, '4', 50, 13, 2, '0000-00-00', '2025-03-17 18:02:39'),
(50, 34, '5', 48, 6, 5, '0000-00-00', '2025-03-17 18:02:46'),
(51, 34, '123', 48, 5, 3, '0000-00-00', '2025-03-17 18:02:55'),
(52, 38, '1', 80, 1, 1, '0000-00-00', '2025-03-17 18:06:17'),
(53, 38, '2', 81, 2, 2, '0000-00-00', '2025-03-17 18:06:24'),
(54, 38, '3', 82, 3, 3, '0000-00-00', '2025-03-17 18:06:31'),
(55, 38, '4', 83, 5, 3, '0000-00-00', '2025-03-17 18:06:37'),
(56, 38, '5', 84, 5, 3, '0000-00-00', '2025-03-17 18:06:46'),
(57, 38, '6', 85, 7, 7, '0000-00-00', '2025-03-17 18:06:53'),
(58, 38, '7', 85, 6, 5, '0000-00-00', '2025-03-17 20:21:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_afastamento`
--

CREATE TABLE `tipos_afastamento` (
  `id` int(11) NOT NULL,
  `descricao` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tipos_afastamento`
--

INSERT INTO `tipos_afastamento` (`id`, `descricao`, `created_at`, `updated_at`) VALUES
(1, 'Férias', '2025-02-27 19:07:01', '2025-02-27 19:07:01'),
(2, 'Licença Médica', '2025-02-27 19:07:01', '2025-02-27 19:07:01'),
(3, 'Licença Maternidade', '2025-02-27 19:07:01', '2025-02-27 19:07:01'),
(4, 'Licença Paternidade', '2025-02-27 19:07:01', '2025-02-27 19:07:01'),
(5, 'Afastamento para Capacitação', '2025-02-27 19:07:01', '2025-02-27 19:07:01'),
(6, 'Outros', '2025-02-27 19:07:01', '2025-02-27 19:07:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('servidor','diretor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `user_type`, `created_at`) VALUES
(25, 'Ronaldo', 'ronaldoneves31@hotmail.com', '$2y$10$59hCNE3TxRNTfYYUAdM.ze3utTj2d/227dfBTzNSk6G.xcESxhcu6', 'servidor', '2025-02-24 16:06:06'),
(26, 'ronaldo', 'ronaldo3@hotmail.com', '$2y$10$MeGVIxpTeNQBNnbtABA0T.DZGCmucMHYgOPlDPCYzahpu97vsvWuG', 'servidor', '2025-02-24 16:11:15'),
(28, 'Kaio Menez zz', 'ronaldoneves311@hotmail.com', '$2y$10$zGWJzO.FE8DEBgNk70auh.XP24TpESS1pRsL.hx9tXzhqTu2ijHyy', 'servidor', '2025-02-24 16:12:31'),
(30, 'ronaldoa', 'ronaldo312@hotmail.com', '$2y$10$lP5kPH2yPKySMxfECN1bcOjEH1arcrCmvJ7c3mUC7ObUCnkgigDJm', 'servidor', '2025-02-24 16:14:20'),
(31, '2', '2@2', '$2y$10$owZOvhafQj..ZTtuBjUdnO7z.kkgyED3/Ja7gtYTf/s3IuqKId1gu', 'servidor', '2025-02-24 16:14:39'),
(32, '3', '3@3', '$2y$10$pOAJbY24cWR6kLxc7tzTHu/w/d8NP11YnvQafFa6VlhLACjqh4MN.', 'servidor', '2025-02-24 16:16:59'),
(33, '4', '4@4', '$2y$10$YT5vOJYA0mMMIPnxZraVoebAzf2TQyUQdcqj1T5EQToW3hATDSoVi', 'servidor', '2025-02-24 16:18:30'),
(34, '5', '5@5', '$2y$10$UehZDM5pORIWhdKVxcLEH.L7J9DL7/1reuCLWXJu57WHuA2L0qqK6', 'servidor', '2025-02-24 16:19:22'),
(36, 'admin', 'admin@admin.com', '$2y$10$.29h5ErXNyppzY9EjwuKIOeQNBiOixMRwdMxpsFWdNzdLTS9/d7Xq', 'servidor', '2025-02-24 17:41:36'),
(37, 'Pedro', 'pedro@pedro.com', '$2y$10$ds/vHrNCDjHKPwYfIldaTOfKFMQXGvQMTehHnMsAgjx3dlXu56vi2', 'diretor', '2025-02-24 17:45:22'),
(38, '6', '6@6', '$2y$10$C//dq/MHBaFzWvoCOnPHYeOZRhlgq4PEvgOsdvNYeuvRozczIT1MO', 'servidor', '2025-02-24 17:56:23'),
(39, 'Ronaldo Neto', 'ronaldoneto@gmail.com', '$2y$10$uh/8n6jLOOSwMqbYTQ5oOOuxGnLgdhv7sdzHz4fDPVN44z6Ap3XUK', 'servidor', '2025-02-24 18:07:59'),
(40, 'Brendo', 'brendo@brendo', '$2y$10$lbEf8iF2xpvBgKCTBcgVfeaDxTnZHXYwy1yTmiusg74h2Lq2iLF1S', 'servidor', '2025-02-25 20:15:39'),
(41, 'bruna', 'bruna@bruna', '$2y$10$k6A2ldqy3MAxy/J8/Q6YPOY8klGbrwXIX5Tkeu6KoW/05DQNZS12y', 'servidor', '2025-02-25 20:15:49');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `afastamentos`
--
ALTER TABLE `afastamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tipo_afastamento_id` (`tipo_afastamento_id`);

--
-- Índices de tabela `decision_types`
--
ALTER TABLE `decision_types`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `group_users`
--
ALTER TABLE `group_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `minute_types`
--
ALTER TABLE `minute_types`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `productivity`
--
ALTER TABLE `productivity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `minute_type_id` (`minute_type_id`),
  ADD KEY `decision_type_id` (`decision_type_id`);

--
-- Índices de tabela `tipos_afastamento`
--
ALTER TABLE `tipos_afastamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `afastamentos`
--
ALTER TABLE `afastamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `decision_types`
--
ALTER TABLE `decision_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `group_users`
--
ALTER TABLE `group_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de tabela `minute_types`
--
ALTER TABLE `minute_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT de tabela `productivity`
--
ALTER TABLE `productivity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de tabela `tipos_afastamento`
--
ALTER TABLE `tipos_afastamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `afastamentos`
--
ALTER TABLE `afastamentos`
  ADD CONSTRAINT `afastamentos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `afastamentos_ibfk_2` FOREIGN KEY (`tipo_afastamento_id`) REFERENCES `tipos_afastamento` (`id`);

--
-- Restrições para tabelas `group_users`
--
ALTER TABLE `group_users`
  ADD CONSTRAINT `group_users_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `group_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `productivity`
--
ALTER TABLE `productivity`
  ADD CONSTRAINT `productivity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `productivity_ibfk_2` FOREIGN KEY (`minute_type_id`) REFERENCES `minute_types` (`id`),
  ADD CONSTRAINT `productivity_ibfk_3` FOREIGN KEY (`decision_type_id`) REFERENCES `decision_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
