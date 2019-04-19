<?php
class BroSolutions_RecreateOrder_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getPreviousOrderGrandTotal()
    {
        $orderGrandTotal = false;
        $adminQuoteSession = Mage::getSingleton('adminhtml/session_quote');
        $quoteId = $adminQuoteSession->getQuoteId();
        $quote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter('entity_id', $quoteId)->setPageSize(1)->getFirstItem();
        $originalOrderId = $quote->getOrigOrderId();
        $originalOrder = Mage::getModel('sales/order')->load($originalOrderId);
        if($originalOrder->getId()){
            $orderGrandTotal = $originalOrder->getGrandTotal();
        }
        return $orderGrandTotal;
    }

    public function getDifferrenceWithPrevOrder($baseTotal)
    {
        $previusOrderGrandTotal = $this->getPreviousOrderGrandTotal();
        return $previusOrderGrandTotal - $baseTotal;
    }
}