CREATE TABLE IF NOT EXISTS `#__dynamic404_redirects` (
    `redirect_id` int(11) NOT NULL AUTO_INCREMENT,
    `match` varchar(255) NOT NULL,
    `url` varchar(255) NOT NULL,
    `http_status` int(3) NOT NULL,
    `description` text NOT NULL,
    `type` varchar(50) NOT NULL,
    `checked_out` int(11) NOT NULL DEFAULT '0',
    `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `published` tinyint(1) NOT NULL DEFAULT '0',
    `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `created_by` int(11) NOT NULL DEFAULT '0',
    `created_by_alias` text NOT NULL,
    `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` int(11) NOT NULL DEFAULT '0',
    `modified_by_alias` text NOT NULL,
    `ordering` int(11) NOT NULL DEFAULT '0',
    `access` tinyint(3) NOT NULL DEFAULT '0',
    `locked` tinyint(1) NOT NULL DEFAULT '0',
    `params` text NOT NULL,
    PRIMARY KEY (`redirect_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__dynamic404_logs` (
    `log_id` int(11) NOT NULL auto_increment,
    `request` varchar(255) NOT NULL,
    `timestamp` int(11) NOT NULL default '0',
    `hits` int(11) NOT NULL default '0',
    `ordering` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY  (`log_id`)
) DEFAULT CHARSET=utf8;
