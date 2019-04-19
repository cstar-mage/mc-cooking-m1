<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('brosolutions_smartslides'),
        'sort_order',
        'varchar (255) default null'
    );
$installer->endSetup();