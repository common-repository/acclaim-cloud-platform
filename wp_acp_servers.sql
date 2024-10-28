-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 27, 2020 at 05:43 PM
-- Server version: 8.0.18-9
-- PHP Version: 7.2.24-0ubuntu0.18.04.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `acclaim`
--

-- --------------------------------------------------------

--
-- Table structure for table `wp_acp_servers`
--

CREATE TABLE IF NOT EXISTS `wp_acp_servers` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(9) NOT NULL,
  `user_name` varchar(55) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `user_temp_pass` varchar(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `server_id` varchar(36) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `server_name` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `server_ip` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `server_env` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `server_type` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `server_host` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `server_host_status` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `server_memory` int(11) NOT NULL,
  `server_disksize` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `server_cpus` int(11) NOT NULL,
  `server_status` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'not created',
  `server_created` datetime DEFAULT NULL,
  `server_meta` text,
  `server_sticky` int(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `server_ip` (`server_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
