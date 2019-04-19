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
class Aitoc_Aitexporter_Model_Import_Type_Order_Item implements Aitoc_Aitexporter_Model_Import_Type_Interface
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

        if (!isset($itemXml->sku))
        {
            Mage::getModel('aitexporter/import_error')
                ->setOrderIncrementId($orderIncrementId)
                ->setError(Mage::helper('aitexporter')->__('Order item %s does not have sku field', $itemXpath))
                ->save();
        }

        $productResourceModel = Mage::getResourceModel('catalog/product');
        $productExists = ($productResourceModel->getIdBySku((string)$itemXml->sku) || $productResourceModel->getIdBySku((string)$itemXml->base_sku));

        if (!$productExists)
        {
            Mage::getModel('aitexporter/import_error')
            ->setOrderIncrementId((string)$orderXml->fields->increment_id)
            ->setError(Mage::helper('aitexporter')->__('Product `%s` doesn\'t exists. Order item will not be linked', (string)$itemXml->name))
            ->setType(Aitoc_Aitexporter_Model_Import_Error::TYPE_WARNING)
            ->save();
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
        $item    = Mage::getModel('sales/order_item');
        /* @var $item Mage_Sales_Model_Order_Item */
        $itemXml = current($orderXml->xpath($itemXpath));
        /* @var $addressXml SimpleXMLElement */

        // empty items from CSV 
        if (!trim(strip_tags($itemXml->asXML())))
        {
            return false;
        }

        foreach ($itemXml->children() as $field)
        {
            switch ($field->getName())
            {
                case 'order_id':
                case 'quote_item_id':
                case 'gift_message_id':
                    break;

                case 'item_id':
                case 'parent_item_id':
                    $item->setData('old_'.$field->getName(), (string)html_entity_decode($field));

                    $item->setQuoteParentItemId($item->getOldParentItemId());
                    break;

                case 'store_id':
                    $item->setStoreId($parentItem->getStoreId());
                    break;

                case 'product_id':
                    $this->_importProductId($item,$itemXml);
                    break;

                case 'product_options':
                    try
                    {
                        $productOptions = unserialize(str_replace("&quot;",'"',(string)$field));
                    }
                    catch(Exception $e)
                    {
                        continue;
                    }
                    if (!is_array($productOptions))
                    {
                        continue;
                    }
                    $this->_importProductOptions($item, $productOptions);
                    break;

                case 'gift_message':
                    // empty gift message from CSV
                    if (!trim(strip_tags($field->asXML())))
                    {
                        break;
                    }
                    $this->_importProductGiftMessage($item,$field);
                    break;

                default:
                    $item->setData($field->getName(), (string)html_entity_decode($field));
                    break;
            }
        }

        $parentItem->addItem($item);
    }

    protected function _importProductId(Mage_Sales_Model_Order_Item $item, $itemXml)
    {
        if (!empty($itemXml->sku) || !empty($itemXml->base_sku))
        {
            if(!empty($itemXml->base_sku))
            {
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', (string)$itemXml->base_sku);
            }

            if(empty($product))
            {
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', (string)$itemXml->sku);
            }
            if ($product)
            {
                /* @var $product Mage_Catalog_Model_Product */
                $item->setProductId($product->getId());
            }
        }
    }

    protected function _importProductGiftMessage(Mage_Sales_Model_Order_Item $item, $field)
    {
        $giftMessage = Mage::getModel('giftmessage/message');
        foreach ($field->children() as $messageField)
        {
            switch ($messageField->getName())
            {
                case 'gift_message_id':
                case 'customer_id':
                    break;

                default:
                    $giftMessage->setData($messageField->getName(), (string)html_entity_decode($messageField));
                    break;
            }
        }
        $item->setGiftMessage($giftMessage);
    }

    protected function _importProductOptions(Mage_Sales_Model_Order_Item $item, $productOptions)
    {
        if (isset($productOptions['options']) && is_array($productOptions['options']))
        {
            foreach ($productOptions['options'] as & $option)
            {
                $option['option_id'] = 0;
                if (!empty($option['option_sku']))
                {
                    $optionModel = Mage::getModel('catalog/product_option')->load($option['option_sku'], 'sku');
                    if ($optionModel->getId())
                    {
                        $option['option_id'] = $optionModel->getId();
                    }
                    else
                    {
                        $optionValue = Mage::getModel('catalog/product_option_value')->load($option['option_sku'], 'sku');

                        if ($optionValue->getId())
                        {
                            $option['option_id'] = (int)$optionValue->getId();
                        }
                    }

                    unset($option['option_sku']);
                }
            }
        }

        $item->setProductOptions($productOptions);
    }

    public function getXpath()
    {
        return '/items/item';
    }

    /**
     * @see Aitoc_Aitexporter_Model_Import_Type_Interface::getErrorType()
     * return string
     */
    public function getErrorType()
    {
        return false;
    }

    public function postProcess(Mage_Sales_Model_Order $order)
    {
        $items         = $order->getItemsCollection();
        $itemsByEntity = array();
        foreach ($items as $item)
        {
            $itemsByEntity[$item->getOldItemId()] = $item;
        }

        foreach ($items as $item)
        {
            if ($item->getOldParentItemId() && $itemsByEntity[$item->getOldParentItemId()])
            {
                $item->setParentItem($itemsByEntity[$item->getOldParentItemId()])
                    ->setParentItemId($itemsByEntity[$item->getOldParentItemId()]->getId())//setting up parent_item_id in case of emergency and forcing update "data_changes" value for magento 1.6
                    ->save();
            }
        }

        return $this;
    }

    public function postProcessAfterSave(Mage_Sales_Model_Order $order)
    {
        foreach ($order->getItemsCollection() as $item)
        {
            $giftMessage = $item->getGiftMessage();
            if ($giftMessage)
            {
                $giftMessage->setCustomerId($order->getCustomerId())->save();
                $item->setGiftMessageId($giftMessage->getId())->save();
            }
        }

        return $this;
    }

    public function deleteGiftMessages(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        /* @var $order Mage_Sales_Model_Order */

        foreach ($order->getItemsCollection() as $item)
        {
            /* @var $item Mage_Sales_Model_Order_Item */
            if ($item->getGiftMessageId())
            {
                Mage::getModel('giftmessage/message')->load($item->getGiftMessageId())->delete();
            }
        }
    }
}