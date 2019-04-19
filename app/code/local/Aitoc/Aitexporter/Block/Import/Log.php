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
class Aitoc_Aitexporter_Block_Import_Log extends Mage_Adminhtml_Block_Widget_Form_Container
{
    private $_currentImport;

    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'aitexporter';
        $this->_controller = 'import';
        $this->_mode       = 'log';

        $importUrl  = Mage::getModel('adminhtml/url')->getUrl('*/*/import', array('id' => $this->getCurrentImport()->getId()));
        $currentUrl = Mage::getModel('adminhtml/url')->getUrl('*/*/*', array('id' => $this->getCurrentImport()->getId()));

        $this->_updateButton('save', 'onclick', 'setLocation(\''.$importUrl.'\')');
        $this->_updateButton('delete', 'label', Mage::helper('aitexporter')->__('Cancel'));

        $currentImport = Mage::registry('current_import');

        switch ($this->getCurrentImport()->getStatus())
        {
            case Aitoc_Aitexporter_Model_Import::STATUS_WARNINGS:
                $this->_updateButton('save', 'label', Mage::helper('aitexporter')->__('Import. Ignore Errors'));
                
                if (Aitoc_Aitexporter_Model_Import::BEHAVIOR_REMOVE == $currentImport->getBehavior())
                {
                    $this->_updateButton('save', 'onclick', 
                    	'if (!confirm(\''.Mage::helper('aitexporter')->__('This will delete all orders from you database. Are you sure you want to do this?').'\')) {return false;} setLocation(\''.$importUrl.'\');');
                }
                break;

            case Aitoc_Aitexporter_Model_Import::STATUS_VALID:
                $this->_updateButton('save', 'label', Mage::helper('aitexporter')->__('Import'));

                if (Aitoc_Aitexporter_Model_Import::BEHAVIOR_REMOVE == $currentImport->getBehavior())
                {
                    $this->_updateButton('save', 'onclick', 
                    	'if (!confirm(\''.Mage::helper('aitexporter')->__('This will delete all orders from you database. Are you sure you want to do this?').'\')) {return false;} setLocation(\''.$importUrl.'\');');
                }
                break;

            case Aitoc_Aitexporter_Model_Import::STATUS_PENDING:
                $this->_updateButton('save', 'label', Mage::helper('aitexporter')->__('Validate'));
                $this->_updateButton('save', 'onclick', 'setLocation(\''.$currentUrl.'\');');
                break;

            case Aitoc_Aitexporter_Model_Import::STATUS_ERRORS:
            case Aitoc_Aitexporter_Model_Import::STATUS_ERRORS:
            case Aitoc_Aitexporter_Model_Import::STATUS_COMPLETE:
                $this->_removeButton('save');
                break;
        }

        //$this->_removeButton('delete');
        $this->_removeButton('reset');
    }

    /**
     * @return Aitoc_Aitexporter_Model_Import
     */
    public function getCurrentImport()
    {
        if (empty($this->_currentImport))
        {
            $this->_currentImport = Mage::registry('current_import');
        }

        return $this->_currentImport;
    }

    public function getHeaderText()
    {
        $date   = $this->getCurrentImport()->getDt();
        $format = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
        $date   = Mage::app()->getLocale()->date($date, Varien_Date::DATETIME_INTERNAL_FORMAT)->toString($format);
        return Mage::helper('aitexporter')->__('Order Import "%s", %s', $this->getCurrentImport()->getFilename(), $date);
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/*/index', array('active_tab' => 'history_section'));
    }
}