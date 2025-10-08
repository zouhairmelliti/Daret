-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mer. 08 oct. 2025 à 15:50
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `daret_app`
--

-- --------------------------------------------------------

--
-- Structure de la table `darets`
--

CREATE TABLE `darets` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `frequency` enum('weekly','monthly') NOT NULL,
  `max_members` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('open','active','completed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `darets`
--

INSERT INTO `darets` (`id`, `name`, `description`, `amount`, `frequency`, `max_members`, `created_by`, `status`, `created_at`) VALUES
(1, 'DARET1', 'DARET HOLDING', 2000.00, 'monthly', 10, 1, 'active', '2025-10-06 15:47:48');

-- --------------------------------------------------------

--
-- Structure de la table `daret_members`
--

CREATE TABLE `daret_members` (
  `id` int(11) NOT NULL,
  `daret_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `join_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `daret_members`
--

INSERT INTO `daret_members` (`id`, `daret_id`, `user_id`, `join_date`, `is_admin`) VALUES
(1, 1, 1, '2025-10-06 15:47:48', 1),
(2, 1, 2, '2025-10-06 16:01:06', 0),
(3, 1, 9, '2025-10-06 16:05:16', 0),
(4, 1, 3, '2025-10-06 16:05:52', 0),
(5, 1, 6, '2025-10-06 16:06:03', 0),
(6, 1, 4, '2025-10-06 16:06:10', 0),
(7, 1, 10, '2025-10-06 16:06:43', 0),
(8, 1, 7, '2025-10-06 16:07:03', 0),
(9, 1, 5, '2025-10-06 16:07:16', 0),
(10, 1, 8, '2025-10-06 16:07:30', 0);

-- --------------------------------------------------------

--
-- Structure de la table `daret_profits`
--

CREATE TABLE `daret_profits` (
  `id` int(11) NOT NULL,
  `daret_id` int(11) NOT NULL,
  `profit_type` enum('fixed','percentage') NOT NULL,
  `profit_value` decimal(10,2) NOT NULL,
  `calculation_method` enum('simple','compound') DEFAULT 'simple',
  `distribution_frequency` enum('per_round','end_of_daret') DEFAULT 'per_round',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `daret_profits`
--

INSERT INTO `daret_profits` (`id`, `daret_id`, `profit_type`, `profit_value`, `calculation_method`, `distribution_frequency`, `created_at`) VALUES
(1, 1, 'fixed', 2000.00, 'compound', 'per_round', '2025-10-06 21:18:27');

-- --------------------------------------------------------

--
-- Structure de la table `daret_rounds`
--

CREATE TABLE `daret_rounds` (
  `id` int(11) NOT NULL,
  `daret_id` int(11) NOT NULL,
  `round_number` int(11) NOT NULL,
  `beneficiary_user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `round_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `daret_rounds`
--

INSERT INTO `daret_rounds` (`id`, `daret_id`, `round_number`, `beneficiary_user_id`, `amount`, `round_date`, `due_date`, `status`) VALUES
(11, 1, 1, 3, 2000.00, '2025-10-06', '2025-10-13', 'pending'),
(12, 1, 2, 10, 2000.00, '2025-10-06', '2025-11-10', 'pending'),
(13, 1, 3, 2, 2000.00, '2025-10-06', '2025-12-10', 'pending'),
(15, 1, 4, 5, 2000.00, '2025-10-06', '2026-01-10', 'pending'),
(16, 1, 5, 6, 2000.00, '2025-10-06', '2026-02-10', 'pending'),
(17, 1, 6, 1, 2000.00, '2025-10-06', '2026-03-10', 'pending'),
(18, 1, 7, 7, 2000.00, '2025-10-06', '2026-04-10', 'pending'),
(19, 1, 8, 8, 2000.00, '2025-10-06', '2026-05-10', 'pending'),
(20, 1, 9, 9, 2000.00, '2025-10-06', '2026-06-10', 'pending'),
(21, 1, 10, 4, 2000.00, '2025-10-06', '2026-07-10', 'pending');

-- --------------------------------------------------------

--
-- Structure de la table `daret_round_order`
--

CREATE TABLE `daret_round_order` (
  `id` int(11) NOT NULL,
  `daret_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `round_number` int(11) NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `daret_round_order`
--

INSERT INTO `daret_round_order` (`id`, `daret_id`, `user_id`, `round_number`, `position`) VALUES
(1, 1, 4, 1, 1),
(2, 1, 3, 2, 2),
(3, 1, 10, 3, 3),
(4, 1, 8, 4, 4),
(5, 1, 6, 5, 5),
(6, 1, 5, 6, 6),
(7, 1, 2, 7, 7),
(8, 1, 9, 8, 8),
(9, 1, 7, 9, 9),
(10, 1, 1, 10, 10);

-- --------------------------------------------------------

--
-- Structure de la table `late_payments`
--

CREATE TABLE `late_payments` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `penalty_amount` decimal(10,2) NOT NULL,
  `penalty_reason` varchar(255) DEFAULT NULL,
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','paid','waived') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `daret_round_id` int(11) NOT NULL,
  `payer_user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','paid') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `payments`
--

INSERT INTO `payments` (`id`, `daret_round_id`, `payer_user_id`, `amount`, `payment_date`, `status`) VALUES
(91, 11, 1, 2000.00, '2025-10-08 11:38:44', 'paid'),
(92, 11, 2, 2000.00, '2025-10-08 11:33:16', 'paid'),
(93, 11, 4, 2000.00, '2025-10-08 11:40:37', 'paid'),
(94, 11, 5, 2000.00, '2025-10-08 11:44:18', 'paid'),
(95, 11, 6, 2000.00, '2025-10-08 11:44:55', 'paid'),
(96, 11, 7, 2000.00, '2025-10-08 11:45:21', 'paid'),
(97, 11, 8, 2000.00, '2025-10-08 11:45:44', 'paid'),
(98, 11, 9, 2000.00, '2025-10-08 11:46:06', 'paid'),
(99, 11, 10, 2000.00, '2025-10-08 11:46:27', 'paid'),
(100, 12, 1, 2000.00, '2025-10-06 21:36:30', 'pending'),
(101, 12, 2, 2000.00, '2025-10-06 21:36:30', 'pending'),
(102, 12, 3, 2000.00, '2025-10-06 21:36:30', 'pending'),
(103, 12, 4, 2000.00, '2025-10-06 21:36:30', 'pending'),
(104, 12, 5, 2000.00, '2025-10-06 21:36:30', 'pending'),
(105, 12, 6, 2000.00, '2025-10-06 21:36:30', 'pending'),
(106, 12, 7, 2000.00, '2025-10-06 21:36:30', 'pending'),
(107, 12, 8, 2000.00, '2025-10-06 21:36:30', 'pending'),
(108, 12, 9, 2000.00, '2025-10-06 21:36:30', 'pending'),
(109, 13, 1, 2000.00, '2025-10-06 21:36:49', 'pending'),
(110, 13, 3, 2000.00, '2025-10-06 21:36:49', 'pending'),
(111, 13, 4, 2000.00, '2025-10-06 21:36:49', 'pending'),
(112, 13, 5, 2000.00, '2025-10-06 21:36:49', 'pending'),
(113, 13, 6, 2000.00, '2025-10-06 21:36:49', 'pending'),
(114, 13, 7, 2000.00, '2025-10-06 21:36:49', 'pending'),
(115, 13, 8, 2000.00, '2025-10-06 21:36:49', 'pending'),
(116, 13, 9, 2000.00, '2025-10-06 21:36:49', 'pending'),
(117, 13, 10, 2000.00, '2025-10-06 21:36:49', 'pending'),
(127, 15, 1, 2000.00, '2025-10-06 21:38:13', 'pending'),
(128, 15, 2, 2000.00, '2025-10-06 21:38:13', 'pending'),
(129, 15, 3, 2000.00, '2025-10-06 21:38:13', 'pending'),
(130, 15, 4, 2000.00, '2025-10-06 21:38:13', 'pending'),
(131, 15, 6, 2000.00, '2025-10-06 21:38:13', 'pending'),
(132, 15, 7, 2000.00, '2025-10-06 21:38:13', 'pending'),
(133, 15, 8, 2000.00, '2025-10-06 21:38:13', 'pending'),
(134, 15, 9, 2000.00, '2025-10-06 21:38:13', 'pending'),
(135, 15, 10, 2000.00, '2025-10-06 21:38:13', 'pending'),
(136, 16, 1, 2000.00, '2025-10-06 21:38:32', 'pending'),
(137, 16, 2, 2000.00, '2025-10-06 21:38:32', 'pending'),
(138, 16, 3, 2000.00, '2025-10-06 21:38:32', 'pending'),
(139, 16, 4, 2000.00, '2025-10-06 21:38:32', 'pending'),
(140, 16, 5, 2000.00, '2025-10-06 21:38:32', 'pending'),
(141, 16, 7, 2000.00, '2025-10-06 21:38:32', 'pending'),
(142, 16, 8, 2000.00, '2025-10-06 21:38:32', 'pending'),
(143, 16, 9, 2000.00, '2025-10-06 21:38:32', 'pending'),
(144, 16, 10, 2000.00, '2025-10-06 21:38:32', 'pending'),
(145, 17, 2, 2000.00, '2025-10-06 21:38:46', 'pending'),
(146, 17, 3, 2000.00, '2025-10-06 21:38:46', 'pending'),
(147, 17, 4, 2000.00, '2025-10-06 21:38:46', 'pending'),
(148, 17, 5, 2000.00, '2025-10-06 21:38:46', 'pending'),
(149, 17, 6, 2000.00, '2025-10-06 21:38:46', 'pending'),
(150, 17, 7, 2000.00, '2025-10-06 21:38:46', 'pending'),
(151, 17, 8, 2000.00, '2025-10-06 21:38:46', 'pending'),
(152, 17, 9, 2000.00, '2025-10-06 21:38:46', 'pending'),
(153, 17, 10, 2000.00, '2025-10-06 21:38:46', 'pending'),
(154, 18, 1, 2000.00, '2025-10-06 21:39:01', 'pending'),
(155, 18, 2, 2000.00, '2025-10-06 21:39:01', 'pending'),
(156, 18, 3, 2000.00, '2025-10-06 21:39:01', 'pending'),
(157, 18, 4, 2000.00, '2025-10-06 21:39:01', 'pending'),
(158, 18, 5, 2000.00, '2025-10-06 21:39:01', 'pending'),
(159, 18, 6, 2000.00, '2025-10-06 21:39:01', 'pending'),
(160, 18, 8, 2000.00, '2025-10-06 21:39:01', 'pending'),
(161, 18, 9, 2000.00, '2025-10-06 21:39:01', 'pending'),
(162, 18, 10, 2000.00, '2025-10-06 21:39:01', 'pending'),
(163, 19, 1, 2000.00, '2025-10-06 21:39:22', 'pending'),
(164, 19, 2, 2000.00, '2025-10-06 21:39:22', 'pending'),
(165, 19, 3, 2000.00, '2025-10-06 21:39:22', 'pending'),
(166, 19, 4, 2000.00, '2025-10-06 21:39:22', 'pending'),
(167, 19, 5, 2000.00, '2025-10-06 21:39:22', 'pending'),
(168, 19, 6, 2000.00, '2025-10-06 21:39:22', 'pending'),
(169, 19, 7, 2000.00, '2025-10-06 21:39:22', 'pending'),
(170, 19, 9, 2000.00, '2025-10-06 21:39:22', 'pending'),
(171, 19, 10, 2000.00, '2025-10-06 21:39:22', 'pending'),
(172, 20, 1, 2000.00, '2025-10-06 21:39:40', 'pending'),
(173, 20, 2, 2000.00, '2025-10-06 21:39:40', 'pending'),
(174, 20, 3, 2000.00, '2025-10-06 21:39:40', 'pending'),
(175, 20, 4, 2000.00, '2025-10-06 21:39:40', 'pending'),
(176, 20, 5, 2000.00, '2025-10-06 21:39:40', 'pending'),
(177, 20, 6, 2000.00, '2025-10-06 21:39:40', 'pending'),
(178, 20, 7, 2000.00, '2025-10-06 21:39:40', 'pending'),
(179, 20, 8, 2000.00, '2025-10-06 21:39:40', 'pending'),
(180, 20, 10, 2000.00, '2025-10-06 21:39:40', 'pending'),
(181, 21, 1, 2000.00, '2025-10-06 21:39:55', 'pending'),
(182, 21, 2, 2000.00, '2025-10-06 21:39:55', 'pending'),
(183, 21, 3, 2000.00, '2025-10-06 21:39:55', 'pending'),
(184, 21, 5, 2000.00, '2025-10-06 21:39:55', 'pending'),
(185, 21, 6, 2000.00, '2025-10-06 21:39:55', 'pending'),
(186, 21, 7, 2000.00, '2025-10-06 21:39:55', 'pending'),
(187, 21, 8, 2000.00, '2025-10-06 21:39:55', 'pending'),
(188, 21, 9, 2000.00, '2025-10-06 21:39:55', 'pending'),
(189, 21, 10, 2000.00, '2025-10-06 21:39:55', 'pending');

-- --------------------------------------------------------

--
-- Structure de la table `profit_distributions`
--

CREATE TABLE `profit_distributions` (
  `id` int(11) NOT NULL,
  `daret_id` int(11) NOT NULL,
  `round_number` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `distribution_date` date NOT NULL,
  `distribution_type` enum('interest','bonus','penalty') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `created_at`) VALUES
(1, 'ZOUHAIR', 'Daret@gmail.com', '$2y$10$6i.DtBTylswMEXdsY8A4cOe4obcKS.BsftyTGvGgJ1KLPPdYRlA4W', 'ZOUHAIR ', '000000000', '2025-10-06 15:46:59'),
(2, 'MAHA', 'Daret@gmail.com', '$2y$10$P.L2fDpas6q1lYcylB1BZO4HqqFfj1EdjzibcmJRC6wTE4niK1RnC', 'MAHA ', '000000000', '2025-10-06 16:00:49'),
(3, 'OUSSEMA', 'Daret@gmail.com', '$2y$10$P.L2fDpas6q1lYcylB1BZO4HqqFfj1EdjzibcmJRC6wTE4niK1RnC', 'OUSSEMA ', '000000000', '2025-10-06 16:00:49'),
(4, 'YESSINE', 'Daret@gmail.com', '$2y$10$P.L2fDpas6q1lYcylB1BZO4HqqFfj1EdjzibcmJRC6wTE4niK1RnC', 'YESSINE ', '000000000', '2025-10-06 16:00:49'),
(5, 'SANAA', 'Daret@gmail.com', '$2y$10$P.L2fDpas6q1lYcylB1BZO4HqqFfj1EdjzibcmJRC6wTE4niK1RnC', 'SANAA', '000000000', '2025-10-06 16:00:49'),
(6, 'FARES', 'Daret@gmail.com', '$2y$10$P.L2fDpas6q1lYcylB1BZO4HqqFfj1EdjzibcmJRC6wTE4niK1RnC', 'FARES', '000000000', '2025-10-06 16:00:49'),
(7, 'ZAKARIA', 'Daret@gmail.com', '$2y$10$P.L2fDpas6q1lYcylB1BZO4HqqFfj1EdjzibcmJRC6wTE4niK1RnC', 'ZAKARIA', '000000000', '2025-10-06 16:00:49'),
(8, 'EPYASSINE', 'Daret@gmail.com', '$2y$10$P.L2fDpas6q1lYcylB1BZO4HqqFfj1EdjzibcmJRC6wTE4niK1RnC', 'EP YASSINE RAF', '000000000', '2025-10-06 16:00:49'),
(9, 'SANAA2', 'Daret@gmail.com', '$2y$10$P.L2fDpas6q1lYcylB1BZO4HqqFfj1EdjzibcmJRC6wTE4niK1RnC', 'SANAA2', '000000000', '2025-10-06 16:00:49'),
(10, 'YESSINE RAF', 'Daret@gmail.com', '$2y$10$P.L2fDpas6q1lYcylB1BZO4HqqFfj1EdjzibcmJRC6wTE4niK1RnC', 'YESSINE RAF', '000000000', '2025-10-06 16:00:49');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `darets`
--
ALTER TABLE `darets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `daret_members`
--
ALTER TABLE `daret_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daret_user` (`daret_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `daret_profits`
--
ALTER TABLE `daret_profits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `daret_id` (`daret_id`);

--
-- Index pour la table `daret_rounds`
--
ALTER TABLE `daret_rounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `daret_id` (`daret_id`),
  ADD KEY `beneficiary_user_id` (`beneficiary_user_id`);

--
-- Index pour la table `daret_round_order`
--
ALTER TABLE `daret_round_order`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daret_round` (`daret_id`,`round_number`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `late_payments`
--
ALTER TABLE `late_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payer_user_id` (`payer_user_id`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_daret_round` (`daret_round_id`,`payer_user_id`);

--
-- Index pour la table `profit_distributions`
--
ALTER TABLE `profit_distributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `daret_id` (`daret_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `darets`
--
ALTER TABLE `darets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `daret_members`
--
ALTER TABLE `daret_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `daret_profits`
--
ALTER TABLE `daret_profits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `daret_rounds`
--
ALTER TABLE `daret_rounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `daret_round_order`
--
ALTER TABLE `daret_round_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `late_payments`
--
ALTER TABLE `late_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT pour la table `profit_distributions`
--
ALTER TABLE `profit_distributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `darets`
--
ALTER TABLE `darets`
  ADD CONSTRAINT `darets_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `daret_members`
--
ALTER TABLE `daret_members`
  ADD CONSTRAINT `daret_members_ibfk_1` FOREIGN KEY (`daret_id`) REFERENCES `darets` (`id`),
  ADD CONSTRAINT `daret_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `daret_profits`
--
ALTER TABLE `daret_profits`
  ADD CONSTRAINT `daret_profits_ibfk_1` FOREIGN KEY (`daret_id`) REFERENCES `darets` (`id`);

--
-- Contraintes pour la table `daret_rounds`
--
ALTER TABLE `daret_rounds`
  ADD CONSTRAINT `daret_rounds_ibfk_1` FOREIGN KEY (`daret_id`) REFERENCES `darets` (`id`),
  ADD CONSTRAINT `daret_rounds_ibfk_2` FOREIGN KEY (`beneficiary_user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `daret_round_order`
--
ALTER TABLE `daret_round_order`
  ADD CONSTRAINT `daret_round_order_ibfk_1` FOREIGN KEY (`daret_id`) REFERENCES `darets` (`id`),
  ADD CONSTRAINT `daret_round_order_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `late_payments`
--
ALTER TABLE `late_payments`
  ADD CONSTRAINT `late_payments_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`daret_round_id`) REFERENCES `daret_rounds` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`payer_user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `profit_distributions`
--
ALTER TABLE `profit_distributions`
  ADD CONSTRAINT `profit_distributions_ibfk_1` FOREIGN KEY (`daret_id`) REFERENCES `darets` (`id`),
  ADD CONSTRAINT `profit_distributions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
