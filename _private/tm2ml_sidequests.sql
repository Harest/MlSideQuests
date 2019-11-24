-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le :  Dim 24 nov. 2019 à 20:22
-- Version du serveur :  10.3.17-MariaDB-0+deb10u1-log
-- Version de PHP :  7.3.11-1~deb10u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `tm2ml_sidequests`
--

-- --------------------------------------------------------

--
-- Structure de la table `players`
--

CREATE TABLE `players` (
  `login` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Login ManiaPlanet',
  `nickname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'In-game NickName',
  `quest_id` smallint(5) UNSIGNED NOT NULL,
  `completion_date_first` datetime NOT NULL DEFAULT current_timestamp(),
  `completion_date_best` datetime NOT NULL DEFAULT current_timestamp(),
  `completion_time_first` int(11) NOT NULL COMMENT 'Completion time for the first time (in milliseconds)',
  `completion_time_best` int(11) NOT NULL COMMENT 'Best completion time (in milliseconds)',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 (default) = OK, 0 = NOK (cheat, ...)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Players who finished a quest';

-- --------------------------------------------------------

--
-- Structure de la table `quests`
--

CREATE TABLE `quests` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT 'Quest name',
  `author_login` varchar(50) CHARACTER SET utf8 NOT NULL COMMENT 'Author ManiaPlanet login',
  `description_short` text CHARACTER SET utf8 DEFAULT NULL COMMENT 'Short quest description displayed in the board',
  `description_full` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Full length quest description displayed in the board only if it''s not a simple board',
  `map_uid` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Track Unique ID',
  `map_mx_id` int(11) DEFAULT NULL COMMENT 'Mania Exchange ID',
  `tokens_errormargin_x` smallint(5) NOT NULL DEFAULT 5 COMMENT 'Error margin on X min/max positions checks',
  `tokens_errormargin_y` smallint(5) NOT NULL DEFAULT 5 COMMENT 'Error margin on Y min/max positions checks',
  `tokens_errormargin_z` smallint(5) NOT NULL DEFAULT 5 COMMENT 'Error margin on Z min/max positions checks',
  `board_head_sentence` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sentence displayed above the players list on the quest board',
  `board_head_sentence_empty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sentence displayed above an empty players list on the quest board',
  `creation_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tokens`
--

CREATE TABLE `tokens` (
  `id` mediumint(8) UNSIGNED NOT NULL,
  `quest_id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_pos_x` smallint(5) UNSIGNED DEFAULT NULL,
  `max_pos_x` smallint(5) UNSIGNED DEFAULT NULL,
  `min_pos_y` smallint(5) UNSIGNED DEFAULT NULL,
  `max_pos_y` smallint(5) UNSIGNED DEFAULT NULL,
  `min_pos_z` smallint(5) UNSIGNED DEFAULT NULL,
  `max_pos_z` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Collectibles needed to complete the quest';

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`login`,`quest_id`),
  ADD KEY `IDX_quest_id` (`quest_id`) USING BTREE;

--
-- Index pour la table `quests`
--
ALTER TABLE `quests`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQUE_quest_id_name` (`quest_id`,`name`),
  ADD KEY `IDX_quest_id` (`quest_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `quests`
--
ALTER TABLE `quests`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
