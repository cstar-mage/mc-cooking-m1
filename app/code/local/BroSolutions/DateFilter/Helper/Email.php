<?php
class BroSolutions_DateFilter_Helper_Email extends Mage_Core_Helper_Abstract
{
    const XML_PATH_EMAIL_TEMPLATE = 'classnotifications/general/template_notifications';

    public function sendNotificationEmail($buyedProductData)
    {
		$this->_setClassReleaseDateFormatted($buyedProductData);
        $customerEmail = $buyedProductData->getCustomerEmail();
        #$customerEmail = 'thomas.g.bennett@gmail.com';
		$emailTemplate  = Mage::getModel('core/email_template');
        $emailTemplate->loadDefault('classnotifications_general_template_notifications');
        $storeEmail = Mage::getStoreConfig('trans_email/ident_general/email');
        $storeName = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
        if(Empty($buyedProductData->getFormattedReleaseDate())){
            $buyedProductData->setFormattedReleaseDate(Mage::registry('formatted_release_date'));
        }
        $emailTemplateVariables = array('data' => $buyedProductData);
        $qtyOrdered = intval($buyedProductData->getQtyOrdered());
        $buyedProductData->setQtyOrdered($qtyOrdered);
        $senderData = array('name' => $storeName, 'email' => $storeEmail);
        $emailTemplate->setTemplateSubject('Class Email Reminder');
        try {
            $emailTemplate->sendTransactional($emailTemplate->getId(), $senderData, $customerEmail, $storeName, $emailTemplateVariables);
            $orderItemId = $buyedProductData->getOrderItemId();
            $orderItem = Mage::getModel('sales/order_item')->load($orderItemId);
            $orderItem->setRemindEmailSent(1);
            $orderItem->save();
        } catch(Exception $e){

        }
        return $this;
    }

    protected function _setClassReleaseDateFormatted($buyedProductData)
    {
        $formattedStr = '';
        $releaseDate = $buyedProductData->getReleaseDate();
        $releaseTime = $buyedProductData->getReleaseTime();
        if(!$releaseDate || !$releaseTime){
            $productId = Mage::getModel("catalog/product")->getIdBySku($buyedProductData->getSku());
            $buyedProductData = Mage::getModel('catalog/product')->load($productId);
            $releaseDate = $buyedProductData->getReleaseDate();
            $releaseTime = $buyedProductData->getReleaseTime();
        }
        $releaseDateObj = DateTime::createFromFormat('Y-m-d H:i:s', $releaseDate);
        $formattedStr = $releaseDateObj->format('l, F d, Y');
        $formattedStr .= ' at '.$releaseTime;
		$buyedProductData->setFormattedReleaseDate($formattedStr);
        if(!Mage::registry('formatted_release_date')){
            Mage::register('formatted_release_date', $formattedStr);
        }
        return $this;
    }

    public function sendCancelClassEmail($product, $customerEmail)
    {
        $customer = Mage::getModel("customer/customer")->getCollection()->addAttributeToFilter('email', array('eq' => $customerEmail))->setPageSize(1)->getFirstItem();
        $emailTemplate  = Mage::getModel('core/email_template');
        $emailTemplate->loadDefault('classnotifications_general_template_notifications_cancel');
        $storeEmail = Mage::getStoreConfig('trans_email/ident_general/email');
        $storeName = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
        $cancelReason = $product->getAttributeText('cancel_reason');
        $emailTemplateVariables = array('data' => $product, 'cancel_reason' => $cancelReason);
        $senderData = array('name' => $storeName, 'email' => $storeEmail);
        $customerEmail = $customer->getEmail();
        //$customerEmail = 'thomas.g.bennett@gmail.com';
        $emailTemplate->setTemplateSubject('Class Has Been Cancelled');
        try {
            if(!empty($cancelReason)){
                $emailTemplate->sendTransactional($emailTemplate->getId(), $senderData, $customerEmail, $storeName, $emailTemplateVariables);
            }
        } catch(Exception $e){
            Mage::logException($e);
        }
        return $this;
    }
}
