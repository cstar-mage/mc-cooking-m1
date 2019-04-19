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
class Aitoc_Aitexporter_Model_Import_Type_Order_Payment extends Aitoc_Aitexporter_Model_Import_Type_Complex implements Aitoc_Aitexporter_Model_Import_Type_Interface
{
    /**
     * 
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $config
     * @param string $orderIncrementId
     * @return boolean is valid 
     */
    public function validate(SimpleXMLElement $orderXml, $itemXpath, array $config, $orderIncrementId = '')
    {
        $isValid    = true;
        $paymentXml = current($orderXml->xpath($itemXpath));

        // empty items from CSV 
        if (!trim(strip_tags($paymentXml->asXML())))
        {
            return $isValid;
        }

        if (empty($paymentXml->method))
        {
            Mage::getModel('aitexporter/import_error')
                ->setOrderIncrementId($orderIncrementId)
                ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_ERROR)
                ->setError(Mage::helper('aitexporter')->__('Payment method does not exist in import document for payment %s', $itemXpath))
                ->save();
            $isValid = false;
        }
        else
        {
            try
            {
                $methodInstance = Mage::helper('payment')->getMethodInstance((string)$paymentXml->method);
            }
            catch (Exception $e) {}
            if (empty($methodInstance))
            {
                Mage::getModel('aitexporter/import_error')
                    ->setOrderIncrementId($orderIncrementId)
                    ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_ERROR)
                    ->setError(Mage::helper('aitexporter')->__('Payment method %s does not exist in system for payment %s', $paymentXml->method, $itemXpath))
                    ->save();
                $isValid = false;
            }
        }

        $isChildredValid = $this->_validateChildren($orderXml, $itemXpath, $config, $orderIncrementId);
        if (!$isChildredValid)
        {
            $isValid = false;
        }          

        return $isValid;
    }

    /**
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $importConfig
     * @param Mage_Sales_Model_Order $parentItem
     */
    public function import(SimpleXMLElement $orderXml, $itemXpath, array $config, Mage_Core_Model_Abstract $parentItem = null)
    {
        $payment    = Mage::getModel('sales/order_payment');
        $paymentXml = current($orderXml->xpath($itemXpath));

        // empty items from CSV 
        if (!trim(strip_tags($paymentXml->asXML())))
        {
            return false;
        }

        foreach ($paymentXml->children() as $field)
        {
            switch ($field->getName())
            {
                case 'entity_id':
                case 'parent_id':
                case 'quote_payment_id':
                    break;
                
                case 'method_instance':
                    if(!empty($field))
                    {
                        $payment->setData($field->getName(), (string)$field);
                    }
                    break;

                default:
                    $payment->setData($field->getName(), (string)$field);
                    break;
            }
        }

        $parentItem->setPayment($payment);

        $this->_importChildren($orderXml, $itemXpath, $config, $payment);
    }

    public function getXpath()
    {
        return '/payments/payment';
    }

    /**
     * @see Aitoc_Aitexporter_Model_Import_Type_Interface::getErrorType()
     * return string
     */
    public function getErrorType()
    {
        return Aitoc_Aitexporter_Model_Import_Error::TYPE_WARNING;
    }

    public function getConfigPath()
    {
        return 'order/order_payment';
    }
}