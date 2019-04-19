<?php
/**
* Magedelight
* Copyright (C) 2015 Magedelight <info@magedelight.com>
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category MD
* @package MD_Membership
* @copyright Copyright (c) 2015 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/

$installer = $this;
$installer->startSetup();
$installer->run("
        DROP TABLE IF EXISTS `{$installer->getTable('md_membership/plans')}`;
            
        CREATE TABLE `{$installer->getTable('md_membership/plans')}`(
            `plan_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Id',
            `title` varchar(250) NOT NULL DEFAULT '',
            `description` text DEFAULT NULL,
            `amount` decimal(12,4) NOT NULL DEFAULT '0',
            `image` varchar(150) NULL DEFAULT NULL,
            `store_ids` varchar(50) NULL DEFAULT NULL,
            `status` tinyint(2) NOT NULL DEFAULT '1',
            `assigned_group_id` tinyint(5) DEFAULT NULL,
            `billing_period` set('day','week','month','bi_month','quarter','year') NOT NULL DEFAULT 'month',
            `billing_frequency` int(11) NOT NULL DEFAULT '0',
            `is_limited` smallint(6) NOT NULL DEFAULT '1',
            `total_occurences` smallint(5) NOT NULL DEFAULT '0',
            `max_failed_payment` tinyint(3) NOT NULL DEFAULT '0',
            `start_date_defined` set('by_customer','day_purchase','last_day_month','day_month') NOT NULL DEFAULT 'by_customer',
            `day_of_month` tinyint(3) DEFAULT NULL,
            `trial_period_count` tinyint(5) NOT NULL DEFAULT '0',
            `trial_amount` decimal(12,4) NOT NULL DEFAULT '0',
            `initial_amount` decimal(12,4) NOT NULL DEFAULT '0',
            `url_key` varchar(250) NULL DEFAULT NULL,
            `is_shipment` tinyint(4) NOT NULL DEFAULT '0',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`plan_id`),
            UNIQUE KEY `MEMBERSHIP_IDX` (`url_key`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;
        
    DROP TABLE IF EXISTS `{$installer->getTable('md_membership/subscribers')}`;
        CREATE TABLE `{$installer->getTable('md_membership/subscribers')}`(
            `subscriber_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Id',
  `plan_id` int(11) NOT NULL,
  `profile_id` varchar(250) DEFAULT NULL,
  `reference_id` varchar(15) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_address_id` int(11) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `postcode` varchar(50) DEFAULT NULL,
  `region` varchar(150) DEFAULT NULL,
  `country_id` varchar(10) DEFAULT NULL,
  `payment_method` varchar(150) DEFAULT NULL,
  `status` tinyint(3) NOT NULL DEFAULT '0',
  `profile_start_date` date NOT NULL DEFAULT '0000-00-00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `next_billing_date` timestamp NULL DEFAULT NULL,
  `last_payment_date` timestamp NULL DEFAULT NULL,
  `billing_cycles_completed` int(11) DEFAULT NULL,
  `billing_cycles_remains` int(11) DEFAULT NULL,
  `regular_billing_cycles` int(11) DEFAULT NULL,
  `trial_billing_cycles` int(11) DEFAULT NULL,
  `payment_failed_count` int(11) DEFAULT NULL,
  `final_payment_date` timestamp NULL DEFAULT NULL,
  `plan_data` text NULL DEFAULT NULL,
            PRIMARY KEY (`subscriber_id`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;
  DROP TABLE IF EXISTS `{$installer->getTable('md_membership/payments')}`;
  CREATE TABLE `{$installer->getTable('md_membership/payments')}`(
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Id',
  `profile_id` varchar(250) DEFAULT NULL,
  `reference_id` varchar(15) DEFAULT NULL,
  `gross_amount` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `due_amount` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `last_payment` date DEFAULT NULL,
  `last_paid_amount` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `next_payment` date DEFAULT NULL,
  `additional_info` text,
  `email_sent` tinyint(2) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `PAYMENTS_SUBSCRIPTIONS_IDX` (`profile_id`,`last_payment`)
    )ENGINE=InnoDB DEFAULT CHARSET=utf8;    
        
  DROP TABLE IF EXISTS `{$installer->getTable('md_membership/increments')}`;
        CREATE TABLE `{$installer->getTable('md_membership/increments')}`(
            `increment_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Id',
            `store_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Store Id',
            `increment_last_id` varchar(50) DEFAULT NULL COMMENT 'Last Incremented Id',
            PRIMARY KEY (`increment_id`),
            UNIQUE KEY `SUBSCRIPTION_INCREMENTS_IDX` (`store_id`)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;      
");
        $storeCollection = Mage::getModel('core/store')
                    ->getCollection()
                    ->addFieldToFilter('store_id',array('gt'=>0));
foreach($storeCollection as $_store){
    $data = array();
    $data['store_id'] = $_store->getId();
    $data['increment_last_id'] = $_store->getId().'000000001';
    Mage::getModel('md_membership/increments')->setData($data)->save();
}
$installer->endSetup();

