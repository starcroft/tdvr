-- MySQL dump 10.14  Distrib 5.5.56-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: tdvr
-- ------------------------------------------------------
-- Server version	5.5.56-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `episodes`
--

DROP TABLE IF EXISTS `episodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `episodes` (
  `episodeid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `showid` mediumint(8) unsigned NOT NULL,
  `season` smallint(5) unsigned NOT NULL,
  `episode_number` varchar(8) NOT NULL,
  `episode_name` varchar(256) NOT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`episodeid`),
  KEY `showid` (`showid`,`timestamp`)
) ENGINE=MyISAM AUTO_INCREMENT=164056 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `favourites`
--

DROP TABLE IF EXISTS `favourites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favourites` (
  `favouriteid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `showid` mediumint(9) NOT NULL,
  `season` tinyint(4) NOT NULL,
  `episode` tinyint(4) NOT NULL,
  `location` varchar(128) NOT NULL,
  `ratio` tinyint(4) NOT NULL,
  `quality` varchar(128) NOT NULL,
  PRIMARY KEY (`favouriteid`),
  KEY `showid` (`showid`)
) ENGINE=MyISAM AUTO_INCREMENT=323 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `feeds`
--

DROP TABLE IF EXISTS `feeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(256) NOT NULL,
  `priority` smallint(6) NOT NULL,
  `ratio` varchar(8) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rank` (`priority`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `text` varchar(1024) NOT NULL,
  `error` tinyint(4) NOT NULL,
  PRIMARY KEY (`logid`),
  KEY `error` (`error`)
) ENGINE=MyISAM AUTO_INCREMENT=3314 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `releases`
--

DROP TABLE IF EXISTS `releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `releases` (
  `releaseid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `episodeid` mediumint(8) unsigned DEFAULT NULL,
  `movieid` mediumint(9) DEFAULT NULL,
  `quality` varchar(16) NOT NULL,
  `resolution` varchar(16) NOT NULL,
  `audio` varchar(16) NOT NULL,
  `url` varchar(256) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  `downloaded` tinyint(1) NOT NULL,
  `proper` tinyint(4) NOT NULL,
  `original_name` varchar(128) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `video` varchar(16) NOT NULL,
  PRIMARY KEY (`releaseid`),
  KEY `episodeid` (`episodeid`,`quality`,`timestamp`),
  KEY `movieid` (`movieid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=645425 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shows`
--

DROP TABLE IF EXISTS `shows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shows` (
  `showid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `description` varchar(4096) NOT NULL,
  `category` varchar(64) NOT NULL,
  `tvdb_id` int(11) NOT NULL,
  `ignore` tinyint(1) NOT NULL,
  `poster` varchar(256) DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`showid`),
  UNIQUE KEY `showlist` (`showid`,`ignore`)
) ENGINE=MyISAM AUTO_INCREMENT=15513 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-06-20  9:53:18
