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
class Aitoc_Aitexporter_Model_Export_Type_Creditmemo_Comment implements Aitoc_Aitexporter_Model_Export_Type_Interface
{
    /**
     * 
     * @param SimpleXMLElement $creditmemoXml
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Varien_Object $exportConfig
     */
    public function prepareXml(SimpleXMLElement $creditMemoXml, Mage_Core_Model_Abstract $creditMemo, Varien_Object $exportConfig)
    {
        /* @var $creditMemo Mage_Sales_Model_Order_Creditmemo */

        $creditMemoCommentsXml = $creditMemoXml->addChild('comments');

        foreach ($creditMemo->getCommentsCollection() as $creditMemoComment)
        {
            $creditMemoCommentXml = $creditMemoCommentsXml->addChild('comment');

            foreach ($creditMemoComment->getData() as $field => $value)
            {
                $creditMemoCommentXml->addChild($field, (string)$value);
            }
        }
    }
}