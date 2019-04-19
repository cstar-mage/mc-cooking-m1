<?php
$installer = $this;
$installer->startSetup();
$installer->run("
DROP TABLE IF EXISTS  `{$installer->getTable('brosolutions_smartslides')}`;
    CREATE TABLE `{$installer->getTable('brosolutions_smartslides')}` (
       `entity_id` int(10) unsigned NOT NULL auto_increment,
       `title` TEXT,
       `subtitle` TEXT,
       `description` TEXT,
       `status` int(25) unsigned,
       `image_path`  TEXT,
       PRIMARY KEY  (`entity_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->run("
DROP TABLE IF EXISTS  `{$installer->getTable('brosolutions_smartslidesgroup')}`;
    CREATE TABLE `{$installer->getTable('brosolutions_smartslidesgroup')}` (
       `entity_id` int(10) unsigned NOT NULL auto_increment,
       `name` TEXT,
       `status` varchar(255) NOT NULL default '',
       `effect` varchar(255) NOT NULL default '',
       `animate_speed` varchar(255) NOT NULL default '',
       `is_arrows_enabled` int(10) unsigned,
       `is_dots_enabled` int(10) unsigned,
       `per_page` int(25) unsigned,
       `auto_play` int(10) unsigned,
       `pause_on_hower` int(10) unsigned,
       `blocks_after` TEXT,
       `handle` TEXT,
       `slides_ids` TEXT,
       `slide_type` varchar(255) NOT NULL default 'default',
       PRIMARY KEY  (`entity_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");


$installer->endSetup();
