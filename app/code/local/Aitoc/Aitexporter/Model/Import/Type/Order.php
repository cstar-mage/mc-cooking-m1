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
class Aitoc_Aitexporter_Model_Import_Type_Order extends Aitoc_Aitexporter_Model_Import_Type_Complex implements Aitoc_Aitexporter_Model_Import_Type_Interface
{
    const STATE_ADD    = 'add'; // added for append and replace
    const STATE_UPDATE = 'update'; // ignored for append, replaced for replace, removed for remove
    const STATE_FAIL   = 'fail'; // contains errors for append and replace, does not exist for remove
    /**
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $config
     * @param string $orderIncrementId
     * @return boolean is valid
     */
    public function validate(SimpleXMLElement $orderXml, $itemXpath, array $config, $orderIncrementId = '')
    {
        return Mage::getSingleton('aitexporter/import_type_orderValidate')->validate($orderXml, $itemXpath, $config, $orderIncrementId);
    }

    /**
     * @param SimpleXMLElement $orderXml
     * @param string $itemXpath
     * @param array $importConfig
     * @param Mage_Sales_Model_Abstract $parentItem
     */
    public function import(SimpleXMLElement $orderXml, $itemXpath, array $config, Mage_Core_Model_Abstract $parentItem = null)
    {
        return Mage::getSingleton('aitexporter/import_type_orderImport')->import($orderXml, $itemXpath, $config, $parentItem);
    }

    public function getConfigPath()
    {
        return 'order';
    }

    public function getXpath()
    {
        return '/order';
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