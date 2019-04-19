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
class Aitoc_Aitexporter_Model_Processor_Direct_Config extends Aitoc_Aitexporter_Model_Processor_Config
{
    protected $_config_path = 'sales/aitexporter/direct_iterator';
    
    /**
     * Init resource table and prevend loading default config
     */
    protected function _construct()
    {
        $this->_init('aitexporter/processor_direct_config');
        #$this->load($this->_config_path, 'path');
    }
    
    /**
    * Prevent saving this model
    * 
    */
    public function save() {
        return $this;
    }
	
}