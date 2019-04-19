<?php
class BroSolutions_OrderedItems_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_FOR_TEACHER_EMAIL_ADDRESS = 'oitems_selection/oitems_group/teacher_email';
    const XML_PATH_STORE_NAME = 'trans_email/ident_sales/name';
    const XML_PATH_STORE_EMAIL = 'trans_email/ident_sales/email';


    public function getOrderedProductsCollection($productId = NULL, $fromObserver = false)
    {
        $collection = Mage::getModel('sales/order_item')->getCollection();
        $allowedIds = array();
        $crossSells = array();
        if($productId){
            $allowedIds = array($productId);
            $crossSells = $this->_getCrossSellsProductsIds();
            $allowedIds = array_merge($allowedIds, $crossSells);
            $collection->addFieldToFilter('product_id', array('in' => $allowedIds));
            $availableOrderIds = $this->getAvailableOrderIds($productId);
            $collection->addFieldToFilter('main_table.order_id', array('in' => $availableOrderIds));
        }
        $dateTimeTableName = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_datetime');
        $releaseDateAttribute = $this->_getReleaseDateAttributeId();
        $now = Mage::getModel('core/date')->date('Y-m-d');
        //$now .= ' 00:00:00';
        //$startPurchasingDate = $dateNowObject->sub(new DateInterval('P1D'))->format('Y-m-d H:i:s');
        $startPurchasingDate = date('Y-m-d');//, strtotime(' +1 day'));
        $startPurchasingDate .= ' 00:00:00';
        $collection->getSelect()->joinLeft(array('datetime'=> $dateTimeTableName), 'datetime.entity_id = main_table.product_id', array('release_date' => 'datetime.value'))->where('datetime.attribute_id = ?', $releaseDateAttribute->getId());
        $collection->getSelect()->joinLeft(array('order_entity'=> $collection->getTable('sales/order')), 'main_table.order_id = order_entity.entity_id');
        $collection->getSelect()->joinLeft(array('customers'=> $collection->getTable('customer/entity')), 'order_entity.customer_id = customers.entity_id');

        $collection->getSelect()->joinLeft( array('order'=> $collection->getTable('sales/order_grid')), 'order.entity_id = main_table.order_id')->where('order.customer_id IS NOT NULL');
        if($fromObserver){
            $collection->getSelect()->where('datetime.entity_type_id = 4')
                //->where('order.created_at <= ?', $now)
                //->where('order.created_at > ?', $startPurchasingDate)->group('main_table.item_id')->order('order.entity_id')
                //->where('datetime.value  ?', $now)
                ->where('datetime.value = ?', $startPurchasingDate);

        }
        if($fromObserver){
            $collection = $this->_appendCrossSells($collection);
        }

        return $collection;
    }


    protected function _appendCrossSells($existingCollection)
    {
        $orderIds = array();
        $crossSellIds = array();
        foreach($existingCollection as $item){
            $orderIds [] = $item->getOrderId();
            $crossSellIds = $this->_getCrossSellsProductsIds($item->getProductId());
        }
        $collection = Mage::getModel('sales/order_item')->getCollection();
        $collection->getSelect()->joinLeft(array('order_entity'=> $collection->getTable('sales/order')), 'main_table.order_id = order_entity.entity_id');
        $collection->getSelect()->joinLeft(array('customers'=> $collection->getTable('customer/entity')), 'order_entity.customer_id = customers.entity_id');
        $collection->getSelect()->joinLeft( array('order'=> $collection->getTable('sales/order_grid')), 'order.entity_id = main_table.order_id');

        $collection->getSelect()->where('main_table.product_id IN (?)', $crossSellIds);
        $collection->getSelect()->where('main_table.order_id IN (?)', $orderIds);
        foreach($collection as $item){
            $existingCollection->addItem($item);
        }
        return $existingCollection;
    }

    protected function _getCrossSellsProductsIds($product = NULL)
    {
        $crossSellsIds = array();
        if(!$product){
            $product = $this->_getProduct();
        }
        if(is_numeric($product)){
            $product = Mage::getModel('catalog/product')->load($product);
        }
        $crossellProductCollection = $product->getCrossSellProductCollection();
        foreach($crossellProductCollection as $crossSell){
            $crossSellsIds[] = $crossSell->getId();
            $children = $crossSell->getTypeInstance(true)->getAssociatedProducts($crossSell);
            foreach($children as $child){
                $crossSellsIds[] = $child->getId();
            }
        }
        return $crossSellsIds;
    }


    public function sendNotificationEmail()
    {
        $mailTemplate = Mage::getModel('core/email_template');
        $translate = Mage::getSingleton('core/translate');
        $template = $mailTemplate->loadDefault('oitems_email_template');
        $templateData = $template->getData();
        if (!empty($templateData)) {
            $mailSubject = 'Ordered Classes Notification';
            $fromEmail = Mage::getStoreConfig(self::XML_PATH_STORE_EMAIL);
            $fromName = Mage::getStoreConfig(self::XML_PATH_STORE_NAME);
            $sender = array('name' => $fromName,
                'email' => $fromEmail);
            $vars = array();
            $storeId = Mage::app()->getStore()->getStoreId();

            $model = $mailTemplate->setReplyTo($sender['email'])->setTemplateSubject($mailSubject);
            $email = Mage::getStoreConfig(self::XML_PATH_FOR_TEACHER_EMAIL_ADDRESS);
            if(strpos($email, ',') !== false){
                $email = explode(',', $email);
            }
            try {
                $model->sendTransactional('oitems_email_template', $sender, $email, $fromName, $vars, $storeId);
                $translate->setTranslateInline(true);
                //$this->_setDisabledStatusForOrderedProducts();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

    protected function _setDisabledStatusForOrderedProducts()
    {
        $collection = $this->getOrderedProductsCollection(NULL, true);
        if($collection->getSize()){
            foreach ($collection as $item) {
                $item->getProduct()->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED)->save();
            }
        }
    }

    protected function _getProduct()
    {
        return Mage::registry('current_product');
    }

    protected function _checkReleaseDateCondition($_product = NULL)
    {
        if(!$_product){
            $_product = $this->_getProduct();
        }
        $productReleaseDate = $_product->getReleaseDate();
        $productReleaseFormatted = date('Y-m-d H:i:s', strtotime($productReleaseDate));
        $now = Mage::getModel('core/date')->date('Y-m-d H:i:s');
        $nowDateTimeObj = new DateTime($now);
        $releaseDateTime = new DateTime($productReleaseFormatted);
        $diff = $nowDateTimeObj->diff($releaseDateTime);
        if($diff->d < 1){
            return true;
        }
        return false;
    }

    protected function _getReleaseDateAttributeId()
    {
        $releaseDateAttribute = Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('attribute_code', array('eq' => 'release_date'))->getFirstItem();
        return $releaseDateAttribute;
    }

    public function getAvailableOrderIds($productIds)
    {
        $orderIds = array();
        $collection = Mage::getModel('sales/order_item')->getCollection();
        $collection->addFieldToFilter('product_id', array('eq' => $productIds));
        foreach($collection as $item){
            $orderIds [] = $item->getOrderId();
        }
        return $orderIds;
    }
}
