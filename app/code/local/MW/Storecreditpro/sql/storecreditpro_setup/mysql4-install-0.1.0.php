<?php
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$setup->removeAttribute('catalog_product','mw_storecredit');
$setup->addAttribute('catalog_product', 'mw_storecredit', array(
	'label' => 'Store credit',
	'type' => 'int',
	'input' => 'text',
	'visible' => true,
	'required' => false,
	'position' => 10,
));

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();

$installer->run("

	DROP TABLE IF EXISTS {$resource->getTableName('storecreditpro/history')};
	CREATE TABLE {$resource->getTableName('storecreditpro/history')} (
	  `history_id` int(11) unsigned NOT NULL auto_increment,
	  `customer_id` int(11) unsigned NOT NULL default '0',
	  `transaction_type` int(2) unsigned NOT NULL,
	  `amount` decimal(12,4) DEFAULT '0.0000',
	  `balance` decimal(12,4) DEFAULT '0.0000',
	  `transaction_params` text DEFAULT '',
	  `transaction_detail` text DEFAULT '',
	  `order_id` int(11) NOT NULL,
	  `transaction_time` datetime,
	  `expired_time` datetime,
	  `remaining_credit` int(11) DEFAULT '0',
	  `status` tinyint(2) DEFAULT '1',
	  
	  PRIMARY KEY (`history_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	
	DROP TABLE IF EXISTS {$resource->getTableName('storecreditpro/customer')};
	CREATE TABLE {$resource->getTableName('storecreditpro/customer')} (
	  `customer_id` int(11) unsigned NOT NULL,
	  `credit_balance` decimal(12,4) NULL DEFAULT '0.0000',
	  `credit_updated_notification` tinyint(2) DEFAULT '1',
	  
	  PRIMARY KEY (`customer_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	
	
");


$sql_quote = "
		ALTER TABLE `{$resource->getTableName('sales/quote')}` 
		
		ADD `mw_storecredit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_checkout_max` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_checkout_min` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_buy_credit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount_show` decimal(12,4) NULL DEFAULT '0.0000'
		
		";

$installer->run($sql_quote);

$sql_quote_address = "
		ALTER TABLE `{$resource->getTableName('sales/quote_address')}` 
		
		ADD `mw_storecredit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_buy_credit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount_show` decimal(12,4) NULL DEFAULT '0.0000'
		
		";

$installer->run($sql_quote_address);


$sql_order = "
		ALTER TABLE `{$resource->getTableName('sales/order')}` 
		
		ADD `mw_storecredit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_buy_credit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount_show` decimal(12,4) NULL DEFAULT '0.0000'
		
		";

$installer->run($sql_order);

$sql_invoice = "
		ALTER TABLE `{$resource->getTableName('sales/invoice')}` 
		
		ADD `mw_storecredit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_buy_credit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount_show` decimal(12,4) NULL DEFAULT '0.0000'
		
		";

$installer->run($sql_invoice);

$sql_creditmemo = "
		ALTER TABLE `{$resource->getTableName('sales/creditmemo')}` 
		
		ADD `mw_storecredit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_check_refund` tinyint(2) DEFAULT '0',
		ADD `mw_storecredit_buy_credit` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_storecredit_discount_show` decimal(12,4) NULL DEFAULT '0.0000'
		
		";


$installer->run($sql_creditmemo);

$installer->endSetup();
