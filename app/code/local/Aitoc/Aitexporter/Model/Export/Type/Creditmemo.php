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
class Aitoc_Aitexporter_Model_Export_Type_Creditmemo extends Aitoc_Aitexporter_Model_Export_Type_Complex implements Aitoc_Aitexporter_Model_Export_Type_Interface
{
    /**
     * 
     * @param SimpleXMLElement $orderXml
     * @param Mage_Sales_Model_Order $order
     * @param Varien_Object $exportConfig
     */
    public function prepareXml(SimpleXMLElement $orderXml, Mage_Core_Model_Abstract $order, Varien_Object $exportConfig)
    {
        /* @var $order Mage_Sales_Model_Order */

        if (empty($exportConfig['entity_type']['creditmemo']['creditmemo']))
        {
            return;
        }

        // Filter shipments by order Id
        $creditmemosXml = $orderXml->addChild('creditmemos');
        
        foreach ($order->getCreditmemosCollection() as $creditmemo)
        {
            $creditmemoXml = $creditmemosXml->addChild('creditmemo');

            foreach($creditmemo->getData() as $field => $value)
            {
                $creditmemoXml->addChild($field, (string)$value);
            }
            
            $creditmemoXml->addChild('increment_prefix', $this->_getIncrementPrefix($order, 'creditmemo'));

            $this->_exportChildData($creditmemoXml, $creditmemo, $exportConfig);
        }
    }

    /**
     * 
     * @see Aitoc_Aitexporter_Model_Export_Type_Complex::getChildTypes()
     * @param Varien_Object $exportConfig
     * @return array
     */
    public function getChildTypes(Varien_Object $exportConfig)
    {
        $entityTypes = $exportConfig->getEntityType() ? $exportConfig->getEntityType() : array();
        $childTypes  = isset($entityTypes['creditmemo']) ? $entityTypes['creditmemo'] : array();

        if (isset($childTypes['creditmemo']))
        {
            unset($childTypes['creditmemo']);
        }

        return $childTypes;
    }
}