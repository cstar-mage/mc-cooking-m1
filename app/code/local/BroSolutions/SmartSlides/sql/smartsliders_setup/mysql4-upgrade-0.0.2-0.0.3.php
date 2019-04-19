<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('brosolutions_smartslidesgroup'),
        'group_code',
        'varchar (255) default null'
    );
$installer->endSetup();