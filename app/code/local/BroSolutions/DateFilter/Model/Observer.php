<?php
class BroSolutions_DateFilter_Model_Observer
{
    public function sendClassNotifications()
    {
        $collection = Mage::getModel('catalog/product')->getCollection();
        $toDate = date('Y-m-d H:i:s', strtotime(' +1 day'));
        $fromDate = date('Y-m-d H:i:s');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('release_time');
        $collection->addAttributeToFilter('release_date', array('from'=>$fromDate, 'to'=>$toDate));
        $salesOrderItemTable = Mage::getSingleton('core/resource')->getTableName('sales/order_item');
        $collection->getSelect()->joinInner(
            array('soi' => $salesOrderItemTable),
            '`e`.`entity_id` = `soi`.`product_id` ',
            array('order_item_id' => 'soi.item_id', 'qty_ordered' => 'soi.qty_ordered')
        );
        $salesOrderTable = Mage::getSingleton('core/resource')->getTableName('sales/order');
        $collection->getSelect()->joinInner(
            array('so' => $salesOrderTable),
            '`soi`.`order_id` = `so`.`entity_id` ',
            array('*')
        );
        $collection->getSelect()->where('so.status = ?', 'complete');
        $collection->getSelect()->where('soi.remind_email_sent IS NULL');
        $collection->getSelect()->group('so.customer_email');
        foreach($collection as $product){
            Mage::helper('datefilter/email')->sendNotificationEmail($product);
        }
        return $this;
    }

    public function cancelClassCheck($event)
    {
        $product = $event->getDataObject();
        $productOrigData = $product->getOrigData();
        $productData = $product->getData();
        $productStatus = $product->getStatus();
        if($productStatus == Mage_Catalog_Model_Product_Status::STATUS_DISABLED){
            return $this;
        }
        if(isset($productOrigData['cancel_reason']) && $productOrigData['cancel_reason'] == NULL && isset($productData['cancel_reason']) && $productData['cancel_reason'] != $productOrigData['cancel_reason']){
            $productId = $product->getId();
            $customers = $this->_getCustomerEmailsWhoOrderedProduct($product);
            if($customers){
                foreach($customers as $customerEmail){
                    Mage::helper('datefilter/email')->sendCancelClassEmail($product, $customerEmail);
                }
                $resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $salesFlatOrderItemsTableName = $resource->getTableName('sales/order_item');
                $query = "UPDATE {$salesFlatOrderItemsTableName} SET remind_email_sent = 1 WHERE product_id = ".(int)$productId;
                $writeConnection->query($query);
                $allStores = Mage::app()->getStores();
                $storeIds = array();
                foreach($allStores as $eachStoreId => $val){
                    $storeIds[] = Mage::app()->getStore($eachStoreId)->getId();
                }
                foreach($storeIds as $storeId){
                    Mage::getModel('catalog/product_status')->updateProductStatus($productId, $storeId, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                }
            }
        }
        return $this;
    }

    protected function _getCustomerEmailsWhoOrderedProduct($product)
    {
        if($product && $product->getId()){
            $orderedItems = Mage::getModel('sales/order_item')->getCollection()->addFieldToFilter('product_id', array('eq' => $product->getId()));
            $salesOrderTable = Mage::getSingleton('core/resource')->getTableName('sales/order');
            $orderedItems->getSelect()->joinInner(
                array('so' => $salesOrderTable),
                '`main_table`.`order_id` = `so`.`entity_id` ',
                array('customer_email')
            );
            $adapter = Mage::getSingleton('core/resource')->getConnection('core_read');
            $result = $adapter->fetchAll($orderedItems->getSelect(), 'customer_email');
            $customerEmails = array_column($result, 'customer_email');
            $customerEmails = array_unique($customerEmails);
            return $customerEmails;
        }
        return false;
    }
}