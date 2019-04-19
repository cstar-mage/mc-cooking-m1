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
class Aitoc_Aitexporter_Model_Import_Type_Creditmemo_Comment implements Aitoc_Aitexporter_Model_Import_Type_Interface
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
        $commentXml = current($orderXml->xpath($itemXpath));

        // empty items from CSV 
        if (!trim(strip_tags($commentXml->asXML())))
        {
            return $isValid;
        }

        return $isValid;
    }

    /**
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $importConfig
     * @param Mage_Sales_Model_Order_Creditmemo $parentItem
     */
    public function import(SimpleXMLElement $orderXml, $itemXpath, array $config, Mage_Core_Model_Abstract $parentItem = null)
    {
        /* @var $parentItem Mage_Sales_Model_Order_Creditmemo */
        $comment    = Mage::getModel('sales/order_creditmemo_comment');
        /* @var $comment Mage_Sales_Model_Order_Creditmemo_Comment */
        $commentXml = current($orderXml->xpath($itemXpath));
        /* @var $commentXml SimpleXMLElement */

        // empty items from CSV 
        if (!trim(strip_tags($commentXml->asXML())))
        {
            return false;
        }

        foreach ($commentXml->children() as $field)
        {
            switch ($field->getName())
            {
                case 'entity_id':
                case 'parent_id':
                    break;

                default:
                    $comment->setData($field->getName(), (string)$field);
                    break;
            }
        }

        $parentItem->addComment($comment, $comment->getIsCustomerNotified(), $comment->getIsVisibleOnFront());
    }

    public function getXpath()
    {
        return '/comments/comment';
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