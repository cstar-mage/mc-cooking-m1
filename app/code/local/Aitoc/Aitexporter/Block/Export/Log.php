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
class Aitoc_Aitexporter_Block_Export_Log extends Mage_Adminhtml_Block_Sales_Order
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'aitexporter';
        $this->_controller = 'export_log';
        $currentExport     = Mage::registry('current_export');

        $this->_addButton('back', array(
            'label'   => $this->getBackButtonLabel(),
            'onclick' => 'setLocation(\''.$this->getBackUrl().'\')',
            'class'   => 'back', 
            ));

        $this->_addButton('delete', array(
            'label'   => $this->helper('aitexporter')->__('Delete'), 
            'onclick' => 'if (!confirm(\''.Mage::helper('aitexporter')->__('Are you sure you want to do this?').'\')) {return false;} setLocation(\''.$this->getUrl('*/*/delete', array('id' => $currentExport->getId())).'\')',
            'class'   => 'delete', 
            ));

        $this->_addButton('download', array(
            'label'   => $this->helper('aitexporter')->__('Download'),
            'onclick' => 'setLocation(\''.$this->getUrl('*/*/download', array('id' => $currentExport->getId())).'\')',
            'class'   => 'save', 
            ));

        $this->_removeButton('add');
    }

    public function getHeaderText()
    {
        $currentExport = Mage::registry('current_export');

        return Mage::helper('aitexporter')->__('Exported Orders %s', $currentExport->getFilename());
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*/index');
    }
}