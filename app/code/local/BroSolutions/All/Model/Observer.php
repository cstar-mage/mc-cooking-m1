<?php
class BroSolutions_All_Model_Observer
{
    public function sendEmailToAdminOutofStockSubscription($event)
    {
        $productId = $event->getProductId();
        $email = $event->getEmail();
        $product = Mage::getModel('catalog/product')->load($productId);
        if($product->getId() && !empty($email)){
            $customer = Mage::getModel("customer/customer");
            $storeId = Mage::app()->getStore()->getWebsiteId();
            $customer->setWebsiteId($storeId);
            $customer->loadByEmail($email);
            $emailTemplate  = Mage::getModel('core/email_template');
            $adminEmail = Mage::getStoreConfig('trans_email/ident_general/email');
            $emailTemplateVariables = array();
            if($customer->getId()){
                $emailTemplate->loadDefault('outofstock_subscription_email_template');
                $emailTemplateVariables['customer'] = $customer;
            } else {
                $emailTemplate->loadDefault('outofstock_subscription_email_template_guest');
                $emailTemplateVariables['customer_email'] = $email;
            }
            $emailTemplateVariables['product'] = $product;
            $processedTemplate = $emailTemplate->getProcessedTemplate($emailTemplateVariables);
            $senderData = array('name' => 'Guest', 'email' => $email);
            //$emailTemplate->sendTransactional($emailTemplate->getId(), $senderData, $adminEmail, $emailTemplateVariables);
        }
        return $this;
    }

    public function disableExpiredProducts()
    {
        $productsCollection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('release_date')
            ->addAttributeToFilter('status', array('eq' => 1));
        $now = new DateTime();
        foreach($productsCollection as $product){
            $releaseDate = new DateTime($product->getReleaseDate());
            $diff = $releaseDate->diff($now);
            $diffFormatted = $diff->format("%a");
            if($now > $releaseDate &&  $diffFormatted >= 1){
                $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                $product->save();
            }
        }
    }
}
