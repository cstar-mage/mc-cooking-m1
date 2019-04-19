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
class Aitoc_Aitexporter_Model_Import_Type_Order_Statushistory implements Aitoc_Aitexporter_Model_Import_Type_Interface
{
    private $_historyPool = array();

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
        $isValid   = true;
        $statusXml = current($orderXml->xpath($itemXpath));

        // empty items from CSV 
        if (!trim(strip_tags($statusXml->asXML())))
        {
            return $isValid;
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
        /* @var $parentItem Mage_Sales_Model_Order */
        $history   = Mage::getModel('sales/order_status_history');
        /* @var $history Mage_Sales_Model_Order_Status_History */
        $statusXml = current($orderXml->xpath($itemXpath));
        /* @var $statusXml SimpleXMLElement */

        // empty items from CSV 
        if (!trim(strip_tags($statusXml->asXML())))
        {
            return false;
        }

        foreach ($statusXml->children() as $field)
        {
            switch ($field->getName())
            {
                case 'entity_id':
                case 'parent_id':
                    break;
                
                case 'status':
                    $fieldName = $field->getName();
                    $field = (string) $field;
                    if(!Mage::helper('aitexporter')->isPreorderEnabled())
                    {
                        $field = str_replace('preorder', '', $field);
                    }
                    $history->setData($fieldName, (string)$field);
                    break;

                default:
                    $history->setData($field->getName(), (string)$field);
                    break;
            }
        }

        $history->setOrder($parentItem);

        $this->_historyPool[] = $history;
    }

    public function getXpath()
    {
        return '/statuseshistory/statushistory';
    }

    /**
     * @see Aitoc_Aitexporter_Model_Import_Type_Interface::getErrorType()
     * @return string
     */
    public function getErrorType()
    {
        return false;
    }

    public function postProcess($order)
    {
        foreach ($this->_historyPool as $history)
        {
            /* @var $history Mage_Sales_Model_Order_Status_History */
            $history->save();
        }

        $this->_historyPool = array();
    }
}