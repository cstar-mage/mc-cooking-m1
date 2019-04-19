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
class Aitoc_Aitexporter_Helper_Aitcheckoutfields extends Mage_Core_Helper_Abstract
{
    protected $_isEnabled = null;
    
    protected $_version = null;

    private $_customFields;
    private $_customFieldOptions = array();

    /**
     * Check whether the CFM module is active or not
     * 
     * @return boolean
     */
    public function isEnabled()
    {
        if($this->_isEnabled === null)
        {
            $this->_isEnabled = false;

            if (Mage::getConfig()->getNode('modules/Aitoc_Aitcheckoutfields'))
            {
                $isActive = Mage::getConfig()->getNode('modules/Aitoc_Aitcheckoutfields/active');
                if ($isActive && in_array((string)$isActive, array('true', '1'))) 
                {
                    $this->_isEnabled = true;
                }
            }
        }

        return $this->_isEnabled;
    }
    
    public function getVersion()
    {  
        if($this->_version === null)
        {
            $this->_version = Mage::getConfig()->getModuleConfig('Aitoc_Aitcheckoutfields')->version;
        }
        return $this->_version;
    }

    /**
     * 
     * @return array 
     */
    public function getCustomFieldsList()
    {
        $attributeIds = array();
        if (null === $this->_customFields)
        {
            $collection = Mage::getModel('eav/entity_attribute')->getCollection()
                    ->setEntityTypeFilter(Mage::getModel('eav/config')->getEntityType('aitoc_checkout')->getId());
            
            foreach ($collection->getItems() as $attribute)
            {
                $this->_customFields[$attribute->getAttributeCode()] = $attribute;
            }
        }

        return $this->_customFields;
    }

    public function getFieldOptions(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        if (!isset($this->_customFieldOptions[$attribute->getAttributeCode()]))
        {
            $this->_customFieldOptions[$attribute->getAttributeCode()] = array();
            $valuesCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setAttributeFilter($attribute->getId())
                ->setStoreFilter(0, true);

            foreach ($valuesCollection as $value)
            {
                $this->_customFieldOptions[$attribute->getAttributeCode()][$value->getValue()] = $value->getOptionId();
            }
        }

        return $this->_customFieldOptions[$attribute->getAttributeCode()];
    }
}