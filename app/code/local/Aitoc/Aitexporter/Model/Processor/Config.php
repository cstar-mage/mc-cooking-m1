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
class Aitoc_Aitexporter_Model_Processor_Config extends Mage_Core_Model_Config_Data
{
    protected $_config_path = 'sales/aitexporter/iterator';
    
    // default config
    protected $_config = array(
        'iterator'   => Aitoc_Aitexporter_Model_Processor::ITERATOR_CRON,
        'status'     => Aitoc_Aitexporter_Model_Processor::ITERATOR_STATUS_FREE,
        'time'       => 0,
        'process'    => '',
        'options'    => array() 
    );
    
    /**
     * Init resource table and load the config
     */
    protected function _construct()
    {
        $this->_init('aitexporter/processor_config');
        $this->load($this->_config_path, 'path');
    }
    
    /**
     * Serialize config data before save
     */
    protected function _beforeSave()
    {
        $this->setValue(serialize($this->_config));
        return parent::_beforeSave();
    }
    
    /**
     * Unserialize config data after model load or create a new config if config entity not found
     */
    protected function _afterLoad()
    {
        if($value = $this->getValue())
        {
            $this->_config = unserialize($value);
        }else{
            $this->_hasDataChanges = true;
            $this->setScope('default')
                 ->setScopeId(0)
                 ->setPath($this->_config_path)
                 ->save();
        }
        return parent::_afterLoad();
    }
    
    /**
     * Get config param
     */
    public function get($param, $default = null)
    {
        return isset($this->_config[$param]) ? $this->_config[$param] : $default;
    }
    
    /**
     * Set config param
     */
    public function set($param, $value = '')
    {
        $this->_hasDataChanges = true;
        $this->_config[$param] = $value;
        return $this;
    }
    
    /**
     * Update config 'options' param and save
     */
    public function updateOption($option, $value)
    {
        $this->_config['options'][$option] = $value;
        $this->save();
        return $this;
    }
    
    
    /**
     * Get config 'options' param and save
     */
    public function getOption($option)
    {
        return isset($this->_config['options'][$option])?$this->_config['options'][$option]:null;
    }    
    
    public function haveActiveProcess()
    {
        return (bool)$this->_config['process'];
    }
    
	public function resetExport() 
	{
	    $this->updateOption('from', null);        
        $configValues = array(
            'iterator'   => Aitoc_Aitexporter_Model_Processor::ITERATOR_CRON,
            'status'     => Aitoc_Aitexporter_Model_Processor::ITERATOR_STATUS_FREE,
            'time'       => 0,
            'forward'    => 0,            
            'process'    => '',
            'options'    => array()
        );
        foreach($configValues as $k=>$v)
        {
            $this->set($k, $v);
        }
        $this->save(); 
	}
	
}