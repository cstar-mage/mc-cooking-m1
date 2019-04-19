<?php
class BroSolutions_OrderedItems_Model_Observer
{
    public function sentOrdersNotifications()
    {
        $orderedItemsCollection = Mage::helper('oitems')->getOrderedProductsCollection();
        $orderedItemsCount = $orderedItemsCollection->count();
        if($orderedItemsCount){
            Mage::helper('oitems')->sendNotificationEmail();
        }
    }
}