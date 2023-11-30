-- phpMyAdmin SQL Dump
-- version 3.5.0-dev
-- https://www.phpmyadmin.net
--
-- Host: barclay
-- Generation Time: Aug 06, 2011 at 04:53 PM
-- Server version: 5.1.49-3-log
-- PHP Version: 5.3.3-7+squeeze1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

DROP DATABASE IF EXISTS `"><script>alert(200);</script>`;
DROP DATABASE IF EXISTS `'><script>alert(201);</script>`;
DROP DATABASE IF EXISTS `exploit_test`;

--
-- Database: `"><script>alert(200);</script>`
--
CREATE DATABASE `"><script>alert(200);</script>` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;

--
-- Database: `'><script>alert(201);</script>`
--
CREATE DATABASE `'><script>alert(201);</script>` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;

--
-- Database: `exploit_test`
--
CREATE DATABASE `exploit_test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `exploit_test`;

-- --------------------------------------------------------

--
-- Table structure for table `"><script>alert(109);</script>`
--

CREATE TABLE IF NOT EXISTS `"><script>alert(109);</script>` (
  `id` int(2) NOT NULL,
  `foo` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `"><script>alert(109);</script>`
--

INSERT INTO `"><script>alert(109);</script>` (`id`, `foo`) VALUES
(1, ''),
(2, '');

-- --------------------------------------------------------

--
-- Table structure for table `';  eval('alert(107)')`
--

CREATE TABLE IF NOT EXISTS `';  eval('alert(107)')` (
  `id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `'><script>alert(106);</script>`
--

CREATE TABLE IF NOT EXISTS `'><script>alert(106);</script>` (
  `id` int(2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `<script>alert(105);</script>`
--

CREATE TABLE IF NOT EXISTS `<script>alert(105);</script>` (
  `dsaf` int(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `<script>alert(105);</script>`
--

INSERT INTO `<script>alert(105);</script>` (`dsaf`) VALUES
(1);

-- --------------------------------------------------------

--
-- Table structure for table `evil_column_names`
--

CREATE TABLE IF NOT EXISTS `evil_column_names` (
  `<script>alert(100);</script>` int(1) NOT NULL,
  `"><script>alert(101);</script>` int(2) NOT NULL,
  `'><script>alert(102);</script>` int(2) NOT NULL,
  `evil_comment` int(3) NOT NULL COMMENT '<script>alert(104);</script>'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `evil_column_names`
--

INSERT INTO `evil_column_names` (`<script>alert(100);</script>`, `"><script>alert(101);</script>`, `'><script>alert(102);</script>`, `evil_comment`) VALUES
(1, 23, 45, 5),
(2, 3, 77, 3);

-- --------------------------------------------------------

--
-- Table structure for table `evil_content`
--

CREATE TABLE IF NOT EXISTS `evil_content` (
	  `text` varchar(255) NOT NULL
	) ENGINE=MyISAM DEFAULT CHARSET=latin1;


--
-- Dumping data for table `evil_content`
--

INSERT INTO `evil_content` (`text`) VALUES
('"><script>alert(301);</script>'),
('''><script>alert(302);</script>'),
('<script>alert(303);</script>'),
(''';  eval(''alert(304)'');');

-- --------------------------------------------------------

--
-- Table structure for table `evil_table_comment`
--

CREATE TABLE IF NOT EXISTS `evil_table_comment` (
  `id` int(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='"><script>alert(400);</script>';


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
