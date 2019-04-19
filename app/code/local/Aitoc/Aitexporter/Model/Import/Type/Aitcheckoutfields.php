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
class Aitoc_Aitexporter_Model_Import_Type_Aitcheckoutfields implements Aitoc_Aitexporter_Model_Import_Type_Interface
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
        $isValid  = true;
        $fieldXml = current($orderXml->xpath($itemXpath));

        // empty items from CSV 
        if (!trim(strip_tags($fieldXml->asXML())))
        {
            return $isValid;
        }

        if (!Mage::helper('aitexporter/aitcheckoutfields')->isEnabled())
        {
            return $isValid;
        }

        $helper = Mage::helper('aitexporter/aitcheckoutfields');
        /* @var $helper Aitoc_Aitexporter_Helper_Aitcheckoutfields */
        $attributes = $helper->getCustomFieldsList();

        if (!isset($attributes[$fieldXml->getName()]))
        {
            Mage::getModel('aitexporter/import_error')
                ->setOrderIncrementId($orderIncrementId)
                ->setError($helper->__('Checkout fields with code "%s" is not configured in checkout fields maganer', $fieldXml->getName()))
                ->save();
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
        if (!Mage::helper('aitexporter/aitcheckoutfields')->isEnabled())
        {
            return false;
        }

        /* @var $parentItem Mage_Sales_Model_Order */
        $fields = Mage::getModel('aitcheckoutfields/aitcheckoutfields');
        /* @var $fields Aitoc_Aitcheckoutfields_Model_Aitcheckoutfields */
        $fieldXml = current($orderXml->xpath($itemXpath));
        /* @var $fieldXml SimpleXMLElement */

        $helper = Mage::helper('aitexporter/aitcheckoutfields');
        /* @var $helper Aitoc_Aitexporter_Helper_Aitcheckoutfields */
        $attributes = $helper->getCustomFieldsList();

        if (!isset($attributes[$fieldXml->getName()]))
        {
            return false;
        }

        $aitcheckoutfieldsValues = $parentItem->getAitchckoutfieldsValues();
        if (empty($aitcheckoutfieldsValues))
        {
            $aitcheckoutfieldsValues = array();
        }

        foreach ($fieldXml->children() as $child)
        {
            $aitcheckoutfieldsValues[$fieldXml->getName()][] = (string)$child;
        }

        $parentItem->setAitchckoutfieldsValues($aitcheckoutfieldsValues);

        /* @var $parentItem Mage_Sales_Model_Order */
    }

    public function getXpath()
    {
        return '/aitcheckoutfields/*';
    }

    /**
     * @see Aitoc_Aitexporter_Model_Import_Type_Interface::getErrorType()
     * return string
     */
    public function getErrorType()
    {
        return false;
    }

    public function deleteAitcheckoutfields(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('aitexporter/aitcheckoutfields')->isEnabled())
        {
            return false;
        }
        
        $order = $observer->getEvent()->getOrder();
        /* @var $order Mage_Sales_Model_Order */
        
        Mage::getResourceModel('aitexporter/aitcheckoutfields')->deleteAitcheckoutfields($order);
    }

    public function postProcess(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('aitexporter/aitcheckoutfields')->isEnabled())
        {
            return false;
        }

        $order = $observer->getEvent()->getOrder();
        /* @var $order Mage_Sales_Model_Order */

        $aitcheckoutfieldsValues = $order->getAitchckoutfieldsValues();
        if (empty($aitcheckoutfieldsValues))
        {
            return false;
        }

        $fieldsResource = Mage::getResourceModel('aitexporter/aitcheckoutfields');
        /* @var $fieldsResource Aitoc_Aitexporter_Model_Mysql4_Aitcheckoutfields */

        $helper = Mage::helper('aitexporter/aitcheckoutfields');
        /* @var $helper Aitoc_Aitexporter_Helper_Aitcheckoutfields */
        $attributes = $helper->getCustomFieldsList();

        foreach ($aitcheckoutfieldsValues as $aitcheckoutfield => $values)
        {
            $fieldsResource->addOrderCheckoutFieldValues($order, $attributes[$aitcheckoutfield], $values);
        }
    }
}