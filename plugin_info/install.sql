SET NAMES utf8;

DROP TABLE IF EXISTS `sigri_atome_hour`;
CREATE TABLE `sigri_atome_hour` (
    `hour` datetime NOT NULL,
    `code` varchar(6) DEFAULT NULL,
    `total_consumption` int(11) NOT NULL,
    `index` int(11) DEFAULT NULL,
    `cost` decimal(10,5) DEFAULT NULL,
    PRIMARY KEY (`hour`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*
DROP TABLE IF EXISTS `sigri_atome_hour`;
CREATE TABLE `sigri_atome_hour` (
    `hour` datetime NOT NULL,
    `total_consumption` int(11) NOT NULL,
    `index_hp` int(11) DEFAULT NULL,
    `index_hc` int(11) DEFAULT NULL,
    `cost_hp` decimal(10,5) DEFAULT NULL,
    `cost_hc` decimal(10,5) DEFAULT NULL,
    PRIMARY KEY (`hour`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
*/

DROP TABLE IF EXISTS `sigri_atome_day`;
CREATE TABLE `sigri_atome_day` (
    `day` date NOT NULL,
    `total_consumption` int(11) NOT NULL,
    `index_hp` int(11) DEFAULT NULL,
    `index_hc` int(11) DEFAULT NULL,
    `cost_hp` decimal(10,5) DEFAULT NULL,
    `cost_hc` decimal(10,5) DEFAULT NULL,
    PRIMARY KEY (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `sigri_atome_config`;
CREATE TABLE `sigri_atome_config` (
    `key` varchar(11) NOT NULL,
    `value` varchar(11) NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;