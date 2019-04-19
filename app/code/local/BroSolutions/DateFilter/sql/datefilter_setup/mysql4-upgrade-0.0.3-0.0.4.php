<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()
    ->addColumn($installer->getTable('sales/order_item'),'remind_email_sent', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => true,
        'length'    => 255,
        'after'     => NULL,
        'default'   => NULL,
        'comment'   => 'Remind Email Sent'
    ));
$installer->endSetup();