<?php
class MW_Storecreditpro_Model_Order_Invoice_Total_Storecredit extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
		$order = $invoice->getOrder();
		
        $totalDiscountAmount     = $order->getMwStorecreditDiscountShow();
        $baseTotalDiscountAmount = $order->getMwStorecreditDiscount();
        
        $invoice->setGrandTotal($invoice->getGrandTotal() - $totalDiscountAmount);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $baseTotalDiscountAmount);
        
        $invoice->setMwStorecredit($order->getMwStorecredit());
        $invoice->setMwStorecreditBuyCredit($order->getMwStorecreditBuyCredit());
        $invoice->setMwStorecreditDiscount($baseTotalDiscountAmount);
        $invoice->setMwStorecreditDiscountShow($totalDiscountAmount);
        
        return $this;
    }


}
