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
class Aitoc_Aitexporter_Model_Mysql4_Profile extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize resource
     *
     */
    protected function _construct()
    {
        $this->_init('aitexporter/profile', 'profile_id');
    }
}