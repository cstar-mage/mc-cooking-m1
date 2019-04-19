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
class Aitoc_Aitexporter_Model_Export_Type_Invoice extends Aitoc_Aitexporter_Model_Export_Type_Complex implements Aitoc_Aitexporter_Model_Export_Type_Interface
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

        if(empty($exportConfig['entity_type']['invoice']['invoice']))
        {
            return false;
        }

        $invoicesXml = $orderXml->addChild('invoices');
        
        foreach ($order->getInvoiceCollection() as $invoice)
        {
            $invoiceXml = $invoicesXml->addChild('invoice');

            foreach ($invoice->getData() as $field => $value)
            {
                switch ($field)
                {
                    case 'increment_id':
                        break;

                    default:
                        $invoiceXml->addChild($field, $value);
                        break;
                
                }
            }

            $invoiceXml->addChild('increment_id', $invoice->getIncrementId());
            $invoiceXml->addChild('increment_prefix', $this->_getIncrementPrefix($order, 'invoice'));
                        
            $this->_exportChildData($invoiceXml, $invoice, $exportConfig);
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
        $childTypes  = isset($entityTypes['invoice']) ? $entityTypes['invoice'] : array();

        if (isset($childTypes['invoice']))
        {
            unset($childTypes['invoice']);
        }

        return $childTypes;
    }
}