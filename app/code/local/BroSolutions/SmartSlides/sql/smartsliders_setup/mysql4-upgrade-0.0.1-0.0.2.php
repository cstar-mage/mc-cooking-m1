<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('brosolutions_smartslides'),
        'video_link',
        'varchar (255) default null'
    );
$installer->endSetup();