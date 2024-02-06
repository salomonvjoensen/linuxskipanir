-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 04, 2024 at 08:27 PM
-- Server version: 10.6.16-MariaDB-0ubuntu0.22.04.1
-- PHP Version: 8.1.2-1ubuntu2.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
--START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kjakdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `kjak_post`
--

CREATE TABLE IF NOT EXISTS `kjak_post` (
  `post_id` int(11) NOT NULL,
  `thread_id` int(11) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `post_text` text NOT NULL,
  `post_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kjak_table`
--

CREATE TABLE IF NOT EXISTS `kjak_table` (
  `forum_id` int(11) NOT NULL,
  `forum_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kjak_thread`
--

CREATE TABLE IF NOT EXISTS `kjak_thread` (
  `thread_id` int(11) NOT NULL,
  `forum_id` int(11) DEFAULT NULL,
  `thread_title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- The 3 tuples that will be inserted in to the Forum.
--

INSERT INTO `kjak_table` (`forum_name`, `description`)
SELECT * FROM (SELECT 'Tíðindir', 'Hvat nýtt veitst tú?') AS tmp
WHERE NOT EXISTS (
  SELECT `forum_name` FROM `kjak_table` WHERE `forum_name` = 'Tíðindir'
) LIMIT 1;

INSERT INTO `kjak_table` (`forum_name`, `description`)
SELECT * FROM (SELECT 'Kjak', 'Kjak um hvat sum helst.') AS tmp
WHERE NOT EXISTS (
  SELECT `forum_name` FROM `kjak_table` WHERE `forum_name` = 'Kjak'
) LIMIT 1;

INSERT INTO `kjak_table` (`forum_name`, `description`)
SELECT * FROM (SELECT 'Áhugi', 'Lat heimin vita um tíni áhugamál.') AS tmp
WHERE NOT EXISTS (
  SELECT `forum_name` FROM `kjak_table` WHERE `forum_name` = 'Áhugi'
) LIMIT 1;

-- --------------------------------------------------------
--
-- Indexes for table `kjak_post`
--
ALTER TABLE `kjak_post`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `thread_id` (`thread_id`);

--
-- Indexes for table `kjak_table`
--
ALTER TABLE `kjak_table`
  ADD PRIMARY KEY (`forum_id`);

--
-- Indexes for table `kjak_thread`
--
ALTER TABLE `kjak_thread`
  ADD PRIMARY KEY (`thread_id`),
  ADD KEY `forum_id` (`forum_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kjak_post`
--
ALTER TABLE `kjak_post`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kjak_table`
--
ALTER TABLE `kjak_table`
  MODIFY `forum_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kjak_thread`
--
ALTER TABLE `kjak_thread`
  MODIFY `thread_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kjak_post`
--
ALTER TABLE `kjak_post`
  ADD CONSTRAINT `kjak_post_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `kjak_thread` (`thread_id`) ON DELETE CASCADE;

--
-- Constraints for table `kjak_thread`
--
ALTER TABLE `kjak_thread`
  ADD CONSTRAINT `kjak_thread_ibfk_1` FOREIGN KEY (`forum_id`) REFERENCES `kjak_table` (`forum_id`) ON DELETE CASCADE;
--COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
