<?php
class BroSolutions_OrderedItems_Block_Order_Email_Items extends Mage_Core_Block_Template
{
    public function getItems()
    {
        $collection = Mage::helper('oitems')->getOrderedProductsCollection(NULL, true);
        return $collection;
    }
}