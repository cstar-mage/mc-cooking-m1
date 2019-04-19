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
abstract class Aitoc_Aitexporter_Model_Export_Type_Complex
{
    /**
     * 
     * @param SimpleXMLElement $element
     * @param Mage_Catalog_Model_Abstract $item
     * @param Varien_Object $exportConfig
     */
    protected function _exportChildData(SimpleXMLElement $contextXml, Mage_Core_Model_Abstract $contextItem, Varien_Object $exportConfig)
    {
        // Add configured entity types to order XML
        if ($exportConfig->getEntityType())
        {
            foreach ($this->getChildTypes($exportConfig) as $entityType => $values)
            {
                // Export type factory class
                $entityTypeModel = Mage::helper('aitexporter')->getExportModelByEntityType($entityType);
                /* @var $entityTypeModel Aitoc_Aitexporter_Model_Export_Type_Iterface */
                $entityTypeModel->prepareXml($contextXml, $contextItem, $exportConfig);
            }
        }
    }

    /**
     * 
     * @param Varien_Object $exportConfig
     * @return array
     * @abstract
     */
    abstract public function getChildTypes(Varien_Object $exportConfig);
    
    protected function _getIncrementPrefix(Mage_Sales_Model_Order $order, $entityCode = '')
    {
        $prefix = false;
        
        //fix for Custom Order Number Pro start
        $settingsLevel = Mage::getStoreConfig('ordernum/general/settings_level', 0);
        if(isset($settingsLevel))//module Custom Order Number Pro exists
        {
            switch($settingsLevel)
            {
                case 'stores':
                    $prefix = Mage::getStoreConfig('ordernum/' . $entityCode . '/prefix', $order->getStoreId());
                    break;
                case 'websites':
                    $prefix = Mage::getStoreConfig('ordernum/' . $entityCode . '/prefix', Mage::getModel('core/store')->load($order->getStoreId())->getWebsiteId());
                    break;
                default:
                    $prefix = Mage::getStoreConfig('ordernum/' . $entityCode . '/prefix', 0);
                    break;
            }
        }
        if($prefix)
        {
            return $prefix;
        }
        //fix for Custom Order Number Pro end
        
        $entityType = Mage::getModel('eav/entity_type')->loadByCode($entityCode);
        $increment = Mage::getModel('eav/entity_store')
            ->loadByEntityStore($entityType->getId(), $order->getStoreId());
            
        return $increment->getIncrementPrefix();  
    }
}