
--
-- Tabeli struktuur tabelile `miuview_items`
--

CREATE TABLE IF NOT EXISTS `miuview_items` (
  `item` varchar(128) COLLATE utf8_estonian_ci NOT NULL,
  `title` varchar(200) COLLATE utf8_estonian_ci DEFAULT '',
  `description` text COLLATE utf8_estonian_ci,
  `type` varchar(10) COLLATE utf8_estonian_ci NOT NULL DEFAULT 'picture',
  `album` varchar(128) COLLATE utf8_estonian_ci NOT NULL,
  `metadata` text COLLATE utf8_estonian_ci,
  `sort` int(11) DEFAULT '0',
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `added` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_estonian_ci;

--
-- Indeksid tabelile `miuview_items`
--
ALTER TABLE `miuview_items`
 ADD PRIMARY KEY (`item`,`album`);
