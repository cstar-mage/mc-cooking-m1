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
class Aitoc_Aitexporter_Model_Export_Type_Order_Payment extends Aitoc_Aitexporter_Model_Export_Type_Complex implements Aitoc_Aitexporter_Model_Export_Type_Interface
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

        if (empty($exportConfig['entity_type']['order_payment']['order_payment']))
        {
            return false;
        }

        $paymentsXml = $orderXml->addChild('payments');

        foreach ($order->getPaymentsCollection() as $payment)
        {
            $paymentXml = $paymentsXml->addChild('payment');

            foreach ($payment->getData() as $field => $value)
            {
                if(is_array($value))
                {
                    $value = serialize($value);
                }
                $paymentXml->addChild($field, (string)$value);
            }

            $this->_exportChildData($paymentXml, $payment, $exportConfig);
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
        $childTypes  = isset($entityTypes['order_payment']) ? $entityTypes['order_payment'] : array();

        if (isset($childTypes['order_payment']))
        {
            unset($childTypes['order_payment']);
        }

        return $childTypes;
    }
}