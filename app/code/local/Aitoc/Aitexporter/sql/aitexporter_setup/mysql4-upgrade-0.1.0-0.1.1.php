<?php
/**
 * Orders Export and Import
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitexporter
 * @version      1.2.11
 * @license:     xwB5BO6b8ADSZdWMZBvj82HAnNdHAZvfJuyG2JiSFP
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
$installer = $this;


$installer->startSetup();

$installer->run('

DROP TABLE IF EXISTS `'.$this->getTable('aitexporter_export_order').'`;
DROP TABLE IF EXISTS `'.$this->getTable('aitexporter_export').'`;

CREATE  TABLE IF NOT EXISTS `'.$this->getTable('aitexporter_export').'` (
  `export_id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `dt` TIMESTAMP NOT NULL ,
  `filename` VARCHAR(255) NOT NULL ,
  `serialized_config` TEXT NOT NULL ,
  `is_ftp_upload` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  `is_email` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  `orders_count` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 ,
  `is_cron` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`export_id`) 
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `'.$this->getTable('aitexporter_export_order').'` (
  `export_order_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `export_id` MEDIUMINT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`export_order_id`)
) ENGINE = InnoDB;

ALTER TABLE `'.$this->getTable('aitexporter_export_order').'` ADD CONSTRAINT `fk_aitexporter_export2order`
    FOREIGN KEY (`export_id`)
    REFERENCES `'.$this->getTable('aitexporter_export').'` (`export_id`)
    ON DELETE CASCADE ON UPDATE NO ACTION;


DROP TABLE IF EXISTS `'.$this->getTable('aitexporter_import_error').'`;
DROP TABLE IF EXISTS `'.$this->getTable('aitexporter_import').'`;

CREATE TABLE IF NOT EXISTS `'.$this->getTable('aitexporter_import').'` (
  `import_id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `filename` VARCHAR(255) NOT NULL ,
  `status` ENUM("pending","complete") NOT NULL DEFAULT "pending" ,
  PRIMARY KEY (`import_id`) 
) ENGINE = InnoDB;

CREATE  TABLE IF NOT EXISTS `'.$this->getTable('aitexporter_import_error').'` (
  `error_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `import_id` MEDIUMINT UNSIGNED NOT NULL ,
  `order_id` INT UNSIGNED NOT NULL ,
  `error` TEXT NOT NULL ,
  PRIMARY KEY (`error_id`)
) ENGINE = InnoDB;

ALTER TABLE `'.$this->getTable('aitexporter_import_error').'` ADD CONSTRAINT `fk_aitexporter_import2error`
    FOREIGN KEY (`import_id`)
    REFERENCES `'.$this->getTable('aitexporter_import').'` (`import_id`)
    ON DELETE CASCADE ON UPDATE NO ACTION;

');


$installer->endSetup();