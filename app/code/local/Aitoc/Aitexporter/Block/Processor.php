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
class Aitoc_Aitexporter_Block_Processor extends Mage_Adminhtml_Block_Template
{
    /**
     * @var Aitoc_Aitexporter_Model_Processor_Config
     */
    protected $_config;
    
    /**
     * @var Aitoc_Aitexporter_Model_Processor
     */
    protected $_processor;
    
    /**
     * @var Aitoc_Aitexporter_Helper_Processor
     */
    protected $_helper;
    
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('aitexporter/processor.phtml');
        
        $this->_config    = Mage::getSingleton('aitexporter/processor_config');
        $this->_processor = Mage::getSingleton('aitexporter/processor');
        $this->_helper    = Mage::helper('aitexporter/processor');
    }
    
    public function getPercent()
    {
        $options = $this->_config->get('options');
        $percent = $this->_helper->calculatePercent($options);
        return $percent;
    }
    
    public function haveActiveProcess()
    {
        return (bool)$this->_config->haveActiveProcess();
    }
    
    public function getProcessName()
    {
        return $this->_helper->getProcessName($this->_config->get('process'));
    }
    
    public function isAjax()
    {
        return (bool)Mage::app()->getRequest()->getParam('isAjax');
    }
}