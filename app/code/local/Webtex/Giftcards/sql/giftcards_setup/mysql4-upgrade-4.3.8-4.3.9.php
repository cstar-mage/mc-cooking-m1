<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()
    ->addColumn($installer->getTable('giftcards/giftcards'),'quote_item_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => true,
        'length'    => 255,
        'after'     => null,
        'comment'   => 'Quote Item Id',
    ));
$installer->endSetup();