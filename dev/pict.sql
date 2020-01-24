-- SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET GLOBAL sql_mode = '';
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE `pict` (
  `GameID` int(11) NOT NULL,
  `startTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `NumPlayers` int(11) NOT NULL,
  `Round` int(11) NOT NULL,
  `PlayOrder` varchar(255) NOT NULL,
  `NextGame` int(11) NOT NULL,
  `Countdown` int(11) NOT NULL,
  `WordList` int(11) NOT NULL,
  `GameMode` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `pictDesc` (
  `GameID` int(11) NOT NULL,
  `Round` int(11) NOT NULL,
  `Artist` int(11) NOT NULL,
  `ArtistName` varchar(255) NOT NULL,
  `Description` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `pictPlayers` (
  `SessionCookie` char(32) NOT NULL,
  `pollTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `GameID` int(11) NOT NULL,
  `PlayerNum` int(11) NOT NULL,
  `Ready` tinyint(1) NOT NULL,
  `Name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


ALTER TABLE `pict`
  ADD PRIMARY KEY (`GameID`);

ALTER TABLE `pictDesc`
  ADD PRIMARY KEY (`GameID`,`Round`,`Artist`);

ALTER TABLE `pictPlayers`
  ADD PRIMARY KEY (`SessionCookie`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
