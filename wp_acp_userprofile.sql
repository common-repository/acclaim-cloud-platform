-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 27, 2020 at 05:44 PM
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
-- Table structure for table `wp_acp_userprofile`
--

CREATE TABLE IF NOT EXISTS `wp_acp_userprofile` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(9) NOT NULL,
  `user_name` varchar(64) NOT NULL,
  `user_name_unix` varchar(64) NOT NULL,
  `user_psswd_set` tinyint(1) NOT NULL DEFAULT '0',
  `user_temp_pass` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `user_vpn_set` tinyint(1) NOT NULL DEFAULT '0',
  `client_goal` varchar(30) NOT NULL,
  `user_svc_acct_set` int(2) NOT NULL DEFAULT '0',
  `machine_cache` int(2) NOT NULL DEFAULT '0',
  `user_last_login` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
