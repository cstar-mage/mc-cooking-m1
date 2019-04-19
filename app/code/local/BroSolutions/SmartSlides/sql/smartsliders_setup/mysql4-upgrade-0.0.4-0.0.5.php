<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()
->addColumn($installer->getTable('brosolutions_smartslides'),
'slide_class',
'varchar (255) default null'
);
$installer->endSetup();