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
class Aitoc_Aitexporter_Model_Import_Type_Shipment_Track implements Aitoc_Aitexporter_Model_Import_Type_Interface
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
        $isValid = true;
        $itemXml = current($orderXml->xpath($itemXpath));

        // empty items from CSV 
        if (!trim(strip_tags($itemXml->asXML())))
        {
            return $isValid;
        }

        if (!isset($itemXml->track_number))
        {
            Mage::getModel('aitexporter/import_error')
                ->setOrderIncrementId($orderIncrementId)
                ->setError(Mage::helper('aitexporter')->__('Shipment Track %s does not have track_number field', $itemXpath))
                ->save();
        }

        return $isValid;
    }

    /**
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $importConfig
     * @param Mage_Sales_Model_Order_Shipment $parentItem
     */
    public function import(SimpleXMLElement $orderXml, $itemXpath, array $config, Mage_Core_Model_Abstract $parentItem = null)
    {
        /* @var $parentItem Mage_Sales_Model_Order_Shipment */
        $track    = Mage::getModel('sales/order_shipment_track');
        /* @var $item Mage_Sales_Model_Order_Shipment_Track */
        $trackXml = current($orderXml->xpath($itemXpath));
        /* @var $itemXml SimpleXMLElement */

        // empty items from CSV 
        if (!trim(strip_tags($trackXml->asXML())))
        {
            return false;
        }

        foreach ($trackXml->children() as $field)
        {
            switch ($field->getName())
            {
                case 'entity_id':
                case 'parent_id':
                case 'order_id':
                    break;

                default:
                    $track->setData($field->getName(), (string)$field);
                    break;
            }
        }

        $parentItem->addTrack($track);
    }

    public function getXpath()
    {
        return '/trackings/tracking';
    }

    /**
     * @see Aitoc_Aitexporter_Model_Import_Type_Interface::getErrorType()
     * return string
     */
    public function getErrorType()
    {
        return false;
    }
}