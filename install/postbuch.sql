-- phpMyAdmin SQL Dump
-- version 3.1.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 27, 2009 at 11:31 AM
-- Server version: 5.0.67
-- PHP Version: 5.2.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `postbuch`
--

-- --------------------------------------------------------

--
-- Table structure for table `bemerkung`
--

CREATE TABLE IF NOT EXISTS `bemerkung` (
  `bemerkung_id` int(10) unsigned NOT NULL auto_increment,
  `postbuch_id` int(10) unsigned NOT NULL,
  `bemerkung` text collate latin1_german1_ci NOT NULL,
  PRIMARY KEY  (`bemerkung_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `bemerkung`
--

INSERT INTO `bemerkung` (`bemerkung_id`, `postbuch_id`, `bemerkung`) VALUES
(1, 1, 'Testeintrag Posteingang');

-- --------------------------------------------------------

--
-- Table structure for table `einrichtung`
--

CREATE TABLE IF NOT EXISTS `einrichtung` (
  `einrichtung_id` int(10) unsigned NOT NULL auto_increment,
  `bezeichnung` varchar(255) collate latin1_german1_ci NOT NULL,
  PRIMARY KEY  (`einrichtung_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `einrichtung`
--

INSERT INTO `einrichtung` (`einrichtung_id`, `bezeichnung`) VALUES
(1, 'i-fabrik GmbH');

-- --------------------------------------------------------

--
-- Table structure for table `einrichtung_nutzer_link`
--

CREATE TABLE IF NOT EXISTS `einrichtung_nutzer_link` (
  `link_id` int(10) unsigned NOT NULL auto_increment,
  `einrichtung_id` int(10) unsigned NOT NULL,
  `nutzer_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`link_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=9 ;

--
-- Dumping data for table `einrichtung_nutzer_link`
--

INSERT INTO `einrichtung_nutzer_link` (`link_id`, `einrichtung_id`, `nutzer_id`) VALUES
(1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `nutzer`
--

CREATE TABLE IF NOT EXISTS `nutzer` (
  `nutzer_id` int(10) unsigned NOT NULL auto_increment,
  `anzeigename` varchar(255) collate latin1_german1_ci NOT NULL default '',
  `titel` varchar(255) collate latin1_german1_ci NOT NULL,
  `vorname` varchar(255) collate latin1_german1_ci NOT NULL,
  `name` varchar(255) collate latin1_german1_ci NOT NULL,
  `login` varchar(255) collate latin1_german1_ci NOT NULL default '',
  `passwort` varchar(255) collate latin1_german1_ci NOT NULL default '',
  `status` enum('L','A') collate latin1_german1_ci NOT NULL default 'L',
  `nutzertyp` varchar(255) collate latin1_german1_ci NOT NULL default 'nutzer',
  `sessionid` varchar(255) collate latin1_german1_ci NOT NULL default '',
  `lastlogin` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`nutzer_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=10 ;

--
-- Dumping data for table `nutzer`
--

INSERT INTO `nutzer` (`nutzer_id`, `anzeigename`, `titel`, `vorname`, `name`, `login`, `passwort`, `status`, `nutzertyp`, `sessionid`, `lastlogin`) VALUES
(1, 'Demoadmin', '', 'Demo', 'Nutzer', 'demonutzer', '72204b1894838afe095072c45416de94', 'A', 'admin', '2009-03-27 11:28:32', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `nutzer_einstellung`
--

CREATE TABLE IF NOT EXISTS `nutzer_einstellung` (
  `einstellung_id` int(10) unsigned NOT NULL auto_increment,
  `nutzer_id` int(10) unsigned NOT NULL,
  `varname` varchar(255) collate latin1_german1_ci NOT NULL,
  `wert` blob NOT NULL,
  PRIMARY KEY  (`einstellung_id`),
  UNIQUE KEY `nutzervars` (`nutzer_id`,`varname`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=6 ;

--
-- Dumping data for table `nutzer_einstellung`
--

INSERT INTO `nutzer_einstellung` (`einstellung_id`, `nutzer_id`, `varname`, `wert`) VALUES
(1, 1, 'listenmodus', 0x6c69737465),
(2, 1, 'eintragliste', 0x3230),
(3, 1, 'eintragtag', 0x3130),
(4, 1, 'schriftgroesse', 0x736d616c6c),
(5, 1, 'farbe', 0x666638303066);

-- --------------------------------------------------------

--
-- Table structure for table `postbuch`
--

CREATE TABLE IF NOT EXISTS `postbuch` (
  `postbuch_id` int(10) unsigned NOT NULL auto_increment,
  `einrichtung_id` int(10) unsigned NOT NULL,
  `datum` date NOT NULL,
  `datumextern` date NOT NULL,
  `typ` enum('eingang','ausgang') collate latin1_german1_ci NOT NULL,
  `medium` enum('post','email','fax') collate latin1_german1_ci NOT NULL default 'post',
  `kurzname` varchar(255) collate latin1_german1_ci NOT NULL,
  `bezeichnung` varchar(255) collate latin1_german1_ci NOT NULL,
  `str` varchar(255) collate latin1_german1_ci NOT NULL,
  `plz` varchar(255) collate latin1_german1_ci NOT NULL,
  `ort` varchar(255) collate latin1_german1_ci NOT NULL,
  `land` varchar(255) collate latin1_german1_ci NOT NULL,
  `fax` varchar(255) collate latin1_german1_ci NOT NULL,
  `email` varchar(255) collate latin1_german1_ci NOT NULL,
  `referenz` int(10) unsigned NOT NULL,
  `referenz_typ` char(1) collate latin1_german1_ci NOT NULL,
  PRIMARY KEY  (`postbuch_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `postbuch`
--

INSERT INTO `postbuch` (`postbuch_id`, `einrichtung_id`, `datum`, `datumextern`, `typ`, `medium`, `kurzname`, `bezeichnung`, `str`, `plz`, `ort`, `land`, `fax`, `email`, `referenz`, `referenz_typ`) VALUES
(1, 1, '2009-03-27', '2009-03-26', 'eingang', 'post', '', 'i-fabrik GmbH', 'Bosestr. 5', '04109', 'Leipzig', 'Deutschland', '', '', 0, 'v');

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `id` varchar(32) collate latin1_german1_ci NOT NULL default '',
  `value` blob,
  `dt` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;

--
-- Dumping data for table `session`
--

INSERT INTO `session` (`id`, `value`, `dt`) VALUES
('f6a24ov6br0s1rs6g2ooa8bp13', 0x6e75747a65725f69647c733a313a2231223b706f7374627563685f6d6f6475737c733a373a2265696e67616e67223b66696c746572646174656e7c733a303a22223b, '2009-03-27 11:30:34');
