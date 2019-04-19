<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('brosolutions_smartslides'),
        'image_link',
        'varchar (255) default null'
    );
$installer->endSetup();