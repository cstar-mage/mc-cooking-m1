<?php
$installer = $this;
$installer->startSetup();
$installer->run("
ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `freepayment` DECIMAL( 10, 2 ) NOT NULL;
ALTER TABLE  `".$this->getTable('sales/quote')."` ADD  `freepayment` DECIMAL( 10, 2 ) NOT NULL;
ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `freepayment` DECIMAL( 10, 2 ) NOT NULL;

");

$installer->endSetup();
