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
class Aitoc_Aitexporter_Model_Export_Type_Order_Item implements Aitoc_Aitexporter_Model_Export_Type_Interface
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

        if (empty($exportConfig['entity_type']['order_item']))
        {
            return false;
        }

        $itemsXml = $orderXml->addChild('items');

        foreach ($order->getItemsCollection() as $item)
        {
            $itemXml = $itemsXml->addChild('item');
            
            foreach($item->getData() as $field => $value)
            {
                switch ($field)
                {
                    case 'product_options':
                        break;

                    case 'product_id':
                        if($value)
                        {
                            $product = Mage::getSingleton('aitexporter/productcontainer')->getProduct($value);
                            $sku = $product->getSku();
                            if($sku)
                            {
                                $itemXml->addChild('base_sku', $sku);
                            }
                        }
                    default:
                          $itemXml->addChild($field, $value);
                        break;
                }
            }

            $this->_addProductOptions($itemXml, $item->getProductOptions());
            $this->_addGiftMessage($itemXml, $item);
        }
    }


    protected function _addGiftMessage(SimpleXMLElement $itemXml, Mage_Core_Model_Abstract $item)
    {
        $giftMessageXml = $itemXml->addChild('gift_message');
        if ($item->getGiftMessageId() != '')
        {
            $giftMessageModel = Mage::getModel('giftmessage/message')->load($item->getGiftMessageId());

            foreach($giftMessageModel->getData() as $key => $value)
            {
                $giftMessageXml->addChild($key, $value);
            }
        }
    }

    protected function _addProductOptions(SimpleXMLElement $itemXml, $productOptions)
    {
        if (isset($productOptions['options']))
        {
            foreach($productOptions['options'] as $key => $productOption)
            {
                $optionModel = Mage::getModel('catalog/product_option')->load($productOption['option_id']);
                $optionSku = $optionModel->getSku();
                $productOptions['options'][$key]['option_sku'] = $optionSku;
            }
        }
        $itemXml->addChild('product_options', serialize($productOptions));
    }
}