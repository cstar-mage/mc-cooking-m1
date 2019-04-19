<?php
/**
 * Orders Export and Import
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitexporter
 * @version      1.2.11
 * @license:     xwB5BO6b8ADSZdWMZBvj82HAnNdHAZvfJuyG2JiSFP
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
class Aitoc_Aitexporter_Model_Mysql4_Export_Order_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('aitexporter/export_order');
    }
    
    public function loadByProfileId($profile_id = 0)
    {
        $this->getSelect()
            ->where('profile_id = ?', $profile_id)
            ->limit(1);
        return $this;
    }

}