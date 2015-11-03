ALTER TABLE `#__dynamic404_redirects` ADD `static` TINYINT(1) NOT NULL DEFAULT '0' AFTER `type`;
ALTER TABLE `#__dynamic404_logs` ADD `http_status` VARCHAR(10) NOT NULL DEFAULT '' AFTER `request`;
ALTER TABLE `#__dynamic404_logs` ADD `message` VARCHAR(255) NOT NULL DEFAULT '' AFTER `http_status`;
UPDATE `#__dynamic404_logs` SET `http_status`='404' WHERE `http_status`='';
UPDATE `#__dynamic404_logs` SET `message`='Page not found' WHERE `message`='';