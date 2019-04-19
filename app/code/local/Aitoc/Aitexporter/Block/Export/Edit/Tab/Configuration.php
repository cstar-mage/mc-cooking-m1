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
class Aitoc_Aitexporter_Block_Export_Edit_Tab_Configuration extends Aitoc_Aitexporter_Block_Export_Edit_Tab_Abstract
{
    protected $_mediumDateFormat;
    
    public function getProfileData()
    {
        $collection = Mage::getResourceModel('aitexporter/profile_collection');
        $collection->load();
        $default = Mage::getModel('aitexporter/profile')
            ->setData(array(
                'profile_id' => 0,
                'name' => Mage::helper('aitexporter')->__("Add New Profile..."),
            ));
        $collection->addItem( $default );
        return $collection;
    }

    public function getEmailSenders()
    {
        return Mage::getModel('adminhtml/system_config_source_email_identity')->toOptionArray();
    }
    
    public function getEmailTemplates()
    {
        return Mage::getModel('adminhtml/system_config_source_email_template')
            ->setPath('aitexporter_template')
            ->toOptionArray();
    }
    
    public function getOrderStatuses()
    {
        $data = Mage::getModel('adminhtml/system_config_source_order_status')->toOptionArray();
        if($data[0]['value'] == '')
        {
            $data[0] = array('value' => '', 'label' => Mage::helper('aitexporter')->__('All Statuses'));
        }

        return $data;
    }
    
    public function getShortDateFormat()
    {
        if (!$this->_shortDateFormat) 
        {
            $this->_shortDateFormat = Mage::app()->getLocale()->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        }

        return $this->_shortDateFormat;
    }

    public function getMediumDateFormat()
    {
        if (!$this->_mediumDateFormat) 
        {
            $this->_mediumDateFormat = Mage::app()->getLocale()->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
        }

        return $this->_mediumDateFormat;
    }
    
    public function isOrdersExported()
    {
        return Mage::getModel('aitexporter/export_order')->isOrdersExported( $this->getProfile()->getId() );
    }
    
    public function getCronFrequency()
    {
        return Mage::getModel('aitexporter/system_config_source_cron')->toOptionArray();
    }
    
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        
        $this->setForm($form);
        
        $this->setTemplate('aitexporter/export.phtml');
        
        return parent::_prepareForm();
    }

    public function isXslExists()
    {
        return (boolean)$this->getProfile()->getXsl();
    }
    
    public function getStoreViews()
    {
        return Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true);
    }

}