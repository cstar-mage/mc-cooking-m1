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
class Aitoc_Aitexporter_Model_Profile extends Mage_Core_Model_Abstract
{
    protected $_flags = array(
        'none'              => 0,
        'after_checkout'    => 1,
        'after_invoice'     => 2,
        'cron_frequency'    => 3,
    );
    
    protected $_config = null;
    
    public function __construct()
    {
        parent::_construct();
        $this->_init('aitexporter/profile');
    }

    /**
    * Save array to serialized text field
    * 
    * @param array $config
    * @return Aitoc_Aitexporter_Model_Profile
    */
    public function setConfig( $config )
    {
        $this->setData('config', serialize($config));
        $this->_config = $config;
        $this->setFlagAuto(isset($config['auto'],$config['auto']['export_type']) ? $config['auto']['export_type'] : 0);
        return $this;
    }
    
    /**
    * @return array
    */
    public function getConfig()
    {
        if(is_null($this->_config)) {
            $this->_config = unserialize( $this->getData('config') );
        }
        return $this->_config;
    }
    
    public function isFlag( $key )
    {
        if(!isset($this->_flags[$key])) {
            return false;
        }
        $data = (int)$this->getData('flag_auto');
        return (bool)$data == $this->_flags[$key];
    }
    
    public function beforeSave()
    {
        
    }
    
    protected function _loadCollection( $id, $store_id = null )
    {
        return $this->getCollection()
            ->loadByFlag( $id, $store_id );
    }
    
    public function loadCronCollection()
    {
        return $this->_loadCollection( $this->_flags['cron_frequency'] );
    }
    
    public function loadCheckoutCollection( $store_id = null )
    {
        return $this->_loadCollection( $this->_flags['after_checkout'], $store_id );
    }

    public function loadInvoiceCollection( $store_id = null )
    {
        return $this->_loadCollection( $this->_flags['after_invoice'], $store_id );
    }

    public function updateDate( $key = 'date' )
    {
        $this->setData($key, Mage::getModel('core/date')->date());
        return $this;
    }
}