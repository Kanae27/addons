-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 15, 2025 at 03:14 AM
-- Server version: 8.0.31
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gad_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `signatories`
--

DROP TABLE IF EXISTS `signatories`;
CREATE TABLE IF NOT EXISTS `signatories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name1` varchar(255) NOT NULL,
  `gad_head_secretariat` varchar(255) NOT NULL,
  `name2` varchar(255) NOT NULL,
  `vice_chancellor_rde` varchar(255) NOT NULL,
  `name3` varchar(255) NOT NULL,
  `chancellor` varchar(255) NOT NULL,
  `name4` varchar(255) NOT NULL,
  `asst_director_gad` varchar(255) NOT NULL,
  `name5` varchar(255) NOT NULL,
  `head_extension_services` varchar(255) NOT NULL,
  `name6` varchar(255) DEFAULT NULL,
  `vice_chancellor_admin_finance` varchar(255) DEFAULT NULL,
  `campus` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `name7` varchar(255) DEFAULT NULL,
  `dean` varchar(255) DEFAULT 'Dean',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `signatories`
--

INSERT INTO `signatories` (`id`, `name1`, `gad_head_secretariat`, `name2`, `vice_chancellor_rde`, `name3`, `chancellor`, `name4`, `asst_director_gad`, `name5`, `head_extension_services`, `name6`, `vice_chancellor_admin_finance`, `campus`, `created_at`, `updated_at`, `name7`, `dean`) VALUES
(2, 'Henry Silva', 'GAD Head Secretariat', 'Ivy Ramirez', 'Vice Chancellor For Research, Development and Extension', 'Jake Navarro', 'Chancellor', 'Kyla Villanueva', 'Assistant Director For GAD Advocacies', 'Luis Ramos', 'Head of Extension Services', 'Maria Dizon', 'Vice Chancellor for Administration and Finance', 'Alangilan', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Noel Aquino', 'Dean'),
(1, 'Anna Cruz', 'GAD Head Secretariat', 'Benjamin Reyes', 'Vice Chancellor For Research, Development and Extension', 'Carla Gomez', 'Chancellor', 'Daniel Lopez', 'Assistant Director For GAD Advocacies', 'Elaine Mendoza', 'Head of Extension Services', 'Francis Tan', 'Vice Chancellor for Administration and Finance', 'Central', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Grace Santos', 'Dean'),
(3, 'Olivia Lim', 'GAD Head Secretariat', 'Patrick Cruz', 'Vice Chancellor For Research, Development and Extension', 'Queenie Torres', 'Chancellor', 'Ryan Garcia', 'Assistant Director For GAD Advocacies', 'Samantha Uy', 'Head of Extension Services', 'Thomas Vega', 'Vice Chancellor for Administration and Finance', 'Pablo Borbon', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Uma Santos', 'Dean'),
(4, 'Victor Chua', 'GAD Head Secretariat', 'Wendy Roxas', 'Vice Chancellor For Research, Development and Extension', 'Xavier Reyes', 'Chancellor', 'Yvonne Delgado', 'Assistant Director For GAD Advocacies', 'Zach Sy', 'Head of Extension Services', 'Abby Ong', 'Vice Chancellor for Administration and Finance', 'ARASOF-Nasugbu', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Brianne Javier', 'Dean'),
(5, 'Carmen Diaz', 'GAD Head Secretariat', 'Diego Bautista', 'Vice Chancellor For Research, Development and Extension', 'Elena Ponce', 'Chancellor', 'Felix Ramos', 'Assistant Director For GAD Advocacies', 'Gloria Rivera', 'Head of Extension Services', 'Harold Tan', 'Vice Chancellor for Administration and Finance', 'Balayan', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Isabel Flores', 'Dean'),
(6, 'Jasper Manalo', 'GAD Head Secretariat', 'Karen Sison', 'Vice Chancellor For Research, Development and Extension', 'Leo Gutierrez', 'Chancellor', 'Monica Santos', 'Assistant Director For GAD Advocacies', 'Nathan Cruz', 'Head of Extension Services', 'Opal Medina', 'Vice Chancellor for Administration and Finance', 'Lemery', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Paulo Enriquez', 'Dean'),
(7, 'Quincy Tan', 'GAD Head Secretariat', 'Rachel Ong', 'Vice Chancellor For Research, Development and Extension', 'Samuel Chavez', 'Chancellor', 'Tina Yulo', 'Assistant Director For GAD Advocacies', 'Ulysses Ramosn', 'Head of Extension Services', 'Vanessa Lim', 'Vice Chancellor for Administration and Finance', 'Lipa', '2025-04-24 00:29:32', '2025-05-07 08:36:41', 'Warren Domingo', 'Dean'),
(8, 'Alex Bautista', 'GAD Head Secretariat', 'Bea Santiago', 'Vice Chancellor For Research, Development and Extension', 'Caleb Morales', 'Chancellor', 'Diana Cruz', 'Assistant Director For GAD Advocacies', 'Eugene Delos Reyes', 'Head of Extension Services', 'Faye Tan', 'Vice Chancellor for Administration and Finance', 'Lobo', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'George Castillo', 'Dean'),
(9, 'Hannah Uy', 'GAD Head Secretariat', 'Ian Garcia', 'Vice Chancellor For Research, Development and Extension', 'Jolina Diaz', 'Chancellor', 'Karl Soriano', 'Assistant Director For GAD Advocacies', 'Lara Buenaventura', 'Head of Extension Services', 'Miguel Tan', 'Vice Chancellor for Administration and Finance', 'Mabini', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Nina Trinidad', 'Dean'),
(10, 'Oscar Dela Cruz', 'GAD Head Secretariat', 'Pamela Reyes', 'Vice Chancellor For Research, Development and Extension', 'Quintin Sarmiento', 'Chancellor', 'Rhea David', 'Assistant Director For GAD Advocacies', 'Sergio Yap', 'Head of Extension Services', 'Tess Mercado', 'Vice Chancellor for Administration and Finance', 'Malvar', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Uriel Santos', 'Dean'),
(11, 'Vera Go', 'GAD Head Secretariat', 'Wilmer Chavez', 'Vice Chancellor For Research, Development and Extension', 'Xandra Ramos', 'Chancellor', 'Yuri Navarro', 'Assistant Director For GAD Advocacies', 'Zenaida Castro', 'Head of Extension Services', 'Ariel Torres', 'Vice Chancellor for Administration and Finance', 'Rosario', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Beatrice Luna', 'Dean'),
(12, 'Clyde Santos', 'GAD Head Secretariat', 'Denise Aquino', 'Vice Chancellor For Research, Development and Extension', 'Enzo Mateo', 'Chancellor', 'Fiona Sy', 'Assistant Director For GAD Advocacies', 'Gavin Reyes', 'Head of Extension Services', 'Hazel Ong', 'Vice Chancellor for Administration and Finance', 'San Juan', '2025-04-24 00:29:32', '2025-05-04 18:49:00', 'Irene Valdez', 'Dean');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
