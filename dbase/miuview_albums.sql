--
-- Tabeli struktuur tabelile `miuview_albums`
--

CREATE TABLE IF NOT EXISTS `miuview_albums` (
  `album` varchar(128) COLLATE utf8_estonian_ci NOT NULL,
  `title` varchar(200) COLLATE utf8_estonian_ci DEFAULT '',
  `thumb` varchar(128) COLLATE utf8_estonian_ci DEFAULT '',
  `public` tinyint(1) DEFAULT '1',
  `sort` int(11) DEFAULT NULL,
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `added` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_estonian_ci;

--
-- Indeksid tabelile `miuview_albums`
--
ALTER TABLE `miuview_albums`
 ADD PRIMARY KEY (`album`);
